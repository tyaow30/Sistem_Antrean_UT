<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use Illuminate\Http\Request;

class DisplayController extends Controller
{
    // Halaman Layar Display TV
    public function show()
    {
        return view('display.show');
    }

    // API Polling untuk Ambil Data Realtime
    public function getLatest()
    {
        $today = now()->toDateString();

        // Ambil antrean yang sedang dipanggil/dilayani (dari semua loket)
        $antreanAktif = Antrean::with('loketAsal')
            ->where('tanggal', $today)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->orderBy('updated_at', 'desc')
            ->first();

        // Ambil riwayat 5 antrean terakhir yang dipanggil
        $riwayat = Antrean::with('loketAsal')
            ->where('tanggal', $today)
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