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
    showModal: false, 
    isEdit: false, 
    formUrl: '{{ route('admin.loket.store') }}',
    formData: { id: '', nomor_loket: '', active_petugas_id: '' },
    
    openCreate() {
        this.isEdit = false;
        this.formUrl = '{{ route('admin.loket.store') }}';
        this.formData = { id: '', nomor_loket: '', active_petugas_id: '' };
        this.showModal = true;
    },
    
    openEdit(item) {
        this.isEdit = true;
        this.formUrl = '{{ url('/admin/loket') }}/' + item.id;
        this.formData = { 
            id: item.id, 
            nomor_loket: item.nomor_loket,
            active_petugas_id: item.active_petugas_id ?? ''
        };
        this.showModal = true;
    }
}">

    <!-- BANNER UTAMA (KUNING) -->
    <div class="bg-[#FDE047] p-6 rounded-3xl shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border border-yellow-300">
        <div>
            <span class="inline-block bg-[#0B3B82] text-white text-xs font-black px-4 py-1.5 rounded-full mb-2">PELMA (PELAYANAN MAHASISWA)</span>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Manajemen Loket</h1>
            <p class="text-sm font-medium text-gray-700 mt-1">Kelola daftar loket pelayanan antrean mahasiswa.</p>
        </div>
        
        <!-- TOMBOL TAMBAH LOKET -->
        <button type="button" @click="openCreate()" class="bg-[#0B3B82] hover:bg-blue-900 text-white font-extrabold py-3 px-5 rounded-2xl text-sm transition flex items-center justify-center space-x-2 shadow-md flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Loket</span>
        </button>
    </div>

    <!-- PANEL UTAMA & TABEL -->
    <div class="bg-[#4D82E2] p-6 rounded-3xl shadow-md mb-6 border border-blue-400/30">
        
        @if(session('success'))
            <div class="bg-emerald-500 text-white p-4 rounded-2xl font-bold mb-6 text-sm flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 text-lg leading-none">&times;</button>
            </div>
        @endif

        <!-- TABEL LOKET -->
        <div class="overflow-x-auto rounded-xl border border-white/20">
            <table class="w-full text-left text-sm text-white border-collapse">
                <thead class="bg-[#FDE047] text-gray-900 font-extrabold text-sm">
                    <tr>
                        <th class="p-3.5 pl-5 w-14">No</th>
                        <th class="p-3.5">Nama / Nomor Loket</th>
                        <th class="p-3.5">Petugas Penanggung Jawab</th>
                        <th class="p-3.5">Status Loket</th>
                        <th class="p-3.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-300/30 bg-blue-600/10">
                    @forelse($lokets as $index => $loket)
                    @php
                        $isLoketAktif = in_array(strtoupper((string)$loket->status), ['1', 'ACTIVE', 'AKTIF']);
                    @endphp
                    <tr class="hover:bg-blue-600/40 transition">
                        <td class="p-3.5 pl-5 font-extrabold text-white">{{ $index + 1 }}</td>
                        <td class="p-3.5 font-bold text-white">{{ $loket->nomor_loket }}</td>
                        
                        <!-- KOLOM PETUGAS -->
                        <td class="p-3.5 font-medium text-blue-100">
                            @if($loket->activePetugas)
                                <span class="inline-flex items-center space-x-2 font-bold text-white">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $isLoketAktif ? 'bg-emerald-400' : 'bg-gray-400' }}"></span>
                                    <span>{{ $loket->activePetugas->name ?? $loket->activePetugas->nama_petugas }}</span>
                                </span>
                            @else
                                <span class="italic text-blue-200/70">Belum ada petugas bertugas</span>
                            @endif
                        </td>

                        <!-- STATUS DETEKSI SESUAI SESI PETUGAS -->
                        <td class="p-3.5">
                            <span class="px-3 py-1 rounded-full text-xs font-black inline-block {{ $isLoketAktif ? 'bg-emerald-400 text-gray-900' : 'bg-red-500 text-white' }}">
                                {{ $isLoketAktif ? 'AKTIF' : 'NON-AKTIF' }}
                            </span>
                        </td>

                        <!-- AKSI -->
                        <td class="p-3.5">
                            <div class="flex items-center justify-center space-x-2">
                                <button type="button" @click="openEdit({{ json_encode($loket) }})" class="bg-[#FDE047] hover:bg-yellow-400 text-gray-900 font-bold p-2 rounded-lg transition shadow-sm" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                
                                <form action="{{ route('admin.loket.destroy', $loket->id) }}" method="POST" onsubmit="return confirm('Yakin mau hapus loket ini?')">
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
                        <td colspan="5" class="p-8 text-center text-blue-100 font-medium">Belum ada data loket.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL FORM (SESUAI DESAIN KUNING DISESUAIKAN) -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            
            <!-- OVERLAY BACKDROP -->
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/60 transition-opacity" 
                 @click="showModal = false"></div>

            <!-- MODAL CONTENT BOX -->
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-lg bg-[#FFDE59] rounded-[2.5rem] p-8 text-left shadow-2xl border border-amber-300 transform transition-all z-10">
                
                <!-- TOMBOL CLOSE (X) -->
                <button type="button" @click="showModal = false" class="absolute top-6 right-6 text-[#0A4191] hover:text-blue-900 transition focus:outline-none">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- HEADER TITLE -->
                <h3 class="text-3xl font-black text-[#0A4191] text-center mb-8 tracking-wide" x-text="isEdit ? 'Edit Loket' : 'Tambah Loket'"></h3>

                <form :action="formUrl" method="POST">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="space-y-6">
                        <!-- INPUT: NAMA / NOMOR LOKET -->
                        <div>
                            <label class="block text-[#0A4191] text-base font-extrabold mb-2">
                                Nama / Nomor Loket
                            </label>
                            <input 
                                type="text" 
                                name="nomor_loket" 
                                x-model="formData.nomor_loket" 
                                required 
                                placeholder="Contoh: Loket 1" 
                                autocomplete="off"
                                class="w-full px-5 py-3.5 bg-[#0A4191] text-white placeholder-slate-300 font-semibold text-base rounded-2xl border-none focus:outline-none focus:ring-4 focus:ring-blue-400 shadow-inner"
                            >
                        </div>

                        <!-- INPUT: SELECT PETUGAS -->
                        <div>
                            <label class="block text-[#0A4191] text-base font-extrabold mb-2">
                                Petugas Penanggung Jawab
                            </label>
                            <div class="relative">
                                <select 
                                    name="active_petugas_id" 
                                    x-model="formData.active_petugas_id" 
                                    class="w-full px-5 py-3.5 bg-[#0A4191] text-white font-semibold text-base rounded-2xl border-none focus:outline-none focus:ring-4 focus:ring-blue-400 appearance-none cursor-pointer pr-12 shadow-inner"
                                >
                                    <option value="" class="text-slate-300">-- Belum Ada Petugas --</option>
                                    @foreach($petugass as $p)
                                        <option value="{{ $p->id }}" class="bg-[#0A4191] text-white py-1">
                                            {{ $p->name ?? $p->nama_petugas }}
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
                            SIMPAN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection