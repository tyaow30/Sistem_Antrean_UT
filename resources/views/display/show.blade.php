<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Antrean - UNIVERSITAS TERBUKA Surabaya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/js/app.js'])

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            yellow: '#FFDC5F',
                            darkblue: '#0A4595',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-slate-900 min-h-screen flex flex-col justify-between p-6 lg:p-10 font-sans antialiased relative">

    <!-- OVERLAY MINTA IZIN SUARA (Solusi Audio Bisu) -->
    <div id="audio-overlay" class="fixed inset-0 bg-slate-900/95 z-50 flex flex-col items-center justify-center cursor-pointer transition-opacity duration-500">
        <svg class="w-24 h-24 mb-6 text-brand-yellow animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
        </svg>
        <h1 class="text-4xl lg:text-5xl font-black text-white mb-4 tracking-wide text-center">KLIK LAYAR UNTUK MEMULAI</h1>
        <p class="text-xl text-white/80 font-medium text-center">Interaksi diperlukan agar suara panggilan otomatis aktif</p>
    </div>

    <!-- HEADER / TOP BAR -->
    <header class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Universitas Terbuka" class="h-16 w-auto object-contain">
        </div>
        <div id="clock" class="text-3xl lg:text-4xl font-light tracking-widest text-slate-800">
            00 . 00 . 00
        </div>
    </header>

    <!-- MAIN DISPLAY CONTENT -->
    <main class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch my-auto">
        
        <!-- UTAMA: NOMOR DIPANGGIL -->
        <section class="lg:col-span-8 bg-brand-darkblue rounded-3xl p-8 lg:p-12 flex flex-col justify-between text-white shadow-xl min-h-[480px]">
            <div>
                <span class="text-2xl lg:text-3xl font-medium tracking-wide text-white/90">
                    Nomor Dipanggil :
                </span>
            </div>

            <div class="my-auto text-center py-6">
                <div id="nomor-aktif" class="text-6xl sm:text-7xl lg:text-8xl xl:text-[9.5rem] font-black text-brand-yellow tracking-tight leading-none">
                    ---
                </div>
            </div>

            <div class="text-center">
                <p class="text-xl lg:text-2xl font-normal text-white/90 mb-1">
                    Silakan menuju ke
                </p>
                <p id="loket-aktif" class="text-2xl lg:text-4xl font-extrabold text-brand-yellow uppercase tracking-wider">
                    MENUNGGU PANGGILAN
                </p>
            </div>
        </section>

        <!-- PANGGILAN TERAKHIR -->
        <section class="lg:col-span-4 bg-brand-yellow rounded-3xl p-6 flex flex-col justify-start shadow-md">
            <h2 class="text-xl font-bold text-slate-900 mb-4 pb-2 border-b-2 border-slate-900/20">
                Panggilan Terakhir
            </h2>

            <div id="daftar-riwayat" class="space-y-3 overflow-y-auto max-h-[460px] pr-1 flex-1">
                <p class="text-slate-700 text-center py-6 font-medium">Belum ada riwayat</p>
            </div>
        </section>

    </main>

    <!-- FOOTER COPYRIGHT -->
    <footer class="mt-6 text-center space-y-1">
        <p class="text-sm font-medium text-slate-500">
            © 2026 Universitas Terbuka. All rights reserved.
        </p>
    </footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let isAudioAllowed = false;
        const overlay = document.getElementById('audio-overlay');

        document.body.addEventListener('click', () => {
            if(!isAudioAllowed) {
                isAudioAllowed = true;
                overlay.classList.add('opacity-0', 'pointer-events-none');
                setTimeout(() => overlay.remove(), 500); 
                
                const pancingan = new SpeechSynthesisUtterance('');
                window.speechSynthesis.speak(pancingan);
            }
        });

        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').innerText = `${hours} . ${minutes} . ${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function formatNomorAntrean(antrean) {
            let nomorPad = String(antrean.nomor_antrean).padStart(3, '0');
            
            let nomor = nomorPad;
            if (!nomor.includes('L')) {
                let prefixLoket = antrean.loket_asal_id || antrean.loket_pelayanan_id;
                nomor = `L${prefixLoket}-${nomorPad}`;
            }
            return nomor;
        }

        function speak(text) {
            if (!isAudioAllowed || !('speechSynthesis' in window)) return;

            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.85; 
            window.speechSynthesis.speak(utterance);
        }

        async function fetchInitialData() {
            try {
                const response = await fetch("{{ route('api.display.latest') }}");
                const data = await response.json();

                if (data.aktif) {
                    const antrean = data.aktif;
                    const formatNomor = formatNomorAntrean(antrean);
                    
                    const loketTujuan = antrean.loket_pelayanan_id; 

                    document.getElementById('nomor-aktif').innerText = formatNomor;
                    document.getElementById('loket-aktif').innerText = `MENUJU LOKET ${loketTujuan}`;
                }

                updateRiwayatUI(data.riwayat);
            } catch (err) {
                console.error("Gagal memuat data awal display:", err);
            }
        }

        function updateRiwayatUI(riwayat) {
            if (riwayat && riwayat.length > 0) {
                const listHtml = riwayat.map(item => {
                    const formatNomor = formatNomorAntrean(item);
                    return `
                        <div class="bg-brand-darkblue text-white text-center py-3.5 px-4 rounded-xl shadow-sm">
                            <span class="text-2xl lg:text-3xl font-extrabold tracking-wider block">
                                ${formatNomor}
                            </span>
                        </div>
                    `;
                }).join('');
                document.getElementById('daftar-riwayat').innerHTML = listHtml;
            }
        }

        fetchInitialData();

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof window.Echo !== 'undefined') {
                window.Echo.channel('display-antrean')
                    .listen('.antrean.dipanggil', (e) => {
                        const antrean = e.antrean;
                        
                        const formatNomor = formatNomorAntrean(antrean);
                        const loketTujuan = antrean.loket_pelayanan_id; 

                        document.getElementById('nomor-aktif').innerText = formatNomor;
                        document.getElementById('loket-aktif').innerText = `MENUJU LOKET ${loketTujuan}`;

                        const teksSuara = formatNomor.replace('-', ' '); 
                        speak(`Nomor antrean, ${teksSuara}, silakan menuju ke loket, ${loketTujuan}`);

                        fetchInitialData();
                    });
            }
        });
    </script>
</body>
</html>