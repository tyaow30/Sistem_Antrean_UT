<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap & Laporan - Sistem Antrean</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8">

        <!-- HEADER & NAVBAR -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logosby.png') }}" alt="Logo UT" class="h-12 md:h-14">
            </div>

            <div class="flex items-center gap-2 md:gap-4 bg-white p-2 rounded-full shadow-sm">
                <a href="{{ route('petugas.dashboard') }}" class="text-[#003B70] font-bold py-2 px-6 rounded-full transition hover:bg-gray-100">
                    Dashboard Loket
                </a>
                <a href="{{ route('petugas.rekap') }}" class="bg-[#FFCC00] text-[#003B70] font-bold py-2 px-6 rounded-full transition hover:bg-yellow-500">
                    Rekap & Laporan
                </a>
                
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="bg-[#D32F2F] text-white font-bold py-2 px-6 rounded-full transition hover:bg-red-700">
                        LOG OUT
                    </button>
                </form>
            </div>
        </div>

        <!-- KOTAK INFORMASI -->
        <div class="bg-[#FFCC00] rounded-2xl p-6 mb-6 shadow-md border-b-[6px] border-yellow-600">
            <h2 class="text-xl md:text-2xl font-bold text-[#003B70]">Petugas : {{ Auth::user()->name ?? 'Petugas Loket' }}</h2>
            <p class="text-[#003B70] text-lg mt-1">Gerai Utama Senay | <span class="font-extrabold uppercase">{{ $loket->nama_loket ?? 'LOKET 1' }}</span></p>
        </div>

        <!-- STATISTIK RINGKAS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-[#004A8D] text-white p-5 rounded-2xl shadow-lg flex flex-col justify-between">
                <p class="text-sm font-semibold text-gray-200">Total Ditangani</p>
                <h3 class="text-4xl font-extrabold mt-2">{{ $totalDiproses }}</h3>
            </div>
            <div class="bg-[#388E3C] text-white p-5 rounded-2xl shadow-lg flex flex-col justify-between">
                <p class="text-sm font-semibold text-gray-200">Selesai (DONE)</p>
                <h3 class="text-4xl font-extrabold mt-2">{{ $totalSelesai }}</h3>
            </div>
            <div class="bg-[#D32F2F] text-white p-5 rounded-2xl shadow-lg flex flex-col justify-between">
                <p class="text-sm font-semibold text-gray-200">Dilewati (SKIPPED)</p>
                <h3 class="text-4xl font-extrabold mt-2">{{ $totalDilewati }}</h3>
            </div>
        </div>

        <!-- KOTAK UTAMA (FILTER, PENCARIAN, & TABEL) -->
        <div class="bg-[#004A8D] rounded-2xl p-6 shadow-xl text-white">
            
            <!-- BARIS FILTER & PENCARIAN -->
            <form method="GET" action="{{ route('petugas.rekap') }}" class="flex flex-col lg:flex-row items-center justify-between gap-4 mb-6 bg-white/10 p-4 rounded-xl">
                
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <!-- Input Cari Nama / NIM -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" name="keyword" value="{{ $keyword ?? '' }}" placeholder="Cari nama atau NIM..." class="w-full pl-10 pr-4 py-2 bg-white text-gray-800 text-sm rounded-lg focus:outline-none">
                    </div>

                    <!-- Filter Tanggal Mulai & Selesai -->
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="py-2 px-3 bg-white text-gray-800 text-sm rounded-lg focus:outline-none">
                        <span class="text-sm font-semibold">s/d</span>
                        <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}" class="py-2 px-3 bg-white text-gray-800 text-sm rounded-lg focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full lg:w-auto justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2 rounded-lg text-sm transition">
                        Filter Data
                    </button>
                    <!-- Tombol Export Excel -->
                    <button type="submit" name="export" value="excel" class="bg-[#388E3C] hover:bg-green-700 text-white font-bold px-4 py-2 rounded-lg text-sm flex items-center gap-2 transition">
                        <i class="fa-solid fa-download"></i> Export Data
                    </button>
                </div>

            </form>

            <!-- TABEL DATA -->
            <div class="bg-white rounded-xl overflow-hidden text-gray-800 shadow-md">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#003B70] text-white text-sm">
                                <th class="p-3.5">No</th>
                                <th class="p-3.5">Tanggal</th>
                                <th class="p-3.5">Nama Mahasiswa</th>
                                <th class="p-3.5">NIM</th>
                                <th class="p-3.5">No. Antrean</th>
                                <th class="p-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @forelse($riwayatAntrean as $index => $riwayat)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-3.5 font-medium">{{ $index + 1 }}</td>
                                    <td class="p-3.5 text-gray-600">{{ $riwayat->tanggal }}</td>
                                    <td class="p-3.5 font-bold text-gray-800">{{ $riwayat->nama ?? '-' }}</td>
                                    <td class="p-3.5 text-gray-600">{{ $riwayat->nim ?? '-' }}</td>
                                    <td class="p-3.5 font-bold text-[#004A8D]">L{{ $loket->id }} - {{ str_pad($riwayat->nomor_antrean, 3, '0', STR_PAD_LEFT) }}</td>
                                    <td class="p-3.5 text-center">
                                        @if($riwayat->status === 'DONE')
                                            <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full">SELESAI</span>
                                        @elseif($riwayat->status === 'SKIPPED')
                                            <span class="bg-red-100 text-red-800 text-xs font-bold px-3 py-1 rounded-full">DILEWATI</span>
                                        @else
                                            <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1 rounded-full">{{ $riwayat->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-400">Tidak ada data rekap antrean pada rentang tanggal atau pencarian tersebut.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="text-center text-gray-400 text-sm mt-8">
            &copy; 2026 Universitas Terbuka. All rights reserved.
        </div>

    </div>

</body>
</html>