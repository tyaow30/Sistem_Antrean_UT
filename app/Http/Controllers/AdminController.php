<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Gerai;
use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        // Cek Sesi yang Sedang Aktif
        $sesi = SesiHari::where('is_open', true)->latest()->first();

        $geraiList = Gerai::with('loket')->get();
        $petugasList = User::where('role', 'PETUGAS')->with(['assignedGerai', 'assignedLoket'])->get();

        // Jika Sesi Aktif Ada, Hitung Statistik Hanya Berdasarkan Sesi Tersebut
        if ($sesi) {
            $totalTiket = Antrean::where('sesi_hari_id', $sesi->id)->count();
            $menunggu   = Antrean::where('sesi_hari_id', $sesi->id)->where('status', 'WAITING')->count();
            $selesai    = Antrean::where('sesi_hari_id', $sesi->id)->where('status', 'DONE')->count();
            $dilewati   = Antrean::where('sesi_hari_id', $sesi->id)->where('status', 'SKIPPED')->count();
        } else {
            // Jika Sesi Ditutup, Semua Angka Statistik Menjadi 0
            $totalTiket = 0;
            $menunggu   = 0;
            $selesai    = 0;
            $dilewati   = 0;
        }

        return view('admin.dashboard', compact(
            'sesi', 'geraiList', 'petugasList', 
            'totalTiket', 'menunggu', 'selesai', 'dilewati'
        ));
    }

    // Toggle Sesi Hari Ini
    public function toggleSesi()
    {
        $today = now()->toDateString();

        // Cari sesi yang sedang terbuka HANYA untuk hari ini
        $sesiHariIni = SesiHari::where('tanggal', $today)->first();

        if ($sesiHariIni && $sesiHariIni->is_open) {

            // Jika sesi hari ini sedang terbuka, tutup
            $sesiHariIni->update([
                'is_open' => false
            ]);

            $msg = 'Sesi harian berhasil ditutup!';

        } else {

            // Tutup sesi lain yang mungkin masih terbuka
            SesiHari::where('is_open', true)
                ->where('tanggal', '!=', $today)
                ->update([
                    'is_open' => false
                ]);

            if ($sesiHariIni) {

                // Jika sesi hari ini sudah pernah dibuat, buka lagi
                $sesiHariIni->update([
                    'is_open' => true
                ]);

            } else {

                // Jika belum ada, buat sesi baru untuk hari ini
                SesiHari::create([
                    'tanggal' => $today,
                    'is_open' => true,
                ]);
            }

            $msg = 'Sesi harian berhasil dibuka!';
        }

        return back()->with('success', $msg);
    }

    // CRUD GERAI (Otomatis buat Loket 1)
    public function storeGerai(Request $request)
    {
        $request->validate([
            'nama_gerai' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            $gerai = Gerai::create([
                'nama_gerai' => $request->nama_gerai,
                'is_active' => true,
            ]);

            Loket::create([
                'gerai_id' => $gerai->id,
                'nomor_loket' => 1,
                'status' => 'INACTIVE',
            ]);
        });

        return back()->with('success', 'Gerai berhasil dibuat beserta Loket 1 bawaan!');
    }

    public function updateGerai(Request $request, $id)
    {
        $request->validate([
            'nama_gerai' => 'required|string|max:255',
        ]);

        $gerai = Gerai::findOrFail($id);

        $gerai->update([
            'nama_gerai' => $request->nama_gerai,
        ]);

        return back()->with('success', 'Gerai berhasil diperbarui!');
    }

    public function toggleGerai($id)
    {
        $gerai = Gerai::findOrFail($id);

        $gerai->update([
            'is_active' => !$gerai->is_active,
        ]);

        $status = $gerai->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Gerai berhasil {$status}!");
    }

    // CRUD LOKET
    public function storeLoket(Request $request)
    {
        $request->validate([
            'gerai_id' => 'required|exists:gerai,id',
            'nomor_loket' => 'required|integer|min:1',
        ]);

        $exists = Loket::where('gerai_id', $request->gerai_id)
            ->where('nomor_loket', $request->nomor_loket)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Nomor loket tersebut sudah ada di gerai ini!');
        }

        Loket::create([
            'gerai_id' => $request->gerai_id,
            'nomor_loket' => $request->nomor_loket,
            'status' => 'INACTIVE',
        ]);

        return back()->with('success', 'Loket baru berhasil ditambahkan!');
    }

    public function updateLoket(Request $request, $id)
    {
        $request->validate([
            'gerai_id' => 'required|exists:gerai,id',
            'nomor_loket' => 'required|integer|min:1',
        ]);

        $loket = Loket::findOrFail($id);

        $exists = Loket::where('gerai_id', $request->gerai_id)
            ->where('nomor_loket', $request->nomor_loket)
            ->where('id', '!=', $loket->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Nomor loket tersebut sudah ada di gerai ini!');
        }

        $loket->update([
            'gerai_id' => $request->gerai_id,
            'nomor_loket' => $request->nomor_loket,
        ]);

        return back()->with('success', 'Loket berhasil diperbarui!');
    }

    // CRUD PETUGAS
    public function storePetugas(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'assigned_gerai_id' => 'required|exists:gerai,id',
            'assigned_loket_id' => 'required|exists:loket,id',
        ]);

        $loket = Loket::findOrFail($request->assigned_loket_id);

        if ($loket->gerai_id != $request->assigned_gerai_id) {
            return back()->with('error', 'Loket yang dipilih bukan bagian dari gerai tersebut.');
        }

        $petugasSudahDitugaskan = User::where('assigned_loket_id', $request->assigned_loket_id)
            ->where('role', 'PETUGAS')
            ->exists();

        if ($petugasSudahDitugaskan) {
            return back()->with('error', 'Loket tersebut sudah memiliki petugas.');
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'PETUGAS',
            'assigned_gerai_id' => $request->assigned_gerai_id,
            'assigned_loket_id' => $request->assigned_loket_id,
        ]);

        return back()->with('success', 'Akun Petugas berhasil dibuat dan ditugaskan!');
    }
}