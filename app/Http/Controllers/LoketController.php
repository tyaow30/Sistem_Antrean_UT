<?php

namespace App\Http\Controllers;

use App\Models\Loket;
use App\Models\User; // Atau model Petugas kamu
use Illuminate\Http\Request;

class LoketController extends Controller
{
    public function index()
    {
        $lokets = Loket::with('activePetugas')->latest()->get();
        
        // Ambil daftar user/petugas untuk diisi ke dropdown
        $petugass = User::where('role', 'PETUGAS')->get(); // Sesuaikan kondisi role jika ada

        return view('admin.loket', compact('lokets', 'petugass'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_loket' => 'required|string|max:255',
        ]);

        Loket::create([
            'nomor_loket'       => $request->nomor_loket,
            'nama_loket'        => $request->nomor_loket,
            'active_petugas_id' => $request->active_petugas_id ?: null,
            'status'            => '0', // Default tetep NON-AKTIF (0/INACTIVE) sampai petugas login
        ]);

        return redirect()->back()->with('success', 'Data loket berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nomor_loket' => 'required|string|max:255',
        ]);

        $loket = Loket::findOrFail($id);
        
        // Tetap jaga status lama, atau tetapkan default '0' jika petugas baru di-assign
        $status = $loket->status;
        if (!$request->active_petugas_id) {
            $status = '0'; // Kalau petugas dilepas, otomatis NON-AKTIF
        }

        $loket->update([
            'nomor_loket'       => $request->nomor_loket,
            'nama_loket'        => $request->nomor_loket,
            'active_petugas_id' => $request->active_petugas_id ?: null,
            'status'            => $status,
        ]);

        return redirect()->back()->with('success', 'Data loket berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $loket = Loket::findOrFail($id);
        $loket->delete();

        return redirect()->back()->with('success', 'Data loket berhasil dihapus!');
    }
}