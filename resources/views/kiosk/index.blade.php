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
    <main class="w-full max-w-2xl mx-auto my-auto">

        <!-- ALERT ERROR / SUCCESS -->
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-bold text-center shadow-sm">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-300 text-emerald-700 px-6 py-4 rounded-2xl font-bold text-center shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- TAHAP 1: WELCOME SCREEN -->
        <div id="section-welcome" class="text-center space-y-8 py-8">
            <div class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-extrabold text-ut-blue tracking-tight uppercase">SELAMAT DATANG!</h1>
                <p class="text-xl font-semibold text-ut-yellowHover">
                    Silakan klik tombol di bawah untuk mengambil antrean layanan.
                </p>
            </div>

            <button type="button" onclick="mulaiIsiForm()" 
                class="bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-black text-2xl md:text-3xl px-16 py-6 rounded-3xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1 active:translate-y-0 uppercase tracking-wider">
                PELMA
            </button>
        </div>

        <!-- FORM UTAMA DALAM SATU CARD BIRU -->
        <form id="form-kiosk" action="{{ route('kiosk.cetak') }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="layanan_id" id="input-layanan-id" required>
            
            <div class="bg-ut-blue text-white rounded-3xl p-6 md:p-10 shadow-2xl space-y-6 relative transition-all duration-500">
                
                <!-- BAGIAN 1: DATA MAHASISWA -->
                <div class="space-y-4">
                    <div class="text-center space-y-1">
                        <h2 class="text-2xl md:text-3xl font-black uppercase tracking-wide">DATA MAHASISWA</h2>
                        <p class="text-ut-yellow font-semibold text-sm">Silakan isi form data diri & kendala</p>
                    </div>

                    <div class="space-y-3 text-left">
                        <div>
                            <label class="block text-xs md:text-sm font-bold mb-1">Nama <span class="text-ut-yellow">*</span></label>
                            <input type="text" name="nama" id="input-nama" required placeholder="Masukkan Nama Lengkap"
                                class="w-full px-4 py-3 rounded-xl text-slate-900 font-semibold text-sm border-none focus:ring-4 focus:ring-ut-yellow outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs md:text-sm font-bold mb-1">NIM / NIK <span class="text-ut-yellow">*</span></label>
                            <input type="text" name="nim" id="input-nim" required placeholder="Masukkan NIM / NIK"
                                class="w-full px-4 py-3 rounded-xl text-slate-900 font-semibold text-sm border-none focus:ring-4 focus:ring-ut-yellow outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs md:text-sm font-bold mb-1">No. HP <span class="text-ut-yellow">*</span></label>
                            <input type="text" name="no_hp" id="input-nohp" required placeholder="Contoh: 081234567890"
                                class="w-full px-4 py-3 rounded-xl text-slate-900 font-semibold text-sm border-none focus:ring-4 focus:ring-ut-yellow outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs md:text-sm font-bold mb-1">Kendala <span class="text-ut-yellow">*</span></label>
                            <input type="text" name="kendala" id="input-kendala" required placeholder="Tuliskan singkat kendala Anda"
                                class="w-full px-4 py-3 rounded-xl text-slate-900 font-semibold text-sm border-none focus:ring-4 focus:ring-ut-yellow outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- TOMBOL LANJUT -->
                <div id="wrapper-btn-lanjut" class="pt-2">
                    <button type="button" onclick="bukaPilihanLayanan()"
                        class="w-full bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-extrabold text-base py-4 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider">
                        LANJUT &rarr;
                    </button>
                    <button type="button" onclick="kembaliKeWelcome()"
                        class="w-full mt-3 bg-white/10 hover:bg-white/20 text-white font-bold text-sm py-3 rounded-xl transition duration-200 uppercase tracking-wider">
                        &larr; KEMBALI KE BERANDA
                    </button>
                </div>

                <!-- BAGIAN 2: PILIH LAYANAN -->
                <div id="section-pilih-layanan" class="hidden space-y-6 pt-2 animate-fadeIn">
                    
                    <hr class="border-white/20">

                    <div class="space-y-4">
                        <div class="text-center space-y-1">
                            <h3 class="text-xl md:text-2xl font-black uppercase tracking-wide text-ut-yellow">Pilih Loket Layanan</h3>
                            <p class="text-white/90 text-xs md:text-sm">
                                Halo <span id="display-nama-mhs" class="underline font-bold">...</span>! Silakan pilih layanan tujuan Anda.
                            </p>
                        </div>

                        @if(!$sesi)
                            <div class="bg-amber-100 border border-amber-300 text-amber-900 p-3 rounded-xl font-bold text-xs text-center">
                                ⚠️ Sesi antrean hari ini belum dibuka resmi oleh Admin.
                            </div>
                        @endif

                        <!-- GRID TOMBOL LAYANAN -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            @php
                                $uniqueLayanan = isset($layananList) ? $layananList->unique('nama_layanan') : collect();
                            @endphp

                            @forelse($uniqueLayanan as $layanan)
                                <button type="button" onclick="pilihLayanan('{{ $layanan->id }}', this)"
                                    class="layanan-btn bg-white hover:bg-slate-100 text-ut-blue font-bold text-xs md:text-sm py-3 px-2 rounded-xl shadow transition duration-200 text-center uppercase tracking-wider border-2 border-transparent">
                                    {{ $layanan->nama_layanan }}
                                </button>
                            @empty
                                <div class="col-span-full py-4 text-white text-center font-bold text-sm">
                                    Belum ada data layanan tersedia.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- TOMBOL AKSI AKHIR -->
                    <div class="pt-2 flex flex-col gap-3">
                        <button type="submit" id="btn-submit-antrean" disabled
                            class="w-full bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-extrabold text-base py-4 rounded-xl shadow-lg opacity-50 cursor-not-allowed transition duration-300 uppercase tracking-wider">
                            AMBIL ANTREAN
                        </button>

                        <button type="button" onclick="tutupPilihanLayanan()"
                            class="w-full bg-white/10 hover:bg-white/20 text-white font-bold text-sm py-3 rounded-xl transition duration-200 uppercase tracking-wider">
                            &larr; KEMBALI KE EDIT DATA
                        </button>
                    </div>

                </div>

            </div>

        </form>

    </main>

    <!-- FOOTER -->
    <footer class="w-full text-center py-4 text-xs font-semibold text-slate-400">
        &copy; {{ date('Y') }} Universitas Terbuka Surabaya. All rights reserved.
    </footer>

    <!-- INTERACTION SCRIPT -->
    <script>
        function mulaiIsiForm() {
            document.getElementById('input-nama').value = '';
            document.getElementById('input-nim').value = '';
            document.getElementById('input-nohp').value = '';
            document.getElementById('input-kendala').value = '';

            document.getElementById('section-pilih-layanan').classList.add('hidden');
            document.getElementById('wrapper-btn-lanjut').classList.remove('hidden');

            document.getElementById('section-welcome').classList.add('hidden');
            document.getElementById('form-kiosk').classList.remove('hidden');
        }

        function kembaliKeWelcome() {
            document.getElementById('form-kiosk').classList.add('hidden');
            document.getElementById('section-welcome').classList.remove('hidden');
        }

        function bukaPilihanLayanan() {
            const nama = document.getElementById('input-nama').value.trim();
            const nim = document.getElementById('input-nim').value.trim();

            if (!nama || !nim) {
                alert('Nama dan NIM wajib diisi terlebih dahulu sebelum melanjutkan!');
                document.getElementById('input-nama').focus();
                return;
            }

            document.getElementById('display-nama-mhs').innerText = nama;

            document.getElementById('wrapper-btn-lanjut').classList.add('hidden');
            document.getElementById('section-pilih-layanan').classList.remove('hidden');
        }

        function tutupPilihanLayanan() {
            document.getElementById('section-pilih-layanan').classList.add('hidden');
            document.getElementById('wrapper-btn-lanjut').classList.remove('hidden');

            document.querySelectorAll('.layanan-btn').forEach(btn => {
                btn.classList.remove('bg-ut-blue', 'text-white', 'border-ut-yellow', 'ring-2', 'ring-ut-yellow');
                btn.classList.add('bg-white', 'text-ut-blue');
            });

            const btnSubmit = document.getElementById('btn-submit-antrean');
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
        }

        function pilihLayanan(id, element) {
            document.getElementById('input-layanan-id').value = id;

            document.querySelectorAll('.layanan-btn').forEach(btn => {
                btn.classList.remove('bg-ut-blue', 'text-white', 'border-ut-yellow', 'ring-2', 'ring-ut-yellow');
                btn.classList.add('bg-white', 'text-ut-blue');
            });

            element.classList.remove('bg-white', 'text-ut-blue');
            element.classList.add('bg-ut-blue', 'text-white', 'border-ut-yellow', 'ring-2', 'ring-ut-yellow');

            const btnSubmit = document.getElementById('btn-submit-antrean');
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    </script>
</body>
</html>