<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Display Antrean - {{ $gerai->nama_gerai }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col justify-between p-8">
    
    <!-- Header -->
    <header class="flex justify-between items-center border-b border-slate-700 pb-4">
        <h1 class="text-3xl font-extrabold tracking-wide text-blue-400">{{ $gerai->nama_gerai }}</h1>
        <div id="clock" class="text-2xl font-mono text-slate-400">00:00:00</div>
    </header>

    <!-- Main Display Section -->
    <main class="grid grid-cols-1 lg:grid-cols-3 gap-8 my-auto py-6">
        
        <!-- Panggilan Utama (Besar) -->
        <section class="lg:col-span-2 bg-slate-800 border-2 border-blue-500 rounded-3xl p-10 flex flex-col items-center justify-center shadow-2xl text-center">
            <span class="text-xl uppercase font-bold tracking-widest text-slate-400 mb-2">Nomor Dipanggil</span>
            <div id="nomor-aktif" class="text-8xl lg:text-9xl font-black text-amber-400 my-4">---</div>
            <div id="loket-aktif" class="text-3xl font-bold text-blue-300">Menunggu Panggilan...</div>
        </section>

        <!-- Riwayat Panggilan -->
        <section class="bg-slate-800 rounded-3xl p-6 border border-slate-700 flex flex-col">
            <h2 class="text-lg font-bold text-slate-300 border-b border-slate-700 pb-3 mb-4">Panggilan Terakhir</h2>
            <div id="daftar-riwayat" class="space-y-3 flex-1 overflow-y-auto">
                <p class="text-slate-500 text-center py-4">Belum ada riwayat</p>
            </div>
        </section>
    </main>

    <!-- Footer Status -->
    <footer class="bg-slate-800 rounded-xl p-4 text-center text-xs text-slate-500">
        Klik layar sekali untuk mengaktifkan Suara Pemanggilan (TTS)
    </footer>

    <script>
        let lastCalledId = null;
        let isAudioAllowed = false;

        // Izinkan audio diputar setelah ada interaksi pengguna
        document.body.addEventListener('click', () => {
            isAudioAllowed = true;
        }, { once: true });

        // Jam Digital
        setInterval(() => {
            document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID');
        }, 1000);

        // Fungsi Bicara Text-to-Speech (Indonesian)
        function speak(text) {
            if (!isAudioAllowed || !('speechSynthesis' in window)) return;

            window.speechSynthesis.cancel(); // Hentikan suara sebelumnya jika ada
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.85; // Kecepatan bicara
            window.speechSynthesis.speak(utterance);
        }

        // Fetch Data Antrean Realtime (Polling tiap 2 detik)
        async function checkAntrean() {
            try {
                const response = await fetch("{{ route('api.display.latest', $gerai->id) }}");
                const data = await response.json();

                if (data.aktif) {
                    const nomor = data.aktif.kode_antrean;
                    const loket = data.aktif.loket_melayani ? data.aktif.loket_melayani.nomor_loket : '-';

                    document.getElementById('nomor-aktif').innerText = nomor;
                    document.getElementById('loket-aktif').innerText = `SILAKAN KE LOKET ${loket}`;

                    // Jika ada antrean baru yang dipanggil, jalankan suara
                    if (lastCalledId !== data.aktif.id) {
                        lastCalledId = data.aktif.id;
                        speak(`Nomor antrean ${nomor}, silahkan menuju ke loket ${loket}`);
                    }
                }

                // Update Riwayat
                if (data.riwayat && data.riwayat.length > 0) {
                    const listHtml = data.riwayat.map(item => `
                        <div class="flex justify-between items-center bg-slate-700/50 p-3 rounded-xl border border-slate-600">
                            <span class="text-2xl font-black text-amber-300">${item.kode_antrean}</span>
                            <span class="text-sm font-semibold text-slate-300">Loket ${item.loket_melayani ? item.loket_melayani.nomor_loket : '-'}</span>
                        </div>
                    `).join('');
                    document.getElementById('daftar-riwayat').innerHTML = listHtml;
                }
            } catch (err) {
                console.error("Gagal mengambil data display:", err);
            }
        }

        setInterval(checkAntrean, 2000);
    </script>
</body>
</html>