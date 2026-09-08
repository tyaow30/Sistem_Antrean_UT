@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header_title', 'Monitoring Antrean')

@section('content')

@if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg shadow-sm" role="alert">
            <p class="font-bold">Berhasil!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    <!-- Banner Utama -->
    <div class="bg-brand-yellow rounded-2xl p-8 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center shadow-md relative overflow-hidden">
        <div class="z-10">
            <span class="inline-block bg-brand-darkblue text-white text-sm font-bold px-4 py-1.5 rounded-full mb-3">
                PELMA (PELAYANAN MAHASISWA)
            </span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-2">Dashboard Administrator</h2>
            <p class="text-slate-800 font-medium">Pantau operasional pelayanan mahasiswa secara real-time hari ini.</p>
        </div>
        <div class="z-10 mt-6 md:mt-0">
            <form action="{{ route('admin.toggle-sesi') }}" method="POST">
                @csrf
                
                @if(isset($sesiHariIni) && $sesiHariIni->is_open)
                    <!-- Tombol TUTUP SESI (Dengan Konfirmasi Pop-up) -->
                    <button type="submit" 
                            onclick="return confirm('Yakin akan mengakhiri sesi? Data jumlah pengunjung akan masuk ke chart dan antrean akan di-reset kembali untuk besok.')"
                            class="bg-[#D32F2F] hover:bg-red-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-transform transform hover:scale-105">
                        TUTUP SESI HARI INI
                    </button>
                @else
                    <!-- Tombol BUKA SESI (Langsung jalan tanpa Pop-up) -->
                    <button type="submit" 
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-transform transform hover:scale-105">
                        BUKA SESI HARI INI
                    </button>
                @endif
            </form>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <!-- Card Total Tiket -->
        <div class="bg-brand-darkblue rounded-2xl p-6 flex justify-between items-center shadow-lg text-white">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white/80">Total Tiket</h3>
            <span class="text-5xl font-black text-brand-yellow">{{ $totalTiket }}</span>
        </div>

        <!-- Card Menunggu -->
        <div class="bg-brand-darkblue rounded-2xl p-6 flex justify-between items-center shadow-lg text-white">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white/80">Menunggu</h3>
            <span class="text-5xl font-black text-brand-yellow">{{ $menunggu }}</span>
        </div>

        <!-- Card Selesai Dilayani -->
        <div class="bg-brand-darkblue rounded-2xl p-6 flex justify-between items-center shadow-lg text-white">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white/80">Selesai Dilayani</h3>
            <span class="text-5xl font-black text-brand-yellow">{{ $selesai }}</span>
        </div>

        <!-- Card Dilewati / Batal -->
        <div class="bg-brand-darkblue rounded-2xl p-6 flex justify-between items-center shadow-lg text-white">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white/80">Dilewati / Batal</h3>
            <span class="text-5xl font-black text-brand-yellow">{{ $dilewati }}</span>
        </div>
    </div>

    <!-- Grafik Placeholder -->
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 min-h-[300px] flex items-center justify-center">
        <p class="text-slate-400 font-medium">Ruang untuk Chart/Grafik Rata-rata Waktu Tunggu...</p>
    </div>
@endsection