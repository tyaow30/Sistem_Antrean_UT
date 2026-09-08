<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Loket;
use App\Models\Antrean;
use App\Models\SesiHari;
use App\Models\Layanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $sesiHariIni = SesiHari::where('tanggal', $today)->first();

        // Statistik antrean berdasarkan tanggal hari ini
        if ($sesiHariIni && $sesiHariIni->is_open) {
            $totalTiket = Antrean::where('tanggal', $today)->count();
            $menunggu   = Antrean::where('tanggal', $today)->where('status', 'WAITING')->count();
            $selesai    = Antrean::where('tanggal', $today)->where('status', 'DONE')->count();
            $dilewati   = Antrean::where('tanggal', $today)->whereIn('status', ['SKIPPED', 'PENDING'])->count();
        } else {
            $totalTiket = 0;
            $menunggu   = 0;
            $selesai    = 0;
            $dilewati   = 0;
        }

        $lokets = Loket::with(['activePetugas', 'layanans'])->get();
        $petugasList = User::where('role', 'PETUGAS')->with('assignedLoket')->get();
        $layanans = Layanan::with('loket')->get();

        return view('admin.dashboard', compact(
            'sesiHariIni', 
            'totalTiket',
            'menunggu',
            'selesai',
            'dilewati',
            'lokets',
            'petugasList',
            'layanans' 
        ));
    }

    // TOGGLE SESI HARIAN
    public function toggleSesi()
    {
        $today = now()->toDateString();
        $msg = '';

        DB::transaction(function () use ($today, &$msg) {
            $sesiHariIni = SesiHari::where('tanggal', $today)
                ->lockForUpdate()
                ->first();

            // Jika sesi hari ini sudah ada dan sedang terbuka -> Tutup sesi & nonaktifkan loket
            if ($sesiHariIni && $sesiHariIni->is_open) {
                $sesiHariIni->update(['is_open' => false]);

                Loket::where('status', 'ACTIVE')->update(['status' => 'INACTIVE']);

                Antrean::where('tanggal', $today)->delete();

                $msg = 'Sesi harian berhasil ditutup dan semua loket dinonaktifkan!';
                return;
            }

            // Jika belum ada sesi hari ini -> Buat baru & buka
            if (!$sesiHariIni) {
                SesiHari::create([
                    'tanggal' => $today,
                    'is_open' => true,
                ]);

                $msg = 'Sesi harian berhasil dibuka!';
                return;
            }

            // Jika sesi hari ini ada tapi tertutup -> Buka kembali
            $sesiHariIni->update(['is_open' => true]);
            $msg = 'Sesi harian berhasil dibuka!';
        });

        return back()->with('success', $msg);
    }

    // CRUD PETUGAS
    public function storePetugas(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'PETUGAS',
        ]);

        return redirect()->back()->with('success', 'Akun petugas baru berhasil ditambahkan!');
    }

    public function destroyPetugas($id)
    {
        $user = User::where('role', 'PETUGAS')->findOrFail($id);

        // Lepas relasi loket jika petugas ini sedang memegangnya
        Loket::where('active_petugas_id', $user->id)->update([
            'status'            => 'INACTIVE',
            'active_petugas_id' => null
        ]);
        
        $user->delete();

        return redirect()->back()->with('success', 'Akun petugas berhasil dihapus.');
    }

    // PENUGASAN PETUGAS KE LOKET TETAP
    public function updatePetugasLoket(Request $request, $loketId)
    {
        $request->validate([
            'active_petugas_id' => 'nullable|exists:users,id',
        ]);

        $loket = Loket::findOrFail($loketId);
        $newPetugasId = $request->active_petugas_id;
        $oldPetugasId = $loket->active_petugas_id;

        if ($newPetugasId == $oldPetugasId) {
            return redirect()->back();
        }

        // Validasi apakah petugas sudah dipakai di loket lain
        if ($newPetugasId) {
            $sudahDipakai = Loket::where('active_petugas_id', $newPetugasId)
                ->where('id', '!=', $loketId)
                ->exists();

            if ($sudahDipakai) {
                return redirect()->back()->with('error', 'Petugas tersebut sudah ditugaskan di loket lain!');
            }
        }

        DB::transaction(function () use ($loket, $newPetugasId, $oldPetugasId) {
            // Kosongkan penugasan loket lama milik petugas jika ada
            if ($oldPetugasId) {
                User::where('id', $oldPetugasId)->update(['assigned_loket_id' => null]);
            }

            // Update loket
            $loket->update([
                'active_petugas_id' => $newPetugasId,
                'status'            => $newPetugasId ? $loket->status : 'INACTIVE',
            ]);

            // Set assigned_loket_id pada petugas baru
            if ($newPetugasId) {
                User::where('id', $newPetugasId)->update(['assigned_loket_id' => $loket->id]);
            }
        });

        return redirect()->back()->with('success', 'Penugasan petugas pada loket berhasil diperbarui!');
    }

    // Menyimpan Layanan Baru
    public function storeLayanan(Request $request)
    {
        $request->validate([
            'nama_layanan' => 'required|string|max:255',
            'loket_id' => 'required|exists:loket,id',
            'deskripsi' => 'nullable|string',
        ]);

        Layanan::create($request->all());

        return back()->with('success', 'Layanan berhasil ditambahkan!');
    }

    // Menghapus Layanan
    public function destroyLayanan($id)
    {
        $layanan = Layanan::findOrFail($id);
        $layanan->delete();

        return back()->with('success', 'Layanan berhasil dihapus!');
    }

    // HALAMAN MANAJEMEN LAYANAN
    public function indexLayanan()
    {
        $layanans = Layanan::with('loket')->get();
        $lokets = Loket::all();
        return view('admin.layanan', compact('layanans', 'lokets'));
    }

    // HALAMAN REKAP LAPORAN
    public function indexRekap()
    {
        $antreans = Antrean::with('layanan', 'loket')->latest()->get();
        return view('admin.rekap', compact('antreans'));
    }
}