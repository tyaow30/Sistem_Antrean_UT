<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelayanan Mahasiswa (PELMA) - UT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center justify-center p-6 font-['Plus_Jakarta_Sans'] text-slate-900">

    <div class="w-full max-w-3xl bg-white p-8 rounded-3xl shadow-md border border-gray-100 my-auto transition-all duration-500">
        
        <!-- LOGO & HEADER -->
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Logo UT" class="h-20 mx-auto mb-4 object-contain">
            <h1 class="text-3xl font-black text-blue-900">Pelayanan Mahasiswa (PELMA)</h1>
            <p class="text-gray-500 text-sm mt-1">Universitas Terbuka Surabaya</p>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl font-bold text-center text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- TOMBOL SELAMAT DATANG (WELCOME SCREEN) -->
        <div id="section-welcome" class="text-center py-6">
            <p class="text-gray-600 mb-6 font-medium">Silakan klik tombol di bawah untuk mengambil antrean layanan.</p>
            <button type="button" onclick="mulaiIsiForm()" 
                class="bg-amber-400 hover:bg-amber-500 text-blue-900 font-extrabold py-4 px-10 rounded-2xl shadow-md transition text-lg uppercase tracking-wide">
                MASUK KE PELMA
            </button>
        </div>

        <!-- TAHAP 1: FORM DATA DIRI -->
        <div id="section-form" class="hidden space-y-5 transition-all duration-500">
            <div class="border-t border-gray-200 pt-6 mb-4">
                <h3 class="text-lg font-bold text-gray-800 text-center mb-1">Identitas Mahasiswa</h3>
                <p class="text-xs text-gray-500 text-center">Masukkan NIM dan Nama lengkap Anda.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">NIM / NIK</label>
                <input type="text" id="input-nim" required placeholder="Masukkan NIM atau NIK" 
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none transition">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" id="input-nama" required placeholder="Masukkan Nama Lengkap" 
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none transition">
            </div>

            <button type="button" onclick="bukaPilihanLoket()" 
                class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-4 rounded-xl shadow-md transition text-base">
                LANJUT PILIH LAYANAN &rarr;
            </button>
        </div>

        <!-- TAHAP 2: PILIHAN LAYANAN & LOKET -->
        <div id="section-loket" class="hidden mt-8 pt-8 border-t border-gray-200 transition-all duration-500">
            <div class="text-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Pilih Loket Layanan</h3>
                <p class="text-sm text-gray-500">Halo, <span id="display-nama" class="font-bold text-blue-900"></span>. Silakan pilih layanan tujuan Anda.</p>
            </div>

            @if(!$sesi)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-2xl text-center font-bold text-sm mb-4">
                    ⚠️ Sesi antrean hari ini belum dibuka oleh Admin. Anda tetap bisa memilih layanan, namun tiket baru bisa dicetak setelah sesi diaktifkan.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($layananList as $layanan)
                    <div class="p-4 bg-white border rounded-xl shadow-sm flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800">{{ $layanan->nama_layanan }}</h3>
                            <p class="text-sm text-gray-500">{{ $layanan->deskripsi ?? 'Layanan Akademik Mahasiswa' }}</p>
                        </div>
                        
                        <!-- Form dikirim dengan fungsi onsubmit agar menyertakan data NIM & Nama terbaru -->
                        <form action="{{ route('kiosk.cetak', $layanan->id) }}" method="POST" onsubmit="sertakanData(this)">
                            @csrf
                            <input type="hidden" name="nim" class="form-nim-hidden">
                            <input type="hidden" name="nama" class="form-nama-hidden">
                            
                            <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg shadow">
                                Ambil Antrean
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-gray-500 col-span-2 text-center py-4">Belum ada layanan atau loket yang aktif saat ini.</p>
                @endforelse
            </div>

            <div class="text-center mt-6">
                <button type="button" onclick="kembaliKeForm()" class="text-sm text-gray-500 hover:text-blue-700 font-medium underline">
                    &larr; Ulangi / Ubah Data Diri
                </button>
            </div>
        </div>

    </div>

    <!-- SCRIPT JAVASCRIPT INTERAKSI HALAMAN -->
    <script>
        function mulaiIsiForm() {
            document.getElementById('section-welcome').classList.add('hidden');
            let sectionForm = document.getElementById('section-form');
            sectionForm.classList.remove('hidden');
            sectionForm.scrollIntoView({ behavior: 'smooth' });
        }

        function bukaPilihanLoket() {
            let nim = document.getElementById('input-nim').value.trim();
            let nama = document.getElementById('input-nama').value.trim();

            if (!nim || !nama) {
                alert('Mohon isi NIM dan Nama Lengkap terlebih dahulu!');
                return;
            }

            document.getElementById('display-nama').innerText = nama;

            // Masukkan nilai input ke semua class hidden form layanan secara otomatis
            document.querySelectorAll('.form-nim-hidden').forEach(el => el.value = nim);
            document.querySelectorAll('.form-nama-hidden').forEach(el => el.value = nama);

            let sectionLoket = document.getElementById('section-loket');
            sectionLoket.classList.remove('hidden');
            sectionLoket.scrollIntoView({ behavior: 'smooth' });
        }

        function kembaliKeForm() {
            document.getElementById('section-loket').classList.add('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function sertakanData(form) {
            let nim = document.getElementById('input-nim').value.trim();
            let nama = document.getElementById('input-nama').value.trim();
            
            form.querySelector('.form-nim-hidden').value = nim;
            form.querySelector('.form-nama-hidden').value = nama;
        }
    </script>
</body>
</html>