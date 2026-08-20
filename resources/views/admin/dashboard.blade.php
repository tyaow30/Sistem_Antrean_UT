<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Administrator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-2xl shadow">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Panel Administrator</h1>
                <p class="text-sm text-gray-500">Kelola operasional dan rekapitulasi antrean</p>
            </div>
            
            <!-- Tombol Buka/Tutup Sesi -->
            <form action="{{ route('admin.toggle-sesi') }}" method="POST">
                @csrf
                @if($sesiHariIni && $sesiHariIni->is_open)
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-3 rounded-xl shadow">
                        TUTUP SESI HARI INI
                    </button>
                @else
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl shadow">
                        BUKA SESI HARI INI
                    </button>
                @endif
            </form>
        </div>

        <!-- Cards Stat Rekapitulasi -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-500">
                <p class="text-xs text-gray-400 font-bold uppercase">Total Tiket</p>
                <p class="text-3xl font-black text-gray-800 mt-1">{{ $totalAntrean }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-amber-500">
                <p class="text-xs text-gray-400 font-bold uppercase">Menunggu</p>
                <p class="text-3xl font-black text-amber-600 mt-1">{{ $totalWaiting }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-emerald-500">
                <p class="text-xs text-gray-400 font-bold uppercase">Selesai Dilayani</p>
                <p class="text-3xl font-black text-emerald-600 mt-1">{{ $totalSelesai }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-rose-500">
                <p class="text-xs text-gray-400 font-bold uppercase">Dilewati / Batal</p>
                <p class="text-3xl font-black text-rose-600 mt-1">{{ $totalLewat }}</p>
            </div>
        </div>

    </div>
</body>
</html>