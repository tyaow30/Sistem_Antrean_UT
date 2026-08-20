<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    // Step 1: Tampilan Utama Kiosk (Pilih Gerai)
    public function index()
    {
        $gerai = Gerai::where('is_active', true)->get();
        return view('kiosk.pilih-gerai', compact('gerai'));
    }

    // Step 2: Pilih Loket Berdasarkan Gerai
    public function pilihLoket($geraiId)
    {
        $today = now()->toDateString();

        // Validasi Sesi Hari Ini sebelum membuka halaman pilih loket
        $sesi = SesiHari::where('tanggal', $today)->first();
        if (!$sesi || !$sesi->is_open) {
            return back()->with('error', 'Maaf, layanan antrean hari ini belum dibuka atau sudah ditutup.');
        }

        // Hanya ambil loket yang ACTIVE dan MEMILIKI PETUGAS aktif
        $loketAktif = Loket::where('gerai_id', $geraiId)
            ->where('status', 'ACTIVE')
            ->whereNotNull('active_petugas_id')
            ->get();

        $gerai = Gerai::findOrFail($geraiId);

        return view('kiosk.pilih-loket', compact('loketAktif', 'gerai'));
    }

    // Step 3: Simpan Transaksi & Cetak Tiket
    public function generateTiket(Request $request)
    {
        $today = now()->toDateString();

        // Validasi Sesi Hari Ini
        $sesi = SesiHari::where('tanggal', $today)->first();
        if (!$sesi || !$sesi->is_open) {
            return redirect()->route('kiosk.index')->with('error', 'Maaf, layanan antrean hari ini belum dibuka atau sudah ditutup.');
        }

        $request->validate([
            'gerai_id' => 'required|exists:gerai,id',
            'loket_id' => 'required|exists:loket,id',
        ]);

        $geraiId = $request->gerai_id;
        $loketId = $request->loket_id;

        $loket = Loket::findOrFail($loketId);

        // Cari nomor urut terakhir di loket tersebut untuk hari ini
        $lastAntrean = Antrean::where('tanggal', $today)
            ->where('loket_asal_id', $loketId)
            ->max('nomor_urut') ?? 0;

        $nextUrut = $lastAntrean + 1;
        $kodeAntrean = sprintf("G%d-L%d-%03d", $geraiId, $loket->nomor_loket, $nextUrut);

        $antrean = Antrean::create([
            'tanggal' => $today,
            'kode_antrean' => $kodeAntrean,
            'nomor_urut' => $nextUrut,
            'gerai_id' => $geraiId,
            'loket_asal_id' => $loketId,
            'status' => 'WAITING',
        ]);

        return view('kiosk.cetak-tiket', compact('antrean', 'loket'));
    }
}