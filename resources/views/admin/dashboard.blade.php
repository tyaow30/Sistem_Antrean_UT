@extends('layouts.admin')

@section('content')
<!-- BANNER KUNING -->
<div class="bg-[#FDE047] p-6 rounded-3xl shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <span class="inline-block bg-[#0B3B82] text-white text-xs font-black px-4 py-1.5 rounded-full mb-2">PELMA (PELAYANAN MAHASISWA)</span>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">Dashboard Administrator</h1>
        <p class="text-sm font-medium text-gray-700 mt-1">Pantau operasional pelayanan mahasiswa secara real-time.</p>
    </div>
    <form action="{{ route('admin.toggle-sesi') }}" method="POST">
        @csrf
        <button type="submit" class="bg-[#22C55E] hover:bg-green-600 text-white font-extrabold px-6 py-3.5 rounded-2xl shadow-md transition text-sm tracking-wide">
            {{ ($sesiHariIni && $sesiHariIni->status == 'OPEN') ? 'TUTUP SESI HARI INI' : 'BUKA SESI HARI INI' }}
        </button>
    </form>
</div>

<!-- 4 KARTU STATISTIK (Warna Biru + Angka Kuning) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-[#0B3B82] text-white p-5 rounded-2xl shadow-md flex justify-between items-center">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-200">TOTAL PENGUNJUNG</span>
        <span class="text-4xl font-black text-[#FACC15]">{{ $totalTiket ?? 0 }}</span>
    </div>
    <div class="bg-[#0B3B82] text-white p-5 rounded-2xl shadow-md flex justify-between items-center">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-200">MENUNGGU</span>
        <span class="text-4xl font-black text-[#FACC15]">{{ $menunggu ?? 0 }}</span>
    </div>
    <div class="bg-[#0B3B82] text-white p-5 rounded-2xl shadow-md flex justify-between items-center">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-200">SELESAI DILAYANI</span>
        <span class="text-4xl font-black text-[#FACC15]">{{ $selesai ?? 0 }}</span>
    </div>
    <div class="bg-[#0B3B82] text-white p-5 rounded-2xl shadow-md flex justify-between items-center">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-200">DILEWATI / BATAL</span>
        <span class="text-4xl font-black text-[#FACC15]">{{ $dilewati ?? 0 }}</span>
    </div>
</div>

<!-- 2 CHART (Latar Biru Tua & Biru Muda) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Chart Seminggu -->
    <div class="bg-[#0B3B82] p-5 rounded-2xl shadow-md text-white">
        <h3 class="text-center font-bold text-base mb-4">Pengunjung Tiap Hari Selama Seminggu</h3>
        <div class="h-64">
            <canvas id="chartSeminggu"></canvas>
        </div>
    </div>

    <!-- Chart Loket Hari Ini -->
    <div class="bg-[#3B82F6] p-5 rounded-2xl shadow-md text-white">
        <h3 class="text-center font-bold text-base mb-4">Pengunjung Tiap Loket Hari Ini</h3>
        <div class="h-64">
            <canvas id="chartLoket"></canvas>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Simpan data ke variabel PHP dulu sebelum di-json_encode
        @php
            $defaultSemingguLabels = ['31/8/26', '1/9/26', '2/9/26', '3/9/26', '4/9/26', '5/9/26', '6/9/26'];
            $defaultSemingguData = [0,0,0,0,0,0,0];
            $defaultLoketLabels = ['LOKET 1', 'LOKET 2', 'LOKET 3', 'LOKET 4'];
            $defaultLoketData = [0,0,0,0];
        @endphp

        // Chart 1: Statistik Antrean Seminggu
        new Chart(document.getElementById('chartSeminggu'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartSemingguLabels ?? $defaultSemingguLabels) !!},
                datasets: [{
                    data: {!! json_encode($chartSemingguData ?? $defaultSemingguData) !!},
                    backgroundColor: '#3B82F6',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#FFFFFF', font: { weight: 'bold' } }, grid: { display: false } },
                    y: { ticks: { color: '#FFFFFF' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } }
                }
            }
        });

        // Chart 2: Statistik Per Loket
        new Chart(document.getElementById('chartLoket'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLoketLabels ?? $defaultLoketLabels) !!},
                datasets: [{
                    data: {!! json_encode($chartLoketData ?? $defaultLoketData) !!},
                    backgroundColor: '#0B3B82',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#FFFFFF', font: { weight: 'bold' } }, grid: { display: false } },
                    y: { ticks: { color: '#FFFFFF' }, grid: { display: false } }
                }
            }
        });
    });
</script>
@endsection