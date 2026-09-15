@extends('layouts.admin')

@section('content')
<style>
    /* Mencegah latar belakang input berubah putih akibat autofill browser */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
        -webkit-text-fill-color: #FFFFFF !important;
        -webkit-box-shadow: 0 0 0px 1000px #0A4191 inset !important;
        transition: background-color 5000s ease-in-out 0s;
    }
</style>

<div x-data="{ 
    isOpen: false,
    isEdit: false, 
    formUrl: '{{ route('admin.layanan.store') }}',
    formData: { id: '', nama_layanan: '', loket_id: '' },

    openAdd() {
        this.resetForm();
        this.isOpen = true;
    },

    resetForm() {
        this.isEdit = false;
        this.formUrl = '{{ route('admin.layanan.store') }}';
        this.formData = { id: '', nama_layanan: '', loket_id: '' };
    },

    openEdit(item) {
        this.isEdit = true;
        this.isOpen = true;
        this.formUrl = '{{ url('/admin/layanan') }}/' + item.id;
        
        // Diperbaiki agar membaca id loket dari relasi many-to-many (lokets)
        let selectedLoketId = '';
        if (item.lokets && item.lokets.length > 0) {
            selectedLoketId = item.lokets[0].id;
        } else if (item.loket_id) {
            selectedLoketId = item.loket_id;
        }

        this.formData = { 
            id: item.id, 
            nama_layanan: item.nama_layanan,
            loket_id: selectedLoketId
        };
    }
}">

    <!-- BANNER UTAMA -->
    <div class="bg-[#FDE047] p-6 rounded-3xl shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border border-yellow-300">
        <div>
            <span class="inline-block bg-[#0B3B82] text-white text-xs font-black px-4 py-1.5 rounded-full mb-2">PELMA (PELAYANAN MAHASISWA)</span>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Kelola Layanan Loket</h1>
            <p class="text-sm font-medium text-gray-700 mt-1">Atur jenis layanan dan keterikatannya dengan loket pelayanan secara terpusat.</p>
        </div>
        <!-- TOMBOL PICU MODAL TAMBAH -->
        <button @click="openAdd()" class="bg-[#0A4191] hover:bg-blue-900 text-white font-extrabold text-sm px-6 py-3 rounded-2xl shadow-md transition flex items-center justify-center gap-2 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Layanan</span>
        </button>
    </div>

    <!-- ALERT SUCCESS -->
    @if(session('success'))
        <div class="bg-emerald-500 text-white p-4 rounded-2xl font-bold mb-6 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 text-lg leading-none">&times;</button>
        </div>
    @endif

    <!-- PANEL UTAMA & TABEL -->
    <div class="bg-[#4D82E2] p-6 rounded-3xl shadow-md mb-6 border border-blue-400/30">
        
        <!-- FILTER BAR (AUTO SUBMIT ON TYPE) -->
        <form id="autoSearchForm" method="GET" action="{{ route('admin.layanan.index') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-5">
            <!-- SEARCH -->
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    type="text" 
                    name="search" 
                    id="autoSearchInput"
                    value="{{ request('search') }}" 
                    placeholder="Cari Layanan..." 
                    autocomplete="off"
                    class="w-full bg-[#D1D5DB] placeholder-gray-600 text-gray-900 text-sm font-medium rounded-xl pl-9 pr-4 py-2 border-0 focus:ring-2 focus:ring-blue-900"
                >
            </div>

            <!-- FILTER LOKET -->
            <div class="w-full sm:w-48 flex gap-2">
                <select 
                    name="loket" 
                    onchange="this.form.submit()" 
                    class="w-full bg-[#0B3B82] text-white text-sm font-bold rounded-xl px-4 py-2 border-0 focus:ring-2 focus:ring-yellow-400 cursor-pointer"
                >
                    <option value="">Semua Loket</option>
                    @foreach($lokets as $l)
                        <option value="{{ $l->nama_loket }}" {{ request('loket') == $l->nama_loket ? 'selected' : '' }}>
                            {{ $l->nama_loket }}
                        </option>
                    @endforeach
                </select>

                @if(request('search') || request('loket'))
                    <a href="{{ route('admin.layanan.index') }}" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-xl text-xs font-bold flex items-center justify-center transition" title="Reset Filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- TABEL -->
        <div class="overflow-x-auto rounded-xl border border-white/20">
            <table class="w-full text-left text-sm text-white border-collapse">
                <thead class="bg-[#FDE047] text-gray-900 font-extrabold text-sm">
                    <tr>
                        <th class="p-3.5 pl-5 w-14">No</th>
                        <th class="p-3.5">Nama Layanan</th>
                        <th class="p-3.5">Loket Terkait</th>
                        <th class="p-3.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-300/30 bg-blue-600/10">
                    @forelse($layanan as $index => $item)
                        <tr class="hover:bg-blue-600/40 transition">
                            <td class="p-3.5 pl-5 font-extrabold text-white">{{ $layanan->firstItem() + $index }}</td>
                            <td class="p-3.5 font-bold text-white capitalize">{{ $item->nama_layanan }}</td>
                            <td class="p-3.5 font-bold text-white">
                                <!-- DIPERBAIKI: Menggunakan perulangan karena relasi belongsToMany (bisa lebih dari 1 loket) -->
                                @if($item->lokets && $item->lokets->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($item->lokets as $loket)
                                            <span class="bg-[#0B3B82] text-white px-3 py-1 rounded-full text-xs font-bold inline-block shadow-xs">
                                                {{ $loket->nama_loket }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-blue-200 text-xs italic">Belum dihubungkan</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                <div class="flex items-center justify-center space-x-2">
                                    <button type="button" @click="openEdit({{ json_encode($item) }})" class="bg-[#FDE047] hover:bg-yellow-400 text-gray-900 font-bold p-2 rounded-lg transition shadow-sm" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    
                                    <form action="{{ route('admin.layanan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus layanan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-[#EF4444] hover:bg-red-600 text-white font-bold p-2 rounded-lg transition shadow-sm" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-blue-100 font-medium">Data layanan tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- PAGINATION LINKS -->
        <div class="mt-4 flex items-center justify-between text-white text-xs font-semibold">
            <div>
                Menampilkan {{ $layanan->firstItem() ?? 0 }} - {{ $layanan->lastItem() ?? 0 }} dari {{ $layanan->total() }} data
            </div>
            <div>
                {{ $layanan->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    <!-- MODAL POP-UP FORM -->
    <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            
            <!-- OVERLAY BACKDROP -->
            <div x-show="isOpen" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/60 transition-opacity" 
                 @click="isOpen = false"></div>

            <!-- MODAL CONTENT BOX -->
            <div x-show="isOpen" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-lg bg-[#FFDE59] rounded-[2.5rem] p-8 text-left shadow-2xl border border-amber-300 transform transition-all z-10">
                
                <!-- TOMBOL CLOSE (X) -->
                <button type="button" @click="isOpen = false" class="absolute top-6 right-6 text-[#0A4191] hover:text-blue-900 transition focus:outline-none">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- HEADER TITLE -->
                <h3 class="text-3xl font-black text-[#0A4191] text-center mb-8 tracking-wide" x-text="isEdit ? 'Edit Layanan' : 'Tambah Layanan Baru'"></h3>

                <form :action="formUrl" method="POST">
                    @csrf
                    <template x-if="isEdit">
                        @method('PUT')
                    </template>

                    <div class="space-y-6">
                        <!-- INPUT: NAMA LAYANAN -->
                        <div>
                            <label class="block text-[#0A4191] text-base font-extrabold mb-2">
                                Nama Layanan
                            </label>
                            <input 
                                type="text" 
                                name="nama_layanan" 
                                x-model="formData.nama_layanan"
                                placeholder="Contoh: Yudisium" 
                                autocomplete="off"
                                required
                                class="w-full px-5 py-3.5 bg-[#0A4191] text-white placeholder-slate-300 font-semibold text-base rounded-2xl border-none focus:outline-none focus:ring-4 focus:ring-blue-400 shadow-inner"
                            >
                        </div>

                       <!-- INPUT: PILIH LOKET (loket_id) -->
                        <div>
                            <label class="block text-[#0A4191] text-base font-extrabold mb-2">
                                Pilih Loket Terkait
                            </label>
                            <div class="relative">
                                <select 
                                    name="loket_id" 
                                    x-model="formData.loket_id"
                                    required
                                    class="w-full px-5 py-3.5 bg-[#0A4191] text-white font-semibold text-base rounded-2xl border-none focus:outline-none focus:ring-4 focus:ring-blue-400 appearance-none cursor-pointer pr-12 shadow-inner"
                                >
                                    <option value="" disabled class="text-slate-300">-- Pilih Loket --</option>
                                    @foreach($lokets as $loketItem)
                                        <!-- Pastikan value-nya murni ID angka dari database -->
                                        <option value="{{ $loketItem->id }}" class="bg-[#0A4191] text-white py-1">
                                            {{ $loketItem->nama_loket }} (ID: {{ $loketItem->id }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-white">
                                    <svg class="w-6 h-6 fill-current" viewBox="0 0 20 20">
                                        <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN -->
                    <div class="mt-8 flex justify-center">
                        <button 
                            type="submit" 
                            class="w-3/5 py-3.5 bg-[#4D82E2] hover:bg-blue-700 text-white font-black text-lg tracking-widest rounded-full shadow-lg hover:shadow-xl transition-all duration-150 uppercase"
                        >
                            <span x-text="isEdit ? 'UPDATE' : 'SIMPAN'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT DEBOUNCE UNTUK AUTO-SUBMIT PENCARIAN -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('autoSearchInput');
        const searchForm  = document.getElementById('autoSearchForm');
        let debounceTimer = null;

        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    searchForm.submit();
                }, 300);
            });

            if (searchInput.value) {
                searchInput.focus();
                const length = searchInput.value.length;
                searchInput.setSelectionRange(length, length);
            }
        }
    });
</script>
@endsection