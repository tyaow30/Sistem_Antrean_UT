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
        // PERBAIKAN: Memuat relasi serviceAwal, serviceAktual, dan loketPelayanan dengan benar
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

    // EXPORT EXCEL MULTI-SHEET & CHART
    public function exportRekapExcel(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', now()->toDateString());

        // PERBAIKAN: Menggunakan 'loketPelayanan' menggantikan 'loket'
        $antreans = Antrean::with(['layanan', 'loketPelayanan', 'petugas'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->groupBy('tanggal');

        if ($antreans->isEmpty()) {
            return back()->with('error', 'Tidak ada data antrean pada rentang tanggal tersebut.');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($antreans as $tanggal => $items) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(Carbon::parse($tanggal)->format('d-m-Y'));

            $headers = [
                'No', 'Tanggal', 'Nama/Identitas Pelapor', 'Status Pelapor',
                'Jenis Keluhan', 'Subjek/Uraian Keluhan', 'Media Penyampaian',
                'Unit/Petugas Penanganan', 'Tindak Lanjut', 'Status Penyelesaian',
                'Tanggal Selesai', 'Lama Penyelesaian (Hari)', 'Kepuasan Setelah Penyelesaian', 'Keterangan'
            ];

            $sheet->fromArray($headers, NULL, 'A1');

            $sheet->getStyle('A1:N1')->getFont()->setBold(true);
            $sheet->getStyle('A1:N1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('001F3F');
            $sheet->getStyle('A1:N1')->getFont()->getColor()->setARGB('FFFFFF');

            $row = 2;
            $no  = 1;
            foreach ($items as $item) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, Carbon::parse($item->tanggal)->format('d/m/Y'));
                $sheet->setCellValue('C' . $row, $item->nama_mahasiswa ?? 'Mahasiswa');
                $sheet->setCellValue('D' . $row, 'Mahasiswa');
                $sheet->setCellValue('E' . $row, $item->layanan->nama_layanan ?? '-');
                $sheet->setCellValue('F' . $row, 'Pelayanan Antrean ' . ($item->layanan->nama_layanan ?? ''));
                $sheet->setCellValue('G' . $row, 'Tatapmuka/Kiosk');
                // PERBAIKAN: Mengambil nama dari relasi 'loketPelayanan'
                $sheet->setCellValue('H' . $row, $item->loketPelayanan->nama_loket ?? '-');
                $sheet->setCellValue('I' . $row, 'Telah dilayani di loket');
                $sheet->setCellValue('J' . $row, $item->status == 'DONE' ? 'Selesai' : $item->status);
                $sheet->setCellValue('K' . $row, Carbon::parse($item->updated_at)->format('d/m/Y'));
                $sheet->setCellValue('L' . $row, 0);
                $sheet->setCellValue('M' . $row, 'Puas');
                $sheet->setCellValue('N' . $row, 'Data Antrean Sistem');
                $row++;
            }

            // PERBAIKAN: Mengelompokkan berdasarkan 'loketPelayanan.nama_loket'
            $loketCounts   = $items->groupBy(function($item) {
                return $item->loketPelayanan->nama_loket ?? 'Umum';
            })->map->count();

            $chartStartRow = $row + 3;
            $sheet->setCellValue('P' . $chartStartRow, 'Loket');
            $sheet->setCellValue('Q' . $chartStartRow, 'Jumlah');

            $r = $chartStartRow + 1;
            foreach ($loketCounts as $loketName => $count) {
                $sheet->setCellValue('P' . $r, $loketName ?: 'Umum');
                $sheet->setCellValue('Q' . $r, $count);
                $r++;
            }

            $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheet->getTitle()}'!\$P\$" . ($chartStartRow + 1) . ":\$P\$" . ($r - 1), null, count($loketCounts))];
            $values     = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheet->getTitle()}'!\$Q\$" . ($chartStartRow + 1) . ":\$Q\$" . ($r - 1), null, count($loketCounts))];

            $series = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_STANDARD,
                range(0, count($values) - 1),
                [],
                $categories,
                $values
            );

            $plotArea = new PlotArea(null, [$series]);
            $legend   = new Legend(Legend::POSITION_RIGHT, null, false);
            $title    = new Title('Grafik Pengunjung Per Loket (' . $sheet->getTitle() . ')');

            $chart = new Chart('chart_' . str_replace('-', '_', $tanggal), $title, $legend, $plotArea);
            $chart->setTopLeftPosition('P2');
            $chart->setBottomRightPosition('AA20');

            $sheet->addChart($chart);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        $fileName = 'Rekap_Antrean_' . $startDate . '_s-d_' . $endDate . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}