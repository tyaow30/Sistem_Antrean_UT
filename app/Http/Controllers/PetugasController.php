<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Loket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\AntreanDipanggil;
use App\Models\SesiHari;

class PetugasController extends Controller
{
    // Halaman Utama Dashboard Petugas
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();

        $today = now()->toDateString();

        // 1. Pastikan petugas mempunyai loket
        if (!$user || !$user->assigned_loket_id) {
            return redirect()->route('kiosk.index')->with(
                'error',
                'Anda belum memiliki loket.'
            );
        }

        // 2. Aktifkan loket untuk petugas yang sedang login
        Loket::where('id', $user->assigned_loket_id)
            ->update([
                'active_petugas_id' => $user->id,
                'status' => 'ACTIVE',
                'last_heartbeat_at' => now(),
            ]);

        // 3. Antrean yang sedang dipanggil/dilayani petugas ini
        $antreanSaatIni = Antrean::where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->whereIn('status', [
                'CALLED',
                'SERVING',
            ])
            ->first();

        // 4. Antrean WAITING milik loket sendiri
        $antreanSaya = Antrean::where('tanggal', $today)
            ->where('loket_asal_id', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->orderBy('id', 'asc')
            ->get();

        // 5. CEK PETUGAS LAIN YANG BENAR-BENAR AKTIF
        $batasAktif = now()->subMinutes(10);

        $adaPetugasLainAktif = Loket::where('id', '!=', $user->assigned_loket_id)
            ->whereNotNull('active_petugas_id')
            ->where('status', 'ACTIVE')
            ->where('last_heartbeat_at', '>=', $batasAktif)
            ->exists();

        // 6. CEK APAKAH PETUGAS INI MASIH PUNYA ANTREAN
        $adaAntreanWaiting = Antrean::where('tanggal', $today)
            ->where('loket_asal_id', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->exists();

        $adaAntreanAktif = Antrean::where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->whereIn('status', [
                'CALLED',
                'SERVING',
            ])
            ->exists();

        $adaAntreanSendiri = $adaAntreanWaiting || $adaAntreanAktif;

        // =====================================================
        // 7. ANTREAN BANTUAN
        // =====================================================
        $antreanBantuan = collect();

        if (!$adaAntreanSendiri || !$adaPetugasLainAktif) {
            $antreanBantuan = Antrean::where('tanggal', $today)
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

    public function heartbeat()
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user || !$user->assigned_loket_id) {
            return response()->json([
                'success' => false,
                'message' => 'Petugas belum memiliki loket.',
            ], 403);
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('active_petugas_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$loket) {
            return response()->json([
                'success' => false,
                'message' => 'Loket tidak aktif.',
            ], 403);
        }

        $loket->update([
            'last_heartbeat_at' => now(),
        ]);

        Loket::where('status', 'ACTIVE')
            ->whereNotNull('active_petugas_id')
            ->where('id', '!=', $loket->id)
            ->where('last_heartbeat_at', '<', now()->subMinutes(10))
            ->update([
                'status' => 'INACTIVE',
                'active_petugas_id' => null,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat berhasil.',
        ]);
    }

    // Panggil Antrean Berikutnya di Loket Sendiri
    public function panggilBerikutnya()
    {
        /** @var User $user */
        $user = auth()->user();
        $today = now()->toDateString();

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
            'petugas_id' => $user->id,
            'waktu_dipanggil' => now(),
        ]);

        event(new AntreanDipanggil($antrean));

        return back()->with('success', 'Memanggil nomor ' . ($antrean->kode_antrean ?? $antrean->nomor_antrean));
    }

    // Panggil Antrean Bantuan dari Loket Lain
    public function panggilBantuan($id)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->assigned_loket_id) {
            return back()->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with('error', 'Loket Anda tidak aktif atau tidak sedang ditugaskan kepada Anda.');
        }

        $today = now()->toDateString();

        $antrean = DB::transaction(function () use ($id, $today, $user, $loket) {
            $sedangDilayani = Antrean::where('tanggal', $today)
                ->where('petugas_id', $user->id)
                ->whereIn('status', ['CALLED', 'SERVING'])
                ->lockForUpdate()
                ->exists();

            if ($sedangDilayani) {
                return null;
            }

            $antrean = Antrean::where('id', $id)
                ->where('tanggal', $today)
                ->where('loket_asal_id', '!=', $loket->id)
                ->where('status', 'WAITING')
                ->lockForUpdate()
                ->first();

            if (!$antrean) {
                return null;
            }

            $antrean->update([
                'status' => 'CALLED',
                'petugas_id' => $user->id,
                'waktu_dipanggil' => now(),
            ]);

            return $antrean->fresh();
        });

        if (!$antrean) {
            return back()->with('error', 'Antrean bantuan sudah dipanggil petugas lain atau Anda masih memiliki antrean aktif.');
        }

        event(new AntreanDipanggil($antrean));

        return back()->with('success', 'Memanggil Antrean Bantuan ' . ($antrean->kode_antrean ?? $antrean->nomor_antrean));
    }

    public function panggilUlang($id)
    {
        $user = auth()->user();

        if (!$user->assigned_loket_id) {
            return back()->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with('error', 'Loket Anda tidak aktif atau tidak sedang ditugaskan kepada Anda.');
        }

        $today = now()->toDateString();

        $antrean = DB::transaction(function () use ($id, $today, $user) {
            $antrean = Antrean::where('id', $id)
                ->where('tanggal', $today)
                ->where('petugas_id', $user->id)
                ->where('status', 'CALLED')
                ->lockForUpdate()
                ->first();

            if (!$antrean) {
                return null;
            }

            $antrean->update([
                'waktu_dipanggil' => now(),
            ]);

            return $antrean->fresh();
        });

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak dapat dipanggil ulang.');
        }

        event(new AntreanDipanggil($antrean));

        return back()->with('success', 'Memanggil ulang antrean ' . ($antrean->kode_antrean ?? $antrean->nomor_antrean));
    }

    // Update Status Antrean
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SERVING,DONE,SKIPPED,CALLED',
        ]);

        $user = auth()->user();

        if (!$user->assigned_loket_id) {
            return back()->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with('error', 'Loket Anda tidak aktif atau tidak sedang ditugaskan kepada Anda.');
        }

        $today = now()->toDateString();

        $antrean = DB::transaction(function () use ($id, $today, $user, $request) {
            $antrean = Antrean::where('id', $id)
                ->where('tanggal', $today)
                ->where('petugas_id', $user->id)
                ->whereIn('status', ['CALLED', 'SERVING'])
                ->lockForUpdate()
                ->first();

            if (!$antrean) {
                return null;
            }

            $allowedTransitions = [
                'CALLED' => ['SERVING', 'DONE', 'SKIPPED', 'CALLED'],
                'SERVING' => ['DONE', 'SKIPPED'],
            ];

            $currentStatus = $antrean->status;
            $newStatus = $request->status;

            if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
                return null;
            }

            $updateData = ['status' => $newStatus];

            if ($newStatus === 'SERVING' && !$antrean->waktu_dilayani) {
                $updateData['waktu_dilayani'] = now();
            }

            if (in_array($newStatus, ['DONE', 'SKIPPED'], true) && !$antrean->waktu_selesai) {
                $updateData['waktu_selesai'] = now();
            }

            $antrean->update($updateData);

            return $antrean->fresh();
        });

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak ditemukan atau perubahan status tidak valid.');
        }

        if ($request->status === 'CALLED') {
            event(new AntreanDipanggil($antrean));
        }

        return back()->with('success', 'Status antrean berhasil diperbarui.');
    }
}