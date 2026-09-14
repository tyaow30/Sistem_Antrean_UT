<?php

namespace App\Http\Controllers;

use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use App\Models\Service; // Menggunakan model Service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    /**
     * Halaman Utama Kiosk (Menampilkan Selamat Datang, Form Data, & Pilihan Layanan Dinamis)
     */
    public function index()
    {
        $today = now()->toDateString();
        $sesi = SesiHari::where('tanggal', $today)->first();

        // Hanya menampilkan layanan yang loketnya berstatus ACTIVE dan memiliki active_petugas_id
        $layananList = Service::whereHas('loket', function($q) {
            $q->where('status', 'ACTIVE')
              ->whereNotNull('active_petugas_id');
        })
        ->with(['loket' => function($q) {
            $q->where('status', 'ACTIVE')
              ->whereNotNull('active_petugas_id');
        }])
        ->get();

        return view('kiosk.index', compact('layananList', 'sesi'));
    }

    /**
     * Memproses Pengambilan Antrean & Membuat Tiket Baru
     */
    public function cetakTiket(Request $request)
    {
        // 1. Validasi Input Data Mahasiswa (Termasuk No. HP & Kendala)
        $request->validate([
            'nama'       => 'required|string|max:100',
            'nim'        => 'required|string|max:50',
            'no_hp'      => 'nullable|string|max:20',
            'kendala'    => 'nullable|string|max:255',
            'layanan_id' => 'required|exists:services,id', // Sesuaikan tabel ke services
        ], [
            'nama.required'       => 'Nama lengkap wajib diisi.',
            'nim.required'        => 'NIM wajib diisi.',
            'layanan_id.required' => 'Silakan pilih salah satu layanan antrean.',
        ]);

        try {
            // 2. Ambil data layanan & loket terkait
            $layanan = Service::with('loket')->findOrFail($request->layanan_id);
            $loket = $layanan->loket;

            if (!$loket) {
                throw new \RuntimeException('Loket untuk layanan ini belum dikonfigurasi!');
            }

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
                    ->where('loket_asal_id', $loket->id)
                    ->orderBy('id', 'desc')
                    ->first();

                $nomorBaru = $lastAntrean ? ((int) $lastAntrean->nomor_antrean) + 1 : 1;

                return Antrean::create([
                    'tanggal'            => $today,
                    'loket_asal_id'      => $loket->id,
                    'loket_pelayanan_id' => $loket->id,
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
        $antrean = Antrean::with(['loketPelayanan', 'serviceAwal'])->findOrFail($id);
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

        return redirect()->route('kiosk.index')->with('success', 'Antrean berhasil dibuat! Silakan ambil tiket Anda.');
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

        return redirect()->route('kiosk.index')->with('info', 'Pengambilan antrean dibatalkan.');
    }
}