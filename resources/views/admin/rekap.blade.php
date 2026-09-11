@extends('layouts.admin')

@section('content')
<!-- BANNER KUNING -->
<div class="bg-[#FDE047] p-6 rounded-3xl shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border border-yellow-300">
    <div>
        <span class="inline-block bg-[#0B3B82] text-white text-xs font-black px-4 py-1.5 rounded-full mb-2">PELMA (PELAYANAN MAHASISWA)</span>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">Riwayat & Rekap Antrean</h1>
        <p class="text-sm font-medium text-gray-700 mt-1">Data histori seluruh mahasiswa yang telah mengambil tiket antrean.</p>
    </div>
    
    <!-- TOMBOL EXPORT EXCEL -->
    <a href="{{ route('admin.rekap.export', request()->all()) }}" class="bg-[#22C55E] hover:bg-green-600 text-white font-extrabold text-sm px-6 py-3 rounded-2xl shadow-md transition flex items-center justify-center gap-2 flex-shrink-0">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export Excel</span>
    </a>
</div>

<!-- PANEL FILTER & ACTION -->
<div class="bg-[#3B82F6] p-6 rounded-2xl shadow-md mb-6">
    <form action="{{ route('admin.rekap.index') }}" method="GET" class="flex flex-col lg:flex-row lg:items-end gap-3 justify-between">
        
        <!-- GROUP INPUT FILTER -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
            <!-- Search Nama -->
            <div>
                <label class="block text-white text-xs font-bold mb-1">CARI NAMA</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama..." onchange="this.form.submit()" class="w-full bg-white border-0 rounded-xl px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-yellow-400">
            </div>

            <!-- Filter Loket -->
            <div>
                <label class="block text-white text-xs font-bold mb-1">LOKET</label>
                <select name="loket_id" onchange="this.form.submit()" class="w-full bg-white border-0 rounded-xl px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-yellow-400">
                    <option value="">Semua Loket</option>
                    @foreach($lokets as $l)
                        <option value="{{ $l->id }}" {{ request('loket_id') == $l->id ? 'selected' : '' }}>{{ $l->nama_loket }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Layanan -->
            <div>
                <label class="block text-white text-xs font-bold mb-1">LAYANAN</label>
                <select name="layanan_id" onchange="this.form.submit()" class="w-full bg-white border-0 rounded-xl px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-yellow-400">
                    <option value="">Semua Layanan</option>
                    @foreach($layanans as $lay)
                        <option value="{{ $lay->id }}" {{ request('layanan_id') == $lay->id ? 'selected' : '' }}>{{ $lay->nama_layanan }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Range Tanggal -->
            <div>
                <label class="block text-white text-xs font-bold mb-1">RENTANG TANGGAL</label>
                <div class="flex items-center space-x-1">
                    <input type="date" name="start_date" value="{{ request('start_date', date('Y-m-d')) }}" onchange="this.form.submit()" class="w-full bg-white border-0 rounded-xl px-2 py-2 text-xs text-gray-800">
                    <span class="text-white text-xs font-bold">s/d</span>
                    <input type="date" name="end_date" value="{{ request('end_date', date('Y-m-d')) }}" onchange="this.form.submit()" class="w-full bg-white border-0 rounded-xl px-2 py-2 text-xs text-gray-800">
                </div>
            </div>
        </div>
    </form>

    <!-- TABEL REKAP -->
    <div class="mt-6 overflow-x-auto rounded-xl">
        <table class="w-full text-left text-sm text-white">
            <thead class="bg-[#FDE047] text-gray-900 font-extrabold uppercase text-xs">
                <tr>
                    <th class="p-3">No Tiket</th>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Nama Mahasiswa</th>
                    <th class="p-3">Layanan</th>
                    <th class="p-3">Loket</th>
                    <th class="p-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-blue-400/30 bg-[#3B82F6]">
                @forelse($antreans as $antrean)
                <tr class="hover:bg-blue-600/50 transition">
                    <td class="p-3 font-extrabold text-yellow-300">{{ $antrean->nomor_antrean }}</td>
                    <td class="p-3 text-xs text-blue-100 font-medium">{{ \Carbon\Carbon::parse($antrean->tanggal)->format('d/m/Y') }}</td>
                    
                    <!-- PERBAIKAN: Menggunakan $antrean->nama (sesuai kolom database) -->
                    <td class="p-3 font-bold">{{ $antrean->nama }}</td>
                    
                    <!-- PERBAIKAN: Menggunakan relasi serviceAwal / serviceAktual -->
                    <td class="p-3 font-medium">
                        {{ $antrean->serviceAwal->nama_layanan ?? ($antrean->serviceAktual->nama_layanan ?? '-') }}
                    </td>
                    
                    <!-- PERBAIKAN: Menggunakan relasi loketPelayanan -->
                    <td class="p-3 font-medium">{{ $antrean->loketPelayanan->nama_loket ?? '-' }}</td>
                    
                    <td class="p-3">
                        <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-yellow-400 text-gray-900">
                            {{ $antrean->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-blue-200">Tidak ada data antrean yang ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection