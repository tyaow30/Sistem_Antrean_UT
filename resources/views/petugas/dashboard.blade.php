<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Loket {{ $user->loket->nomor_loket ?? '-' }}</title>
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
                        brand: {
                            yellow: '#FFDC5F',
                            darkblue: '#0A4595',
                            lightblue: '#4A7ED4',
                            cardblue: '#3B72CB',
                            red: '#D12727',
                            green: '#48BB78',
                            btnblue: '#1E60C6',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-slate-900 antialiased p-6 lg:p-10">

    <div class="max-w-5xl mx-auto space-y-6">

        {{-- LOGO UNIVERSITAS TERBUKA --}}
        <div class="flex items-center justify-start">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Universitas Terbuka" class="h-14 w-auto object-contain">
        </div>

        {{-- BANNER HEADER KUNING --}}
        <div class="bg-brand-yellow p-6 rounded-3xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Petugas : {{ $user->name }}
                </h1>
                <p class="text-lg font-bold text-slate-800 mt-1">
                    {{ $user->gerai->nama_gerai ?? 'Gerai Utama' }} | <span class="text-brand-darkblue">LOKET {{ $user->loket->nomor_loket ?? '-' }}</span>
                </p>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="bg-brand-red hover:bg-red-700 text-white font-black text-sm px-8 py-3 rounded-2xl shadow-md transition-all uppercase tracking-wider">
                    LOG OUT
                </button>
            </form>
        </div>

        {{-- SECTION UTAMA: SEDANG DILAYANI --}}
        <div class="bg-brand-darkblue p-6 sm:p-8 rounded-3xl text-white shadow-xl">
            <h2 class="text-xs sm:text-sm font-extrabold tracking-wider uppercase mb-2 text-white/90">
                SEDANG DILAYANI
            </h2>

            @if($antreanSaatIni)
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                    {{-- NOMOR ANTREAN AKTIF --}}
                    <div class="md:col-span-7 flex items-center justify-center border-b md:border-b-0 md:border-r border-white/20 pb-6 md:pb-0 md:pr-6 min-h-[160px]">
                        <span class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white text-center">
                            {{ $antreanSaatIni->kode_antrean }}
                        </span>
                    </div>

                    {{-- TOMBOL AKSI PEMANGGILAN --}}
                    <div class="md:col-span-5 space-y-3">
                        {{-- Tombol Panggil Ulang --}}
                        <form action="{{ route('petugas.panggil-ulang', $antreanSaatIni->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-brand-yellow hover:bg-yellow-300 text-brand-darkblue font-extrabold text-sm py-3 rounded-xl shadow-md transition-all uppercase tracking-wider">
                                PANGGIL ULANG
                            </button>
                        </form>

                        {{-- Tombol Selesai --}}
                        <form action="{{ route('petugas.update-status', $antreanSaatIni->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="DONE">
                            <button type="submit" class="w-full bg-brand-green hover:bg-emerald-600 text-white font-extrabold text-sm py-3 rounded-xl shadow-md transition-all uppercase tracking-wider">
                                SELESAI
                            </button>
                        </form>

                        {{-- Tombol Lewati --}}
                        <form action="{{ route('petugas.update-status', $antreanSaatIni->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="SKIPPED">
                            <button type="submit" class="w-full bg-brand-red hover:bg-red-700 text-white font-extrabold text-sm py-3 rounded-xl shadow-md transition-all uppercase tracking-wider">
                                LEWATI
                            </button>
                        </form>
                    </div>
                </div>
            @else
                {{-- TAMPILAN JIKA BELUM ADA ANTREAN DIPANGGIL --}}
                <div class="flex flex-col md:flex-row items-center justify-between gap-6 py-4">
                    <div class="text-white/70 text-lg font-bold">
                        Belum ada antrean dipanggil
                    </div>
                    <form action="{{ route('petugas.panggil-next') }}" method="POST" class="w-full md:w-auto">
                        @csrf
                        <button type="submit" class="w-full md:w-auto bg-brand-yellow hover:bg-yellow-300 text-brand-darkblue font-black text-base px-10 py-4 rounded-2xl shadow-lg transition-all uppercase tracking-wider">
                            PANGGIL NEXT
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- BOTTOM GRID: ANTREAN LOKET & ANTREAN BANTUAN --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

            {{-- KOLOM ANTREAN LOKET INI --}}
            <div class="bg-brand-darkblue p-6 rounded-3xl text-white shadow-xl min-h-[320px] flex flex-col">
                <h3 class="text-base font-bold text-white mb-4">
                    Antrean Loket Ini ({{ $antreanSaya->count() }})
                </h3>

                <div class="space-y-3 overflow-y-auto max-h-72 pr-1 flex-1">
                    @forelse($antreanSaya as $item)
                        <div class="bg-brand-cardblue p-3.5 rounded-2xl flex items-center justify-between shadow-inner">
                            <span class="font-extrabold text-lg text-white tracking-wide">
                                {{ $item->kode_antrean }}
                            </span>
                            <span class="text-xs font-semibold text-white/90">
                                {{ $item->created_at->format('H:i') }}
                            </span>
                        </div>
                    @empty
                        <div class="flex items-center justify-center h-full py-10 text-white/60 text-sm font-medium">
                            Tidak ada antrean menunggu
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- KOLOM ANTREAN BANTUAN --}}
            <div class="bg-brand-darkblue p-6 rounded-3xl text-white shadow-xl min-h-[320px] flex flex-col">
                <h3 class="text-base font-bold text-white mb-4">
                    Antrean Bantuan
                </h3>

                <div class="space-y-3 overflow-y-auto max-h-72 pr-1 flex-1">
                    @forelse($antreanBantuan as $item)
                        <div class="bg-brand-yellow p-3.5 rounded-2xl flex items-center justify-between text-slate-900 shadow-md">
                            <div>
                                <span class="font-black text-lg text-brand-darkblue block leading-tight">
                                    {{ $item->kode_antrean }}
                                </span>
                                <span class="text-xs font-bold text-brand-darkblue/80">
                                    Asal : Loket {{ $item->loketAsal->nomor_loket ?? '-' }}
                                </span>
                            </div>

                            <form action="{{ route('petugas.panggil-bantuan', $item->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-brand-lightblue hover:bg-blue-600 text-white text-xs font-extrabold px-4 py-2 rounded-xl transition-all shadow">
                                    Bantu
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="flex items-center justify-center h-full py-10 text-white/60 text-sm font-medium">
                            Tidak ada antrean butuh bantuan
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    {{-- SCRIPT HEARTBEAT --}}
    <script>
        function kirimHeartbeat() {
            fetch("{{ route('petugas.heartbeat') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                console.log("Heartbeat:", data);
            })
            .catch(error => {
                console.error("Heartbeat gagal:", error);
            });
        }

        // Kirim heartbeat pertama kali & berkala
        kirimHeartbeat();
        setInterval(kirimHeartbeat, 10000);
    </script>

</body>
</html>