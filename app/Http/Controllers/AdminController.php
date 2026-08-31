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

        $loketIds = $gerai->loket->pluck('id');

        // 1. HAPUS SEMUA AKUN PETUGAS yang terikat dengan Gerai ini atau Loket di Gerai ini
        User::where('role', 'PETUGAS')
            ->where(function ($query) use ($gerai, $loketIds) {
                $query->where('assigned_gerai_id', $gerai->id)
                      ->orWhere('gerai_id', $gerai->id)
                      ->orWhereIn('assigned_loket_id', $loketIds);
            })->delete();

        // 2. Hapus loket yang terhubung dengan gerai
        Loket::where('gerai_id', $gerai->id)->delete();
        
        // 3. Hapus gerai
        $gerai->delete();

        return back()->with('success', 'Gerai beserta loket dan akun petugas di dalamnya berhasil dihapus!');
    }

    // HALAMAN DETAIL GERAI
    public function showGeraiDetail(Request $request, $id)
    {
        $gerai = Gerai::findOrFail($id);

        // Paginate Loket
        $loketList = Loket::where('gerai_id', $id)
            ->paginate(5, ['*'], 'loket_page');

        // Petugas KHUSUS untuk Gerai ini saja
        $allPetugas = User::where('role', 'PETUGAS')
            ->where(function ($query) use ($id) {
                $query->where('assigned_gerai_id', $id)
                      ->orWhere('gerai_id', $id);
            })
            ->get();

        // Paginate Direktori Petugas khusus gerai ini
        $petugasList = User::where('role', 'PETUGAS')
            ->where(function ($query) use ($id) {
                $query->where('assigned_gerai_id', $id)
                      ->orWhere('gerai_id', $id);
            })
            ->paginate(5, ['*'], 'petugas_page');

        return view('admin.gerai-detail', compact('gerai', 'loketList', 'allPetugas', 'petugasList'));
    }

    // CRUD LOKET
    public function storeLoket(Request $request)
    {
        $request->validate([
            'gerai_id'          => 'required|exists:gerai,id',
            'nomor_loket'       => 'required|string|max:50',
            'active_petugas_id' => 'nullable|exists:users,id',
        ]);

        if ($request->filled('active_petugas_id')) {
            $petugasSudahPakai = Loket::where('active_petugas_id', $request->active_petugas_id)->exists();
            if ($petugasSudahPakai) {
                return redirect()->back()->withErrors(['active_petugas_id' => 'Petugas ini sudah ditugaskan di loket lain!'])->withInput();
            }
        }

        DB::transaction(function () use ($request) {
            $loket = Loket::create([
                'gerai_id'          => $request->gerai_id,
                'nomor_loket'       => $request->nomor_loket,
                'active_petugas_id' => $request->active_petugas_id,
                'status'            => 'INACTIVE',
            ]);

            if ($request->filled('active_petugas_id')) {
                User::where('id', $request->active_petugas_id)->update([
                    'gerai_id'         => $request->gerai_id,
                    'assigned_loket_id' => $loket->id,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Loket dan penugasan petugas berhasil disimpan!');
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
            return back()
                ->withInput()
                ->with('error', 'Nomor loket tersebut sudah ada di gerai ini!');
        }

        if (
            $loket->status === 'ACTIVE' &&
            $loket->active_petugas_id &&
            (int) $loket->gerai_id !== (int) $request->gerai_id
        ) {
            return back()
                ->withInput()
                ->with('error', 'Loket yang sedang aktif dan memiliki petugas tidak dapat dipindahkan ke gerai lain.');
        }

        $loket->update([
            'gerai_id' => $request->gerai_id,
            'nomor_loket' => $request->nomor_loket,
        ]);

        return back()->with('success', 'Loket berhasil diperbarui!');
    }

    public function destroyLoket($id)
    {
        $loket = Loket::findOrFail($id);
        
        // Lepas petugas dari loket ini
        User::where('assigned_loket_id', $loket->id)
            ->update(['assigned_loket_id' => null]);

        $loket->delete();

        return redirect()->back()->with('success', 'Loket berhasil dihapus dan petugas telah dilepas dari tugasnya.');
    }

    // CRUD PETUGAS
    public function storePetugas(Request $request)
    {
        $request->validate([
            'gerai_id' => 'required|exists:gerai,id',
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'gerai_id' => $request->gerai_id,
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'PETUGAS',
        ]);

        return redirect()->back()->with('success', 'Akun petugas baru berhasil ditambahkan!');
    }

    // PENUGASAN PETUGAS KE LOKET (SINKRONISASI 2 ARAH)
public function updatePetugas(Request $request, $id)
    {
        $request->validate([
            'active_petugas_id' => 'nullable|exists:users,id',
        ]);

        $loket = Loket::findOrFail($id);
        $newPetugasId = $request->active_petugas_id;
        $oldPetugasId = $loket->active_petugas_id;

        // Jika memilih petugas yang sama, tidak perlu diproses
        if ($newPetugasId == $oldPetugasId) {
            return redirect()->back();
        }

        // Cek apakah petugas baru sudah ditugaskan di LOKET LAIN
        if ($newPetugasId) {
            $sudahDipakai = Loket::where('active_petugas_id', $newPetugasId)
                                ->where('id', '!=', $id)
                                ->exists();

            if ($sudahDipakai) {
                return redirect()->back()->with('error', 'Petugas tersebut sudah bertugas di loket lain! Lepaskan dulu dari loket sebelumnya.');
            }
        }

        DB::transaction(function () use ($loket, $newPetugasId, $oldPetugasId) {
            
            // 1. Lepaskan petugas LAMA dari loket ini (jika ada)
            if ($oldPetugasId) {
                User::where('id', $oldPetugasId)->update([
                    'assigned_loket_id' => null,
                    'assigned_gerai_id' => null, // KOSONGKAN JUGA PENUGASAN GERAINYA
                ]);
            }

            // 2. Update data Loket saat ini
            // Jika petugas dikosongkan (Ganti jadi "Pilih Petugas"), otomatis set loket jadi INACTIVE
            $loket->update([
                'active_petugas_id' => $newPetugasId,
                'status' => $newPetugasId ? $loket->status : 'INACTIVE',
            ]);

            // 3. Hubungkan Petugas BARU ke Loket & Gerai ini
            if ($newPetugasId) {
                User::where('id', $newPetugasId)->update([
                    'assigned_gerai_id' => $loket->gerai_id, // UPDATE ASSIGNED_GERAI_ID, BUKAN GERAI_ID
                    'assigned_loket_id' => $loket->id,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Penugasan petugas pada loket berhasil diperbarui!');
    }

    public function destroyPetugas($id)
    {
        $user = User::where('role', 'PETUGAS')->findOrFail($id);

        // Jika petugas yang dihapus sedang aktif di loket, nonaktifkan loketnya
        Loket::where('active_petugas_id', $user->id)->update([
            'status'            => 'INACTIVE',
            'active_petugas_id' => null
        ]);
        
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
            return back()->with('error', 'Sesi hari ini belum dibuka. Petugas tidak dapat diaktifkan.');
        }

        if (!$petugas->assigned_loket_id) {
            return back()->with('error', 'Petugas belum memiliki loket.');
        }

        $loket = Loket::find($petugas->assigned_loket_id);

        if (!$loket) {
            return back()->with('error', 'Loket yang ditugaskan kepada petugas tidak ditemukan.');
        }

        // Aktifkan / Nonaktifkan
        if ($loket->status === 'ACTIVE' && (int) $loket->active_petugas_id === (int) $petugas->id) {
            $loket->update(['status' => 'INACTIVE']);
            return back()->with('success', 'Petugas berhasil dinonaktifkan.');
        }

        if ($loket->status === 'ACTIVE' && $loket->active_petugas_id) {
            $petugasLain = User::find($loket->active_petugas_id);
            $namaPetugas = $petugasLain ? $petugasLain->name : 'petugas lain';
            return back()->with('error', "Loket tersebut sedang digunakan oleh {$namaPetugas}.");
        }

        $loket->update([
            'status'            => 'ACTIVE',
            'active_petugas_id' => $petugas->id,
        ]);

        return back()->with('success', "Petugas {$petugas->name} berhasil diaktifkan.");
    }
}