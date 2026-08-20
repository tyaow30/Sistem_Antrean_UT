<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pilih Loket - {{ $gerai->nama_gerai }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="text-center w-full max-w-2xl p-6">
        <h1 class="text-2xl font-bold text-gray-700 mb-1">PILIH LOKET</h1>
        <h2 class="text-xl font-semibold text-blue-600 mb-6">{{ $gerai->nama_gerai }}</h2>

        @if($loketAktif->count() > 0)
            <div class="grid grid-cols-2 gap-4">
                @foreach($loketAktif as $loket)
                    <form action="{{ route('kiosk.cetak') }}" method="POST">
                        @csrf
                        <input type="hidden" name="gerai_id" value="{{ $gerai->id }}">
                        <input type="hidden" name="loket_id" value="{{ $loket->id }}">
                        <button type="submit" 
                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-6 rounded-xl shadow-lg text-2xl transition">
                            LOKET {{ $loket->nomor_loket }}
                        </button>
                    </form>
                @endforeach
            </div>
        @else
            <!-- Tampilan jika tidak ada loket dengan petugas aktif -->
            <div class="bg-white p-8 rounded-xl shadow-md border border-gray-200">
                <h3 class="text-xl font-bold text-red-600 mb-2">BELUM ADA LOKET AKTIF</h3>
                <p class="text-gray-600 mb-6">Saat ini belum ada loket yang sedang melayani.</p>
                <a href="{{ route('kiosk.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg">
                    KEMBALI
                </a>
            </div>
        @endif
    </div>
</body>
</html>