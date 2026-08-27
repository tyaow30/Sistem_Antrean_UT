<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            yellow: '#FFDC5F',
                            darkblue: '#0A4595',
                            lightblue: '#4A7ED4',
                            red: '#D12727',
                            green: '#48BB78',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-slate-900 antialiased p-6 lg:p-10">

    <div class="max-w-6xl mx-auto space-y-6">

        {{-- TOP HEADER LOGO & LOG OUT --}}
        <div class="flex items-center justify-between">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Universitas Terbuka" class="h-12 w-auto object-contain">
            
            {{-- Tombol Logout (Mengacu ke route auth.php bawaan) --}}
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="bg-brand-red hover:bg-red-700 text-white font-extrabold text-sm px-6 py-2.5 rounded-2xl shadow-md transition-all uppercase tracking-wider">
                    LOG OUT
                </button>
            </form>
        </div>

        {{-- BANNER KUNING HEADER --}}
        <div class="bg-brand-yellow p-8 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                    Dashboard Administrator
                </h1>
                <p class="text-sm font-medium mt-1 text-slate-800">
                    Kelola gerai, loket, dan rekapitulasi antrean.
                </p>
            </div>

            {{-- Tombol Buka / Tutup Sesi --}}
            <form action="{{ route('admin.toggle-sesi') }}" method="POST">
                @csrf
                @if(isset($sesi) && $sesi->is_open)
                    <button type="submit" class="bg-brand-red hover:bg-red-700 text-white font-extrabold text-sm px-8 py-3.5 rounded-2xl shadow-md transition-all uppercase tracking-wider">
                        TUTUP SESI HARI INI
                    </button>
                @else
                    <button type="submit" class="bg-brand-green hover:bg-emerald-600 text-white font-extrabold text-sm px-8 py-3.5 rounded-2xl shadow-md transition-all uppercase tracking-wider">
                        BUKA SESI HARI INI
                    </button>
                @endif
            </form>
        </div>

        {{-- NOTIFIKASI --}}
        @if(session('success'))
            <div class="bg-emerald-200 border border-emerald-400 text-emerald-900 px-5 py-3 rounded-2xl text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-200 border border-red-400 text-red-900 px-5 py-3 rounded-2xl text-sm font-bold">
                {{ session('error') }}
            </div>
        @endif

        {{-- STATISTIK --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-brand-darkblue p-5 rounded-2xl text-white shadow-md flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider">TOTAL TIKET</span>
                <span class="text-4xl font-extrabold text-brand-yellow">{{ $totalTiket ?? 0 }}</span>
            </div>
            <div class="bg-brand-darkblue p-5 rounded-2xl text-white shadow-md flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider">MENUNGGU</span>
                <span class="text-4xl font-extrabold text-brand-yellow">{{ $menunggu ?? 0 }}</span>
            </div>
            <div class="bg-brand-darkblue p-5 rounded-2xl text-white shadow-md flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider">SELESAI DILAYANI</span>
                <span class="text-4xl font-extrabold text-brand-yellow">{{ $selesai ?? 0 }}</span>
            </div>
            <div class="bg-brand-darkblue p-5 rounded-2xl text-white shadow-md flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider">DILEWATI / BATAL</span>
                <span class="text-4xl font-extrabold text-brand-yellow">{{ $dilewati ?? 0 }}</span>
            </div>
        </div>

        {{-- SECTION KELOLA GERAI --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {{-- Form Tambah Gerai (Kiri) --}}
            <div class="lg:col-span-4 bg-brand-darkblue p-6 rounded-2xl text-white shadow-md">
                <h3 class="text-base font-extrabold tracking-wide uppercase mb-4 text-brand-yellow">
                    TAMBAH GERAI
                </h3>
                
                <form action="{{ route('admin.gerai.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-200 mb-2">Nama Gerai</label>
                        <input type="text" name="nama_gerai" placeholder="Contoh : Gerai Layanan Utama" class="w-full bg-white text-slate-800 rounded-xl px-4 py-2.5 text-sm outline-none font-medium placeholder:text-slate-400" required>
                    </div>

                    <div class="pt-2 text-center">
                        <button type="submit" class="w-2/3 bg-brand-yellow hover:bg-yellow-300 text-brand-darkblue py-2.5 rounded-full font-black text-xs uppercase tracking-wider transition-all">
                            SIMPAN GERAI
                        </button>
                    </div>
                </form>
            </div>

            {{-- Daftar Gerai Aktif (Kanan) --}}
            <div class="lg:col-span-8 bg-brand-lightblue p-6 rounded-2xl text-white shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-extrabold tracking-wide uppercase text-brand-yellow">
                            DAFTAR GERAI AKTIF
                        </h3>
                        <p class="text-xs text-white/90 font-medium">
                            Klik pada gerai untuk mengelola loket dan petugas di dalamnya.
                        </p>
                    </div>
                    <span class="bg-brand-darkblue text-brand-yellow text-xs font-bold px-4 py-1.5 rounded-full">
                        {{ $geraiList->count() }} Gerai
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @forelse($geraiList as $g)
                        <div class="bg-brand-darkblue p-4 rounded-xl shadow-inner flex flex-col justify-between">
                            <div class="flex items-center justify-between border-b border-white/20 pb-2 mb-3">
                                <h4 class="font-bold text-white text-sm truncate pr-2">
                                    {{ $g->nama_gerai }}
                                </h4>
                                <span class="bg-white/20 text-white text-[10px] font-semibold px-2.5 py-0.5 rounded-full shrink-0">
                                    {{ $g->loket->count() }} Loket
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-2 pt-1">
                                <a href="{{ route('admin.gerai.detail', $g->id) }}" class="flex-1 bg-brand-yellow hover:bg-yellow-300 text-brand-darkblue text-center py-1.5 px-3 rounded-full text-xs font-black transition-all">
                                    Kelola Loket & Petugas &rarr;
                                </a>

                                <form action="{{ route('admin.gerai.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Hapus gerai {{ $g->nama_gerai }}?');" class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-brand-red hover:text-red-400 p-1 transition-colors" title="Hapus Gerai">
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 text-center py-8 text-white/80 text-sm font-medium">
                            Belum ada gerai yang ditambahkan.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</body>
</html>