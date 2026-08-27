<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Gerai - {{ $gerai->nama_gerai }}</title>
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
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-slate-900 antialiased p-6 lg:p-10">

    <div class="max-w-6xl mx-auto space-y-8">

        {{-- LOGO ATAS KIRI --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-xl text-slate-700 hover:bg-slate-100 transition-colors flex items-center justify-center" title="Kembali ke Dashboard">
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
            </a>
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Universitas Terbuka" class="h-12 w-auto object-contain">
        </div>

        {{-- BANNER KUNING HEADER --}}
        <div class="bg-brand-yellow p-8 rounded-2xl shadow-sm text-slate-900">
            <h1 class="text-3xl font-extrabold tracking-tight">
                {{ $gerai->nama_gerai }}
            </h1>
            <p class="text-sm font-medium mt-1 text-slate-800">
                Kelola struktur loket fisik dan tentukan petugas yang berjaga di masing-masing loket.
            </p>
        </div>

        {{-- NOTIFIKASI --}}
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500 text-emerald-800 px-5 py-3 rounded-xl text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-brand-red/10 border border-brand-red text-brand-red px-5 py-3 rounded-xl text-sm font-bold">
                {{ session('error') }}
            </div>
        @endif

        {{-- SECTION 1: LOKET FISIK --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- Form Tambah Loket (Kiri) --}}
            <div class="lg:col-span-4 bg-brand-darkblue p-6 rounded-2xl text-white shadow-md">
                <h3 class="text-base font-extrabold tracking-wide uppercase mb-4 text-brand-yellow">
                    TAMBAH LOKET FISIK
                </h3>
                
                <form action="{{ route('admin.loket.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="gerai_id" value="{{ $gerai->id }}">
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-200 mb-2">Nama / Nomor Loket</label>
                        <input type="text" name="nomor_loket" placeholder="Contoh : Loket 1 / CS A" class="w-full bg-white text-slate-800 rounded-xl px-4 py-2.5 text-sm outline-none font-medium placeholder:text-slate-400" required>
                    </div>

                    <div class="pt-2 text-center">
                        <button type="submit" class="w-2/3 bg-brand-yellow hover:bg-yellow-300 text-brand-darkblue py-2.5 rounded-full font-black text-xs uppercase tracking-wider transition-all">
                            TAMBAH LOKET
                        </button>
                    </div>
                </form>
            </div>

            {{-- Daftar Loket (Kanan) --}}
            <div class="lg:col-span-8 bg-brand-lightblue p-6 rounded-2xl text-white shadow-md">
                <h3 class="text-base font-extrabold tracking-wide uppercase mb-4 text-brand-yellow">
                    DAFTAR LOKET & PENUGASAN PETUGAS
                </h3>

                <div class="space-y-3">
                    @forelse($loketList as $l)
                        <div class="bg-brand-darkblue px-5 py-3.5 rounded-xl flex items-center justify-between gap-4">
                            
                            {{-- Nama Loket (Kiri) --}}
                            <span class="font-bold text-white text-sm shrink-0">
                                {{ $l->nomor_loket }}
                            </span>

                            {{-- Kelompok Aksi (Dropdown + Ganti + Trash) di Kanan --}}
                            <div class="flex items-center gap-3 ml-auto">
                                <form action="{{ route('admin.loket.update-petugas', $l->id) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    
                                    <select name="active_petugas_id" class="bg-white/20 text-white rounded-full px-4 py-1.5 text-xs font-semibold outline-none border border-white/30 cursor-pointer">
                                        <option value="" class="text-slate-800">Belum Ditugaskan</option>
                                        @foreach($allPetugas as $p)
                                            <option value="{{ $p->id }}" class="text-slate-800" {{ (string)$l->active_petugas_id === (string)$p->id ? 'selected' : '' }}>
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="submit" class="bg-brand-yellow hover:bg-yellow-300 text-brand-darkblue px-4 py-1.5 rounded-full text-xs font-black transition-all shrink-0">
                                        Ganti
                                    </button>
                                </form>

                                {{-- Tombol Hapus --}}
                                <form action="{{ route('admin.loket.destroy', $l->id) }}" method="POST" onsubmit="return confirm('Hapus loket ini?');" class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-brand-red hover:text-red-400 transition-colors p-1 flex items-center" title="Hapus">
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </button>
                                </form>
                            </div>

                        </div>
                    @empty
                        <div class="text-center py-6 text-white/80 text-sm font-medium">
                            Belum ada loket di gerai ini.
                        </div>
                    @endforelse

                    {{-- PAGINATION LOKET --}}
                    <div class="flex justify-end pt-3 text-white font-semibold text-xs">
                        {{ $loketList->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>

        </div>

        {{-- GARIS PEMBATAS --}}
        <hr class="border-t-2 border-slate-900 my-8">

        {{-- SECTION 2: MANAJEMEN AKUN PETUGAS --}}
        <div>
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slate-900">
                    Manajemen Akun Petugas
                </h2>
                <p class="text-xs text-slate-600 font-medium">
                    Kelola direktori data petugas yang nantinya dapat ditugaskan ke loket.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                {{-- Form Tambah Petugas (Kiri) --}}
                <div class="lg:col-span-4 bg-brand-darkblue p-6 rounded-2xl text-white shadow-md">
                    <h3 class="text-base font-extrabold tracking-wide uppercase mb-4 text-brand-yellow">
                        TAMBAH PETUGAS BARU
                    </h3>
                    
                    <form action="{{ route('admin.petugas.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="gerai_id" value="{{ $gerai->id }}">
                        
                        <div>
                            <label class="block text-xs font-medium text-slate-200 mb-1">Nama Lengkap</label>
                            <input type="text" name="name" placeholder="Contoh : Budi Santoso" class="w-full bg-white text-slate-800 rounded-xl px-4 py-2.5 text-sm outline-none font-medium placeholder:text-slate-400" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-200 mb-1">Alamat Email</label>
                            <input type="email" name="email" placeholder="budi@example.com" class="w-full bg-white text-slate-800 rounded-xl px-4 py-2.5 text-sm outline-none font-medium placeholder:text-slate-400" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-200 mb-1">Password</label>
                            <input type="password" name="password" placeholder="Minimal 6 karakter" class="w-full bg-white text-slate-800 rounded-xl px-4 py-2.5 text-sm outline-none font-medium placeholder:text-slate-400" required>
                        </div>

                        <div class="pt-2 text-center">
                            <button type="submit" class="w-3/4 bg-brand-yellow hover:bg-yellow-300 text-brand-darkblue py-2.5 rounded-full font-black text-xs uppercase tracking-wider transition-all">
                                BUAT AKUN PETUGAS
                            </button>
                        </div>
                    </form>
                </div>

                {{-- List Petugas (Kanan) --}}
                <div class="lg:col-span-8 bg-brand-lightblue p-6 rounded-2xl text-white shadow-md">
                    <div class="flex items-center justify-between mb-4 border-b border-white/20 pb-3">
                        <h3 class="text-base font-extrabold tracking-wide uppercase text-brand-yellow">
                            DAFTAR PETUGAS
                        </h3>
                        <span class="bg-brand-darkblue text-brand-yellow text-xs font-bold px-4 py-1.5 rounded-full">
                            {{ $petugasList->total() }} Petugas
                        </span>
                    </div>

                    <div class="space-y-3">
                        @forelse($petugasList as $p)
                            <div class="bg-brand-darkblue px-5 py-3 rounded-xl flex items-center justify-between gap-4">
                                
                                {{-- Avatar + Detail --}}
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="w-9 h-9 rounded-full bg-white/20 text-white flex items-center justify-center font-bold text-base shrink-0 uppercase">
                                        {{ substr($p->name, 0, 1) }}
                                    </div>

                                    <div class="truncate">
                                        <h4 class="font-bold text-white text-sm leading-tight truncate">{{ $p->name }}</h4>
                                        <p class="text-xs text-slate-300 font-normal truncate">{{ $p->email }}</p>
                                    </div>
                                </div>

                                {{-- Tombol Hapus (Kanan) --}}
                                <form action="{{ route('admin.petugas.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus permanen akun petugas {{ $p->name }}?');" class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-brand-red hover:text-red-400 transition-colors p-1 flex items-center" title="Hapus Akun">
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </button>
                                </form>

                            </div>
                        @empty
                            <div class="text-center py-6 text-white/80 text-sm font-medium">
                                Belum ada akun petugas.
                            </div>
                        @endforelse

                        {{-- PAGINATION PETUGAS --}}
                        <div class="flex justify-end pt-3 text-white font-semibold text-xs">
                            {{ $petugasList->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>
</html>