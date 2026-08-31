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
    // KIOSK (HALAMAN UTAMA / PILIH GERAI)
    // =========================================================
    public function index()
    {
        $gerai = Gerai::where('is_active', true)->get();

        return view('kiosk.pilih-gerai', compact('gerai'));
    }

    // =========================================================
    // PILIH GERAI (OPSI LOKET)
    // =========================================================
    public function pilihGerai($id)
    {
        $gerai = Gerai::findOrFail($id);

        if (!$gerai->is_active) {
            return redirect()
                ->route('kiosk.index')
                ->with('error', 'Gerai tersebut sedang tidak aktif!');
        }

        $sesi = SesiHari::where('is_open', true)
            ->whereDate('tanggal', today())
            ->latest()
            ->first();

        if (!$sesi) {
            return redirect()
                ->route('kiosk.index')
                ->with('error', 'Mohon maaf, sesi antrean saat ini sedang ditutup!');
        }

        // PERBAIKAN: Longgarkan batas toleransi heartbeat menjadi 5 menit (atau hapus jika ingin murni berdasarkan status aktif petugas)
        // Kalau mau lebih aman dari masalah jeda waktu, kita buat jadi 5 menit atau cek null-nya.
        $batasAktif = now()->subMinutes(10);

        // Ambil SEMUA loket aktif di gerai ini
        $loketList = Loket::where('gerai_id', $id)
            ->where('status', 'ACTIVE')
            ->whereNotNull('active_petugas_id')
            ->where('last_heartbeat_at', '>=', $batasAktif)
            ->withCount(['antrean as total_antrean' => function ($query) use ($sesi) {
                $query->where('sesi_hari_id', $sesi->id)
                    ->whereIn('status', ['WAITING', 'CALLING', 'PRINTING']);
            }])
            ->get();

                return view('kiosk.pilih-loket', compact('gerai', 'loketList'));
            }

    // =========================================================
    // CETAK TIKET (DRAFT RECORD "PRINTING")
    // =========================================================
    public function cetakTiket($loket_id)
    {
        try {
            $antrean = DB::transaction(function () use ($loket_id) {

                $sesi = SesiHari::where('is_open', true)
                    ->whereDate('tanggal', today())
                    ->latest()
                    ->lockForUpdate()
                    ->first();

                if (!$sesi) {
                    throw new \RuntimeException('Sesi antrean sedang ditutup!');
                }

                $loket = Loket::with('gerai')
                    ->where('id', $loket_id)
                    ->lockForUpdate()
                    ->first();

                if (!$loket) {
                    throw new \RuntimeException('Loket tidak ditemukan!');
                }

                if ($loket->status !== 'ACTIVE') {
                    throw new \RuntimeException('Loket tersebut sedang tidak aktif!');
                }

                if (!$loket->active_petugas_id) {
                    throw new \RuntimeException('Loket belum memiliki petugas aktif!');
                }

                if (!$loket->gerai || !$loket->gerai->is_active) {
                    throw new \RuntimeException('Gerai loket sedang tidak aktif!');
                }

                // Ambil nomor urut antrean terakhir
                $lastAntrean = Antrean::where('sesi_hari_id', $sesi->id)
                    ->where('loket_asal_id', $loket->id)
                    ->max('nomor_antrean');

                $nomorBaru = ((int) $lastAntrean) + 1;

                $kodeAntrean = 'G' . $loket->gerai_id .
                    '-L' . $loket->nomor_loket .
                    '-' . str_pad($nomorBaru, 3, '0', STR_PAD_LEFT);

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

            $loket = Loket::with('gerai')->findOrFail($loket_id);

            return view('kiosk.cetak-tiket', compact('antrean', 'loket'));

        } catch (\RuntimeException $e) {
            return redirect()
                ->route('kiosk.index')
                ->with('error', $e->getMessage());
        }
    }

    // =========================================================
    // KONFIRMASI CETAK (STATUS DIUBAH MENJADI WAITING)
    // =========================================================
    public function confirmCetak($id)
    {
        $antrean = Antrean::findOrFail($id);

        if ($antrean->status !== 'PRINTING') {
            return redirect()
                ->route('kiosk.index')
                ->with('error', 'Tiket sudah diproses.');
        }

        $sesi = SesiHari::where('id', $antrean->sesi_hari_id)
            ->where('is_open', true)
            ->whereDate('tanggal', today())
            ->first();

        if (!$sesi) {
            return redirect()
                ->route('kiosk.index')
                ->with('error', 'Sesi tiket sudah ditutup.');
        }

        $antrean->update([
            'status' => 'WAITING',
        ]);

        return redirect()->route('kiosk.index');
    }

    // =========================================================
    // BATAL CETAK (RECORD DIHAPUS DARI DATABASE)
    // =========================================================
    public function cancelCetak($id)
    : \Illuminate\Http\RedirectResponse
    {
        $antrean = Antrean::findOrFail($id);

        if ($antrean->status !== 'PRINTING') {
            return redirect()
                ->route('kiosk.index')
                ->with('error', 'Tiket sudah diproses.');
        }

        $antrean->delete();

        return redirect()
            ->route('kiosk.index')
            ->with('info', 'Antrean dibatalkan.');
    }
}