<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use App\Models\Layanan;
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

        // Ambil data jumlah antrean 7 hari terakhir dalam 1 query saja
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
        $layanans    = Layanan::with('loket')->get();

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

    // HALAMAN MANAJEMEN PETUGAS
    public function indexPetugas()
    {
        $petugasList = User::where('role', 'PETUGAS')->with('assignedLoket')->get();
        $lokets      = Loket::all();

        return view('admin.petugas.index', compact('petugasList', 'lokets'));
    }

    // CRUD PETUGAS
    public function storePetugas(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'PETUGAS',
        ]);

        return redirect()->back()->with('success', 'Akun petugas baru berhasil ditambahkan!');
    }

    public function destroyPetugas($id)
    {
        $user = User::where('role', 'PETUGAS')->findOrFail($id);

        // Lepas relasi loket jika petugas ini sedang memegangnya
        Loket::where('active_petugas_id', $user->id)->update([
            'status'            => 'INACTIVE',
            'active_petugas_id' => null
        ]);
        
        $user->delete();

        return redirect()->back()->with('success', 'Akun petugas berhasil dihapus.');
    }

    // PENUGASAN PETUGAS KE LOKET
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

        DB::transaction(function () use ($loket, $newPetugasId, $oldPetugasId) {
            // Kosongkan penugasan loket lama milik petugas jika ada
            if ($oldPetugasId) {
                User::where('id', $oldPetugasId)->update(['assigned_loket_id' => null]);
            }

            // Update loket
            $loket->update([
                'active_petugas_id' => $newPetugasId,
                'status'            => $newPetugasId ? $loket->status : 'INACTIVE',
            ]);

            // Set assigned_loket_id pada petugas baru
            if ($newPetugasId) {
                User::where('id', $newPetugasId)->update(['assigned_loket_id' => $loket->id]);
            }
        });

        return redirect()->back()->with('success', 'Penugasan petugas pada loket berhasil diperbarui!');
    }

    // Menyimpan Layanan Baru
    public function storeLayanan(Request $request)
    {
        $request->validate([
            'nama_layanan' => 'required|string|max:255',
            'loket_id'     => 'required|exists:loket,id',
            'deskripsi'    => 'nullable|string',
        ]);

        Layanan::create($request->all());

        return back()->with('success', 'Layanan berhasil ditambahkan!');
    }

    // Menghapus Layanan
    public function destroyLayanan($id)
    {
        $layanan = Layanan::findOrFail($id);
        $layanan->delete();

        return back()->with('success', 'Layanan berhasil dihapus!');
    }

    public function destroyLoket($id)
    {
        $loket = Loket::findOrFail($id);
        $loket->delete();

        return redirect()->back()->with('success', 'Data loket berhasil dihapus!');
    }

    // HALAMAN MANAJEMEN LAYANAN
    public function indexLayanan()
    {
        $layanans = Layanan::with('loket')->get();
        $lokets   = Loket::all();

        return view('admin.layanan', compact('layanans', 'lokets'));
    }

    // REKAP DENGAN FILTER
    public function indexRekap(Request $request)
    {
        $query = Antrean::with(['layanan', 'loket', 'petugas']);

        // Filter Search (Nama Mahasiswa)
        if ($request->filled('search')) {
            $query->where('nama_mahasiswa', 'like', '%' . $request->search . '%');
        }

        // Filter Loket
        if ($request->filled('loket_id')) {
            $query->where('loket_pelayanan_id', $request->loket_id);
        }

        // Filter Layanan
        if ($request->filled('layanan_id')) {
            $query->where('layanan_id', $request->layanan_id);
        }

        // Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        $antreans = $query->latest()->get();
        $lokets   = Loket::all();
        $layanans = Layanan::all();

        return view('admin.rekap', compact('antreans', 'lokets', 'layanans'));
    }

    // EXPORT EXCEL MULTI-SHEET & CHART
    public function exportRekapExcel(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', now()->toDateString());

        $antreans = Antrean::with(['layanan', 'loket', 'petugas'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->groupBy('tanggal');

        if ($antreans->isEmpty()) {
            return back()->with('error', 'Tidak ada data antrean pada rentang tanggal tersebut.');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Hapus sheet default

        foreach ($antreans as $tanggal => $items) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(Carbon::parse($tanggal)->format('d-m-Y'));

            // Header Kolom
            $headers = [
                'No', 'Tanggal', 'Nama/Identitas Pelapor', 'Status Pelapor',
                'Jenis Keluhan', 'Subjek/Uraian Keluhan', 'Media Penyampaian',
                'Unit/Petugas Penanganan', 'Tindak Lanjut', 'Status Penyelesaian',
                'Tanggal Selesai', 'Lama Penyelesaian (Hari)', 'Kepuasan Setelah Penyelesaian', 'Keterangan'
            ];

            $sheet->fromArray($headers, NULL, 'A1');

            // Style Header
            $sheet->getStyle('A1:N1')->getFont()->setBold(true);
            $sheet->getStyle('A1:N1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('001F3F'); // Dark Blue
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
                $sheet->setCellValue('H' . $row, $item->loket->nama_loket ?? '-');
                $sheet->setCellValue('I' . $row, 'Telah dilayani di loket');
                $sheet->setCellValue('J' . $row, $item->status == 'DONE' ? 'Selesai' : $item->status);
                $sheet->setCellValue('K' . $row, Carbon::parse($item->updated_at)->format('d/m/Y'));
                $sheet->setCellValue('L' . $row, 0);
                $sheet->setCellValue('M' . $row, 'Puas');
                $sheet->setCellValue('N' . $row, 'Data Antrean Sistem');
                $row++;
            }

            // GRAFIK DI EXCEL (Bar Chart Pengunjung Per Loket Hari Itu)
            $loketCounts   = $items->groupBy('loket.nama_loket')->map->count();
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