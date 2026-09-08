<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Data Diri - PELMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 flex flex-col min-h-screen items-center justify-center font-['Plus_Jakarta_Sans']">

    <div class="w-full max-w-lg bg-white p-8 rounded-3xl shadow-md border border-gray-100">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Logo UT" class="h-16 mx-auto mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Data Pengunjung</h2>
            <p class="text-gray-500 text-sm mt-1">Silakan isi data diri Anda sebelum memilih layanan</p>
        </div>

        <!-- Form ini akan mengirim data ke session lalu lanjut ke pilih loket -->
        <form action="{{ route('kiosk.simpan-form') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">NIM / NIK</label>
                <input type="text" name="nim" required placeholder="Masukkan NIM atau NIK" 
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama" required placeholder="Masukkan Nama Lengkap" 
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
            </div>

            <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-4 rounded-xl shadow-md transition-all mt-4">
                LANJUT PILIH LAYANAN &rarr;
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="{{ route('kiosk.index') }}" class="text-sm text-gray-500 hover:text-blue-700 font-medium">
                Batal & Kembali
            </a>
        </div>
    </div>

</body>
</html>