<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Antrean PELMA - Universitas Terbuka Surabaya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        ut: {
                            blue: '#0A4595',
                            darkblue: '#083370',
                            yellow: '#FCD34D',
                            yellowHover: '#F59E0B',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-between items-center p-4 md:p-8 font-sans antialiased text-slate-800">

    <!-- HEADER LOGO UT -->
    <header class="w-full text-center pt-4 pb-6">
        <img src="{{ asset('images/logosby.png') }}" alt="Universitas Terbuka Surabaya" class="h-14 md:h-16 mx-auto object-contain">
    </header>

    <!-- MAIN CONTAINER -->
    <main class="w-full max-w-4xl mx-auto my-auto">

        <!-- ALERT ERROR -->
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-bold text-center shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- TAHAP 1: WELCOME SCREEN -->
        <div id="section-welcome" class="text-center space-y-8 py-8">
            <div class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-extrabold text-ut-blue tracking-tight uppercase">SELAMAT DATANG!</h1>
                <p class="text-xl md:text-xl font-semibold text-ut-yellowHover">
                    Silakan klik tombol di bawah untuk mengambil antrean layanan.
                </p>
            </div>

            <button type="button" onclick="mulaiIsiForm()" 
                class="bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-black text-2xl md:text-3xl px-16 py-6 rounded-3xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1 active:translate-y-0 uppercase tracking-wider">
                PELMA
            </button>
        </div>

        <!-- FORM UTAMA & PILIH LAYANAN -->
        <form id="form-kiosk" action="{{ route('kiosk.cetak') }}" method="POST" class="hidden space-y-8 transition-all duration-500">
            @csrf
            
            <!-- TAHAP 2: FORM DATA MAHASISWA -->
            <div id="card-data-mahasiswa" class="bg-ut-blue text-white rounded-3xl p-6 md:p-10 shadow-2xl space-y-6 max-w-2xl mx-auto relative">
                
                <div class="text-center space-y-1">
                    <h2 class="text-2xl md:text-3xl font-black uppercase tracking-wide">DATA MAHASISWA</h2>
                    <p class="text-ut-yellow font-semibold text-sm md:text-base">Silakan isi form data diri & kendala</p>
                </div>

                <div class="space-y-4 text-left">
                    <div>
                        <label class="block text-sm font-bold mb-2">Nama</label>
                        <input type="text" name="nama" id="input-nama" required placeholder="Masukkan Nama Lengkap"
                            class="w-full px-5 py-3.5 rounded-2xl text-slate-900 font-semibold border-none focus:ring-4 focus:ring-ut-yellow outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-2">NIM</label>
                        <input type="text" name="nim" id="input-nim" required placeholder="Masukkan NIM / NIK"
                            class="w-full px-5 py-3.5 rounded-2xl text-slate-900 font-semibold border-none focus:ring-4 focus:ring-ut-yellow outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-2">No. HP</label>
                        <input type="text" name="no_hp" id="input-nohp" placeholder="Masukkan Nomor WhatsApp / HP"
                            class="w-full px-5 py-3.5 rounded-2xl text-slate-900 font-semibold border-none focus:ring-4 focus:ring-ut-yellow outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-2">Kendala</label>
                        <input type="text" name="kendala" id="input-kendala" placeholder="Tuliskan singkat kendala Anda"
                            class="w-full px-5 py-3.5 rounded-2xl text-slate-900 font-semibold border-none focus:ring-4 focus:ring-ut-yellow outline-none transition">
                    </div>
                </div>

                <!-- HIDDEN INPUT UNTUK LAYANAN YANG DIPILIH -->
                <input type="hidden" name="layanan_id" id="input-layanan-id">

                <div class="pt-2 flex items-center gap-4">
                    <!-- TOMBOL BACK: DARI FORM KE WELCOME SCREEN -->
                    <button type="button" onclick="kembaliKeWelcome()"
                        class="w-1/3 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-lg py-4 rounded-2xl transition duration-200 uppercase tracking-wider">
                        &larr; KEMBALI
                    </button>

                    <button type="button" id="btn-simpan-form" onclick="tampilkanPilihLayanan()"
                        class="w-2/3 bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-extrabold text-xl py-4 rounded-2xl shadow-lg hover:shadow-xl transition duration-200 uppercase tracking-wider">
                        LANJUT &rarr;
                    </button>
                </div>
            </div>

            <!-- TAHAP 3: PILIH LOKET LAYANAN DINAMIS -->
            <div id="section-pilih-layanan" class="hidden space-y-8 text-center pt-4">
                <div class="space-y-1">
                    <h2 class="text-3xl font-black text-ut-yellowHover tracking-tight">Pilih Loket Layanan</h2>
                    <p class="text-ut-blue font-bold text-lg">
                        Halo <span id="display-nama-mhs" class="underline">...</span>! Silakan pilih layanan tujuan Anda.
                    </p>
                </div>

                @if(!$sesi)
                    <div class="bg-amber-100 border border-amber-300 text-amber-900 p-4 rounded-2xl font-bold text-sm max-w-2xl mx-auto">
                        ⚠️ Sesi antrean hari ini sedang belum dibuka oleh Admin. Anda dapat memilih layanan saat sesi aktif.
                    </div>
                @endif

                <!-- GRID BUTTONS LAYANAN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 max-w-3xl mx-auto">
                    @forelse($layananList as $layanan)
                        <button type="button" onclick="pilihLayanan('{{ $layanan->id }}')"
                            class="bg-ut-yellow hover:bg-ut-yellowHover active:scale-95 text-ut-blue font-black py-5 px-4 rounded-2xl shadow-md hover:shadow-lg transition duration-200 text-center uppercase tracking-wide flex items-center justify-center min-h-[70px]">
                            {{ $layanan->nama_layanan }}
                        </button>
                    @empty
                        <div class="col-span-full py-8 text-slate-500 font-bold bg-white rounded-2xl border border-slate-200 shadow-sm">
                            Belum ada layanan antrean yang ditambahkan oleh Admin.
                        </div>
                    @endforelse
                </div>

                <!-- NAVIGATION BUTTONS (BACK & SUBMIT) -->
                <div class="pt-6 max-w-md mx-auto space-y-4">
                    <div class="flex items-center gap-4">
                        <!-- TOMBOL BACK: DARI PILIH LAYANAN KE FORM MAHASISWA -->
                        <button type="button" onclick="kembaliKeForm()"
                            class="w-1/3 bg-slate-300 hover:bg-slate-400 text-slate-800 font-bold text-lg py-4 rounded-full transition duration-200 uppercase tracking-wider">
                            &larr; KEMBALI
                        </button>

                        <button type="button" id="btn-submit-antrean" onclick="kirimFormAntrean()" disabled
                            class="w-2/3 bg-ut-blue hover:bg-ut-darkblue text-white font-extrabold text-xl py-4 rounded-full shadow-xl opacity-50 cursor-not-allowed transition duration-300 uppercase tracking-wider">
                            AMBIL ANTREAN
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </main>

    <!-- FOOTER -->
    <footer class="w-full text-center py-4 text-xs font-semibold text-slate-400">
        &copy; {{ date('Y') }} Universitas Terbuka. All rights reserved.
    </footer>

    <!-- INTERACTION SCRIPT -->
    <script>
        let selectedLayananId = null;

        function mulaiIsiForm() {
            document.getElementById('section-welcome').classList.add('hidden');
            document.getElementById('form-kiosk').classList.remove('hidden');
            document.getElementById('card-data-mahasiswa').classList.remove('hidden');
            document.getElementById('section-pilih-layanan').classList.add('hidden');
        }

        function kembaliKeWelcome() {
            document.getElementById('form-kiosk').classList.add('hidden');
            document.getElementById('section-welcome').classList.remove('hidden');
        }

        function tampilkanPilihLayanan() {
            const nama = document.getElementById('input-nama').value.trim();
            const nim = document.getElementById('input-nim').value.trim();

            if (!nama || !nim) {
                alert('Silakan isi Nama dan NIM terlebih dahulu!');
                return;
            }

            document.getElementById('display-nama-mhs').innerText = nama;
            document.getElementById('card-data-mahasiswa').classList.add('hidden');
            
            const secPilih = document.getElementById('section-pilih-layanan');
            secPilih.classList.remove('hidden');
            secPilih.scrollIntoView({ behavior: 'smooth' });
        }

        function kembaliKeForm() {
            document.getElementById('section-pilih-layanan').classList.add('hidden');
            document.getElementById('card-data-mahasiswa').classList.remove('hidden');
            document.getElementById('card-data-mahasiswa').scrollIntoView({ behavior: 'smooth' });
        }

        function pilihLayanan(id) {
            selectedLayananId = id;
            document.getElementById('input-layanan-id').value = id;

            const buttons = document.querySelectorAll('#section-pilih-layanan button[onclick^="pilihLayanan"]');
            buttons.forEach(btn => {
                btn.classList.remove('ring-4', 'ring-ut-blue', 'scale-95');
            });
            event.currentTarget.classList.add('ring-4', 'ring-ut-blue', 'scale-95');

            const btnSubmit = document.getElementById('btn-submit-antrean');
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        function kirimFormAntrean() {
            if (!selectedLayananId) {
                alert('Silakan pilih salah satu layanan terlebih dahulu!');
                return;
            }
            document.getElementById('form-kiosk').submit();
        }
    </script>
</body>
</html>