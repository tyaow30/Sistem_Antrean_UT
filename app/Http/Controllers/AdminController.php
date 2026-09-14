<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use App\Models\Service; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class AdminController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $sesiHariIni = SesiHari::where('tanggal', $today)->first();

        // Statistik antrean hari ini
        $totalTiket = Antrean::where('tanggal', $today)->count();
        $menunggu   = Antrean::where('tanggal', $today)->where('status', 'WAITING')->count();
        $selesai    = Antrean::where('tanggal', $today)->where('status', 'DONE')->count();
        $dilewati   = Antrean::where('tanggal', $today)->whereIn('status', ['SKIPPED', 'PENDING'])->count();

        // DATA CHART 1: Pengunjung Tiap Hari Selama Seminggu Terakhir (Real-Time)
        $startDate = now()->subDays(6)->startOfDay();
        $endDate   = now()->endOfDay();

        $antreanSeminggu = Antrean::select(
                DB::raw('DATE(tanggal) as date'),
                DB::raw('count(*) as total')
            )
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $chartSemingguLabels = [];
        $chartSemingguData   = [];

        for ($i = 6; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $dateString = $dateObj->toDateString();

            $chartSemingguLabels[] = $dateObj->format('d/m/y');
            $chartSemingguData[]   = $antreanSeminggu[$dateString] ?? 0;
        }

        // DATA CHART 2: Pengunjung Tiap Loket Hari Ini (Real-Time)
        $lokets = Loket::with(['activePetugas', 'layanans'])->get();
        $chartLoketLabels = [];
        $chartLoketData   = [];

        foreach ($lokets as $loket) {
            $chartLoketLabels[] = $loket->nama_loket;
            $chartLoketData[]   = Antrean::where('tanggal', $today)
                ->where('loket_pelayanan_id', $loket->id)
                ->count();
        }

        $petugasList = User::where('role', 'PETUGAS')->with('assignedLoket')->get();
        $layanans    = Service::with('loket')->get();

        return view('admin.dashboard', compact(
            'sesiHariIni', 'totalTiket', 'menunggu', 'selesai', 'dilewati',
            'lokets', 'petugasList', 'layanans',
            'chartSemingguLabels', 'chartSemingguData', 'chartLoketLabels', 'chartLoketData'
        ));
    }

    // TOGGLE SESI HARIAN
    public function toggleSesi()
    {
        $today = now()->toDateString();
        $msg = '';

        DB::transaction(function () use ($today, &$msg) {
            $sesiHariIni = SesiHari::where('tanggal', $today)
                ->lockForUpdate()
                ->first();

            // Jika sesi hari ini sudah ada dan sedang terbuka -> Tutup sesi & nonaktifkan loket
            if ($sesiHariIni && $sesiHariIni->is_open) {
                $sesiHariIni->update(['is_open' => false]);

                Loket::where('status', 'ACTIVE')->update(['status' => 'INACTIVE']);

                Antrean::where('tanggal', $today)->delete();

                $msg = 'Sesi harian berhasil ditutup dan semua loket dinonaktifkan!';
                return;
            }

            // Jika belum ada sesi hari ini -> Buat baru & buka
            if (!$sesiHariIni) {
                SesiHari::create([
                    'tanggal' => $today,
                    'is_open' => true,
                ]);

                $msg = 'Sesi harian berhasil dibuka!';
                return;
            }

            // Jika sesi hari ini ada tapi tertutup -> Buka kembali
            $sesiHariIni->update(['is_open' => true]);
            $msg = 'Sesi harian berhasil dibuka!';
        });

        return back()->with('success', $msg);
    }

    // TAMBAH LOKET BARU
    public function storeLoket(Request $request)
    {
        $request->validate([
            'nama_loket' => 'required|string|max:255',
            'status'     => 'required|in:ACTIVE,INACTIVE',
        ]);

        Loket::create([
            'nama_loket' => $request->nama_loket,
            'status'     => $request->status,
        ]);

        return redirect()->back()->with('success', 'Loket baru berhasil ditambahkan!');
    }

    // UPDATE DATA LOKET & PETUGAS SEKALIGUS
    public function updateLoket(Request $request, $loketId)
    {
        $request->validate([
            'nama_loket'        => 'required|string|max:255',
            'status'            => 'required|in:ACTIVE,INACTIVE',
            'active_petugas_id' => 'nullable|exists:users,id',
        ]);

        $loket        = Loket::findOrFail($loketId);
        $newPetugasId = $request->active_petugas_id;
        $oldPetugasId = $loket->active_petugas_id;

        DB::transaction(function () use ($loket, $request, $newPetugasId, $oldPetugasId, $loketId) {
            // 1. Lepas assigned_loket_id dari petugas lama jika ada
            if ($oldPetugasId && $oldPetugasId != $newPetugasId) {
                User::where('id', $oldPetugasId)->update(['assigned_loket_id' => null]);
            }

            // 2. Lepas petugas lain yang sebelumnya memegang loket ini
            User::where('assigned_loket_id', $loketId)->update(['assigned_loket_id' => null]);

            // 3. Update data loket
            $loket->update([
                'nama_loket'        => $request->nama_loket,
                'status'            => $request->status,
                'active_petugas_id' => $newPetugasId,
            ]);

            // 4. Update assigned_loket_id di tabel users untuk petugas baru
            if ($newPetugasId) {
                User::where('id', $newPetugasId)->update(['assigned_loket_id' => $loketId]);
            }
        });

        return redirect()->back()->with('success', 'Data loket berhasil diperbarui!');
    }

    // HALAMAN MANAJEMEN PETUGAS
    public function indexPetugas()
    {
        $petugasList = User::where('role', 'PETUGAS')->with('assignedLoket')->get();
        $lokets      = Loket::all();

        return view('admin.petugas', compact('petugasList', 'lokets'));
    }

    // CRUD PETUGAS
    public function storePetugas(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,email', 
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->username, 
            'password' => Hash::make($request->password),
            'role'     => 'PETUGAS',
        ]);

        return redirect()->back()->with('success', 'Akun petugas baru berhasil ditambahkan!');
    }

    // UPDATE AKUN PETUGAS
    public function updateAkunPetugas(Request $request, $id)
    {
        $user = User::where('role', 'PETUGAS')->findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,email,' . $id, 
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->username, 
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Data akun petugas berhasil diperbarui!');
    }

    public function destroyPetugas($id)
    {
        $user = User::where('role', 'PETUGAS')->findOrFail($id);

        // Lepas relasi loket jika petugas ini sedang memegangnya
        Loket::where('active_petugas_id', $user->id)->update([
            'status'            => 'INACTIVE',
            'active_petugas_id' => null
        ]);

        $user->update(['assigned_loket_id' => null]);
        $user->delete();

        return redirect()->back()->with('success', 'Akun petugas berhasil dihapus.');
    }

    // PENUGASAN PETUGAS KE LOKET (Sinkronisasi dua arah tabel lokets & users)
    public function updatePetugas(Request $request, $loketId)
    {
        $request->validate([
            'active_petugas_id' => 'nullable|exists:users,id',
        ]);

        $loket        = Loket::findOrFail($loketId);
        $newPetugasId = $request->active_petugas_id;
        $oldPetugasId = $loket->active_petugas_id;

        if ($newPetugasId == $oldPetugasId) {
            return redirect()->back();
        }

        // Validasi apakah petugas sudah dipakai di loket lain
        if ($newPetugasId) {
            $sudahDipakai = Loket::where('active_petugas_id', $newPetugasId)
                ->where('id', '!=', $loketId)
                ->exists();

            if ($sudahDipakai) {
                return redirect()->back()->with('error', 'Petugas tersebut sudah ditugaskan di loket lain!');
            }
        }

        DB::transaction(function () use ($loket, $newPetugasId, $oldPetugasId, $loketId) {
            // 1. Lepas assigned_loket_id dari petugas lama jika ada
            if ($oldPetugasId) {
                User::where('id', $oldPetugasId)->update(['assigned_loket_id' => null]);
            }

            // 2. Lepas petugas lain yang mungkin memegang assigned_loket_id ini sebelumnya
            User::where('assigned_loket_id', $loketId)->update(['assigned_loket_id' => null]);

            // 3. Update tabel lokets
            $loket->update([
                'active_petugas_id' => $newPetugasId,
                'status'            => $newPetugasId ? $loket->status : 'INACTIVE',
            ]);

            // 4. Update assigned_loket_id di tabel users untuk petugas baru
            if ($newPetugasId) {
                User::where('id', $newPetugasId)->update(['assigned_loket_id' => $loket->id]);
            }
        });

        return redirect()->back()->with('success', 'Penugasan petugas pada loket berhasil diperbarui!');
    }

    public function destroyLoket($id)
    {
        $loket = Loket::findOrFail($id);
        
        // Lepas relasi petugas yang terikat dengan loket ini
        User::where('assigned_loket_id', $id)->update(['assigned_loket_id' => null]);

        $loket->delete();

        return redirect()->back()->with('success', 'Data loket berhasil dihapus!');
    }

    // HALAMAN MANAJEMEN LAYANAN
    public function indexLayanan(Request $request)
    {
        $lokets = Loket::all();

        $query = Service::with('loket');

        if ($request->filled('search')) {
            $query->where('nama_layanan', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('loket')) {
            $query->whereHas('loket', function ($q) use ($request) {
                $q->where('nama_loket', $request->loket);
            });
        }

        $layanan = $query->paginate(5)->withQueryString();

        return view('admin.layanan', compact('layanan', 'lokets'));
    }

    public function storeLayanan(Request $request)
    {
        $request->validate([
            'nama_layanan' => 'required|string|max:255',
            'loket_id'     => 'required|exists:loket,id', 
        ]);

        Service::create([
            'nama_layanan' => $request->nama_layanan,
            'loket_id'     => $request->loket_id,
        ]);

        return back()->with('success', 'Layanan berhasil ditambahkan!');
    }

    public function updateLayanan(Request $request, $id)
    {
        $request->validate([
            'nama_layanan' => 'required|string|max:255',
            'loket_id'     => 'required|exists:loket,id',
        ]);

        $layanan = Service::findOrFail($id);
        $layanan->update([
            'nama_layanan' => $request->nama_layanan,
            'loket_id'     => $request->loket_id,
        ]);

        return back()->with('success', 'Layanan berhasil diperbarui!');
    }

    public function destroyLayanan($id)
    {
        $layanan = Service::findOrFail($id);
        $layanan->delete();

        return back()->with('success', 'Layanan berhasil dihapus!');
    }

    public function indexRekap(Request $request)
    {
        $query = Antrean::with(['serviceAwal', 'serviceAktual', 'loketPelayanan', 'petugas']);

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('loket_id')) {
            $query->where('loket_pelayanan_id', $request->loket_id);
        }

        if ($request->filled('layanan_id')) {
            $query->where(function($q) use ($request) {
                $q->where('service_awal_id', $request->layanan_id)
                  ->orWhere('service_aktual_id', $request->layanan_id);
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        $antreans = $query->latest()->get();
        $lokets   = Loket::all();      
        $layanans = Service::all();

        return view('admin.rekap', compact('antreans', 'lokets', 'layanans'));
    }

    public function exportRekapExcel(Request $request)
    {
        // Menyesuaikan dengan form input start_date & end_date di Blade kamu
        $startDate = $request->input('start_date', date('Y-m-d'));
        $endDate   = $request->input('end_date', date('Y-m-d'));

        // Ambil data antrean sesuai rentang tanggal yang dipilih di filter
        $antreans = Antrean::with(['serviceAwal', 'serviceAktual', 'loketPelayanan', 'petugas'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

        if ($antreans->isEmpty()) {
            return back()->with('error', 'Tidak ada data antrean pada rentang tanggal tersebut.');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Hapus sheet default bawaan

        // ==========================================
        // 1. SHEET DASHBOARD & CHART (DI DEPAN)
        // ==========================================
        $sheetDash = $spreadsheet->createSheet(0);
        $sheetDash->setTitle('Dashboard');

        // Judul Dashboard
        $sheetDash->setCellValue('A1', 'DASHBOARD REKAPITULASI PELAYANAN ANTREAN');
        $sheetDash->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Hitung Statistik Ringkasan
        $totalKeluhan  = $antreans->count();
        $totalSelesai  = $antreans->where('status', 'DONE')->count();
        $totalProses   = $antreans->where('status', 'WAITING')->count();
        $totalBatal    = $antreans->whereNotIn('status', ['DONE', 'WAITING'])->count();
        $persenSelesai = $totalKeluhan > 0 ? round(($totalSelesai / $totalKeluhan) * 100, 1) . '%' : '0%';

        // Tabel Ringkasan Kartu Statistik (Kiri Atas)
        $sheetDash->setCellValue('A3', 'Total Pengunjung');      $sheetDash->setCellValue('B3', $totalKeluhan);
        $sheetDash->setCellValue('A4', 'Selesai');                $sheetDash->setCellValue('B4', $totalSelesai);
        $sheetDash->setCellValue('A5', 'Dalam Proses (Waiting)'); $sheetDash->setCellValue('B5', $totalProses);
        $sheetDash->setCellValue('A6', 'Belum Selesai / Batal');  $sheetDash->setCellValue('B6', $totalBatal);
        $sheetDash->setCellValue('A7', '% Penyelesaian');         $sheetDash->setCellValue('B7', $persenSelesai);


        // --- KUMPULKAN DATA UNTUK CHART ---
        // A. Pengunjung Per Hari (Sesuai rentang tanggal yang di-filter)
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        $dataPerHari = [];
        foreach ($period as $date) {
            $tglStr = $date->format('Y-m-d');
            $tglLabel = $date->format('d-m-Y');
            $dataPerHari[$tglLabel] = $antreans->where('tanggal', $tglStr)->count();
        }

        // B. Pengunjung Per Loket
        $dataPerLoket = $antreans->groupBy(function($item) {
            return $item->loketPelayanan->nama_loket ?? 'Umum';
        })->map->count();

        // C. Status Penyelesaian
        $dataStatus = [
            'Selesai' => $totalSelesai,
            'Dalam Proses' => $totalProses,
            'Belum Selesai/Batal' => $totalBatal
        ];


        // --- MASUKKAN DATA PENDUKUNG CHART KE KOLOM TERSEMBUNYI ---
        // 1. Data Chart Harian (Kolom P & Q)
        $r = 3;
        $sheetDash->setCellValue('P2', 'Tanggal'); $sheetDash->setCellValue('Q2', 'Jumlah');
        foreach ($dataPerHari as $tgl => $jml) {
            $sheetDash->setCellValue('P' . $r, $tgl);
            $sheetDash->setCellValue('Q' . $r, $jml);
            $r++;
        }
        $endHariRow = $r - 1;

        // 2. Data Chart Loket (Kolom S & T)
        $r = 3;
        $sheetDash->setCellValue('S2', 'Loket'); $sheetDash->setCellValue('T2', 'Jumlah');
        foreach ($dataPerLoket as $loket => $jml) {
            $sheetDash->setCellValue('S' . $r, $loket);
            $sheetDash->setCellValue('T' . $r, $jml);
            $r++;
        }
        $endLoketRow = $r - 1;

        // 3. Data Chart Status (Kolom V & W)
        $r = 3;
        $sheetDash->setCellValue('V2', 'Status'); $sheetDash->setCellValue('W2', 'Jumlah');
        foreach ($dataStatus as $status => $jml) {
            $sheetDash->setCellValue('V' . $r, $status);
            $sheetDash->setCellValue('W' . $r, $jml);
            $r++;
        }
        $endStatusRow = $r - 1;


        // --- BUAT 6 CHART & TEMPEL DI SHEET DASHBOARD ---
        
        if (count($dataPerHari) > 0) {
            $catHari = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Dashboard'!\$P\$3:\$P\${$endHariRow}", null, count($dataPerHari))];
            $valHari = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Dashboard'!\$Q\$3:\$Q\${$endHariRow}", null, count($dataPerHari))];
            
            // 1. Chart Pengunjung Tiap Hari (Bar Chart)
            $serHariBar = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_STANDARD, range(0, count($valHari)-1), [], $catHari, $valHari);
            $pltHariBar = new PlotArea(null, [$serHariBar]);
            $chtHariBar = new Chart('chart_hari_bar', new Title('Pengunjung Tiap Hari (Bar Chart)'), new Legend(Legend::POSITION_RIGHT, null, false), $pltHariBar);
            $chtHariBar->setTopLeftPosition('D3');
            $chtHariBar->setBottomRightPosition('K14');
            $sheetDash->addChart($chtHariBar);

            // 2. Chart Pengunjung Tiap Hari (Pie Chart)
            $serHariPie = new DataSeries(DataSeries::TYPE_PIECHART, DataSeries::GROUPING_STANDARD, range(0, count($valHari)-1), [], $catHari, $valHari);
            $pltHariPie = new PlotArea(null, [$serHariPie]);
            $chtHariPie = new Chart('chart_hari_pie', new Title('Pengunjung Tiap Hari (Pie Chart)'), new Legend(Legend::POSITION_RIGHT, null, false), $pltHariPie);
            $chtHariPie->setTopLeftPosition('D16');
            $chtHariPie->setBottomRightPosition('K27');
            $sheetDash->addChart($chtHariPie);
        }

        if ($dataPerLoket->count() > 0) {
            $catLoket = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Dashboard'!\$S\$3:\$S\${$endLoketRow}", null, count($dataPerLoket))];
            $valLoket = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Dashboard'!\$T\$3:\$T\${$endLoketRow}", null, count($dataPerLoket))];
            
            // 3. Chart Pengunjung Tiap Loket (Bar Chart)
            $serLoketBar = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_STANDARD, range(0, count($valLoket)-1), [], $catLoket, $valLoket);
            $pltLoketBar = new PlotArea(null, [$serLoketBar]);
            $chtLoketBar = new Chart('chart_loket_bar', new Title('Pengunjung Tiap Loket (Bar Chart)'), new Legend(Legend::POSITION_RIGHT, null, false), $pltLoketBar);
            $chtLoketBar->setTopLeftPosition('D29');
            $chtLoketBar->setBottomRightPosition('K40');
            $sheetDash->addChart($chtLoketBar);

            // 4. Chart Pengunjung Tiap Loket (Pie Chart)
            $serLoketPie = new DataSeries(DataSeries::TYPE_PIECHART, DataSeries::GROUPING_STANDARD, range(0, count($valLoket)-1), [], $catLoket, $valLoket);
            $pltLoketPie = new PlotArea(null, [$serLoketPie]);
            $chtLoketPie = new Chart('chart_loket_pie', new Title('Pengunjung Tiap Loket (Pie Chart)'), new Legend(Legend::POSITION_RIGHT, null, false), $pltLoketPie);
            $chtLoketPie->setTopLeftPosition('D42');
            $chtLoketPie->setBottomRightPosition('K53');
            $sheetDash->addChart($chtLoketPie);
        }

        $catStatus = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Dashboard'!\$V\$3:\$V\${$endStatusRow}", null, count($dataStatus))];
        $valStatus = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Dashboard'!\$W\$3:\$W\${$endStatusRow}", null, count($dataStatus))];
        
        // 5. Chart Status Penyelesaian (Bar Chart)
        $serStatusBar = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_STANDARD, range(0, count($valStatus)-1), [], $catStatus, $valStatus);
        $pltStatusBar = new PlotArea(null, [$serStatusBar]);
        $chtStatusBar = new Chart('chart_status_bar', new Title('Status Penyelesaian (Bar Chart)'), new Legend(Legend::POSITION_RIGHT, null, false), $pltStatusBar);
        $chtStatusBar->setTopLeftPosition('D55');
        $chtStatusBar->setBottomRightPosition('K66');
        $sheetDash->addChart($chtStatusBar);

        // 6. Chart Status Penyelesaian (Pie Chart)
        $serStatusPie = new DataSeries(DataSeries::TYPE_PIECHART, DataSeries::GROUPING_STANDARD, range(0, count($valStatus)-1), [], $catStatus, $valStatus);
        $pltStatusPie = new PlotArea(null, [$serStatusPie]);
        $chtStatusPie = new Chart('chart_status_pie', new Title('Status Penyelesaian (Pie Chart)'), new Legend(Legend::POSITION_RIGHT, null, false), $pltStatusPie);
        $chtStatusPie->setTopLeftPosition('D68');
        $chtStatusPie->setBottomRightPosition('K79');
        $sheetDash->addChart($chtStatusPie);


        // ==========================================
        // 2. SHEET REKAPITULASI PER TANGGAL (MULTI-SHEET)
        // ==========================================
        $groupedByDate = $antreans->groupBy('tanggal');

        foreach ($groupedByDate as $tanggal => $items) {
            $sheetData = $spreadsheet->createSheet();
            $sheetData->setTitle(Carbon::parse($tanggal)->format('d-m-Y'));

            $headers = [
                'No', 'Tanggal', 'Nama/Identitas Pelapor', 'Status Pelapor',
                'Jenis Keluhan', 'Subjek/Uraian Keluhan', 'Media Penyampaian',
                'Unit/Petugas Penanganan', 'Tindak Lanjut', 'Status Penyelesaian',
                'Tanggal Selesai', 'Lama Penyelesaian (Hari)', 'Kepuasan Setelah Penyelesaian', 'Keterangan'
            ];

            $sheetData->fromArray($headers, NULL, 'A1');

            // Styling Header Table
            $sheetData->getStyle('A1:N1')->getFont()->setBold(true);
            $sheetData->getStyle('A1:N1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('001F3F');
            $sheetData->getStyle('A1:N1')->getFont()->getColor()->setARGB('FFFFFF');

            // Aktifkan Filter (Dropdown) pada Header Tabel
            $lastRowData = count($items) + 1;
            $sheetData->setAutoFilter("A1:N{$lastRowData}");

            $row = 2;
            $no  = 1;
            foreach ($items as $item) {
                $namaLayanan   = $item->serviceAwal->nama_layanan ?? ($item->serviceAktual->nama_layanan ?? '-');
                $statusSelesai = $item->status == 'DONE' ? 'Selesai' : ($item->status == 'WAITING' ? 'Dalam Proses' : 'Belum Selesai/Batal');
                $tglSelesai    = $item->status == 'DONE' ? Carbon::parse($item->updated_at)->format('d/m/Y') : '';

                $sheetData->setCellValue('A' . $row, $no++);
                $sheetData->setCellValue('B' . $row, Carbon::parse($item->tanggal)->format('d/m/Y'));
                $sheetData->setCellValue('C' . $row, $item->nama ?? 'Mahasiswa');
                $sheetData->setCellValue('D' . $row, 'Mahasiswa');
                $sheetData->setCellValue('E' . $row, $namaLayanan);
                $sheetData->setCellValue('F' . $row, 'Pelayanan Antrean ' . $namaLayanan);
                $sheetData->setCellValue('G' . $row, 'Tatapmuka/Kiosk');
                $sheetData->setCellValue('H' . $row, $item->loketPelayanan->nama_loket ?? '-');
                $sheetData->setCellValue('I' . $row, 'Telah dilayani di loket');
                $sheetData->setCellValue('J' . $row, $statusSelesai);
                $sheetData->setCellValue('K' . $row, $tglSelesai);
                $sheetData->setCellValue('L' . $row, 0);
                $sheetData->setCellValue('M' . $row, 'Puas');
                $sheetData->setCellValue('N' . $row, 'Data Antrean Sistem');
                $row++;
            }
        }


        // ==========================================
        // 3. OUTPUT FILE EXCEL
        // ==========================================
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        $fileName = 'Rekap_Lengkap_Antrean_' . $startDate . '_s-d_' . $endDate . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}