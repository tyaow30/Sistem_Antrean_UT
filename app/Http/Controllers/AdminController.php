<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Gerai;
use App\Models\SesiHari;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Dashboard Utama Admin
    public function index()
    {
        $today = now()->toDateString();
        $gerai = Gerai::all();
        
        // Cek sesi aktif hari ini
        $sesiHariIni = SesiHari::where('tanggal', $today)->first();

        // Rekapitulasi Antrean Hari Ini
        $totalAntrean = Antrean::where('tanggal', $today)->count();
        $totalSelesai = Antrean::where('tanggal', $today)->where('status', 'DONE')->count();
        $totalLewat = Antrean::where('tanggal', $today)->where('status', 'SKIPPED')->count();
        $totalWaiting = Antrean::where('tanggal', $today)->where('status', 'WAITING')->count();

        return view('admin.dashboard', compact(
            'gerai', 'sesiHariIni', 'totalAntrean', 'totalSelesai', 'totalLewat', 'totalWaiting'
        ));
    }

    // Buka / Tutup Sesi Hari Ini
    public function toggleSesi(Request $request)
    {
        $today = now()->toDateString();
        
        $sesi = SesiHari::firstOrCreate(
            ['tanggal' => $today],
            ['is_open' => false]
        );

        $sesi->update([
            'is_open' => !$sesi->is_open
        ]);

        $statusText = $sesi->is_open ? 'DIBUKA' : 'DITUTUP';
        return back()->with('success', "Sesi antrean hari ini berhasil {$statusText}.");
    }
}