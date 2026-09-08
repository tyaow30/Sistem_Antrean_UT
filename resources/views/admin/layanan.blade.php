@extends('layouts.admin')

@section('title', 'Manajemen Layanan')
@section('header_title', 'Kelola Layanan Loket')

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Daftar Layanan Tersedia</h3>
        <p class="text-slate-500 mb-6">Halaman ini digunakan untuk mengatur jenis layanan yang terikat pada loket pelayanan.</p>
        
        <!-- Tabel Layanan -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase text-xs tracking-wider">
                        <th class="p-3 rounded-l-lg">No</th>
                        <th class="p-3">Nama Layanan</th>
                        <th class="p-3">Loket Terkait</th>
                        <th class="p-3">Deskripsi</th>
                        <th class="p-3 rounded-r-lg text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($layanans as $index => $layanan)
                        <tr>
                            <td class="p-3 font-medium">{{ $index + 1 }}</td>
                            <td class="p-3 font-bold text-slate-800">{{ $layanan->nama_layanan }}</td>
                            <td class="p-3">
                                <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded-md">
                                    {{ $layanan->loket->nama_loket ?? 'Loket Tidak Ditemukan' }}
                                </span>
                            </td>
                            <td class="p-3 text-slate-600">{{ $layanan->deskripsi ?? '-' }}</td>
                            <td class="p-3 text-center">
                                <form action="{{ route('admin.layanan.destroy', $layanan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus layanan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow transition">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-400">Belum ada data layanan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection