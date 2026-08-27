<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Gerai - {{ $gerai->nama_gerai }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased p-4 sm:p-6 lg:p-8">

    <div class="max-w-5xl mx-auto space-y-8 pb-12">

        {{-- TOMBOL KEMBALI & HEADER --}}
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-blue-600 mb-4 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Dashboard Utama
            </a>
            <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100">
                <span class="inline-block bg-blue-50 text-blue-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-2">
                    Detail Gerai Aktif
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $gerai->nama_gerai }}
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Kelola struktur loket fisik dan tentukan petugas yang berjaga di masing-masing loket.
                </p>
            </div>
        </div>

        {{-- NOTIFIKASI --}}
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Form Tambah Loket (Kiri) --}}
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 h-fit">
                <h3 class="text-lg font-bold mb-2 text-slate-900">Tambah Loket Fisik</h3>
                <p class="text-xs text-slate-400 mb-4">Buat nomor/nama loket baru untuk gerai ini.</p>
                
                <form action="{{ route('admin.loket.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="gerai_id" value="{{ $gerai->id }}">
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Nama / Nomor Loket</label>
                        <input type="text" name="nomor_loket" placeholder="Contoh: Loket 1 / CS A" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm outline-none focus:border-blue-500 transition" required>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold text-sm shadow-md shadow-blue-600/20 transition">
                        Tambah Loket
                    </button>
                </form>
            </div>

            {{-- Daftar Loket & Ganti Petugas Langsung (Kanan) --}}
            <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold mb-4 text-slate-900">Daftar Loket & Penugasan Petugas</h3>

                <div class="space-y-4">
                    @forelse($gerai->loket as $l)
                        <div class="border border-slate-100 bg-slate-50/50 p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            
                            {{-- Info Loket --}}
                            <div class="min-w-[120px]">
                                <h4 class="font-bold text-slate-900 text-base">{{ $l->nomor_loket }}</h4>
                                <span class="text-[11px] text-slate-400 font-medium">Loket Permanen</span>
                            </div>

                            {{-- Form Ubah Petugas Langsung (Inline) --}}
                            <form action="{{ route('admin.loket.update-petugas', $l->id) }}" method="POST" class="flex items-center gap-2 w-full sm:w-auto">
                                @csrf
                                @method('PATCH')
                                
                                <select name="active_petugas_id" class="border border-slate-200 bg-white rounded-xl px-3 py-2 text-xs outline-none focus:border-blue-500 flex-1 sm:w-48">
                                    <option value="">-- Belum Ditugaskan --</option>
                                    @foreach($petugas as $p)
                                        <option value="{{ $p->id }}" {{ $l->active_petugas_id == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-3 py-2 rounded-xl text-xs font-semibold transition shrink-0">
                                    Ganti
                                </button>
                            </form>
                            
                            {{-- Tombol Hapus Loket --}}
                            <form action="{{ route('admin.loket.destroy', $l->id) }}" method="POST" onsubmit="return confirm('Hapus loket ini?');" class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition" title="Hapus Loket">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-400 text-sm">
                            Belum ada loket di gerai ini. Silakan tambah melalui form di samping.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <hr class="my-6 border-slate-200/80">

        {{-- BAGIAN 2: MANAJEMEN AKUN PETUGAS --}}
        <div>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Manajemen Akun Petugas
                </h2>
                <p class="text-sm text-slate-500 mt-1">Kelola direktori data petugas yang nantinya dapat ditugaskan ke loket.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 h-fit relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-blue-500"></div>
                    <h3 class="text-base font-bold mb-4 text-slate-800">Tambah Petugas Baru</h3>
                    
                    <form action="{{ route('admin.petugas.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="gerai_id" value="{{ $gerai->id }}">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap</label>
                            <input type="text" name="name" placeholder="Cth: Budi Santoso" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm outline-none focus:bg-white focus:border-blue-500 transition-all" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Alamat Email</label>
                            <input type="email" name="email" placeholder="budi@example.com" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm outline-none focus:bg-white focus:border-blue-500 transition-all" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Password</label>
                            <input type="password" name="password" placeholder="Minimal 6 karakter" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm outline-none focus:bg-white focus:border-blue-500 transition-all" required>
                        </div>
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white py-3 rounded-xl font-semibold text-sm transition-all shadow-md mt-2">
                            Buat Akun Petugas di Gerai Ini
                        </button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Direktori Petugas</h3>
                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full">{{ $petugas->count() }} Orang</span>
                    </div>
                    
                    <div class="divide-y divide-slate-100 flex-1">
                        @forelse($petugas as $p)
                            <div class="p-4 sm:p-5 hover:bg-slate-50 transition-colors flex items-center justify-between gap-4 group">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm shrink-0 border border-blue-100">
                                        {{ substr($p->name, 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-slate-900 text-sm truncate">{{ $p->name }}</h4>
                                        <p class="text-xs text-slate-500 truncate">{{ $p->email }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 shrink-0">
                                    <form action="{{ route('admin.petugas.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus permanen akun petugas {{ $p->name }}?');" class="sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-400 hover:text-white hover:bg-rose-500 rounded-xl transition-colors" title="Hapus Akun">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-10 text-center flex flex-col items-center justify-center">
                                <p class="text-slate-600 text-sm font-bold">Belum ada akun petugas</p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>
</html>