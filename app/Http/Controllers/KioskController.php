<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    // Step 1: Tampilan Utama Kiosk (Pilih Gerai)
    public function index()
    {
        $gerai = Gerai::where('is_active', true)->get();
        return view('kiosk.pilih-gerai', compact('gerai'));
    }

    // Halaman Pilih Loket / Cetak Tiket berdasarkan Gerai
    public function pilihGerai($id)
    {
        $gerai = Gerai::with(['loket' => function ($query) {
            $query->where('status', 'ACTIVE')
                ->whereNotNull('active_petugas_id');
        }])->findOrFail($id);
        
        // Cek Sesi Aktif
        $sesi = SesiHari::where('is_open', true)->latest()->first();

        if (!$sesi) {
            return back()->with('error', 'Mohon maaf, sesi antrean saat ini sedang ditutup!');
        }

        return view('kiosk.pilih-loket', compact('gerai'));
    }

    // Process Cetak Tiket
    public function cetakTiket($loket_id)
    {
        // 1. Pastikan Ada Sesi Aktif
        $sesi = SesiHari::where('is_open', true)->latest()->first();

        if (!$sesi) {
            return redirect()->route('kiosk.index')->with('error', 'Sesi antrean sedang ditutup!');
        }

        $loket = Loket::findOrFail($loket_id);

        // 2. Ambil nomor antrean terakhir KHUSUS untuk Sesi Hari ini
        $lastAntrean = Antrean::where('sesi_hari_id', $sesi->id)
            ->where('loket_asal_id', $loket->id)
            ->max('nomor_antrean') ?? 0;

        $nomorBaru = $lastAntrean + 1;
        $kodeAntrean = 'G' . $loket->gerai_id . '-L' . $loket->nomor_loket . '-' . str_pad($nomorBaru, 3, '0', STR_PAD_LEFT);

        $antrean = Antrean::create([
            'sesi_hari_id' => $sesi->id,
            'tanggal' => $sesi->tanggal,
            'gerai_id' => $loket->gerai_id,
            'loket_asal_id' => $loket->id,
            'loket_melayani_id' => $loket->id,
            'nomor_antrean' => $nomorBaru,
            'kode_antrean' => $kodeAntrean,
            'status' => 'PRINTING',
            'waktu_ambil' => now(),
        ]);

        return view('kiosk.cetak-tiket', compact('antrean', 'loket'));
    }

    public function confirmCetak($id)
    {
        $antrean = Antrean::findOrFail($id);

        if ($antrean->status !== 'PRINTING') {
            return redirect()
                ->route('kiosk.index')
                ->with('error', 'Tiket sudah diproses.');
        }

        $antrean->update([
            'status' => 'WAITING',
        ]);

        return redirect()->route('kiosk.index');
    }

    public function cancelCetak($id)
    {
        $antrean = Antrean::findOrFail($id);

        if ($antrean->status !== 'PRINTING') {
            return redirect()
                ->route('kiosk.index')
                ->with('error', 'Tiket sudah diproses.');
        }

        $antrean->delete();

        return redirect()->route('kiosk.index');
    }
}