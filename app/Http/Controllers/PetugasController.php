<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Gerai;
use App\Models\Loket;
use App\Models\User;
use Illuminate\Http\Request;
use App\Events\AntreanDipanggil;

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
        $antreanBantuan = Antrean::where('tanggal', $today)
            ->where('gerai_id', $user->assigned_gerai_id)
            ->where('loket_asal_id', '!=', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->orderBy('id', 'asc')
            ->get();

        return view('petugas.dashboard', compact('antreanSaatIni', 'antreanSaya', 'antreanBantuan', 'user'));
    }

    // Panggil Antrean Berikutnya di Loket Sendiri
    public function panggilBerikutnya()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $today = now()->toDateString();

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
        ]);

        // Broadcast event ke Display Realtime
        event(new AntreanDipanggil($antrean));

        return back()->with('success', 'Memanggil nomor ' . $antrean->kode_antrean);
    }

    // Panggil Antrean Bantuan dari Loket Lain
    public function panggilBantuan($id)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $antrean = Antrean::where('id', $id)
            ->where('status', 'WAITING')
            ->firstOrFail();

        $antrean->update([
            'status' => 'CALLED',
            'loket_melayani_id' => $user->assigned_loket_id,
            'petugas_id' => $user->id,
        ]);

        // Broadcast event ke Display Realtime
        event(new AntreanDipanggil($antrean));

        return back()->with('success', 'Memanggil Antrean Bantuan ' . $antrean->kode_antrean);
    }

    // Update Status Antrean (Selesai / Lewati / Panggil Ulang)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SERVING,DONE,SKIPPED,CALLED'
        ]);

        $antrean = Antrean::findOrFail($id);
        $antrean->update(['status' => $request->status]);

        // Kirim sinyal broadcast HANYA jika memanggil ulang (CALLED)
        if ($request->status === 'CALLED') {
            event(new AntreanDipanggil($antrean));
        }

        return back()->with('success', 'Status antrean berhasil diperbarui.');
    }
}