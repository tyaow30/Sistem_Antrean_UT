<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Antrean - Pilih Gerai</title>
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
                            yellow: '#FFD453',
                            yellowHover: '#F6C83B',
                            darkblue: '#0A4595',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white min-h-screen flex flex-col items-center justify-center p-6 font-sans antialiased text-slate-900">

    <div class="w-full max-w-4xl mx-auto text-center flex flex-col items-center space-y-8">

        {{-- LOGO UNIVERSITAS TERBUKA --}}
        <div class="flex justify-center mb-2">
            <img src="{{ asset('images/logo-UT-1.png') }}" alt="Universitas Terbuka" class="h-20 lg:h-24 w-auto object-contain">
        </div>

        {{-- JUDUL HALAMAN --}}
        <div class="space-y-2">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-brand-darkblue tracking-tight">
                SELAMAT DATANG!
            </h1>
            <p class="text-lg sm:text-xl font-semibold text-brand-yellow tracking-wide">
                Silahkan pilih gerai tujuan untuk mengambil antrean
            </p>
        </div>

        {{-- ALERT ERROR (JIKA SESI DITUTUP / GERAI NONAKTIF) --}}
        @if(session('error'))
            <div class="w-full max-w-xl bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl font-bold text-center shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- CONTAINER DASHBOARD BUTTONS --}}
        <div class="w-full pt-4 flex justify-center">
            <div class="flex flex-wrap justify-center gap-6 max-w-3xl w-full">
                @forelse($gerai as $g)
                    <a href="{{ route('kiosk.gerai', $g->id) }}" 
                       class="w-full sm:w-[calc(50%-12px)] max-w-xs bg-brand-yellow hover:bg-brand-yellowHover text-brand-darkblue font-extrabold text-2xl py-8 px-6 rounded-2xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-1 flex items-center justify-center text-center uppercase leading-snug">
                        {{ $g->nama_gerai }}
                    </a>
                @empty
                    <div class="w-full bg-red-50 border border-red-200 rounded-2xl p-6 text-red-600 font-bold text-lg">
                        Belum ada gerai yang aktif saat ini.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</body>
</html>