<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Loket - {{ $gerai->nama_gerai }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-2xl w-full bg-white p-8 rounded-2xl shadow-lg text-center space-y-6">
        <h1 class="text-3xl font-extrabold text-gray-800">{{ $gerai->nama_gerai }}</h1>
        <p class="text-gray-500">Silakan pilih loket untuk ambil nomor antrean</p>

        @if(session('error'))
            <div class="bg-red-100 text-red-700 p-4 rounded-xl font-medium">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($gerai->loket as $l)
                <form action="{{ route('kiosk.cetak', $l->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-xl py-6 rounded-xl shadow transition">
                        LOKET {{ $l->nomor_loket }}
                    </button>
                </form>
            @empty
                <p class="col-span-2 text-gray-400">Belum ada loket di gerai ini.</p>
            @endforelse
        </div>

        <a href="{{ route('kiosk.index') }}" class="inline-block text-gray-500 hover:text-gray-700 underline text-sm mt-4">
            &larr; Kembali ke Pilihan Gerai
        </a>
    </div>
</body>
</html>