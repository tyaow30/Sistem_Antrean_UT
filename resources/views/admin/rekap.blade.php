@extends('layouts.admin')

@section('title', 'Rekap Laporan')
@section('header_title', 'Rekapitulasi Antrean')

@section('content')
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Riwayat & Rekap Antrean Keseluruhan</h3>
                <p class="text-slate-500 text-sm">Data histori seluruh mahasiswa yang telah mengambil tiket antrean.</p>
            </div>
            <!-- Tombol Export Excel (Placeholder / Siap dikembangkan) -->
            <button onclick="alert('Fitur export Excel siap dihubungkan ke library Excel!')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow transition flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </button>
        </div>

        <!-- Tabel Rekap -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase text-xs tracking-wider">
                        <th class="p-3 rounded-l-lg">No Tiket</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Layanan</th>
                        <th class="p-3">Loket</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($antreans as $antrean)
                        <tr>
                            <td class="p-3 font-black text-brand-darkblue">{{ $antrean->nomor_antrean ?? $antrean->id }}</td>
                            <td class="p-3 text-slate-600">{{ $antrean->tanggal ?? $antrean->created_at->format('Y-m-d') }}</td>
                            <td class="p-3 font-medium text-slate-800">{{ $antrean->layanan->nama_layanan ?? '-' }}</td>
                            <td class="p-3">{{ $antrean->loket->nama_loket ?? '-' }}</td>
                            <td class="p-3">
                                @if($antrean->status == 'DONE')
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full">Selesai</span>
                                @elseif($antrean->status == 'WAITING')
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2.5 py-1 rounded-full">Menunggu</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-bold px-2.5 py-1 rounded-full">{{ $antrean->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-400">Belum ada histori rekap antrean.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection