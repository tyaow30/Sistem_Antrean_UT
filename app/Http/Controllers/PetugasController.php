<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Loket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\AntreanDipanggil;

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
            return redirect()->route('kiosk.index')->with('error', 'Anda belum memiliki loket.');
        }

        // 2. Aktifkan loket untuk petugas yang sedang login
        $loket = Loket::find($user->assigned_loket_id);
        
        Loket::where('id', $user->assigned_loket_id)->update([
            'active_petugas_id' => $user->id,
            'status' => 'ACTIVE',
            'last_heartbeat_at' => now(),
        ]);

        // 3. Antrean yang sedang dipanggil/dilayani petugas ini
        $antreanSaatIni = Antrean::where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->first();

        // 4. Antrean WAITING milik loket sendiri
        $daftarAntreanLoket = Antrean::where('tanggal', $today)
            ->where('loket_asal_id', $user->assigned_loket_id)
            ->where('status', 'WAITING')
            ->orderBy('id', 'asc')
            ->get();

        // 5. Cek Petugas Lain yang aktif
        $batasAktif = now()->subMinutes(10);
        $adaPetugasLainAktif = Loket::where('id', '!=', $user->assigned_loket_id)
            ->whereNotNull('active_petugas_id')
            ->where('status', 'ACTIVE')
            ->where('last_heartbeat_at', '>=', $batasAktif)
            ->exists();

        // 6. Cek apakah petugas ini masih punya antrean
        $adaAntreanWaiting = $daftarAntreanLoket->isNotEmpty();
        $adaAntreanAktif = $antreanSaatIni !== null;
        $adaAntreanSendiri = $adaAntreanWaiting || $adaAntreanAktif;

        // 7. Antrean Bantuan dari loket lain
        $daftarAntreanBantuan = collect();
        if (!$adaAntreanSendiri || !$adaPetugasLainAktif) {
            $daftarAntreanBantuan = Antrean::where('tanggal', $today)
                ->where('loket_asal_id', '!=', $user->assigned_loket_id)
                ->where('status', 'WAITING')
                ->orderBy('id', 'asc')
                ->get();
        }

        $daftarLoket = Loket::where('id', '!=', $user->assigned_loket_id)->get();

        return view('petugas.dashboard', compact(
            'loket',
            'antreanSaatIni',
            'daftarAntreanLoket',
            'daftarAntreanBantuan',
            'user',
            'daftarLoket'
        ));
    }

    public function heartbeat()
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user || !$user->assigned_loket_id) {
            return response()->json(['success' => false, 'message' => 'Petugas belum memiliki loket.'], 403);
        }

        $loket = Loket::where('id', $user->assigned_loket_id)
            ->where('active_petugas_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$loket) {
            return response()->json(['success' => false, 'message' => 'Loket tidak aktif.'], 403);
        }

        $loket->update(['last_heartbeat_at' => now()]);

        Loket::where('status', 'ACTIVE')
            ->whereNotNull('active_petugas_id')
            ->where('id', '!=', $loket->id)
            ->where('last_heartbeat_at', '<', now()->subMinutes(10))
            ->update([
                'status' => 'INACTIVE',
                'active_petugas_id' => null,
            ]);

        return response()->json(['success' => true, 'message' => 'Heartbeat berhasil.']);
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
            'loket_pelayanan_id' => $user->assigned_loket_id,
            'waktu_dipanggil' => now(),
        ]);

        event(new AntreanDipanggil($antrean->load(['loketPelayanan', 'serviceAwal'])));

        return back()->with('success', 'Memanggil nomor ' . $antrean->nomor_antrean);
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
                'loket_pelayanan_id' => $user->assigned_loket_id,
                'waktu_dipanggil' => now(),
            ]);

            return $antrean->fresh();
        });

        if (!$antrean) {
            return back()->with('error', 'Antrean bantuan sudah dipanggil petugas lain atau Anda masih memiliki antrean aktif.');
        }

        event(new AntreanDipanggil($antrean->load(['loketPelayanan', 'serviceAwal'])));

        return back()->with('success', 'Memanggil Antrean Bantuan ' . $antrean->nomor_antrean);
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
            return back()->with('error', 'Loket Anda tidak aktif.');
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

        event(new AntreanDipanggil($antrean->load(['loketPelayanan', 'serviceAwal'])));

        return back()->with('success', 'Memanggil ulang antrean ' . $antrean->nomor_antrean);
    }

    // Tombol Selesai 
    public function selesai($id)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak ditemukan.');
        }

        $antrean->update([
            'status' => 'DONE',
            'waktu_selesai' => now(),
        ]);

        return back()->with('success', 'Antrean telah diselesaikan.');
    }

    // Tombol Lewati 
    public function lewati($id)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak ditemukan.');
        }

        $antrean->update([
            'status' => 'SKIPPED',
            'waktu_selesai' => now(),
        ]);

        return back()->with('success', 'Antrean berhasil dilewati.');
    }

    // Update Status Antrean
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SERVING,DONE,SKIPPED,CALLED',
        ]);

        $user = auth()->user();
        $today = now()->toDateString();

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Antrean tidak ditemukan.');
        }

        $updateData = ['status' => $request->status];

        if ($request->status === 'SERVING' && !$antrean->waktu_dilayani) {
            $updateData['waktu_dilayani'] = now();
        }

        if (in_array($request->status, ['DONE', 'SKIPPED'], true) && !$antrean->waktu_selesai) {
            $updateData['waktu_selesai'] = now();
        }

        $antrean->update($updateData);

        if ($request->status === 'CALLED') {
            event(new AntreanDipanggil($antrean->load(['loketPelayanan', 'serviceAwal'])));
        }

        return back()->with('success', 'Status antrean berhasil diperbarui.');
    }

    // Pengalihan Antrean
    public function alihAntrean(Request $request, $id)
    {
        $request->validate([
            'loket_tujuan' => 'required',
            'catatan' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $today = now()->toDateString();

        $antrean = Antrean::where('id', $id)
            ->where('tanggal', $today)
            ->where('petugas_id', $user->id)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->first();

        if (!$antrean) {
            return back()->with('error', 'Gagal mengalihkan! Pastikan antrean sedang aktif dilayani.');
        }

        $antrean->update([
            'status' => 'WAITING',
            'loket_asal_id' => $request->loket_tujuan,
            'petugas_id' => null,
            'waktu_dipanggil' => null,
        ]);

        return back()->with('success', 'Antrean berhasil dialihkan.');
    }

    // Halaman Rekap & Laporan Petugas
    public function rekap(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user || !$user->assigned_loket_id) {
            return redirect()->route('kiosk.index')->with('error', 'Anda belum memiliki loket.');
        }

        $loket = Loket::find($user->assigned_loket_id);
        
        $tanggalMulai = $request->input('tanggal_mulai', now()->toDateString());
        $tanggalSelesai = $request->input('tanggal_selesai', now()->toDateString());
        $keyword = $request->input('keyword');

        $query = Antrean::where('petugas_id', $user->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);

        // Tambahkan pencarian nama atau NIM jika diisi
        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('nim', 'like', "%{$keyword}%");
            });
        }

        $riwayatAntrean = $query->orderBy('waktu_selesai', 'desc')->get();

        $totalSelesai = $riwayatAntrean->where('status', 'DONE')->count();
        $totalDilewati = $riwayatAntrean->where('status', 'SKIPPED')->count();
        $totalDiproses = $riwayatAntrean->count();

        // Jika tombol export ditekan
        if ($request->has('export') && $request->export == 'excel') {
            return $this->exportExcel($riwayatAntrean, $loket, $tanggalMulai, $tanggalSelesai);
        }

        return view('petugas.rekap', compact(
            'loket',
            'user',
            'riwayatAntrean',
            'tanggalMulai',
            'tanggalSelesai',
            'keyword',
            'totalSelesai',
            'totalDilewati',
            'totalDiproses'
        ));
    }

    // Fungsi tambahan untuk export ke CSV/Excel sederhana tanpa package tambahan
    private function exportExcel($data, $loket, $mulai, $selesai)
    {
        $fileName = "rekap-antrean-loket-{$loket->id}-{$mulai}-to-{$selesai}.csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($data, $loket) {
            $file = fopen('php://output', 'w');
            // Header CSV
            fputcsv($file, ['No', 'Nomor Antrean', 'Tanggal', 'Nama Mahasiswa', 'NIM', 'Waktu Selesai', 'Status']);

            foreach ($data as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    'L' . $loket->id . ' - ' . str_pad($row->nomor_antrean, 3, '0', STR_PAD_LEFT),
                    $row->tanggal,
                    $row->nama ?? '-',
                    $row->nim ?? '-',
                    $row->waktu_selesai ?? '-',
                    $row->status
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}