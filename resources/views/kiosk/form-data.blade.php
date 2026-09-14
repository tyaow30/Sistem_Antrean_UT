<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Data & Pilih Loket - PELMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col justify-between items-center p-6 font-['Plus_Jakarta_Sans'] text-slate-900">

    <div class="w-full max-w-4xl mx-auto text-center flex flex-col items-center my-auto py-10">

        <!-- LOGO & HEADER -->
        <div class="mb-6">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Logo UT" class="h-16 mx-auto mb-3">
            <h2 class="text-2xl font-bold text-gray-800">Data Pengunjung</h2>
            <p class="text-gray-500 text-sm mt-1">Silakan isi data diri Anda sebelum memilih loket layanan</p>
        </div>

        <!-- ALERT ERROR -->
        @if(session('error'))
            <div class="w-full max-w-lg bg-red-100 border border-red-400 text-red-700 px-6 py-3 rounded-2xl font-bold text-center shadow-sm mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- KOTAK UTAMA YANG AKAN MELAR KE BAWAH -->
        <div id="main-card" class="w-full max-w-lg bg-white p-8 rounded-3xl shadow-md border border-gray-100 transition-all duration-500 ease-in-out">
            
            <!-- FORM DATA DIRI -->
            <form id="form-data-diri" onsubmit="tampilkanPilihanLoket(event)" class="space-y-4 text-left">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">NIM / NIK</label>
                    <input type="text" id="nim" required placeholder="Masukkan NIM atau NIK" 
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="nama" required placeholder="Masukkan Nama Lengkap" 
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>

                <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-4 rounded-xl shadow-md transition-all mt-2 uppercase tracking-wide">
                    LANJUT PILIH LAYANAN &rarr;
                </button>
            </form>

            <!-- BAGIAN PILIHAN LOKET (MULA-MULA TERSEMBUNYI, NANTI TURUN KE BAWAH) -->
            <div id="section-loket" class="hidden opacity-0 transition-all duration-500 ease-in-out mt-6 pt-6 border-t border-gray-100 text-left">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-600">Halo, <span id="display-nama" class="font-bold text-blue-700"></span>. Silakan pilih loket:</p>
                    <button type="button" onclick="ubahData()" class="text-xs text-blue-600 hover:underline font-semibold">Ubah Data</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($loketList as $loket)
                        <div class="flex flex-col items-center bg-gray-50 p-5 rounded-2xl border border-gray-200 text-center hover:border-blue-500 transition">
                            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">
                                LOKET {{ $loket->nomor_loket }}
                            </h3>
                            <p class="text-xs text-gray-500 font-medium mt-1 mb-4">
                                Antrean Saat Ini : <span class="font-bold text-slate-800">{{ str_pad($loket->total_antrean ?? 0, 2, '0', STR_PAD_LEFT) }}</span>
                            </p>

                            <!-- FORM KIRIM KE CONTROLLER CETAK -->
                            <form action="{{ route('kiosk.cetak', $loket->id) }}" method="POST" class="w-full">
                                @csrf
                                <!-- Input hidden buat bawa data nama & nim ke backend -->
                                <input type="hidden" name="nim" class="input-nim-hidden">
                                <input type="hidden" name="nama" class="input-nama-hidden">

                                <button type="submit" class="w-full bg-amber-400 hover:bg-amber-500 text-blue-950 font-extrabold py-3 px-4 rounded-xl shadow-sm transition uppercase text-sm tracking-wide">
                                    AMBIL ANTREAN
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="col-span-2 text-center py-4 text-gray-500">
                            <p class="text-sm font-semibold">Tidak ada loket yang aktif saat ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    <!-- TOMBOL KEMBALI DI BAWAH -->
    <div class="pb-4">
        <a href="{{ route('kiosk.index') }}" class="text-sm text-gray-500 hover:text-blue-700 font-medium transition-colors">
            &larr; Batal & Kembali
        </a>
    </div>

    <!-- SCRIPT ANIMASI TURUN / EXPAND -->
    <script>
        function tampilkanPilihanLoket(event) {
            event.preventDefault(); // Mencegah form reload halaman

            // Ambil value input
            const nim = document.getElementById('nim').value;
            const nama = document.getElementById('nama').value;

            // Masukkan ke teks sapaan dan input hidden loket
            document.getElementById('display-nama').innerText = nama;
            document.querySelectorAll('.input-nim-hidden').forEach(el => el.value = nim);
            document.querySelectorAll('.input-nama-hidden').forEach(el => el.value = nama);

            // Lebarkan kotak card utamanya agar muat grid loket
            const mainCard = document.getElementById('main-card');
            mainCard.classList.remove('max-w-lg');
            mainCard.classList.add('max-w-2xl');

            // Sembunyikan bagian form input data diri
            document.getElementById('form-data-diri').style.display = 'none';

            // Munculkan section loket dengan animasi lembut
            const sectionLoket = document.getElementById('section-loket');
            sectionLoket.classList.remove('hidden');
            setTimeout(() => {
                sectionLoket.classList.remove('opacity-0');
            }, 50);
        }

        function ubahData() {
            // Kembalikan kotak card ke ukuran semula
            const mainCard = document.getElementById('main-card');
            mainCard.classList.remove('max-w-2xl');
            mainCard.classList.add('max-w-lg');

            // Sembunyikan loket dan tampilkan kembali form input data diri
            document.getElementById('section-loket').classList.add('hidden', 'opacity-0');
            document.getElementById('form-data-diri').style.display = 'block';
        }
    </script>
</body>
</html>