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
        // Sesi yang sedang aktif
        $sesi = SesiHari::where('is_open', true)
            ->latest()
            ->first();

        $geraiList = Gerai::with('loket')->get();

        $petugasList = User::where('role', 'PETUGAS')
            ->with(['assignedGerai', 'assignedLoket'])
            ->get();

        // Statistik hanya untuk sesi aktif
        if ($sesi) {
            $totalTiket = Antrean::where('sesi_hari_id', $sesi->id)->count();

            $menunggu = Antrean::where('sesi_hari_id', $sesi->id)
                ->where('status', 'WAITING')
                ->count();

            $selesai = Antrean::where('sesi_hari_id', $sesi->id)
                ->where('status', 'DONE')
                ->count();

            $dilewati = Antrean::where('sesi_hari_id', $sesi->id)
                ->where('status', 'SKIPPED')
                ->count();
        } else {
            $totalTiket = 0;
            $menunggu = 0;
            $selesai = 0;
            $dilewati = 0;
        }

        return view('admin.dashboard', compact(
            'sesi',
            'geraiList',
            'petugasList',
            'totalTiket',
            'menunggu',
            'selesai',
            'dilewati'
        ));
    }

    // SESI HARIAN    
    public function toggleSesi()
    {
        $today = now()->toDateString();
        $msg = '';

        DB::transaction(function () use ($today, &$msg) {

            $sesiHariIni = SesiHari::where('tanggal', $today)
                ->lockForUpdate()
                ->first();

            // Tutup sesi tanggal lain
            SesiHari::where('is_open', true)
                ->where('tanggal', '!=', $today)
                ->update([
                    'is_open' => false,
                ]);

            // Jika sesi hari ini sedang terbuka
            if ($sesiHariIni && $sesiHariIni->is_open) {

                $sesiHariIni->update([
                    'is_open' => false,
                ]);

                // Semua loket ACTIVE menjadi INACTIVE.
                // active_petugas_id tetap dipertahankan.
                Loket::where('status', 'ACTIVE')
                    ->update([
                        'status' => 'INACTIVE',
                    ]);

                $msg = 'Sesi harian berhasil ditutup dan semua loket dinonaktifkan!';

                return;
            }

            // Belum pernah ada sesi hari ini
            if (!$sesiHariIni) {

                SesiHari::create([
                    'tanggal' => $today,
                    'is_open' => true,
                ]);

                $msg = 'Sesi harian berhasil dibuka!';

                return;
            }

            // Sesi hari ini ada tetapi tertutup
            $sesiHariIni->update([
                'is_open' => true,
            ]);

            $msg = 'Sesi harian berhasil dibuka!';
        });

        return back()->with('success', $msg);
    }

    // CRUD GERAI
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

            // Setiap gerai otomatis mempunyai Loket 1
            Loket::create([
                'gerai_id' => $gerai->id,
                'nomor_loket' => 1,
                'status' => 'INACTIVE',
            ]);
        });

        return back()->with(
            'success',
            'Gerai berhasil dibuat beserta Loket 1 bawaan!'
        );
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

        return back()->with(
            'success',
            'Gerai berhasil diperbarui!'
        );
    }

    public function toggleGerai($id)
    {
        $gerai = Gerai::findOrFail($id);

        $gerai->update([
            'is_active' => !$gerai->is_active,
        ]);

        $status = $gerai->is_active
            ? 'diaktifkan'
            : 'dinonaktifkan';

        return back()->with(
            'success',
            "Gerai berhasil {$status}!"
        );
    }
    public function destroyGerai($id)
    {
        $gerai = Gerai::with('loket')->findOrFail($id);

        foreach ($gerai->loket as $loket) {
            if ($loket->active_petugas_id) {
                return back()->with('error', 'Gerai tidak bisa dihapus karena ada loket yang sedang digunakan petugas!');
            }
        }

        Loket::where('gerai_id', $gerai->id)->delete();
        
        $gerai->delete();

        return back()->with('success', 'Gerai beserta loket di dalamnya berhasil dihapus!');
    }

        public function showGeraiDetail($id)
        {
        $gerai = \App\Models\Gerai::with(['loket'])->findOrFail($id);
        $petugas = \App\Models\User::where('role', 'PETUGAS')
                                    ->where('gerai_id', $id)
                                    ->get();

        return view('admin.gerai-detail', compact('gerai', 'petugas'));
        }

    // CRUD LOKET
    public function storeLoket(Request $request)
    {
        // 1. Validasi data
        $request->validate([
            'gerai_id'          => 'required|exists:gerai,id',
            'nomor_loket'       => 'required|string|max:50',
            'active_petugas_id' => 'nullable|exists:users,id',
        ]);
        // 2. Cek apakah petugas yang dipilih sudah terpakai di loket lain (Opsional tapi aman)
        if ($request->filled('active_petugas_id')) {
            $petugasSudahPakai = Loket::where('active_petugas_id', $request->active_petugas_id)->exists();
            if ($petugasSudahPakai) {
                return redirect()->back()->withErrors(['active_petugas_id' => 'Petugas ini sudah ditugaskan di loket lain!'])->withInput();
            }
        }
        // 3. Simpan loket baru dengan memasukkan active_petugas_id secara eksplisit
        Loket::create([
            'gerai_id'          => $request->gerai_id,
            'nomor_loket'       => $request->nomor_loket,
            'active_petugas_id' => $request->active_petugas_id,
            'status'            => 'INACTIVE',
        ]);

        return redirect()->back()->with('success', 'Loket dan penugasan petugas berhasil disimpan!');
    }

    public function updateLoket(Request $request, $id)
    {
        $request->validate([
            'gerai_id' => 'required|exists:gerai,id',
            'nomor_loket' => 'required|integer|min:1',
        ]);

        $loket = Loket::findOrFail($id);

        // Cegah nomor duplikat
        $exists = Loket::where('gerai_id', $request->gerai_id)
            ->where('nomor_loket', $request->nomor_loket)
            ->where('id', '!=', $loket->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Nomor loket tersebut sudah ada di gerai ini!'
                );
        }

        if (
            $loket->status === 'ACTIVE' &&
            $loket->active_petugas_id &&
            (int) $loket->gerai_id !== (int) $request->gerai_id
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Loket yang sedang aktif dan memiliki petugas tidak dapat dipindahkan ke gerai lain. Nonaktifkan loket atau lepaskan petugas terlebih dahulu.'
                );
        }

        if (
            $loket->active_petugas_id &&
            (int) $loket->gerai_id !== (int) $request->gerai_id
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Loket yang masih memiliki penugasan petugas tidak dapat dipindahkan. Ubah penugasan petugas terlebih dahulu.'
                );
        }

        $loket->update([
            'gerai_id' => $request->gerai_id,
            'nomor_loket' => $request->nomor_loket,
        ]);

        return back()->with(
            'success',
            'Loket berhasil diperbarui!'
        );
    }
    public function destroyLoket($id)
    {
        $loket = \App\Models\Loket::findOrFail($id);
        \App\Models\User::where('assigned_loket_id', $loket->id)
                        ->update(['assigned_loket_id' => null]);

        $loket->delete();

        return redirect()->back()->with('success', 'Loket berhasil dihapus dan petugas telah dilepas dari tugasnya.');
    }

    // CRUD PETUGAS
        public function storePetugas(Request $request)
        {
        // 1. Validasi murni untuk pembuatan akun saja
        $request->validate([
            'gerai_id' => 'required|exists:gerai,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // 2. Buat akun petugas (tanpa penugasan gerai/loket dulu)
        \App\Models\User::create([
            'gerai_id' => $request->gerai_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'PETUGAS', // Tetap pakai sistem role dari kodemu
        ]);

        return redirect()->back()->with('success', 'Akun petugas baru berhasil ditambahkan!');
    }

public function updatePetugas(Request $request, $id)
{
    $request->validate([
        'active_petugas_id' => 'nullable|exists:users,id',
    ]);

    // Cek apakah petugas sudah dipakai di loket lain (kecuali loket ini sendiri)
    if ($request->filled('active_petugas_id')) {
        $sudahDipakai = Loket::where('active_petugas_id', $request->active_petugas_id)
                             ->where('id', '!=', $id)
                             ->exists();
        if ($sudahDipakai) {
            return redirect()->back()->with('error', 'Petugas tersebut sudah bertugas di loket lain!');
        }
    }

    $loket = Loket::findOrFail($id);
    $loket->update([
        'active_petugas_id' => $request->active_petugas_id,
    ]);

    return redirect()->back()->with('success', 'Petugas pada loket berhasil diperbarui!');
}

    public function destroyPetugas($id)
    {
        $user = User::where('role', 'PETUGAS')->findOrFail($id);

        Loket::where('active_petugas_id', $user->id)->update([
            'status' => 'INACTIVE',
            'active_petugas_id' => null
        ]);
        
        // Hapus akun petugas
        $user->delete();

        return redirect()->back()->with('success', 'Akun petugas berhasil dihapus.');
    }
    // TOGGLE PETUGAS
    public function togglePetugas($id)
    {
        $petugas = User::where('id', $id)
            ->where('role', 'PETUGAS')
            ->firstOrFail();

        $today = now()->toDateString();

        $sesi = SesiHari::where('tanggal', $today)
            ->where('is_open', true)
            ->first();

        if (!$sesi) {
            return back()->with(
                'error',
                'Sesi hari ini belum dibuka. Petugas tidak dapat diaktifkan.'
            );
        }

        if (!$petugas->assigned_loket_id) {
            return back()->with(
                'error',
                'Petugas belum memiliki loket.'
            );
        }

        $loket = Loket::find(
            $petugas->assigned_loket_id
        );

        if (!$loket) {
            return back()->with(
                'error',
                'Loket yang ditugaskan kepada petugas tidak ditemukan.'
            );
        }

        if (
            (int) $loket->gerai_id !==
            (int) $petugas->assigned_gerai_id
        ) {
            return back()->with(
                'error',
                'Penugasan gerai dan loket petugas tidak sesuai.'
            );
        }

        // Jika sedang ACTIVE oleh petugas ini
        if (
            $loket->status === 'ACTIVE' &&
            (int) $loket->active_petugas_id ===
            (int) $petugas->id
        ) {
            $loket->update([
                'status' => 'INACTIVE',
            ]);

            return back()->with(
                'success',
                'Petugas berhasil dinonaktifkan.'
            );
        }

        // Jika digunakan petugas lain
        if (
            $loket->status === 'ACTIVE' &&
            $loket->active_petugas_id
        ) {
            $petugasLain = User::find(
                $loket->active_petugas_id
            );

            $namaPetugas = $petugasLain
                ? $petugasLain->name
                : 'petugas lain';

            return back()->with(
                'error',
                "Loket tersebut sedang digunakan oleh {$namaPetugas}."
            );
        }

        // Aktifkan
        $loket->update([
            'status' => 'ACTIVE',
            'active_petugas_id' => $petugas->id,
        ]);

        return back()->with(
            'success',
            "Petugas {$petugas->name} berhasil diaktifkan."
        );
    }
}