@extends('layouts.admin')

@section('content')
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
        this.formUrl = '/admin/loket/' + item.id;
        this.formData = { 
            id: item.id, 
            nomor_loket: item.nomor_loket,
            active_petugas_id: item.active_petugas_id ?? ''
        };
        this.showModal = true;
    }
}">

    <!-- BANNER KUNING -->
    <div class="bg-[#FDE047] p-6 rounded-3xl shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <span class="inline-block bg-[#0B3B82] text-white text-xs font-black px-4 py-1.5 rounded-full mb-2">PELMA (PELAYANAN MAHASISWA)</span>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Manajemen Loket</h1>
            <p class="text-sm font-medium text-gray-700 mt-1">Kelola daftar loket pelayanan antrean mahasiswa.</p>
        </div>
        
        <!-- TOMBOL TAMBAH LOKET -->
        <button @click="openCreate()" class="bg-[#0B3B82] hover:bg-blue-900 text-white font-extrabold py-3 px-5 rounded-2xl text-sm transition flex items-center justify-center space-x-2 shadow-md flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Loket</span>
        </button>
    </div>

    <!-- PANEL UTAMA & TABEL -->
    <div class="bg-[#3B82F6] p-6 rounded-2xl shadow-md mb-6">
        
        @if(session('success'))
            <div class="bg-green-500 text-white p-4 rounded-xl font-bold mb-4 text-sm flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">&times;</button>
            </div>
        @endif

        <!-- TABEL LOKET -->
        <div class="overflow-x-auto rounded-xl">
            <table class="w-full text-left text-sm text-white">
                <thead class="bg-[#FDE047] text-gray-900 font-extrabold uppercase text-xs">
                    <tr>
                        <th class="p-3">No</th>
                        <th class="p-3">Nama / Nomor Loket</th>
                        <th class="p-3">Petugas Penanggung Jawab</th>
                        <th class="p-3">Status Loket</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-400/30 bg-[#3B82F6]">
                    @forelse($lokets as $index => $loket)
                    @php
                        // Cek status berdasarkan kolom status di database (bukan sekadar ada active_petugas_id)
                        $isLoketAktif = in_array(strtoupper((string)$loket->status), ['1', 'ACTIVE', 'AKTIF']);
                    @endphp
                    <tr class="hover:bg-blue-600/50 transition">
                        <td class="p-3 font-extrabold text-yellow-300">{{ $index + 1 }}</td>
                        <td class="p-3 font-bold">{{ $loket->nomor_loket }}</td>
                        
                        <!-- KOLOM PETUGAS -->
                        <td class="p-3 font-medium text-blue-100">
                            @if($loket->activePetugas)
                                <span class="inline-flex items-center space-x-2 font-bold text-white">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $isLoketAktif ? 'bg-green-400' : 'bg-gray-400' }}"></span>
                                    <span>{{ $loket->activePetugas->name ?? $loket->activePetugas->nama_petugas }}</span>
                                </span>
                            @else
                                <span class="italic text-blue-200/70">Belum ada petugas bertugas</span>
                            @endif
                        </td>

                        <!-- STATUS DETEKSI SESUAI SESI PETUGAS -->
                        <td class="p-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-extrabold {{ $isLoketAktif ? 'bg-green-400 text-gray-900' : 'bg-red-500 text-white' }}">
                                {{ $isLoketAktif ? 'AKTIF' : 'NON-AKTIF' }}
                            </span>
                        </td>

                        <!-- AKSI -->
                        <td class="p-3">
                            <div class="flex items-center justify-center space-x-2">
                                <button @click="openEdit({{ json_encode($loket) }})" class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold p-2 rounded-xl transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                
                                <form action="{{ route('admin.loket.destroy', $loket->id) }}" method="POST" onsubmit="return confirm('Yakin mau hapus loket ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold p-2 rounded-xl transition" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-blue-200">Belum ada data loket.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL FORM -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" class="fixed inset-0 bg-black/50 transition-opacity" @click="showModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showModal" class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="formUrl" method="POST">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="bg-[#0B3B82] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-black text-white" x-text="isEdit ? 'Edit Loket' : 'Tambah Loket Baru'"></h3>
                        <button type="button" @click="showModal = false" class="text-white hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- INPUT NOMOR LOKET -->
                        <div>
                            <label class="block text-gray-800 text-xs font-bold mb-1">NAMA / NOMOR LOKET</label>
                            <input type="text" name="nomor_loket" x-model="formData.nomor_loket" required placeholder="Contoh: Loket 1" class="w-full bg-gray-100 border-0 rounded-xl px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- DROPDOWN PILIHAN PETUGAS -->
                        <div>
                            <label class="block text-gray-800 text-xs font-bold mb-1">PETUGAS PENANGGUNG JAWAB</label>
                            <select name="active_petugas_id" x-model="formData.active_petugas_id" class="w-full bg-gray-100 border-0 rounded-xl px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Belum Ada Petugas --</option>
                                @foreach($petugass as $p)
                                    <option value="{{ $p->id }}">{{ $p->name ?? $p->nama_petugas }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-2 rounded-b-3xl">
                        <button type="button" @click="showModal = false" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold px-4 py-2 rounded-xl text-sm transition">Batal</button>
                        <button type="submit" class="bg-[#22C55E] hover:bg-green-600 text-white font-bold px-5 py-2 rounded-xl text-sm transition shadow-sm" x-text="isEdit ? 'Simpan Perubahan' : 'Simpan'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection