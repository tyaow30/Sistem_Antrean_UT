<?php

namespace App\Http\Controllers;

use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use Illuminate\Http\Request;
use App\Models\Layanan;
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
        $layananList = Layanan::all(); 

        return view('kiosk.index', compact('layananList', 'sesi'));
    }

    /**
     * Halaman Utama Kiosk (Form Data Diri & Pilihan Loket dalam 1 Halaman)
     */
    public function index()
    {
        $today = now()->toDateString();
        $sesi = SesiHari::where('tanggal', $today)->first();

        // Ambil daftar loket yang aktif beserta total antrean hari ini
        $loketList = Loket::where('status', 'ACTIVE')
            ->whereNotNull('active_petugas_id')
            ->withCount(['antreans as total_antrean' => function ($query) use ($today) {
                $query->where('tanggal', $today)->where('status', 'WAITING');
            }])
            ->get();

        return view('kiosk.form-data', compact('loketList', 'sesi'));
    }

    /**
     * Memproses Pengambilan Antrean & Membuat Tiket Baru Berdasarkan Loket yang Dipilih
     */
    public function cetakTiket(Request $request)
    {
        // 1. Validasi Input Data Pengunjung & Pastikan Layanan yang Dipilih Valid
        $request->validate([
            'layanan_id' => 'required|exists:layanans,id', // Sesuaikan nama tabel layanan (biasanya 'layanans' atau 'layanan')
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
            // 2. Ambil data layanan beserta relasi loketnya (pastikan model Layanan punya relasi ke Loket, atau ambil id loket terkait)
            // Asumsi di tabel 'layanans' ada kolom 'loket_id' atau 'loket_pelayanan_id' yang menautkan layanan ke loket.
            $layanan = \App\Models\Layanan::findOrFail($layananId);
            
            // Ambil ID loket yang terikat dengan layanan ini (sesuaikan nama kolom foreign key di tabel layanans kamu, misal: loket_id)
            $loketId = $layanan->loket_id ?? $layanan->loket_pelayanan_id ?? 1; 
            $loket = Loket::findOrFail($loketId);

            // 3. Simpan data antrean ke database menggunakan Database Transaction
            $antrean = DB::transaction(function () use ($request, $layanan, $loket) {
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

                // Hitung nomor antrean berikutnya untuk loket tersebut
                $lastAntrean = Antrean::where('tanggal', $today)
                    ->where('loket_pelayanan_id', $loket->id)
                    ->orderBy('id', 'desc')
                    ->first();

                $nomorBaru = $lastAntrean ? ((int) $lastAntrean->nomor_antrean) + 1 : 1;

                return Antrean::create([
                    'tanggal'            => $today,
                    'loket_asal_id'      => $loket->id,
                    'loket_pelayanan_id' => $loket->id,
                    'service_awal_id'    => $layanan->id, // Simpan ID layanan yang dipilih di sini
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

        // Setelah selesai, kembalikan ke halaman awal (welcome / index) supaya data bersih untuk pelanggan berikutnya
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

        // Kembalikan ke halaman welcome jika dibatalkan
        return redirect()->route('kiosk.welcome')->with('info', 'Pengambilan antrean dibatalkan.');
    }
}