<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tiket Antrean</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Styling Khusus Kertas Thermal (Abaikan UI Browser) */
        @media print {
            body * {
                visibility: hidden;
            }
            #area-cetak, #area-cetak * {
                visibility: visible;
            }
            #area-cetak {
                position: absolute;
                left: 0;
                top: 0;
                width: 58mm; /* Ukuran umum kertas thermal mini */
            }
        }
    </style>

    <script>
        // Otomatis print begitu halaman tiket terbuka
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-xl text-center max-w-sm w-full border">
        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-1">Nomor Anda</h2>
        <div class="text-5xl font-extrabold text-blue-600 my-4">{{ $antrean->kode_antrean }}</div>
        
        <div class="text-gray-700 font-medium mb-6">
            <p>Loket {{ $loket->nomor_loket }}</p>
            <p class="text-xs text-gray-400 mt-1">Harap menunggu nomor dipanggil</p>
        </div>

        <p class="text-xs text-gray-400 animate-pulse">Mencetak tiket...</p>
    </div>

    <script>
        // Otomatis cetak dan kembali ke halaman utama Kiosk setelah 3 detik
        window.onload = function() {
            window.print();
            setTimeout(function() {
                window.location.href = "{{ route('kiosk.index') }}";
            }, 3000);
        }
    </script>
</body>
</html>