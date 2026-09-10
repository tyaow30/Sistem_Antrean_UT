@extends('layouts.admin')

@section('title', 'Manajemen Petugas')

@section('content')

@if(session('success'))
    <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-4 mb-6 rounded-r-2xl font-bold shadow-sm">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-black text-brand-darkblue">Kelola Akun Petugas Loket</h3>
            <p class="text-slate-500 text-sm font-medium">Atur akun staf petugas yang menjaga tiap-tiap loket pelayanan.</p>
        </div>
        <button type="button" onclick="toggleModal('modalTambahPetugas')" class="bg-brand-darkblue hover:bg-brand-navy text-white font-extrabold px-5 py-2.5 rounded-2xl shadow-md transition flex items-center gap-2 self-start md:self-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            <span>Tambah Petugas</span>
        </button>
    </div>

    <!-- Tabel Petugas -->
    <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-brand-darkblue text-white font-extrabold text-xs uppercase tracking-wider">
                    <th class="p-4 text-center w-16">No</th>
                    <th class="p-4">Nama Petugas</th>
                    <th class="p-4">Username / Email</th>
                    <th class="p-4">Loket Tugas</th>
                    <th class="p-4 text-center w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-sm font-medium">
                @forelse($petugass ?? [] as $index => $petugas)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4 text-center font-bold text-slate-500">{{ $index + 1 }}</td>
                        <td class="p-4 font-extrabold text-slate-800">{{ $petugas->name ?? $petugas->nama }}</td>
                        <td class="p-4 text-slate-600">{{ $petugas->username ?? $petugas->email }}</td>
                        <td class="p-4">
                            <span class="bg-brand-yellow text-brand-darkblue text-xs font-black px-3 py-1 rounded-lg">
                                {{ $petugas->loket->nama_loket ?? 'Belum Diatur' }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <form action="{{ route('admin.petugas.destroy', $petugas->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus akun petugas ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded-xl text-xs font-bold shadow transition">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-400 font-bold">Belum ada data petugas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH PETUGAS -->
<div id="modalTambahPetugas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl border border-slate-100">
        <div class="bg-brand-darkblue p-5 text-white flex justify-between items-center">
            <h4 class="font-extrabold text-lg">Tambah Akun Petugas</h4>
            <button type="button" onclick="toggleModal('modalTambahPetugas')" class="text-white/80 hover:text-white font-bold text-xl">&times;</button>
        </div>
        <form action="{{ route('admin.petugas.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-darkblue focus:outline-none text-sm font-medium">
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Username / Email</label>
                <input type="text" name="username" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-darkblue focus:outline-none text-sm font-medium">
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-darkblue focus:outline-none text-sm font-medium">
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Tugaskan di Loket</label>
                <select name="loket_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-darkblue focus:outline-none text-sm font-medium bg-white">
                    <option value="">-- Pilih Loket --</option>
                    @if(isset($lokets))
                        @foreach($lokets as $loket)
                            <option value="{{ $loket->id }}">{{ $loket->nama_loket }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModal('modalTambahPetugas')" class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-extrabold">BATAL</button>
                <button type="submit" class="px-5 py-2.5 bg-brand-yellow hover:bg-amber-400 text-brand-darkblue rounded-xl text-xs font-extrabold shadow">SIMPAN</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
</script>

@endsection