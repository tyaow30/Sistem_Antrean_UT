<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrator</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased p-4 sm:p-6 lg:p-8">

    <div class="max-w-7xl mx-auto space-y-8">

        {{-- HEADER --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100 gap-4">
            <div>
                <span class="inline-block bg-blue-50 text-blue-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-2">
                    Admin Control Panel
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Dashboard Administrator
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Kelola gerai, loket, dan rekapitulasi antrean secara terstruktur.
                </p>
            </div>

            {{-- Tombol Buka / Tutup Sesi --}}
            <form action="{{ route('admin.toggle-sesi') }}" method="POST" class="w-full sm:w-auto">
                @csrf
                @if(isset($sesi) && $sesi->is_open)
                    <button type="submit" class="w-full sm:w-auto bg-rose-600 hover:bg-rose-700 text-white font-semibold px-6 py-3.5 rounded-2xl shadow-lg shadow-rose-600/20 transition-all duration-200 flex items-center justify-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span>
                        TUTUP SESI HARI INI
                    </button>
                @else
                    <button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-3.5 rounded-2xl shadow-lg shadow-emerald-600/20 transition-all duration-200 flex items-center justify-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-white"></span>
                        BUKA SESI HARI INI
                    </button>
                @endif
            </form>
        </div>

        {{-- NOTIFICATION --}}
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- STATISTIK --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-500"></div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Total Tiket</p>
                <p class="text-3xl font-extrabold text-slate-800 mt-2">{{ $totalTiket ?? 0 }}</p>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500"></div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Menunggu</p>
                <p class="text-3xl font-extrabold text-amber-600 mt-2">{{ $menunggu ?? 0 }}</p>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Selesai Dilayani</p>
                <p class="text-3xl font-extrabold text-emerald-600 mt-2">{{ $selesai ?? 0 }}</p>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-rose-500"></div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Dilewati / Batal</p>
                <p class="text-3xl font-extrabold text-rose-600 mt-2">{{ $dilewati ?? 0 }}</p>
            </div>
        </div>

        {{-- SECTION KELOLA GERAI (TAMBAH & LIST GRID) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Form Tambah Gerai (Kiri) --}}
            <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100 h-fit">
                <h3 class="text-lg font-bold mb-4 text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Tambah Gerai Baru
                </h3>
                <form action="{{ route('admin.gerai.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Gerai</label>
                        <input
                            type="text"
                            name="nama_gerai"
                            placeholder="Contoh: Gerai Layanan Utama"
                            class="w-full border border-slate-200 bg-slate-50/50 rounded-2xl px-4 py-3 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-sm transition"
                            required
                        >
                    </div>
                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-semibold text-sm transition shadow-md shadow-blue-600/20"
                    >
                        Simpan Gerai
                    </button>
                </form>
            </div>

            {{-- Daftar Gerai dalam Bentuk Grid Card (Kanan - Menghemat Tempat) --}}
            <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Daftar Gerai Aktif</h3>
                        <p class="text-sm text-slate-500">Klik pada gerai untuk mengelola loket dan petugas di dalamnya.</p>
                    </div>
                    <span class="text-xs font-bold bg-blue-50 text-blue-600 px-3 py-1 rounded-full">
                        {{ $geraiList->count() }} Gerai
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @forelse($geraiList as $g)
                        <div class="border border-slate-100 bg-slate-50/60 hover:bg-white hover:border-blue-200 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $g->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                        {{ $g->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium">
                                        {{ $g->loket->count() }} Loket
                                    </span>
                                </div>
                                <h4 class="font-bold text-slate-800 text-base group-hover:text-blue-600 transition">
                                    {{ $g->nama_gerai }}
                                </h4>
                            </div>

                            <div class="mt-5 pt-4 border-t border-slate-200/60 flex items-center justify-between gap-2">
                                {{-- Tombol Masuk ke Halaman Detail Gerai (Route showGeraiDetail kamu) --}}
                                <a href="{{ route('admin.gerai.detail', $g->id) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-3 rounded-xl text-xs font-semibold transition">
                                    Kelola Loket & Petugas &rarr;
                                </a>

                                {{-- Aksi Cepat Edit Nama / Hapus Gerai --}}
                                <form action="{{ route('admin.gerai.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Hapus gerai {{ $g->nama_gerai }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition" title="Hapus Gerai">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 text-center py-8 text-slate-400 text-sm">
                            Belum ada gerai yang ditambahkan.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</body>
</html>