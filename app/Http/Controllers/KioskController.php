<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    // =========================================================
    // KIOSK
    // =========================================================

    public function index()
    {
        $gerai = Gerai::where('is_active', true)->get();

        return view(
            'kiosk.pilih-gerai',
            compact('gerai')
        );
    }

    // =========================================================
    // PILIH GERAI
    // =========================================================

    public function pilihGerai($id)
    {
        $gerai = Gerai::with([
            'loket' => function ($query) {
                $query->where('status', 'ACTIVE')
                    ->whereNotNull('active_petugas_id');
            }
        ])->findOrFail($id);

        if (!$gerai->is_active) {
            return redirect()
                ->route('kiosk.index')
                ->with(
                    'error',
                    'Gerai tersebut sedang tidak aktif!'
                );
        }

        $sesi = SesiHari::where('is_open', true)
            ->whereDate('tanggal', today())
            ->latest()
            ->first();

        if (!$sesi) {
            return redirect()
                ->route('kiosk.index')
                ->with(
                    'error',
                    'Mohon maaf, sesi antrean saat ini sedang ditutup!'
                );
        }

        return view(
            'kiosk.pilih-loket',
            compact('gerai')
        );
    }

    // =========================================================
    // CETAK TIKET
    // =========================================================

    public function cetakTiket($loket_id)
    {
        try {
            $antrean = DB::transaction(function () use ($loket_id) {

                /*
                 * LOCK SESI
                 *
                 * Sesi dikunci supaya dua request bersamaan
                 * tidak bisa mengambil nomor antrean secara
                 * bersamaan.
                 */
                $sesi = SesiHari::where('is_open', true)
                    ->whereDate('tanggal', today())
                    ->latest()
                    ->lockForUpdate()
                    ->first();

                if (!$sesi) {
                    throw new \RuntimeException(
                        'Sesi antrean sedang ditutup!'
                    );
                }

                /*
                 * LOCK LOKET
                 *
                 * Pastikan status loket masih benar ketika
                 * transaksi berlangsung.
                 */
                $loket = Loket::with('gerai')
                    ->where('id', $loket_id)
                    ->lockForUpdate()
                    ->first();

                if (!$loket) {
                    throw new \RuntimeException(
                        'Loket tidak ditemukan!'
                    );
                }

                // Loket harus ACTIVE
                if ($loket->status !== 'ACTIVE') {
                    throw new \RuntimeException(
                        'Loket tersebut sedang tidak aktif!'
                    );
                }

                // Harus ada petugas aktif
                if (!$loket->active_petugas_id) {
                    throw new \RuntimeException(
                        'Loket belum memiliki petugas aktif!'
                    );
                }

                // Gerai harus aktif
                if (
                    !$loket->gerai ||
                    !$loket->gerai->is_active
                ) {
                    throw new \RuntimeException(
                        'Gerai loket sedang tidak aktif!'
                    );
                }

                /*
                 * Ambil nomor terakhir.
                 *
                 * Karena SESI dikunci, request cetak tiket
                 * lain akan menunggu transaksi ini selesai.
                 */
                $lastAntrean = Antrean::where(
                    'sesi_hari_id',
                    $sesi->id
                )
                    ->where(
                        'loket_asal_id',
                        $loket->id
                    )
                    ->max('nomor_antrean');

                $nomorBaru = ((int) $lastAntrean) + 1;

                $kodeAntrean =
                    'G' . $loket->gerai_id .
                    '-L' . $loket->nomor_loket .
                    '-' .
                    str_pad(
                        $nomorBaru,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );

                return Antrean::create([
                    'sesi_hari_id' => $sesi->id,
                    'tanggal' => $sesi->tanggal,
                    'gerai_id' => $loket->gerai_id,
                    'loket_asal_id' => $loket->id,
                    'loket_melayani_id' => $loket->id,
                    'petugas_id' => null,
                    'nomor_antrean' => $nomorBaru,
                    'kode_antrean' => $kodeAntrean,
                    'status' => 'PRINTING',
                    'waktu_ambil' => now(),
                ]);
            });

            // Ambil ulang loket untuk view
            $loket = Loket::with('gerai')
                ->findOrFail($loket_id);

            return view(
                'kiosk.cetak-tiket',
                compact('antrean', 'loket')
            );

        } catch (\RuntimeException $e) {

            return redirect()
                ->route('kiosk.index')
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    // =========================================================
    // KONFIRMASI CETAK
    // =========================================================

    public function confirmCetak($id)
    {
        $antrean = Antrean::findOrFail($id);

        if ($antrean->status !== 'PRINTING') {
            return redirect()
                ->route('kiosk.index')
                ->with(
                    'error',
                    'Tiket sudah diproses.'
                );
        }

        // Pastikan tiket masih berasal dari sesi yang aktif
        $sesi = SesiHari::where('id', $antrean->sesi_hari_id)
            ->where('is_open', true)
            ->whereDate('tanggal', today())
            ->first();

        if (!$sesi) {
            return redirect()
                ->route('kiosk.index')
                ->with(
                    'error',
                    'Sesi tiket sudah ditutup.'
                );
        }

        $antrean->update([
            'status' => 'WAITING',
        ]);

        return redirect()
            ->route('kiosk.index');
    }

    // =========================================================
    // BATAL CETAK
    // =========================================================

    public function cancelCetak($id)
    {
        $antrean = Antrean::findOrFail($id);

        if ($antrean->status !== 'PRINTING') {
            return redirect()
                ->route('kiosk.index')
                ->with(
                    'error',
                    'Tiket sudah diproses.'
                );
        }

        $sesi = SesiHari::where('id', $antrean->sesi_hari_id)
            ->where('is_open', true)
            ->whereDate('tanggal', today())
            ->first();

        if (!$sesi) {
            return redirect()
                ->route('kiosk.index')
                ->with(
                    'error',
                    'Sesi tiket sudah ditutup.'
                );
        }

        $antrean->delete();

        return redirect()
            ->route('kiosk.index');
    }
}