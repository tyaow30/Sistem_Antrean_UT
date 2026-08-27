<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Antrean - {{ $gerai->nama_gerai }}</title>
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
<body class="bg-white text-slate-900 min-h-screen flex flex-col justify-between p-6 lg:p-10 font-sans antialiased">

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
                    {{ strtoupper($gerai->nama_gerai) }} - MENUNGGU PANGGILAN
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

    <!-- FOOTER COPYRIGHT & AUDIO NOTIFICATION -->
    <footer class="mt-6 text-center space-y-1">
        <p class="text-sm font-medium text-slate-500">
            © 2026 Universitas Terbuka. All rights reserved.
        </p>
    </footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let isAudioAllowed = false;
        const namaGerai = "{{ strtoupper($gerai->nama_gerai) }}";

        // Izinkan audio diputar setelah ada interaksi pengguna
        document.body.addEventListener('click', () => {
            isAudioAllowed = true;
        }, { once: true });

        // Jam Digital dengan format titik (09 . 09 . 15)
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').innerText = `${hours} . ${minutes} . ${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Suara Text-to-Speech
        function speak(text) {
            if (!isAudioAllowed || !('speechSynthesis' in window)) return;

            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.85;
            window.speechSynthesis.speak(utterance);
        }

        // Fetch Data Initial Display
        async function fetchInitialData() {
            try {
                const response = await fetch("{{ route('api.display.latest', $gerai->id) }}");
                const data = await response.json();

                if (data.aktif) {
                    const nomor = data.aktif.kode_antrean;
                    const loket = data.aktif.loket_melayani ? data.aktif.loket_melayani.nomor_loket : '-';
                    document.getElementById('nomor-aktif').innerText = nomor;
                    document.getElementById('loket-aktif').innerText = `${namaGerai} - LOKET ${loket}`;
                }

                updateRiwayatUI(data.riwayat);
            } catch (err) {
                console.error("Gagal memuat data awal display:", err);
            }
        }

        // Render Riwayat UI (Struktur Card Biru Tua)
        function updateRiwayatUI(riwayat) {
            if (riwayat && riwayat.length > 0) {
                const listHtml = riwayat.map(item => `
                    <div class="bg-brand-darkblue text-white text-center py-3.5 px-4 rounded-xl shadow-sm">
                        <span class="text-2xl lg:text-3xl font-extrabold tracking-wider block">
                            ${item.kode_antrean}
                        </span>
                    </div>
                `).join('');
                document.getElementById('daftar-riwayat').innerHTML = listHtml;
            }
        }

        fetchInitialData();

        // WebSocket Event Listener (Laravel Reverb)
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof window.Echo !== 'undefined') {
                window.Echo.channel(`display-gerai.{{ $gerai->id }}`)
                    .listen('.antrean.dipanggil', (e) => {
                        const antrean = e.antrean;
                        const nomor = antrean.kode_antrean;
                        const loket = antrean.loket_melayani ? antrean.loket_melayani.nomor_loket : '-';

                        document.getElementById('nomor-aktif').innerText = nomor;
                        document.getElementById('loket-aktif').innerText = `${namaGerai} - LOKET ${loket}`;

                        speak(`Nomor antrean ${nomor}, silakan menuju ke ${namaGerai}, loket ${loket}`);

                        fetchInitialData();
                    });
            }
        });
    </script>
</body>
</html>