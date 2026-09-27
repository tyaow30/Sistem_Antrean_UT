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
<body class="bg-white min-h-screen flex flex-col justify-center items-center p-4 md:p-8 font-sans antialiased text-slate-800 overflow-x-hidden">

    <!-- MAIN CONTAINER -->
    <main class="flex-1 w-full flex flex-col items-center justify-center">

        <!-- ALERT ERROR / SUCCESS -->
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-bold text-center shadow-sm w-full max-w-md absolute top-4 z-50">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-300 text-emerald-700 px-6 py-4 rounded-2xl font-bold text-center shadow-sm w-full max-w-md absolute top-4 z-50">
                {{ session('success') }}
            </div>
        @endif

       <!-- TAHAP 1: WELCOME SCREEN -->
        <div id="section-welcome" class="text-center transition-opacity duration-500 flex flex-col items-center w-full justify-center min-h-full">
            
            <div class="fixed top-8 md:top-12 flex items-center justify-center w-full z-10">
                <img src="{{ asset('images/logosby.png') }}" alt="Universitas Terbuka Surabaya" class="h-20 md:h-24 object-contain">
            </div>

            <div class="space-y-2 mt-20 md:mt-24">
                <h1 class="text-3xl md:text-5xl font-extrabold text-ut-blue tracking-tight uppercase">SELAMAT DATANG!</h1>
                <p class="text-xl md:text-2xl font-semibold text-slate-500">
                    Sistem Antrean Layanan Mahasiswa (PELMA)
                </p>
            </div>

            <button type="button" onclick="mulaiIsiForm()" 
                class="bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-black text-2xl md:text-3xl px-16 py-6 rounded-3xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1 active:translate-y-0 uppercase tracking-wider mt-12">
                Ambil Antrean
            </button>
        </div>

        <!-- FORM UTAMA KIOSK -->
        <form id="form-kiosk" action="{{ route('kiosk.cetak') }}" method="POST" class="hidden w-full flex justify-center items-center mt-[-15px]">               
            @csrf
            <input type="hidden" name="layanan_id" id="input-layanan-id" required>
            
            <!-- CARD CONTAINER (h-fit membuat tinggi hanya sebatas isi konten) -->
            <div id="kiosk-card" class="bg-ut-blue text-white rounded-[2rem] shadow-2xl flex transition-all duration-700 ease-in-out w-full max-w-[450px] h-fit overflow-hidden relative ring-8 ring-ut-blue/10">
                
                <!-- ========================================== -->
                <!-- BAGIAN KIRI: DATA MAHASISWA -->
                <!-- ========================================== -->
                <div id="left-panel" class="w-[450px] shrink-0 p-8 flex flex-col relative z-10 bg-ut-blue">                  
                    <!-- HEADER LOGO -->
                    <div class="flex items-center justify-center gap-4 mb-8">                        
                        <img src="{{ asset('images/logosbykecil.png') }}" alt="UT Surabaya" class="h-12 w-12 object-contain bg-white rounded-xl p-1 shrink-0">
                        <div class="text-left">
                            <h1 class="text-sm md:text-base font-extrabold leading-tight tracking-wider uppercase">Universitas Terbuka</h1>
                            <p class="text-xs md:text-sm font-bold text-ut-yellow uppercase tracking-widest">Surabaya</p>
                        </div>
                    </div>

                    <div class="flex flex-col space-y-6">
                        <div class="text-center space-y-1">
                            <h2 class="text-3xl font-black uppercase tracking-wide">DATA MAHASISWA</h2>
                            <p class="text-ut-yellow font-semibold text-sm">Silakan isi form data diri & kendala</p>
                        </div>

                        <div class="space-y-4 text-left">
                            <div>
                                <label class="block text-sm font-bold mb-1">Nama <span class="text-ut-yellow">*</span></label>
                                <input type="text" name="nama" id="input-nama" required placeholder="Masukkan Nama Lengkap"
                                    class="w-full px-5 py-3.5 rounded-full text-slate-900 font-bold text-sm border-none focus:ring-4 focus:ring-ut-yellow outline-none transition shadow-inner">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-1">NIM</label>
                                <input type="text" name="nim" id="input-nim" placeholder="Masukkan NIM"
                                    class="w-full px-5 py-3.5 rounded-full text-slate-900 font-bold text-sm border-none focus:ring-4 focus:ring-ut-yellow outline-none transition shadow-inner">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-1">No. HP <span class="text-ut-yellow">*</span></label>
                                <input type="text" name="no_hp" id="input-nohp" required placeholder="Contoh: 081234567890"
                                    class="w-full px-5 py-3.5 rounded-full text-slate-900 font-bold text-sm border-none focus:ring-4 focus:ring-ut-yellow outline-none transition shadow-inner">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-1">Kendala <span class="text-ut-yellow">*</span></label>
                                <input type="text" name="kendala" id="input-kendala" required placeholder="Tuliskan singkat kendala Anda"
                                    class="w-full px-5 py-3.5 rounded-full text-slate-900 font-bold text-sm border-none focus:ring-4 focus:ring-ut-yellow outline-none transition shadow-inner">
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL KEMBALI & LANJUT (Langsung menempel di bawah form) -->
                    <div id="wrapper-btn-lanjut" class="flex gap-4 pt-8 transition-opacity duration-300">
                        <button type="button" onclick="kembaliKeWelcome()"
                            class="flex-1 bg-white/10 hover:bg-white/20 text-white font-bold text-sm py-4 rounded-full transition duration-200 uppercase tracking-wider border border-white/30 backdrop-blur-sm">
                            Kembali
                        </button>
                        <button type="button" onclick="bukaPilihanLayanan()"
                            class="flex-1 bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-extrabold text-sm py-4 rounded-full shadow-lg transition duration-300 uppercase tracking-wider">
                            Lanjut
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- BAGIAN KANAN: PILIH LAYANAN (Disembunyikan total di awal) -->
                <!-- ========================================== -->
                <div id="section-pilih-layanan" class="hidden flex-1 min-w-[500px] p-8 border-l border-white/10 flex-col justify-between opacity-0 transition-opacity duration-500 bg-ut-darkblue/40 shadow-inner">
                    
                    <div class="space-y-6 mt-4">
                        <div class="text-center space-y-2">
                            <h3 class="text-3xl font-black uppercase tracking-wide text-ut-yellow drop-shadow-md">Pilih Loket Layanan</h3>
                            <p class="text-white text-sm">
                                Halo <span id="display-nama-mhs" class="font-bold underline decoration-ut-yellow underline-offset-4">...</span>! Silakan pilih layanan tujuan Anda.
                            </p>
                        </div>

                        @if(!$sesi)
                            <div class="bg-amber-100 border border-amber-300 text-amber-900 p-3 rounded-xl font-bold text-sm text-center mx-auto max-w-lg shadow-md">
                                ⚠️ Sesi antrean hari ini belum dibuka resmi oleh Admin.
                            </div>
                        @endif

                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 pt-4 px-4 overflow-y-auto max-h-[400px] custom-scrollbar">
                            @php
                                $uniqueLayanan = isset($layananList) ? $layananList->unique('nama_layanan') : collect();
                            @endphp

                            @forelse($uniqueLayanan as $layanan)
                                <button type="button" onclick="pilihLayanan('{{ $layanan->id }}', this)"
                                    class="layanan-btn bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-extrabold text-xs py-4 px-3 rounded-2xl shadow-md transition-all duration-200 text-center uppercase tracking-wide border-4 border-transparent flex items-center justify-center min-h-[80px] hover:scale-[1.02] active:scale-95">
                                    {{ $layanan->nama_layanan }}
                                </button>
                            @empty
                                <div class="col-span-full py-8 text-white/70 text-center font-bold text-lg border-2 border-dashed border-white/20 rounded-2xl">
                                    Belum ada data layanan tersedia.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-8 mt-auto border-t border-white/10 px-4">
                        <button type="button" onclick="tutupPilihanLayanan()"
                            class="text-white hover:text-ut-yellow font-bold text-sm transition flex items-center gap-2 uppercase tracking-wide bg-white/5 px-6 py-3 rounded-full hover:bg-white/10">
                            ← Kembali Edit Data
                        </button>
                        
                        <button type="submit" id="btn-submit-antrean" disabled
                            class="bg-ut-yellow hover:bg-ut-yellowHover text-ut-blue font-black px-10 py-4 rounded-full shadow-[0_0_20px_rgba(252,211,77,0.4)] opacity-50 cursor-not-allowed transition duration-300 uppercase tracking-widest text-lg transform hover:-translate-y-1">
                            Ambil Antrean
                        </button>
                    </div>
                </div>

            </div>
        </form>

    </main>

    <!-- FOOTER -->     
    <footer class="w-full text-center text-sm font-bold text-slate-400 mt-8 pb-4">
        &copy; {{ date('Y') }} Universitas Terbuka Surabaya. All rights reserved.
    </footer>

    <!-- INTERACTION SCRIPT -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const successAlert = document.querySelector('.bg-emerald-50');
            const errorAlert = document.querySelector('.bg-red-50');

            [successAlert, errorAlert].forEach(alertEl => {
                if (alertEl) {
                    setTimeout(() => {
                        alertEl.style.transition = "opacity 0.5s ease, transform 0.5s ease";
                        alertEl.style.opacity = "0";
                        alertEl.style.transform = "translateY(-10px)";
                        setTimeout(() => alertEl.remove(), 500);
                    }, 3000); // Alert akan mulai memudar setelah 3 detik
                }
            });
        });
        
        function mulaiIsiForm() {
            document.getElementById('input-nama').value = '';
            document.getElementById('input-nim').value = '';
            document.getElementById('input-nohp').value = '';
            document.getElementById('input-kendala').value = '';

            tutupPilihanLayanan();

            document.getElementById('section-welcome').classList.add('hidden');
            document.getElementById('form-kiosk').classList.remove('hidden');
        }

        function kembaliKeWelcome() {
            document.getElementById('form-kiosk').classList.add('hidden');
            document.getElementById('section-welcome').classList.remove('hidden');
        }

        function bukaPilihanLayanan() {
                const nama = document.getElementById('input-nama').value.trim();
                const nohp = document.getElementById('input-nohp').value.trim();
                const kendala = document.getElementById('input-kendala').value.trim();

                if (!nama || !nohp || !kendala) {
                    alert('Nama, No. HP, dan Kendala wajib diisi terlebih dahulu sebelum melanjutkan!');
                    
                    if (!nama) {
                        document.getElementById('input-nama').focus();
                    } else if (!nohp) {
                        document.getElementById('input-nohp').focus();
                    } else {
                        document.getElementById('input-kendala').focus();
                    }
                    return;
                }

                document.getElementById('display-nama-mhs').innerText = nama;
                const card = document.getElementById('kiosk-card');
                const rightSection = document.getElementById('section-pilih-layanan');
                const wrapperLanjut = document.getElementById('wrapper-btn-lanjut');

                card.classList.remove('max-w-[450px]');
                card.classList.add('max-w-[95%]', 'xl:max-w-[1250px]');

                rightSection.classList.remove('hidden');
                rightSection.classList.add('flex');
                
                setTimeout(() => {
                    rightSection.classList.remove('opacity-0');
                    rightSection.classList.add('opacity-100');
                }, 50);

                wrapperLanjut.classList.add('opacity-30', 'pointer-events-none');
                document.querySelectorAll('#left-panel input').forEach(inp => inp.readOnly = true);
            }
    

        function tutupPilihanLayanan() {
            const card = document.getElementById('kiosk-card');
            const rightSection = document.getElementById('section-pilih-layanan');
            const wrapperLanjut = document.getElementById('wrapper-btn-lanjut');

            card.classList.remove('max-w-[95%]', 'xl:max-w-[1250px]');
            card.classList.add('max-w-[450px]');

            // Pudarkan dulu opacity-nya
            rightSection.classList.remove('opacity-100');
            rightSection.classList.add('opacity-0');

            // Hapus dari layout total setelah animasi pudarnya selesai
            setTimeout(() => {
                rightSection.classList.add('hidden');
                rightSection.classList.remove('flex');
            }, 500);

            wrapperLanjut.classList.remove('opacity-30', 'pointer-events-none');
            document.querySelectorAll('#left-panel input').forEach(inp => inp.readOnly = false);

            document.getElementById('input-layanan-id').value = '';
            document.querySelectorAll('.layanan-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'text-ut-blue', 'border-white', 'shadow-[0_0_15px_rgba(255,255,255,0.6)]');
                btn.classList.add('bg-ut-yellow', 'text-ut-blue', 'border-transparent');
            });

            const btnSubmit = document.getElementById('btn-submit-antrean');
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
        }

        function pilihLayanan(id, element) {
            document.getElementById('input-layanan-id').value = id;

            document.querySelectorAll('.layanan-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'text-ut-blue', 'border-white', 'shadow-[0_0_15px_rgba(255,255,255,0.6)]');
                btn.classList.add('bg-ut-yellow', 'text-ut-blue', 'border-transparent');
            });

            element.classList.remove('bg-ut-yellow', 'text-ut-blue', 'border-transparent');
            element.classList.add('bg-white', 'text-ut-blue', 'border-white', 'shadow-[0_0_15px_rgba(255,255,255,0.6)]');

            const btnSubmit = document.getElementById('btn-submit-antrean');
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    </script>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(252, 211, 77, 0.5);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(252, 211, 77, 0.8);
        }
    </style>
</body>
</html>