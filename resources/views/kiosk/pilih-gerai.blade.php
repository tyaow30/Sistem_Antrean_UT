<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Antrean - Pilih Gerai</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="text-center w-full max-w-2xl p-6">
        <h1 class="text-3xl font-bold mb-2">SELAMAT DATANG</h1>
        <p class="text-gray-600 mb-8">Silakan pilih gerai tujuan Anda</p>

        <div class="grid grid-cols-2 gap-4">
            @forelse($gerai as $g)
                <a href="{{ route('kiosk.loket', $g->id) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-6 px-4 rounded-xl shadow-lg text-xl transition">
                    {{ $g->nama_gerai }}
                </a>
            @empty
                <div class="col-span-2 text-red-500 font-semibold">
                    Belum ada gerai yang aktif saat ini.
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>