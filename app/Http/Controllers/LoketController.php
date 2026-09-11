<?php

namespace App\Http\Controllers;

use App\Models\Loket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoketController extends Controller
{
    public function index()
    {
        $lokets = Loket::with('activePetugas')->latest()->get();
        $petugass = User::where('role', 'PETUGAS')->get();

        return view('admin.loket', compact('lokets', 'petugass'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_loket'       => 'required|string|max:255',
            'active_petugas_id' => 'nullable|exists:users,id',
        ]);

        $newPetugasId = $request->active_petugas_id;

        DB::transaction(function () use ($request, $newPetugasId) {
            $status = $newPetugasId ? 'ACTIVE' : 'INACTIVE';

            $loket = Loket::create([
                'nomor_loket'       => $request->nomor_loket,
                'nama_loket'        => $request->nomor_loket,
                'active_petugas_id' => $newPetugasId,
                'status'            => 'INACTIVE',
            ]);

            if ($newPetugasId) {
                User::where('id', $newPetugasId)->update(['assigned_loket_id' => $loket->id]);
            }
        });

        return redirect()->back()->with('success', 'Data loket berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nomor_loket'       => 'required|string|max:255',
            'active_petugas_id' => 'nullable|exists:users,id',
        ]);

        $loket        = Loket::findOrFail($id);
        $oldPetugasId = $loket->active_petugas_id;
        $newPetugasId = $request->active_petugas_id;

        DB::transaction(function () use ($request, $loket, $id, $oldPetugasId, $newPetugasId) {
            // 1. Lepas assigned_loket_id dari petugas lama jika diganti
            if ($oldPetugasId && $oldPetugasId != $newPetugasId) {
                User::where('id', $oldPetugasId)->update(['assigned_loket_id' => null]);
            }

            // 2. Lepas petugas lain yang sebelumnya memegang loket ini
            User::where('assigned_loket_id', $id)->update(['assigned_loket_id' => null]);

            // 3. Tentukan status loket
            $status = $newPetugasId ? $loket->status : 'INACTIVE';

            // 4. Update data loket
            $loket->update([
                'nomor_loket'       => $request->nomor_loket,
                'nama_loket'        => $request->nomor_loket,
                'active_petugas_id' => $newPetugasId,
                'status'            => $status,
            ]);

            // 5. Update assigned_loket_id di tabel users untuk petugas baru
            if ($newPetugasId) {
                User::where('id', $newPetugasId)->update(['assigned_loket_id' => $id]);
            }
        });

        return redirect()->back()->with('success', 'Data loket berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $loket = Loket::findOrFail($id);

        DB::transaction(function () use ($loket, $id) {
            User::where('assigned_loket_id', $id)->update(['assigned_loket_id' => null]);
            $loket->delete();
        });

        return redirect()->back()->with('success', 'Data loket berhasil dihapus!');
    }
}