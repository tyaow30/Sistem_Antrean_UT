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

    // =========================================================
    // SESI HARIAN
    // =========================================================

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

    // =========================================================
    // CRUD GERAI
    // =========================================================

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

    // =========================================================
    // CRUD LOKET
    // =========================================================

    public function storeLoket(Request $request)
    {
        $request->validate([
            'gerai_id' => 'required|exists:gerai,id',
            'nomor_loket' => 'required|integer|min:1',
        ]);

        $exists = Loket::where('gerai_id', $request->gerai_id)
            ->where('nomor_loket', $request->nomor_loket)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Nomor loket tersebut sudah ada di gerai ini!'
                );
        }

        Loket::create([
            'gerai_id' => $request->gerai_id,
            'nomor_loket' => $request->nomor_loket,
            'status' => 'INACTIVE',
        ]);

        return back()->with(
            'success',
            'Loket baru berhasil ditambahkan!'
        );
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

        // Loket ACTIVE + mempunyai petugas
        // tidak boleh dipindahkan ke gerai lain.
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

        // Jika loket mempunyai petugas dan dipindahkan,
        // penugasan petugas harus ikut disesuaikan.
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

    // =========================================================
    // CRUD PETUGAS
    // =========================================================

    public function storePetugas(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'assigned_gerai_id' => 'required|exists:gerai,id',
            'assigned_loket_id' => 'required|exists:loket,id',
        ]);

        $gerai = Gerai::findOrFail(
            $request->assigned_gerai_id
        );

        if (!$gerai->is_active) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gerai yang dipilih sedang nonaktif.'
                );
        }

        $loket = Loket::findOrFail(
            $request->assigned_loket_id
        );

        // Pastikan loket benar-benar milik gerai
        if (
            (int) $loket->gerai_id !==
            (int) $request->assigned_gerai_id
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Loket yang dipilih bukan bagian dari gerai tersebut.'
                );
        }

        // Cek apakah loket sudah mempunyai petugas
        $petugasSudahDitugaskan = User::where(
            'assigned_loket_id',
            $request->assigned_loket_id
        )
            ->where('role', 'PETUGAS')
            ->exists();

        if ($petugasSudahDitugaskan) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Loket tersebut sudah memiliki petugas.'
                );
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'PETUGAS',
            'assigned_gerai_id' => $request->assigned_gerai_id,
            'assigned_loket_id' => $request->assigned_loket_id,
        ]);

        return back()->with(
            'success',
            'Akun Petugas berhasil dibuat dan ditugaskan!'
        );
    }

    public function updatePetugas(Request $request, $id)
    {
        $petugas = User::where('id', $id)
            ->where('role', 'PETUGAS')
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $petugas->id,
            'password' => 'nullable|string|min:6',
            'assigned_gerai_id' => 'required|exists:gerai,id',
            'assigned_loket_id' => 'required|exists:loket,id',
        ]);

        $gerai = Gerai::findOrFail(
            $request->assigned_gerai_id
        );

        if (!$gerai->is_active) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gerai yang dipilih sedang nonaktif.'
                );
        }

        $loketBaru = Loket::findOrFail(
            $request->assigned_loket_id
        );

        // Loket harus berada di gerai yang dipilih
        if (
            (int) $loketBaru->gerai_id !==
            (int) $request->assigned_gerai_id
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Loket yang dipilih bukan bagian dari gerai tersebut.'
                );
        }

        // Jika loket berubah, cek apakah sudah dimiliki petugas lain
        $loketDigunakanPetugasLain = User::where(
            'assigned_loket_id',
            $request->assigned_loket_id
        )
            ->where('role', 'PETUGAS')
            ->where('id', '!=', $petugas->id)
            ->exists();

        if ($loketDigunakanPetugasLain) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Loket tersebut sudah digunakan petugas lain.'
                );
        }

        DB::transaction(function () use (
            $request,
            $petugas,
            $loketBaru
        ) {
            $loketLama = $petugas->assignedLoket;

            // Jika loket berubah dan petugas sedang aktif
            if (
                $loketLama &&
                $loketLama->active_petugas_id == $petugas->id &&
                $loketLama->id != $loketBaru->id
            ) {
                $loketLama->update([
                    'status' => 'INACTIVE',
                    'active_petugas_id' => null,
                ]);
            }

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'assigned_gerai_id' => $request->assigned_gerai_id,
                'assigned_loket_id' => $request->assigned_loket_id,
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make(
                    $request->password
                );
            }

            $petugas->update($data);
        });

        return back()->with(
            'success',
            'Data Petugas berhasil diperbarui!'
        );
    }

    public function deletePetugas($id)
    {
        $petugas = User::where('id', $id)
            ->where('role', 'PETUGAS')
            ->firstOrFail();

        DB::transaction(function () use ($petugas) {

            // Jika sedang aktif di loket
            $loket = Loket::where(
                'active_petugas_id',
                $petugas->id
            )->first();

            if ($loket) {
                $loket->update([
                    'status' => 'INACTIVE',
                    'active_petugas_id' => null,
                ]);
            }

            // Lepaskan relasi dari antrean lama
            Antrean::where(
                'petugas_id',
                $petugas->id
            )->update([
                'petugas_id' => null,
            ]);

            $petugas->delete();
        });

        return back()->with(
            'success',
            'Akun Petugas berhasil dihapus!'
        );
    }

    // =========================================================
    // TOGGLE PETUGAS
    // =========================================================

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