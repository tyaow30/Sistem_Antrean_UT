<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Gerai;
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
    /** @var \App\Models\User $user */
    $user = auth()->user();

    $today = now()->toDateString();

    // 1. Pastikan petugas mempunyai loket (Ubah bagian ini)
    if (!$user || !$user->assigned_loket_id) {
        // PERBAIKAN: Gunakan redirect()->route() ke halaman aman, BUKAN back()
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


        // 5. CEK PETUGAS LAIN DI GERAI YANG BENAR-BENAR AKTIF
        
        $batasAktif = now()->subMinutes(10);

        $adaPetugasLainAktif = Loket::where(
                'gerai_id',
                $user->assigned_gerai_id
            )
            ->where('id', '!=', $user->assigned_loket_id)
            ->whereNotNull('active_petugas_id')
            ->where('status', 'ACTIVE')
            ->where('last_heartbeat_at', '>=', $batasAktif)
            ->exists();

        // 6. CEK APAKAH PETUGAS INI MASIH PUNYA ANTREAN

        // Antrean WAITING milik loket sendiri
        $adaAntreanWaiting = Antrean::where('tanggal', $today)
            ->where('loket_asal_id', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->exists();

        // Antrean yang sedang dipanggil/dilayani petugas sendiri
        $adaAntreanAktif = Antrean::where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->whereIn('status', [
                'CALLED',
                'SERVING',
            ])
            ->exists();

        // Petugas masih punya pekerjaan
        $adaAntreanSendiri =
            $adaAntreanWaiting ||
            $adaAntreanAktif;

        // =====================================================
        // 7. ANTREAN BANTUAN
        // =====================================================

        $antreanBantuan = collect();

        /*
        * Antrean bantuan hanya ditampilkan jika:
        *
        * A. Petugas sendiri sudah tidak punya antrean
        *
        * ATAU
        *
        * B. Petugas lain di gerai sudah tidak aktif.
        *
        * Jadi:
        *
        * - Kita punya antrean
        * - Petugas lain masih aktif
        *
        * => bantuan TIDAK ditampilkan.
        */

        if (!$adaAntreanSendiri || !$adaPetugasLainAktif) {

            $antreanBantuan = Antrean::where('tanggal', $today)
                ->where('gerai_id', $user->assigned_gerai_id)
                ->where('loket_asal_id', '!=', $user->assigned_loket_id)
                ->where('status', 'WAITING')
                ->orderBy('id', 'asc')
                ->get();
        }

        // =====================================================
        // 8. KIRIM DATA KE DASHBOARD
        // =====================================================

        return view('petugas.dashboard', compact(
            'antreanSaatIni',
            'antreanSaya',
            'antreanBantuan',
            'user'
        ));
    }

    public function heartbeat()
    {
        /** @var \App\Models\User $user */
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

        // Bersihkan loket lain yang heartbeat-nya sudah kadaluarsa
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

        // 1. Pastikan petugas memiliki loket
        if (!$user->assigned_loket_id) {
            return back()->with(
                'error',
                'Anda belum memiliki loket.'
            );
        }

        // 2. Pastikan ada sesi aktif untuk hari ini
        $sesi = SesiHari::where('is_open', true)
            ->whereDate('tanggal', today())
            ->latest()
            ->first();

        if (!$sesi) {
            return back()->with(
                'error',
                'Sesi antrean hari ini sedang ditutup.'
            );
        }

        // 3. Pastikan loket benar-benar milik petugas yang login
        //    dan masih ACTIVE
        $loket = Loket::with('gerai')
            ->where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with(
                'error',
                'Loket Anda tidak aktif atau tidak sedang ditugaskan kepada Anda.'
            );
        }

        // 4. Pastikan gerai loket masih aktif
        if (!$loket->gerai || !$loket->gerai->is_active) {
            return back()->with(
                'error',
                'Gerai tempat Anda bertugas sedang tidak aktif.'
            );
        }

        // 5. Pastikan gerai penugasan user sama dengan gerai loket
        if ((int) $user->assigned_gerai_id !== (int) $loket->gerai_id) {
            return back()->with(
                'error',
                'Penugasan gerai Anda tidak sesuai dengan loket.'
            );
        }

        // 6. Semua pengecekan dan pengambilan antrean
        //    dilakukan dalam satu transaction
        $antrean = DB::transaction(function () use (
            $id,
            $sesi,
            $user,
            $loket
        ) {
            // 6a. Pastikan petugas belum memiliki antrean aktif
            $sedangDilayani = Antrean::where(
                    'sesi_hari_id',
                    $sesi->id
                )
                ->where('tanggal', $sesi->tanggal)
                ->where('petugas_id', $user->id)
                ->whereIn('status', [
                    'CALLED',
                    'SERVING',
                ])
                ->lockForUpdate()
                ->exists();

            if ($sedangDilayani) {
                return null;
            }

            // 6b. Ambil antrean bantuan
            //
            // Syarat:
            // - ID sesuai request
            // - berasal dari sesi aktif
            // - tanggal sesuai sesi
            // - berasal dari gerai yang sama
            // - bukan dari loket sendiri
            // - masih WAITING
            $antrean = Antrean::where('id', $id)
                ->where('sesi_hari_id', $sesi->id)
                ->where('tanggal', $sesi->tanggal)
                ->where('gerai_id', $loket->gerai_id)
                ->where('loket_asal_id', '!=', $loket->id)
                ->where('status', 'WAITING')
                ->lockForUpdate()
                ->first();

            if (!$antrean) {
                return null;
            }

            // 6c. Ambil antrean tersebut untuk dilayani
            $antrean->update([
                'status' => 'CALLED',
                'loket_melayani_id' => $loket->id,
                'petugas_id' => $user->id,
                'waktu_panggil' => now(),
            ]);

            return $antrean->fresh();
        });

        // 7. Jika gagal mengambil antrean
        if (!$antrean) {
            return back()->with(
                'error',
                'Antrean bantuan sudah dipanggil petugas lain atau Anda masih memiliki antrean aktif.'
            );
        }

        // 8. Broadcast ke Display Realtime
        event(new AntreanDipanggil($antrean));

        return back()->with(
            'success',
            'Memanggil Antrean Bantuan ' .
            ($antrean->kode_antrean ?? $antrean->nomor_antrean)
        );
    }

    public function panggilUlang($id)
    {
        $user = auth()->user();

        if (!$user->assigned_loket_id) {
            return back()->with(
                'error',
                'Anda belum memiliki loket.'
            );
        }

        $sesi = SesiHari::where('is_open', true)
            ->whereDate('tanggal', today())
            ->latest()
            ->first();

        if (!$sesi) {
            return back()->with(
                'error',
                'Sesi antrean hari ini sedang ditutup.'
            );
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with(
                'error',
                'Loket Anda tidak aktif atau tidak sedang ditugaskan kepada Anda.'
            );
        }

        $antrean = DB::transaction(function () use (
            $id,
            $sesi,
            $user,
            $loket
        ) {

            $antrean = Antrean::where('id', $id)
                ->where('sesi_hari_id', $sesi->id)
                ->where('tanggal', $sesi->tanggal)
                ->where('petugas_id', $user->id)
                ->where('loket_melayani_id', $loket->id)
                ->where('status', 'CALLED')
                ->lockForUpdate()
                ->first();

            if (!$antrean) {
                return null;
            }

            $antrean->update([
                'waktu_panggil' => now(),
            ]);

            return $antrean->fresh();
        });

        if (!$antrean) {
            return back()->with(
                'error',
                'Antrean tidak dapat dipanggil ulang.'
            );
        }

        event(new AntreanDipanggil($antrean));

        return back()->with(
            'success',
            'Memanggil ulang antrean ' .
            ($antrean->kode_antrean ?? $antrean->nomor_antrean)
        );
    }

    // Update Status Antrean (Selesai / Lewati / Panggil Ulang / Mulai Melayani)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SERVING,DONE,SKIPPED,CALLED',
        ]);

        $user = auth()->user();

        // 1. Pastikan user memiliki loket
        if (!$user->assigned_loket_id) {
            return back()->with(
                'error',
                'Anda belum memiliki loket.'
            );
        }

        // 2. Pastikan ada sesi aktif untuk hari ini
        $sesi = SesiHari::where('is_open', true)
            ->whereDate('tanggal', today())
            ->latest()
            ->first();

        if (!$sesi) {
            return back()->with(
                'error',
                'Sesi antrean hari ini sedang ditutup.'
            );
        }

        // 3. Pastikan loket benar-benar milik petugas yang login
        //    dan masih aktif
        $loket = Loket::with('gerai')
            ->where('id', $user->assigned_loket_id)
            ->where('status', 'ACTIVE')
            ->where('active_petugas_id', $user->id)
            ->first();

        if (!$loket) {
            return back()->with(
                'error',
                'Loket Anda tidak aktif atau tidak sedang ditugaskan kepada Anda.'
            );
        }

        // 4. Pastikan gerai loket masih aktif
        if (!$loket->gerai || !$loket->gerai->is_active) {
            return back()->with(
                'error',
                'Gerai tempat Anda bertugas sedang tidak aktif.'
            );
        }

        // 5. Update antrean dalam transaction
        $antrean = DB::transaction(function () use (
            $id,
            $sesi,
            $user,
            $loket,
            $request
        ) {
            // Kunci antrean selama proses update
            $antrean = Antrean::where('id', $id)
                ->where('sesi_hari_id', $sesi->id)
                ->where('tanggal', $sesi->tanggal)
                ->where('petugas_id', $user->id)
                ->where('loket_melayani_id', $loket->id)
                ->whereIn('status', [
                    'CALLED',
                    'SERVING'
                ])
                ->lockForUpdate()
                ->first();

            if (!$antrean) {
                return null;
            }

            // Aturan perpindahan status
            $allowedTransitions = [
                'CALLED' => [
                    'SERVING',
                    'DONE',
                    'SKIPPED',
                    'CALLED',
                ],

                'SERVING' => [
                    'DONE',
                    'SKIPPED',
                ],
            ];

            $currentStatus = $antrean->status;
            $newStatus = $request->status;

            if (!in_array(
                $newStatus,
                $allowedTransitions[$currentStatus] ?? [],
                true
            )) {
                return null;
            }

            $updateData = [
                'status' => $newStatus,
            ];

            // Catat waktu mulai melayani
            if (
                $newStatus === 'SERVING'
                && !$antrean->waktu_layani
            ) {
                $updateData['waktu_layani'] = now();
            }

            // Catat waktu selesai
            if (
                in_array($newStatus, ['DONE', 'SKIPPED'], true)
                && !$antrean->waktu_selesai
            ) {
                $updateData['waktu_selesai'] = now();
            }

            $antrean->update($updateData);

            return $antrean->fresh();
        });

        // 6. Jika antrean tidak ditemukan / tidak valid
        if (!$antrean) {
            return back()->with(
                'error',
                'Antrean tidak ditemukan atau perubahan status tidak valid.'
            );
        }

        // 7. Broadcast ketika antrean dipanggil ulang
        if ($request->status === 'CALLED') {
            event(new AntreanDipanggil($antrean));
        }

        return back()->with(
            'success',
            'Status antrean berhasil diperbarui.'
        );
    }
}