<?php

namespace App\Http\Controllers;

use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    /**
     * Halaman Paling Awal (Splash Screen / Selamat Datang)
     */
    public function welcome()
    {
        $today = now()->toDateString();
        $sesi = SesiHari::where('tanggal', $today)->first();
        $layananList = Service::all(); 

        return view('kiosk.index', compact('layananList', 'sesi'));
    }

    /**
     * Halaman Utama Kiosk (Form Data Diri & Pilihan Layanan dalam 1 Halaman)
     */
    public function index()
    {
        $today = now()->toDateString();
        $sesi = SesiHari::where('tanggal', $today)->first();

        // Menggunakan model Service yang terhubung ke tabel 'services'
        $layananList = Service::all();

        // Ambil daftar loket yang aktif beserta total antrean hari ini
        $loketList = Loket::where('status', 'ACTIVE')
            ->whereNotNull('active_petugas_id')
            ->withCount(['antreans as total_antrean' => function ($query) use ($today) {
                $query->where('tanggal', $today)->where('status', 'WAITING');
            }])
            ->get();

        return view('kiosk.index', compact('loketList', 'sesi', 'layananList'));
    }

    /**
     * Memproses Pengambilan Antrean & Membuat Tiket Baru Berdasarkan Layanan yang Dipilih
     */
    public function cetakTiket(Request $request)
    {
        $request->validate([
            'layanan_id' => 'required|exists:services,id',
            'nama'       => 'required|string|max:100',
            'nim'        => 'required|string|max:50',
            'no_hp'      => 'nullable|string|max:20',
            'kendala'    => 'nullable|string|max:255',
        ], [
            'layanan_id.required' => 'Silakan pilih salah satu layanan terlebih dahulu.',
            'layanan_id.exists'   => 'Layanan yang dipilih tidak valid.',
            'nama.required'       => 'Nama lengkap wajib diisi.',
            'nim.required'        => 'NIM atau NIK wajib diisi.',
        ]);

        $layananId = $request->input('layanan_id');

        try {
            // Simpan data antrean ke database menggunakan Database Transaction
            $antrean = DB::transaction(function () use ($request, $layananId) {
                $today = now()->toDateString();

                // Cek apakah sesi antrean hari ini dibuka
                $sesi = SesiHari::where('is_open', true)
                    ->where('tanggal', $today)
                    ->latest()
                    ->lockForUpdate()
                    ->first();

                if (!$sesi) {
                    throw new \RuntimeException('Sesi antrean saat ini sedang ditutup oleh Admin!');
                }

                // AMBIL DATA LAYANAN & LOKETNYA LANGSUNG DI DALAM TRANSAKSI
                $layanan = Service::with('lokets')->findOrFail($layananId);
                $loket = $layanan->lokets->first();

                if (!$loket) {
                    $loket = Loket::first();
                }

                if (!$loket) {
                    throw new \RuntimeException('Data Loket di database masih kosong. Harap tambahkan loket terlebih dahulu.');
                }

                // Ambil ID loket dengan aman
                $loketId = $loket->id;

                // Hitung nomor antrean berikutnya untuk loket tersebut
                $lastAntrean = Antrean::where('tanggal', $today)
                    ->where('loket_pelayanan_id', $loketId)
                    ->orderBy('id', 'desc')
                    ->first();

                $nomorBaru = $lastAntrean ? ((int) $lastAntrean->nomor_antrean) + 1 : 1;

                dd([
    'loket_id_yang_dicari' => $loket->id ?? 'KOSONG',
    'data_yang_mau_disimpan' => [
        'tanggal' => $today,
        'loket_asal_id' => $loket->id ?? null,
        'loket_pelayanan_id' => $loket->id ?? null,
        'service_awal_id' => $layanan->id,
        'nama' => $request->nama,
    ]
]);

                return Antrean::create([
                    'tanggal'            => $today,
                    'loket_asal_id'      => $loketId,
                    'loket_pelayanan_id' => $loketId,
                    'service_awal_id'    => $layanan->id, 
                    'petugas_id'         => null,
                    'nomor_antrean'      => $nomorBaru,
                    'status'             => 'PRINTING',
                    'waktu_ambil'        => now(),
                    'nama'               => $request->nama,
                    'nim'                => $request->nim,
                    'no_hp'              => $request->no_hp,
                    'kendala'            => $request->kendala,
                ]);
            });

            return redirect()->route('kiosk.tiket.preview', $antrean->id);

        } catch (\RuntimeException $e) {
            return redirect()
                ->route('kiosk.index')
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }
    /**
     * Menampilkan Halaman Preview Tiket Antrean (Kiosk PREVIEW TIKET)
     */
    public function previewTiket($id)
    {
        $antrean = Antrean::with(['loketPelayanan'])->findOrFail($id);
        $loket = $antrean->loketPelayanan;

        return view('kiosk.cetak-tiket', compact('antrean', 'loket'));
    }

    /**
     * Konfirmasi Cetak Tiket (Mengubah status antrean menjadi WAITING)
     */
    public function confirmCetak($id)
    {
        $antrean = Antrean::findOrFail($id);
        
        if ($antrean->status === 'PRINTING') {
            $antrean->update(['status' => 'WAITING']);
        }

        return redirect()->route('kiosk.welcome')->with('success', 'Antrean berhasil dibuat! Silakan ambil tiket Anda.');
    }

    /**
     * Pembatalan Cetak Tiket (Menghapus antrean jika dibatalkan)
     */
    public function cancelCetak($id)
    {
        $antrean = Antrean::findOrFail($id);

        if ($antrean->status === 'PRINTING') {
            $antrean->delete();
        }

        return redirect()->route('kiosk.welcome')->with('info', 'Pengambilan antrean dibatalkan.');
    }
}