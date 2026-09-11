@extends('layouts.admin')

@section('title', 'Manajemen Petugas')

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
    formUrl: '{{ route('admin.petugas.store') }}',
    formData: { id: '', name: '', username: '' },
    
    openCreate() {
        this.isEdit = false;
        this.formUrl = '{{ route('admin.petugas.store') }}';
        this.formData = { id: '', name: '', username: '' };
        this.showModal = true;
    },
    
    openEdit(item) {
        this.isEdit = true;
        this.formUrl = '{{ url('/admin/petugas') }}/' + item.id;
        this.formData = { 
            id: item.id, 
            name: item.name ?? item.nama ?? '',
            username: item.username ?? item.email ?? ''
        };
        this.showModal = true;
    }
}">

    <!-- BANNER UTAMA (KUNING) -->
    <div class="bg-[#FDE047] p-6 rounded-3xl shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border border-yellow-300">
        <div>
            <span class="inline-block bg-[#0B3B82] text-white text-xs font-black px-4 py-1.5 rounded-full mb-2">PELMA (PELAYANAN MAHASISWA)</span>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Akun Petugas</h1>
            <p class="text-sm font-medium text-gray-700 mt-1">Halaman ini digunakan untuk melihat dan mengelola petugas yang terikat pada loket pelayanan.</p>
        </div>
        
        <!-- TOMBOL TAMBAH PETUGAS -->
        <button type="button" @click="openCreate()" class="bg-[#0B3B82] hover:bg-blue-900 text-white font-extrabold py-3 px-5 rounded-2xl text-sm transition flex items-center justify-center space-x-2 shadow-md flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>Tambah Petugas</span>
        </button>
    </div>

    <!-- ALERT SUCCESS -->
    @if(session('success'))
        <div class="bg-emerald-500 text-white p-4 rounded-2xl font-bold mb-6 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 text-lg leading-none">&times;</button>
        </div>
    @endif

    <!-- PANEL UTAMA & TABEL (BIRU) -->
    <div class="bg-[#4D82E2] p-6 rounded-3xl shadow-md mb-6 border border-blue-400/30">
        <div class="overflow-x-auto rounded-xl border border-white/20">
            <table class="w-full text-left text-sm text-white border-collapse">
                <thead class="bg-[#FDE047] text-gray-900 font-extrabold text-sm">
                    <tr>
                        <th class="p-4 text-center w-16">No</th>
                        <th class="p-4">Nama Petugas</th>
                        <th class="p-4">Email / Username</th>
                        <th class="p-4">Password</th>
                        <th class="p-4">Loket Terkait</th>
                        <th class="p-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-300/30 bg-blue-600/10">
                    @forelse($petugass ?? $petugasList ?? [] as $index => $petugas)
                        <tr class="hover:bg-blue-600/40 transition">
                            <td class="p-4 text-center font-extrabold text-white">{{ $index + 1 }}</td>
                            <td class="p-4 font-bold text-white">{{ $petugas->name ?? $petugas->nama ?? '-' }}</td>
                            <td class="p-4 font-bold text-white">{{ $petugas->email ?? $petugas->username }}</td>
                            <td class="p-4 font-bold text-white">••••••••</td>
                            <td class="p-4 font-bold text-white">
                                @if(isset($petugas->loket) || isset($petugas->assignedLoket))
                                    <span class="bg-[#0B3B82] text-white px-3 py-1 rounded-full text-xs font-bold inline-block shadow-xs">
                                        {{ $petugas->loket->nama_loket ?? $petugas->assignedLoket->nama_loket ?? $petugas->loket->nomor_loket ?? 'Loket' }}
                                    </span>
                                @else
                                    <span class="text-blue-200 text-xs italic">Belum Diatur</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- TOMBOL EDIT -->
                                    <button type="button" @click="openEdit({{ json_encode($petugas) }})" class="bg-[#FDE047] hover:bg-yellow-400 text-gray-900 font-bold p-2 rounded-lg transition shadow-sm" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- TOMBOL HAPUS -->
                                    <form action="{{ route('admin.petugas.destroy', $petugas->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus akun petugas ini?')">
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
                            <td colspan="6" class="p-8 text-center text-blue-100 font-medium">Belum ada data petugas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT PETUGAS -->
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
                <h3 class="text-3xl font-black text-[#0A4191] text-center mb-6 tracking-wide" x-text="isEdit ? 'Edit Petugas' : 'Tambah Petugas'"></h3>

                <form :action="formUrl" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-[#0A4191] text-sm font-extrabold mb-1">Nama Lengkap</label>
                        <input type="text" name="name" x-model="formData.name" required placeholder="Masukkan nama lengkap" class="w-full px-4 py-3 bg-[#0A4191] text-white placeholder-slate-300 font-semibold text-sm rounded-xl border-none focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-[#0A4191] text-sm font-extrabold mb-1">Email / Username</label>
                        <input type="text" name="username" x-model="formData.username" required placeholder="Contoh: petugas1@mail.com" class="w-full px-4 py-3 bg-[#0A4191] text-white placeholder-slate-300 font-semibold text-sm rounded-xl border-none focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-[#0A4191] text-sm font-extrabold mb-1">
                            Password <span class="text-xs font-normal" x-show="isEdit">(Kosongkan jika tidak ingin mengubah password)</span>
                        </label>
                        <input type="text" name="password" :required="!isEdit" placeholder="Masukkan password" class="w-full px-4 py-3 bg-[#0A4191] text-white placeholder-slate-300 font-semibold text-sm rounded-xl border-none focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <!-- TOMBOL AKSI -->
                    <div class="pt-4 flex justify-center">
                        <button type="submit" class="w-3/5 py-3.5 bg-[#4D82E2] hover:bg-blue-700 text-white font-black text-sm tracking-widest rounded-full shadow-lg hover:shadow-xl transition-all uppercase">
                            SIMPAN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection