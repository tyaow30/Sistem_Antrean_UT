<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Sistem Antrean</title>
    @vite('resources/css/app.css')
    <!-- Menambahkan Font Awesome untuk Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Container Utama -->
    <div class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8">

        <!-- HEADER & NAVBAR -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logosby.png') }}" alt="Logo UT" class="h-12 md:h-14">
            </div>

            <!-- Menu Navigasi -->
            <div class="flex items-center gap-2 md:gap-4 bg-white p-2 rounded-full shadow-sm">
                <a href="#" class="bg-[#FFCC00] text-[#003B70] font-bold py-2 px-6 rounded-full transition hover:bg-yellow-500">
                    Dashboard Loket
                </a>
                <a href="{{ route('petugas.rekap') }}" class="text-[#FFCC00] font-bold py-2 px-6 rounded-full transition hover:bg-gray-100">
                    Rekap & Laporan
                </a>
                
                <!-- Tombol Log Out -->
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="bg-[#D32F2F] text-white font-bold py-2 px-6 rounded-full transition hover:bg-red-700">
                        LOG OUT
                    </button>
                </form>
            </div>
        </div>

        <!-- KOTAK IDENTITAS PETUGAS -->
        @php
            $loketId = $loket->id ?? (Auth::user()->assigned_loket_id ?? 1);
            $namaLoket = $loket->nama_loket ?? ('LOKET ' . $loketId);
        @endphp

        <div class="bg-[#FFCC00] rounded-2xl p-6 mb-6 shadow-md border-b-[6px] border-yellow-600">
            <h2 class="text-xl md:text-2xl font-bold text-[#003B70]">Petugas : {{ Auth::user()->name ?? 'Petugas Loket' }}</h2>
            <p class="text-[#003B70] text-lg mt-1">Gerai Utama Senay | <span class="font-extrabold uppercase">{{ $namaLoket }}</span></p>
        </div>

        <!-- MAIN GRID CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            
            <!-- PANEL KIRI : SEDANG DILAYANI -->
            <div class="lg:col-span-3 bg-[#004A8D] rounded-2xl p-6 md:p-8 text-white shadow-xl flex flex-col justify-between min-h-[400px]">
                
                <div>
                    <p class="font-semibold tracking-wide text-sm mb-4">SEDANG DILAYANI</p>
                    
                    <!-- Area Nomor Antrean -->
                    <div class="mb-8">
                        <h1 class="text-7xl md:text-8xl font-extrabold tracking-tighter">
                            {{ isset($antreanSaatIni) && $antreanSaatIni ? 'L' . $loketId . ' - ' . str_pad($antreanSaatIni->nomor_antrean, 3, '0', STR_PAD_LEFT) : '-' }}
                        </h1>
                    </div>
                    
                    <!-- Area Detail Mahasiswa (Dinamis dari Database) -->
                    <div class="space-y-3 mb-8 text-lg">
                        <p class="flex items-center gap-3">
                            <i class="fa-solid fa-user w-6 text-center text-xl"></i> 
                            {{ isset($antreanSaatIni) && $antreanSaatIni ? $antreanSaatIni->nama : 'Belum ada antrean dipanggil' }}
                        </p>
                        <p class="flex items-center gap-3">
                            <i class="fa-solid fa-id-card w-6 text-center text-xl"></i> 
                            {{ isset($antreanSaatIni) && $antreanSaatIni ? $antreanSaatIni->nim : '-' }}
                        </p>
                        <p class="flex items-center gap-3 text-yellow-300">
                            <i class="fa-solid fa-triangle-exclamation w-6 text-center text-xl"></i> 
                            {{ isset($antreanSaatIni) && $antreanSaatIni && $antreanSaatIni->kendala ? $antreanSaatIni->kendala : 'Tidak ada kendala khusus' }}
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi 4 Kotak -->
                <div class="grid grid-cols-2 gap-4">
                    <form action="{{ isset($antreanSaatIni) && $antreanSaatIni ? route('petugas.panggil-ulang', $antreanSaatIni->id) : route('petugas.panggil-next') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="w-full bg-[#FFCC00] text-[#003B70] font-bold py-3 px-4 rounded-xl shadow-md hover:bg-yellow-500 transition">
                            {{ isset($antreanSaatIni) && $antreanSaatIni ? 'PANGGIL ULANG' : 'PANGGIL BERIKUTNYA' }}
                        </button>
                    </form>
                    <button type="button" onclick="bukaModal()" class="w-full bg-[#F57C00] text-white font-bold py-3 px-4 rounded-xl shadow-md hover:bg-orange-600 transition">ALIHKAN</button>                     
                    
                    <form action="{{ isset($antreanSaatIni) && $antreanSaatIni ? route('petugas.selesai', $antreanSaatIni->id) : '#' }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="w-full bg-[#388E3C] text-white font-bold py-3 px-4 rounded-xl shadow-md hover:bg-green-700 transition">SELESAI</button>
                    </form>

                    <form action="{{ isset($antreanSaatIni) && $antreanSaatIni ? route('petugas.lewati', $antreanSaatIni->id) : '#' }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="w-full bg-[#D32F2F] text-white font-bold py-3 px-4 rounded-xl shadow-md hover:bg-red-700 transition">LEWATI</button>
                    </form>
                </div>
            </div>

            <!-- PANEL KANAN : DAFTAR ANTREAN -->
            <div class="lg:col-span-2 flex flex-col gap-6">
                
                <!-- Kotak Antrean Loket Ini -->
                <div class="bg-[#004A8D] rounded-2xl p-5 text-white shadow-xl flex-1 flex flex-col">
                    <p class="font-semibold text-sm mb-4">Antrean Loket Ini ({{ isset($daftarAntreanLoket) ? count($daftarAntreanLoket) : 0 }})</p>
                    
                    <div class="space-y-3 flex-1 overflow-y-auto max-h-[250px] pr-2">
                        @forelse($daftarAntreanLoket ?? [] as $antre)
                            <div class="bg-[#4C7BAD] bg-opacity-40 p-3 rounded-lg flex justify-between items-center border border-[#7A9EBD]">
                                <span class="font-bold text-xl">L{{ $loketId }} - {{ str_pad($antre->nomor_antrean, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-xs text-gray-200">{{ $antre->serviceAwal->nama_layanan ?? 'Layanan' }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-300 text-center py-4">Tidak ada antrean menunggu di loket ini.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Kotak Antrean Bantuan -->
                @if(in_array($loketId, [2, 3]))
                    @php
                        $partnerLoket = ($loketId == 2) ? 3 : 2;
                    @endphp
                    <div class="bg-[#0A4595] rounded-2xl p-5 text-[#ffffff] shadow-xl flex-1 flex flex-col border-b-[6px] border-yellow-600">
                        <p class="font-bold text-sm mb-4">Antrean Bantuan ({{ isset($daftarAntreanBantuan) ? count($daftarAntreanBantuan) : 0 }})</p>
                        
                        <div class="space-y-3 flex-1 overflow-y-auto max-h-[250px] pr-2">
                            @forelse($daftarAntreanBantuan ?? [] as $bantu)
                                <div class="bg-[#FFCC00] p-3 rounded-lg flex justify-between items-center text-[#004A8D]">
                                    <div>
                                        <span class="font-bold text-xl block">L{{ $partnerLoket }} - {{ str_pad($bantu->nomor_antrean, 3, '0', STR_PAD_LEFT) }}</span>
                                        <span class="text-xs font-semibold">Asal : Loket {{ $partnerLoket }} - {{ $bantu->serviceAwal->nama_layanan ?? '' }}</span>
                                    </div>
                                    <form action="{{ route('petugas.ambil-bantuan', $bantu->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-[#004A8D] text-white text-xs font-bold py-2 px-4 rounded-lg hover:bg-blue-900 transition">BANTU</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-blue-100 text-center py-4">Tidak ada permintaan bantuan.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

            </div>
        </div>
        
        <!-- Footer / Copyright -->
        <div class="text-center text-gray-400 text-sm mt-8">
            &copy; 2026 Universitas Terbuka. All rights reserved.
        </div>

    </div>

    <!-- MODAL PENGALIHAN ANTREAN -->
    <div id="modalPengalihan" class="fixed inset-0 z-50 hidden bg-black bg-opacity-60 flex justify-center items-center backdrop-blur-sm transition-opacity">
        <div class="bg-[#004A8D] rounded-2xl w-full max-w-md p-6 shadow-2xl border-4 border-[#FFCC00]">
            <h3 class="text-[#FFCC00] text-xl font-bold text-center mb-6 uppercase tracking-wider">Pengalihan Antrean</h3>
            
            <form action="{{ isset($antreanSaatIni) && $antreanSaatIni ? route('petugas.alih-antrean', $antreanSaatIni->id) : '#' }}" method="POST">
                @csrf
                
                <!-- No Antrean -->
                <div class="mb-4">
                    <label class="block text-white text-sm font-semibold mb-2">No. Antrean :</label>
                    <input type="text" value="{{ isset($antreanSaatIni) && $antreanSaatIni ? 'L' . $loketId . ' - ' . str_pad($antreanSaatIni->nomor_antrean, 3, '0', STR_PAD_LEFT) : '-' }}" readonly class="w-full bg-[#003B70] text-gray-300 border border-[#4C7BAD] rounded-lg p-2.5 focus:outline-none cursor-not-allowed font-bold">
                </div>

                <!-- Pilihan Loket Tujuan -->
                <div class="mb-4">
                    <label class="block text-white text-sm font-semibold mb-2">Pilih Loket Tujuan :</label>
                    <select name="loket_tujuan" required class="w-full bg-white text-[#003B70] font-semibold border-none rounded-lg p-2.5 focus:outline-none focus:ring-4 focus:ring-yellow-400">
                        <option value="" disabled selected>-- Pilih Loket Tujuan --</option>
                        @foreach($daftarLoket ?? [] as $lkt)
                            <option value="{{ $lkt->id }}">
                                {{ $lkt->nama_loket }} 
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Catatan -->
                <div class="mb-8">
                    <label class="block text-white text-sm font-semibold mb-2">Catatan (Opsional) :</label>
                    <input type="text" name="catatan" placeholder="Contoh : Kurang stempel" class="w-full bg-white text-gray-800 border-none rounded-lg p-2.5 focus:outline-none focus:ring-4 focus:ring-yellow-400">
                </div>

                <!-- Tombol Aksi -->
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="tutupModal()" class="bg-[#D32F2F] text-white font-bold py-2.5 px-6 rounded-lg hover:bg-red-700 transition shadow-lg">Batal</button>
                    <button type="submit" {{ !isset($antreanSaatIni) || !$antreanSaatIni ? 'disabled' : '' }} class="bg-[#388E3C] text-white font-bold py-2.5 px-6 rounded-lg hover:bg-green-700 transition shadow-lg disabled:opacity-50">Kirim Pengalihan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModal() {
            document.getElementById('modalPengalihan').classList.remove('hidden');
        }

        function tutupModal() {
            document.getElementById('modalPengalihan').classList.add('hidden');
        }
    </script>

</body>
</html>