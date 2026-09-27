<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Loket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\AntreanDipanggil;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PetugasController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();
        $today = now()->toDateString();

        if (!$user || !$user->assigned_loket_id) {
            return redirect()->route('kiosk.index')->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::find($user->assigned_loket_id);
        
        Loket::where('id', $user->assigned_loket_id)->update([
            'active_petugas_id' => $user->id,
            'status' => 'ACTIVE',
            'last_heartbeat_at' => now(),
        ]);

        $antreanSaatIni = Antrean::where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->whereIn('status', ['PREPARING', 'CALLED', 'SERVING'])
            ->first();

        $daftarAntreanLoket = Antrean::where('tanggal', $today)
            ->where('loket_pelayanan_id', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->orderBy('id', 'asc')
            ->get();

        $daftarAntreanTertunda = collect();
        if ($user->assigned_loket_id == 1) {
            $daftarAntreanTertunda = Antrean::where('tanggal', $today)
                ->where('loket_pelayanan_id', $user->assigned_loket_id)
                ->where('status', 'ON_HOLD')
                ->orderBy('updated_at', 'asc')
                ->get();
        }

        $batasAktif = now()->subMinutes(10);
        $adaPetugasLainAktif = Loket::where('id', '!=', $user->assigned_loket_id)
            ->whereNotNull('active_petugas_id')
            ->where('status', 'ACTIVE')
            ->where('last_heartbeat_at', '>=', $batasAktif)
            ->exists();

        $adaAntreanWaiting = $daftarAntreanLoket->isNotEmpty();
        $adaAntreanAktif = $antreanSaatIni !== null;
        $adaAntreanSendiri = $adaAntreanWaiting || $adaAntreanAktif;

        $daftarAntreanBantuan = collect();
        if (!$adaAntreanSendiri || !$adaPetugasLainAktif) {
            $layananLoketIds = $loket->services()->pluck('services.id');

            $daftarAntreanBantuan = Antrean::where('tanggal', $today)
                ->where('loket_pelayanan_id', '!=', $loket->id)
                ->whereIn('service_awal_id', $layananLoketIds)
                ->where('status', 'WAITING')
                ->orderBy('id', 'asc')
                ->get();
        }

        $daftarLoket = Loket::where('id', '!=', $user->assigned_loket_id)->get();

        return view('petugas.dashboard', compact(
            'loket',
            'antreanSaatIni',
            'daftarAntreanLoket',
            'daftarAntreanTertunda',
            'daftarAntreanBantuan',
            'user',
            'daftarLoket'
        ));
    }

    public function heartbeat()
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user || !$user->assigned_loket_id) {
            return response()->json(['success' => false, 'message' => 'Petugas belum memiliki loket.'], 403);
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('active_petugas_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$loket) {
            return response()->json(['success' => false, 'message' => 'Loket tidak aktif.'], 403);
        }

        $loket->update(['last_heartbeat_at' => now()]);

        Loket::where('status', 'ACTIVE')
            ->whereNotNull('active_petugas_id')
            ->where('id', '!=', $loket->id)
            ->where('last_heartbeat_at', '<', now()->subMinutes(10))
            ->update([
                'status' => 'INACTIVE',
                'active_petugas_id' => null,
            ]);

        return response()->json(['success' => true, 'message' => 'Heartbeat berhasil.']);
    }

    public function selanjutnya()
    {
        /** @var User $user */
        $user = auth()->user();
        $today = now()->toDateString();

        $loket = Loket::find($user->assigned_loket_id);

        if (!$loket) {
            return back()->with('error', 'Loket petugas tidak valid.');
        }

        // 1. Cek loket petugas ini masih memegang antrean aktif atau tidak
        $sedangDilayani = Antrean::where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->whereIn('status', ['PREPARING', 'CALLED', 'SERVING'])
            ->exists();

        if ($sedangDilayani) {
            return back()->with('error', 'Selesaikan atau lewati antrean saat ini terlebih dahulu!');
        }

        // 2. Ambil antrean WAITING yang memang milik loket ini
        $antrean = Antrean::where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->where('status', 'WAITING')
            ->orderBy('id', 'asc') 
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Tidak ada antrean tersisa di loket Anda.');
        }

        // 3. Ubah status jadi PREPARING untuk dipanggil di loket ini
        $antrean->update([
            'status' => 'PREPARING',
            'petugas_id' => $user->id,
        ]);
        

        return back()->with('success', 'Data antrean nomor ' . $antrean->nomor_antrean . ' berhasil disiapkan.');
    }

    public function panggilBantuan($id)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->assigned_loket_id) {
            return back()->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with('error', 'Loket Anda tidak aktif atau tidak sedang ditugaskan kepada Anda.');
        }

        $today = now()->toDateString();

        $antrean = DB::transaction(function () use ($id, $today, $user, $loket) {
            $sedangDilayani = Antrean::where('tanggal', $today)
                ->where('loket_pelayanan_id', $loket->id)
                ->whereIn('status', ['PREPARING', 'CALLED', 'SERVING'])
                ->lockForUpdate()
                ->exists();

            if ($sedangDilayani) {
                return null;
            }

            $antrean = Antrean::where('id', $id)
                ->where('tanggal', $today)
                ->where('loket_pelayanan_id', '!=', $loket->id)
                ->where('status', 'WAITING')
                ->lockForUpdate()
                ->first();

            if (!$antrean) {
                return null;
            }

            $antrean->update([
                'status' => 'PREPARING',
                'petugas_id' => $user->id,
                'loket_pelayanan_id' => $user->assigned_loket_id,
            ]);

            return $antrean->fresh();
        });

        if (!$antrean) {
            return back()->with('error', 'Antrean bantuan sudah diambil petugas lain atau Anda masih memiliki antrean aktif.');
        }

        return back()->with('success', 'Menyiapkan Data Bantuan ' . $antrean->nomor_antrean);
    }

    public function panggil($id)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $loket = Loket::find($user->assigned_loket_id);

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->where('status', 'PREPARING')
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak valid untuk dipanggil.');
        }

        $antrean->update([
            'status' => 'CALLED',
            'waktu_dipanggil' => now(),
        ]);

        event(new AntreanDipanggil($antrean->load(['loketPelayanan', 'serviceAwal'])));

        return back()->with('success', 'Memanggil nomor ' . $antrean->nomor_antrean);
    }

    public function panggilUlang($id)
    {
        $user = auth()->user();

        if (!$user->assigned_loket_id) {
            return back()->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with('error', 'Loket Anda tidak aktif.');
        }

        $today = now()->toDateString();

        $antrean = DB::transaction(function () use ($id, $today, $user, $loket) {
            $antrean = Antrean::where('id', $id)
                ->where('tanggal', $today)
                ->where('loket_pelayanan_id', $loket->id)
                ->whereIn('status', ['CALLED', 'SERVING'])
                ->lockForUpdate()
                ->first();

            if (!$antrean) {
                return null;
            }

            $antrean->update([
                'waktu_dipanggil' => now(),
            ]);

            return $antrean->fresh();
        });

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak dapat dipanggil ulang.');
        }

        event(new AntreanDipanggil($antrean->load(['loketPelayanan', 'serviceAwal'])));

        return back()->with('success', 'Memanggil ulang antrean ' . $antrean->nomor_antrean);
    }

    public function selesai($id)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $loket = Loket::find($user->assigned_loket_id);

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak ditemukan.');
        }

        $antrean->update([
            'status' => 'DONE',
            'waktu_selesai' => now(),
        ]);

        return back()->with('success', 'Antrean telah diselesaikan.');
    }

    public function lewati($id)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $loket = Loket::find($user->assigned_loket_id);

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak ditemukan.');
        }

        $antrean->update([
            'status' => 'SKIPPED',
            'waktu_selesai' => now(),
        ]);

        return back()->with('success', 'Antrean berhasil dilewati.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SERVING,DONE,SKIPPED,CALLED,PREPARING,ON_HOLD',
        ]);

        $user = auth()->user();
        $today = now()->toDateString();
        $loket = Loket::find($user->assigned_loket_id);

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak ditemukan.');
        }

        $updateData = ['status' => $request->status];

        if ($request->status === 'SERVING' && !$antrean->waktu_dilayani) {
            $updateData['waktu_dilayani'] = now();
        }

        if (in_array($request->status, ['DONE', 'SKIPPED'], true) && !$antrean->waktu_selesai) {
            $updateData['waktu_selesai'] = now();
        }

        $antrean->update($updateData);

        if ($request->status === 'CALLED') {
            event(new AntreanDipanggil($antrean->load(['loketPelayanan', 'serviceAwal'])));
        }

        return back()->with('success', 'Status antrean berhasil diperbarui.');
    }

    public function tunda($id)
    {
        $user = auth()->user();
        $today = now()->toDateString();
        $loket = Loket::find($user->assigned_loket_id);

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak ditemukan atau bukan milik loket ini.');
        }

        $antrean->update([
            'status' => 'ON_HOLD'
        ]);

        return back()->with('success', 'Antrean nomor ' . $antrean->nomor_antrean . ' berhasil ditunda untuk verifikasi IKA.');
    }

    public function panggilKembali($id)
    {
        $user = auth()->user();
        $today = now()->toDateString();
        $loket = Loket::find($user->assigned_loket_id);

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket->id)
            ->where('status', 'ON_HOLD')
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tertunda tidak ditemukan.');
        }

        // Dikembalikan ke status PREPARING agar masuk ke kotak biru dan bisa diklik 'Panggil'
        $antrean->update([
            'status' => 'PREPARING'
        ]);

        return back()->with('success', 'Antrean nomor ' . $antrean->nomor_antrean . ' dipanggil kembali.');
    }

    public function alihAntrean(Request $request, $id)
    {
        $request->validate([
            'loket_tujuan' => 'required|exists:loket,id',
            'catatan' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $today = now()->toDateString();
        // Ambil ID loket petugas saat ini
        $loket_asal_id = $user->assigned_loket_id; 

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('loket_pelayanan_id', $loket_asal_id)
            ->whereIn('status', ['PREPARING', 'CALLED', 'SERVING'])
            ->first();

        if (!is_null($antrean)) {
            // PENTING: Gabungkan catatan alih dengan kendala lama agar keluhan awal dari warga/pengguna tidak hilang
            $kendala_baru = $antrean->kendala;
            if ($request->catatan) {
                $kendala_baru .= " | [Dialihkan dari Loket " . ($loket_asal_id ?? '?') . "]: " . $request->catatan;
            } else {
                $kendala_baru .= " | [Dialihkan dari Loket " . ($loket_asal_id ?? '?') . "]";
            }

            $antrean->update([
                'status' => 'WAITING',
                'loket_asal_id' => $loket_asal_id, // Catat dari loket mana antrean ini berasal (sesuai tabel DB)
                'loket_pelayanan_id' => $request->loket_tujuan, // Pindahkan ke loket tujuan
                'petugas_id' => null, // Reset petugas karena belum dipanggil di loket baru
                'waktu_dipanggil' => null, // Reset waktu panggil
                'waktu_dilayani' => null, // Reset waktu pelayanan agar loket baru bisa menghitung ulang durasi
                'kendala' => $kendala_baru, // Simpan kendala yang sudah digabung catatan alih
                'status_penyelesaian' => 'BELUM DITINDAKLANJUTI', // Pastikan status penyelesaiannya kembali awal
            ]);

            return back()->with('success', 'Antrean berhasil dialihkan ke Loket tujuan.');
        }

        return back()->with('error', 'Gagal mengalihkan! Antrean tidak ditemukan atau sudah selesai.');
    }

    public function rekap(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user || !$user->assigned_loket_id) {
            return redirect()->route('kiosk.index')->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::find($user->assigned_loket_id);
        
        $tanggalMulai = $request->input('tanggal_mulai', now()->toDateString());
        $tanggalSelesai = $request->input('tanggal_selesai', now()->toDateString());
        $keyword = $request->input('keyword');

        $query = Antrean::with(['serviceAwal', 'serviceAktual', 'loketPelayanan', 'petugas'])
            ->where(function($q) use ($loket) {
                // Tampilkan antrean yang saat ini ada di loket ini, ATAU yang asalnya dari loket ini
                $q->where('loket_pelayanan_id', $loket->id)
                  ->orWhere('loket_asal_id', $loket->id);
            })
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);

        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('nim', 'like', "%{$keyword}%");
            });
        }

        $riwayatAntrean = $query->orderBy('tanggal', 'asc')
                                ->orderBy('waktu_selesai', 'desc')
                                ->get();

        $totalSelesai = $riwayatAntrean->where('status', 'DONE')->count();
        $totalDilewati = $riwayatAntrean->where('status', 'SKIPPED')->count();
        $totalDiproses = $riwayatAntrean->count();

        if ($request->has('export') && $request->export == 'excel') {
            return $this->exportExcel($riwayatAntrean, $loket, $tanggalMulai, $tanggalSelesai);
        }

        return view('petugas.rekap', compact(
            'loket',
            'user',
            'riwayatAntrean',
            'tanggalMulai',
            'tanggalSelesai',
            'keyword',
            'totalSelesai',
            'totalDilewati',
            'totalDiproses'
        ));
    }

    private function exportExcel($antreans, $loket, $startDate, $endDate)
    {
        if ($antreans->isEmpty()) {
            return back()->with('error', 'Tidak ada data antrean pada rentang tanggal tersebut.');
        }

        $spreadsheet = new Spreadsheet();

        // 1. Grouping Data Antrean Berdasarkan Tanggal (Format: dd-mm-yyyy untuk nama sheet)
        $antreansByDate = $antreans->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('d-m-Y');
        });

        // Header Kolom Data
        $headers = [
            'No', 'Tanggal', 'NIM', 'Nama/Identitas Pelapor', 'No. HP', 'Status Pelapor',
            'Jenis Keluhan', 'Subjek/Uraian Keluhan', 'Media Penyampaian',
            'Loket Awal', 'Loket Akhir (Tujuan)', 'Unit/Petugas Penanganan', 
            'Tindak Lanjut', 'Status Penyelesaian', 'Tanggal Selesai', 
            'Lama Penyelesaian (Hari)', 'Keterangan'
        ];

        // ==========================================
        // SHEET 1: DASHBOARD & CHART (Posisi Paling Depan)
        // ==========================================
        $sheetDashboard = $spreadsheet->getActiveSheet();
        $sheetDashboard->setTitle('Dashboard');

        $sheetDashboard->setCellValue('B2', 'DASHBOARD REKAPITULASI & GRAFIK ANALITIK LOKET');
        $sheetDashboard->getStyle('B2')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('001F3F');
        $sheetDashboard->setCellValue('B3', 'Loket: Loket ' . $loket->id . ' | Periode: ' . $startDate . ' s.d. ' . $endDate);

        $currRow = 5;

        // --- A. TABEL & GRAFIK: STATUS PENYELESAIAN ---
        $totalSelesai = $antreans->where('status', 'DONE')->count();
        $totalDilewati = $antreans->where('status', 'SKIPPED')->count();
        $totalLainnya = $antreans->whereNotIn('status', ['DONE', 'SKIPPED'])->count();

        $sheetDashboard->setCellValue("B{$currRow}", 'A. Rekapitulasi Status Penyelesaian');
        $sheetDashboard->getStyle("B{$currRow}")->getFont()->setBold(true);
        $currRow++;

        $sheetDashboard->fromArray(['Status', 'Total'], NULL, "B{$currRow}");
        $sheetDashboard->getStyle("B{$currRow}:C{$currRow}")->getFont()->setBold(true);
        $currRow++;

        $statusData = [
            ['Selesai (DONE)', $totalSelesai],
            ['Dilewati (SKIPPED)', $totalDilewati],
            ['Lainnya/Proses', $totalLainnya],
        ];
        
        $startStatusRow = $currRow;
        foreach ($statusData as $stData) {
            $sheetDashboard->setCellValue("B{$currRow}", $stData[0]);
            $sheetDashboard->setCellValue("C{$currRow}", $stData[1]);
            $currRow++;
        }
        $endStatusRow = $currRow - 1;

        try {
            $dataseriesLabelsStatus = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Dashboard'!\$C\$" . $startStatusRow, null, 1)];
            $xAxisTickValuesStatus   = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Dashboard'!\$B\${$startStatusRow}:\$B\${$endStatusRow}", null, 3)];
            $dataSeriesValuesStatus  = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Dashboard'!\$C\${$startStatusRow}:\$C\${$endStatusRow}", null, 3)];

            $seriesStatus = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_STANDARD, range(0, count($dataSeriesValuesStatus) - 1), $dataseriesLabelsStatus, $xAxisTickValuesStatus, $dataSeriesValuesStatus);
            $seriesStatus->setPlotDirection(DataSeries::DIRECTION_COL);
            $plotAreaStatus = new PlotArea(null, [$seriesStatus]);
            $legendStatus = new Legend(Legend::POSITION_RIGHT, null, false);
            $titleStatus = new Title('Grafik Bar - Status Penyelesaian');
            $chartStatus = new Chart('chart_status_bar', $titleStatus, $legendStatus, $plotAreaStatus);
            $chartStatus->setTopLeftPosition('E5');
            $chartStatus->setBottomRightPosition('L15');
            $sheetDashboard->addChart($chartStatus);

            $seriesPieStatus = new DataSeries(DataSeries::TYPE_PIECHART, DataSeries::GROUPING_STANDARD, range(0, count($dataSeriesValuesStatus) - 1), $dataseriesLabelsStatus, $xAxisTickValuesStatus, $dataSeriesValuesStatus);
            $plotAreaPieStatus = new PlotArea(null, [$seriesPieStatus]);
            $titlePieStatus = new Title('Grafik Pie - Status Penyelesaian');
            $chartPieStatus = new Chart('chart_status_pie', $titlePieStatus, $legendStatus, $plotAreaPieStatus);
            $chartPieStatus->setTopLeftPosition('M5');
            $chartPieStatus->setBottomRightPosition('T15');
            $sheetDashboard->addChart($chartPieStatus);
        } catch (\Exception $e) {
            // Lewati jika error chart
        }

        $currRow += 3;

        // --- B. TABEL & GRAFIK: PER HARI / PER LAYANAN ---
        $sheetDashboard->setCellValue("B{$currRow}", 'B. Rekapitulasi Kunjungan Layanan Per Hari');
        $sheetDashboard->getStyle("B{$currRow}")->getFont()->setBold(true);
        $currRow++;

        $daftarTanggal = $antreans->pluck('tanggal')->unique()->sort()->values();
        
        foreach ($daftarTanggal as $tanggalItem) {
            $formattedTgl = Carbon::parse($tanggalItem)->format('d/m/Y');
            $sheetDashboard->setCellValue("B{$currRow}", 'Tanggal: ' . $formattedTgl);
            $sheetDashboard->getStyle("B{$currRow}")->getFont()->setBold(true);
            $currRow++;

            $sheetDashboard->fromArray(['Nama Layanan', 'Jumlah Pengunjung'], NULL, "B{$currRow}");
            $sheetDashboard->getStyle("B{$currRow}:C{$currRow}")->getFont()->setBold(true);
            $currRow++;

            $antreanPerTgl = $antreans->filter(function($val) use ($tanggalItem) {
                return Carbon::parse($val->tanggal)->format('Y-m-d') === Carbon::parse($tanggalItem)->format('Y-m-d');
            });

            $layananGroup = $antreanPerTgl->groupBy(function($item) {
                return $item->serviceAwal->nama_layanan ?? ($item->serviceAktual->nama_layanan ?? 'Layanan Umum');
            });

            $startDayRow = $currRow;
            if ($layananGroup->isNotEmpty()) {
                foreach ($layananGroup as $namaLayanan => $itemsLayanan) {
                    $sheetDashboard->setCellValue("B{$currRow}", $namaLayanan);
                    $sheetDashboard->setCellValue("C{$currRow}", $itemsLayanan->count());
                    $currRow++;
                }
            } else {
                $sheetDashboard->setCellValue("B{$currRow}", 'Tidak ada data');
                $sheetDashboard->setCellValue("C{$currRow}", 0);
                $currRow++;
            }
            $endDayRow = $currRow - 1;

            try {
                $dLabel = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Dashboard'!\$C\$" . $startDayRow, null, 1)];
                $xTick   = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Dashboard'!\$B\${$startDayRow}:\$B\${$endDayRow}", null, max(1, ($endDayRow - $startDayRow + 1)))];
                $dVal    = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Dashboard'!\$C\${$startDayRow}:\$C\${$endDayRow}", null, max(1, ($endDayRow - $startDayRow + 1)))];

                $seriesDayBar = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_STANDARD, range(0, count($dVal) - 1), $dLabel, $xTick, $dVal);
                $seriesDayBar->setPlotDirection(DataSeries::DIRECTION_COL);
                $pAreaDayBar = new PlotArea(null, [$seriesDayBar]);
                $legendDay = new Legend(Legend::POSITION_RIGHT, null, false);
                $tDayBar = new Title('Bar Chart - ' . $formattedTgl);
                $cDayBar = new Chart('chart_bar_' . str_replace('-', '', $tanggalItem), $tDayBar, $legendDay, $pAreaDayBar);
                $cDayBar->setTopLeftPosition('E' . ($startDayRow - 2));
                $cDayBar->setBottomRightPosition('L' . ($startDayRow + 8));
                $sheetDashboard->addChart($cDayBar);

                $seriesDayPie = new DataSeries(DataSeries::TYPE_PIECHART, DataSeries::GROUPING_STANDARD, range(0, count($dVal) - 1), $dLabel, $xTick, $dVal);
                $pAreaDayPie = new PlotArea(null, [$seriesDayPie]);
                $tDayPie = new Title('Pie Chart - ' . $formattedTgl);
                $cDayPie = new Chart('chart_pie_' . str_replace('-', '', $tanggalItem), $tDayPie, $legendDay, $pAreaDayPie);
                $cDayPie->setTopLeftPosition('M' . ($startDayRow - 2));
                $cDayPie->setBottomRightPosition('T' . ($startDayRow + 8));
                $sheetDashboard->addChart($cDayPie);
            } catch (\Exception $e) {
                // Lewati jika error chart
            }

            $currRow += 3;
        }

        // ==========================================
        // 2. GENERATE SHEET UNTUK MASING-MASING TANGGAL
        // ==========================================
        foreach ($antreansByDate as $dateStr => $items) {
            // Buat sheet baru untuk tanggal terkait
            $sheetData = $spreadsheet->createSheet();
            $sheetData->setTitle($dateStr); // Nama Sheet: misal 15-09-2026, 16-09-2026, dst.

            // Header Style
            $sheetData->fromArray($headers, NULL, 'A1');
            $sheetData->getStyle('A1:Q1')->getFont()->setBold(true);
            $sheetData->getStyle('A1:Q1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('001F3F');
            $sheetData->getStyle('A1:Q1')->getFont()->getColor()->setARGB('FFFFFF');

            $row = 2;
            $no = 1;

            foreach ($items as $item) {
                $namaLayanan = $item->serviceAwal->nama_layanan ?? ($item->serviceAktual->nama_layanan ?? 'Layanan Umum');
                if ($item->status === 'DONE') {
                    $statusSelesai = 'Selesai';
                } elseif ($item->status === 'SKIPPED') {
                    $statusSelesai = 'Belum Selesai/Batal';
                } else {
                    $statusSelesai = 'Dalam Proses';
                }
                $tglSelesai = $item->status == 'DONE' && $item->updated_at ? Carbon::parse($item->updated_at)->format('d/m/Y') : '';
                $lamaPenyelesaian = 0;
                if ($item->waktu_selesai && $item->tanggal) {
                    $lamaPenyelesaian = Carbon::parse($item->tanggal)->diffInDays(Carbon::parse($item->waktu_selesai));
                }

                if (!empty($item->loket_asal_id)) {
                    $loketAwalNama = 'Loket ' . $item->loket_asal_id;
                    $loketAkhirNama = 'Loket ' . $item->loket_pelayanan_id; 
                } else {
                    $loketAwalNama = 'Loket ' . $item->loket_pelayanan_id; 
                    $loketAkhirNama = 'Sama (Tidak Dialihkan)'; 
                }

                if (!empty($item->loket_asal_id)) {
                    $tindakLanjutDefault = 'Dialihkan ke Loket ' . $item->loket_pelayanan_id;
                } elseif ($item->status === 'DONE') {
                    $tindakLanjutDefault = 'WhatsApp';
                } else {
                    $tindakLanjutDefault = 'Belum Selesai';
                }

                $sheetData->setCellValue('A' . $row, $no++);
                $sheetData->setCellValue('B' . $row, Carbon::parse($item->tanggal)->format('Y-m-d'));
                $sheetData->setCellValueExplicit('C' . $row, $item->nim ?? '-', DataType::TYPE_STRING);
                $sheetData->setCellValue('D' . $row, $item->nama ?? 'Mahasiswa');
                $sheetData->setCellValueExplicit('E' . $row, $item->no_hp ?? '-', DataType::TYPE_STRING);
                $sheetData->setCellValue('F' . $row, 'Mahasiswa');
                $sheetData->setCellValue('G' . $row, $namaLayanan); 
                $sheetData->setCellValue('H' . $row, 'Pelayanan Antrean ' . $namaLayanan);
                $sheetData->setCellValue('I' . $row, 'Tatapmuka/Kiosk');
                $sheetData->setCellValue('J' . $row, $loketAwalNama);
                $sheetData->setCellValue('K' . $row, $loketAkhirNama);
                $sheetData->setCellValue('L' . $row, 'Loket ' . $loket->id . ' / ' . ($item->petugas->name ?? 'Petugas')); 
                $sheetData->setCellValue('M' . $row, $tindakLanjutDefault);                           
                $sheetData->setCellValue('N' . $row, $statusSelesai);                                
                $sheetData->setCellValue('O' . $row, $tglSelesai);
                $sheetData->setCellValue('P' . $row, $lamaPenyelesaian);
                $sheetData->setCellValue('Q' . $row, 'Data Loket ' . $loket->id);
                $row++;
            }

            $lastRow = $row - 1;

            // AutoFilter per Sheet Tanggal
            if ($lastRow >= 1) {
                $sheetData->setAutoFilter('A1:Q' . max(2, $lastRow));
            }

            // --- PENERAPAN DATA VALIDATION PER SHEET ---
            if ($lastRow >= 2) {
                for ($i = 2; $i <= $lastRow; $i++) {
                    
                    // 1. DROPDOWN KOLOM STATUS PELAPOR 
                    $validationF = $sheetData->getCell('F' . $i)->getDataValidation();
                    $validationF->setType(DataValidation::TYPE_LIST);
                    $validationF->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validationF->setAllowBlank(true);
                    $validationF->setShowInputMessage(true);
                    $validationF->setShowErrorMessage(false);
                    $validationF->setShowDropDown(true);
                    $validationF->setFormula1('"Mahasiswa,Calon Mahasiswa,Umum"');

                    // 2. DROPDOWN KOLOM TINDAK LANJUT
                    $validationM = $sheetData->getCell('M' . $i)->getDataValidation();
                    $validationM->setType(DataValidation::TYPE_LIST);
                    $validationM->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validationM->setAllowBlank(true);
                    $validationM->setShowInputMessage(true);
                    $validationM->setShowErrorMessage(false);
                    $validationM->setShowDropDown(true);
                    $validationM->setFormula1('"WhatsApp,Telepon,Email"');

                    // 3. DROPDOWN KOLOM STATUS PENYELESAIAN
                    $validationN = $sheetData->getCell('N' . $i)->getDataValidation();
                    $validationN->setType(DataValidation::TYPE_LIST);
                    $validationN->setErrorStyle(DataValidation::STYLE_STOP);
                    $validationN->setAllowBlank(true);
                    $validationN->setShowInputMessage(true);
                    $validationN->setShowErrorMessage(true);
                    $validationN->setShowDropDown(true);
                    $validationN->setFormula1('"Selesai,Belum Selesai/Batal,Dalam Proses"');
                }
            }
        }

        // Set sheet aktif pertama kali saat file dibuka (Dashboard)
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        
        $fileName = 'Rekap_Grafik_Antrean_Loket_' . $loket->id . '_' . $startDate . '_s-d_' . $endDate . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}