<?php

namespace App\Http\Controllers;

use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Layanan;
class KioskController extends Controller
{
    // Halaman utama kiosk (Form & Pilih Loket jadi satu)
public function index()
    {
        $today = now()->toDateString();
        $sesi = SesiHari::where('tanggal', $today)->first();
        $batasAktif = now()->subMinutes(5);

        // Ambil semua layanan yang loketnya sedang aktif bertugas
        $layananList = Layanan::with(['loket' => function($q) use ($batasAktif) {
            $q->where('status', 'ACTIVE')
              ->whereNotNull('active_petugas_id')
              ->where('last_heartbeat_at', '>=', $batasAktif);
        }])
        ->whereHas('loket', function($q) use ($batasAktif) {
            $q->where('status', 'ACTIVE')
              ->whereNotNull('active_petugas_id')
              ->where('last_heartbeat_at', '>=', $batasAktif);
        })
        ->get();

        return view('kiosk.index', compact('layananList', 'sesi'));
    }

    public function cetakTiket(Request $request, $layanan_id)
{
    $request->validate([
        'nim' => 'required|string|max:50',
        'nama' => 'required|string|max:100',
    ]);

    try {
        // 1. Ambil data layanan & loket DI LUAR transaction agar $loket terbaca sampai baris return view
        $layanan = Layanan::with('loket')->findOrFail($layanan_id);
        $loket = $layanan->loket;

        if (!$loket || $loket->status !== 'ACTIVE') {
            throw new \RuntimeException('Loket untuk layanan ini sedang tidak aktif!');
        }

        $antrean = DB::transaction(function () use ($request, $layanan, $loket) {
            $today = now()->toDateString();
            $sesi = SesiHari::where('is_open', true)
                ->where('tanggal', $today)
                ->latest()
                ->lockForUpdate()
                ->first();

            if (!$sesi) {
                throw new \RuntimeException('Sesi antrean sedang ditutup!');
            }

            $lastAntrean = Antrean::where('tanggal', $today)
                ->where('loket_asal_id', $loket->id)
                ->max('nomor_antrean');

            $nomorBaru = ((int) $lastAntrean) + 1;
        
            return Antrean::create([
                'tanggal' => $today,
                'loket_asal_id' => $loket->id,
                'loket_pelayanan_id' => $loket->id,
                'service_awal_id' => $layanan->id,
                'petugas_id' => null,
                'nomor_antrean' => $nomorBaru,
                'status' => 'WAITING',
                'waktu_ambil' => now(),
                'nim' => $request->nim,
                'nama' => $request->nama,
            ]);
        });

        // $loket sekarang aman dipanggil di sini
        return view('kiosk.cetak-tiket', compact('antrean', 'loket'));

    } catch (\RuntimeException $e) {
        return redirect()
            ->route('kiosk.index')
            ->with('error', $e->getMessage());
    }
}

    public function confirmCetak($id)
    {
        $antrean = Antrean::findOrFail($id);
        if ($antrean->status === 'PRINTING') {
            $antrean->update(['status' => 'WAITING']);
        }
        return redirect()->route('kiosk.index')->with('success', 'Antrean berhasil dicetak!');
    }

    public function cancelCetak($id)
    {
        $antrean = Antrean::findOrFail($id);
        if ($antrean->status === 'PRINTING') {
            $antrean->delete();
        }
        return redirect()->route('kiosk.index')->with('info', 'Antrean dibatalkan.');
    }
}