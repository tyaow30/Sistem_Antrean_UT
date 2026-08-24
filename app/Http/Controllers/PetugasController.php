<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Gerai;
use App\Models\Loket;
use App\Models\User;
use Illuminate\Http\Request;
use App\Events\AntreanDipanggil;
use Illuminate\Support\Facades\DB;

class PetugasController extends Controller
{
    // Halaman Utama Dashboard Petugas
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $today = now()->toDateString();

        // 1. Set petugas aktif di loket saat ini
        if ($user && $user->assigned_loket_id) {
            Loket::where('id', $user->assigned_loket_id)->update([
                'active_petugas_id' => $user->id,
                'status' => 'ACTIVE'
            ]);
        }

        // 2. Antrean yang sedang dipanggil/dilayani oleh petugas ini
        $antreanSaatIni = Antrean::where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->first();

        // 3. Daftar antrean menunggu di loket sendiri
        $antreanSaya = Antrean::where('tanggal', $today)
            ->where('loket_asal_id', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->orderBy('id', 'asc')
            ->get();

        // 4. Antrean Bantuan (Waiting dari loket lain di gerai yang sama)
        // $antreanBantuan = Antrean::where('tanggal', $today)
        //     ->where('gerai_id', $user->assigned_gerai_id)
        //     ->where('loket_asal_id', '!=', $user->assigned_loket_id)
        //     ->where('status', 'WAITING')
        //     ->orderBy('id', 'asc')
        //     ->get();

        // return view('petugas.dashboard', compact('antreanSaatIni', 'antreanSaya', 'antreanBantuan', 'user'));

        // 4. Antrean Bantuan
        $antreanBantuan = collect();

        $adaAntreanSendiri = $antreanSaya->isNotEmpty();

        $adaPetugasLogout = Loket::where('gerai_id', $user->assigned_gerai_id)
            ->where('id', '!=', $user->assigned_loket_id)
            ->whereNull('active_petugas_id')
            ->exists();

        if (!$adaAntreanSendiri || $adaPetugasLogout) {
            $antreanBantuan = Antrean::where('tanggal', $today)
                ->where('gerai_id', $user->assigned_gerai_id)
                ->where('loket_asal_id', '!=', $user->assigned_loket_id)
                ->where('status', 'WAITING')
                ->orderBy('id', 'asc')
                ->get();
        }

        return view('petugas.dashboard', compact(
            'antreanSaatIni',
            'antreanSaya',
            'antreanBantuan',
            'user'
        ));
    }

    // Panggil Antrean Berikutnya di Loket Sendiri
    public function panggilBerikutnya()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $today = now()->toDateString();

        // Cek apakah masih ada antrean yang belum diselesaikan/dilewati oleh petugas ini
        $sedangDilayani = Antrean::where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->exists();

        if ($sedangDilayani) {
            return back()->with('error', 'Selesaikan atau lewati antrean saat ini terlebih dahulu!');
        }

        $antrean = Antrean::where('tanggal', $today)
            ->where('loket_asal_id', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->orderBy('id', 'asc')
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Tidak ada antrean tersisa di loket Anda.');
        }

        $antrean->update([
            'status' => 'CALLED',
            'loket_melayani_id' => $user->assigned_loket_id,
            'petugas_id' => $user->id,
            'waktu_panggil' => now(),
        ]);

        // Broadcast event ke Display Realtime
        event(new AntreanDipanggil($antrean));

        return back()->with('success', 'Memanggil nomor ' . ($antrean->kode_antrean ?? $antrean->nomor_antrean));
    }

    // Panggil Antrean Bantuan dari Loket Lain
    public function panggilBantuan($id)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $today = now()->toDateString();

        // Cek apakah masih ada antrean yang belum diselesaikan
        $sedangDilayani = Antrean::where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->exists();

        if ($sedangDilayani) {
            return back()->with('error', 'Selesaikan atau lewati antrean saat ini terlebih dahulu!');
        }

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('gerai_id', $user->assigned_gerai_id)
            ->where('loket_asal_id', '!=', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean bantuan sudah dipanggil oleh petugas lain!');
        }

        $antrean->update([
            'status' => 'CALLED',
            'loket_melayani_id' => $user->assigned_loket_id,
            'petugas_id' => $user->id,
            'waktu_panggil' => now(),
        ]);

        // Broadcast event ke Display Realtime
        event(new AntreanDipanggil($antrean));

        return back()->with('success', 'Memanggil Antrean Bantuan ' . ($antrean->kode_antrean ?? $antrean->nomor_antrean));
    }

    // Update Status Antrean (Selesai / Lewati / Panggil Ulang / Mulai Melayani)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SERVING,DONE,SKIPPED,CALLED',
        ]);

        $user = auth()->user();
        $today = now()->toDateString();

        if (!$user->assigned_loket_id) {
            return back()->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with('error', 'Loket Anda tidak aktif.');
        }

        // Pastikan antrean memang milik petugas yang sedang login
        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->where('loket_melayani_id', $loket->id)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->first();

        if (!$antrean) {
            return back()->with(
                'error',
                'Antrean tidak ditemukan atau bukan antrean yang sedang Anda layani.'
            );
        }

        $allowedTransitions = [
            'CALLED' => ['SERVING', 'SKIPPED', 'CALLED'],
            'SERVING' => ['DONE', 'SKIPPED'],
        ];

        if (!in_array(
            $request->status,
            $allowedTransitions[$antrean->status] ?? []
        )) {
            return back()->with(
                'error',
                'Perubahan status antrean tidak valid.'
            );
        }

        $updateData = [
            'status' => $request->status,
        ];

        // Catat waktu mulai melayani
        if ($request->status === 'SERVING' && !$antrean->waktu_layani) {
            $updateData['waktu_layani'] = now();
        }

        // Catat waktu selesai
        if ($request->status === 'DONE' && !$antrean->waktu_selesai) {
            $updateData['waktu_selesai'] = now();
        }

        $antrean->update($updateData);

        // Broadcast hanya ketika antrean dipanggil ulang
        if ($request->status === 'CALLED') {
            event(new AntreanDipanggil($antrean));
        }

        return back()->with('success', 'Status antrean berhasil diperbarui.');
    }
}