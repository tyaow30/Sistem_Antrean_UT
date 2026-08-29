<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Loket - {{ $gerai->nama_gerai }}</title>
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
<body class="bg-white min-h-screen flex flex-col justify-between items-center p-6 font-sans antialiased text-slate-900">

    <div class="w-full max-w-4xl mx-auto text-center flex flex-col items-center space-y-8 my-auto">

        {{-- LOGO UNIVERSITAS TERBUKA --}}
        <div class="flex justify-center mb-2">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Universitas Terbuka" class="h-20 lg:h-24 w-auto object-contain">
        </div>

        {{-- JUDUL GERAI --}}
        <div class="space-y-2">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-brand-darkblue tracking-tight uppercase">
                {{ $gerai->nama_gerai }}
            </h1>
            <p class="text-lg sm:text-xl font-semibold text-brand-yellow tracking-wide">
                Silahkan pilih loket untuk ambil nomor antrean
            </p>
        </div>

        {{-- ALERT ERROR --}}
        @if(session('error'))
            <div class="w-full max-w-xl bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl font-bold text-center shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- WADAH DAFTAR LOKET --}}
        <div class="flex flex-wrap justify-center items-center gap-6 max-w-4xl mx-auto my-8">
            
            @forelse($loketList as $loket)
                {{-- CARD PER LOKET --}}
                <div class="flex flex-col items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-100 w-72 text-center">
                    <h3 class="text-2xl font-black text-black uppercase tracking-tight">
                        LOKET {{ $loket->nomor_loket }}
                    </h3>
                    <p class="text-sm text-gray-600 font-medium mt-1 mb-4">
                        Antrean Saat Ini : <span class="font-bold text-black">{{ str_pad($loket->total_antrean ?? 0, 2, '0', STR_PAD_LEFT) }}</span>
                    </p>

                    {{-- FORM UNTUK AMBIL ANTREAN --}}
                    <form action="{{ route('kiosk.cetak', $loket->id) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full bg-amber-400 hover:bg-amber-500 text-blue-900 font-extrabold py-3.5 px-6 rounded-xl shadow-md transition uppercase tracking-wide">
                            AMBIL ANTREAN
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <p class="text-lg font-semibold">Tidak ada loket yang aktif saat ini.</p>
                </div>
            @endforelse

        </div>

    </div>

    {{-- KEMBALI KE PILIHAN GERAI --}}
    <div class="pb-4">
        <a href="{{ route('kiosk.index') }}" class="inline-flex items-center gap-2 text-slate-900 hover:text-brand-darkblue font-medium text-base transition-colors">
            <span>&larr;</span> Kembali ke Pilihan Gerai
        </a>
    </div>

</body>
</html>