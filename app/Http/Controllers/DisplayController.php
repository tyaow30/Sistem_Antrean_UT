<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Gerai;
use Illuminate\Http\Request;

class DisplayController extends Controller
{
    // Halaman Layar Display TV
    public function show($geraiId)
    {
        $gerai = Gerai::findOrFail($geraiId);
        return view('display.show', compact('gerai'));
    }

    // API Polling untuk Ambil Data Realtime
    public function getLatest($geraiId)
    {
        $today = now()->toDateString();

        // Ambil antrean yang sedang dipanggil/dilayani
        $antreanAktif = Antrean::with('loketMelayani')
            ->where('tanggal', $today)
            ->where('gerai_id', $geraiId)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->orderBy('updated_at', 'desc')
            ->first();

        // Ambil riwayat 5 antrean terakhir yang dipanggil
        $riwayat = Antrean::with('loketMelayani')
            ->where('tanggal', $today)
            ->where('gerai_id', $geraiId)
            ->whereIn('status', ['CALLED', 'SERVING', 'DONE'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'aktif' => $antreanAktif,
            'riwayat' => $riwayat
        ]);
    }
}