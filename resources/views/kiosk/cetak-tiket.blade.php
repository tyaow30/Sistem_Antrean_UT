<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Tiket Antrean</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS Khusus Thermal Printer 58mm */
        @media print {
            body {
                background: none !important;
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }
            .no-print {
                display: none !important;
            }
            .ticket-card {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: 58mm !important;
                padding: 2px !important;
                margin: 0 auto !important;
                border-radius: 0 !important;
            }
            @page {
                size: 58mm auto; /* Diubah ke ukuran printer 58mm */
                margin: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

    {{-- CARD PREVIEW TIKET --}}
    <div class="ticket-card bg-white w-[300px] p-4 rounded-2xl shadow-md text-center text-black flex flex-col items-center mb-6">
        
        {{-- LOGO --}}
        <img src="{{ asset('images/logo-UT-1.png') }}" alt="Universitas Terbuka" class="h-8 w-auto object-contain mb-1">

        {{-- ALAMAT --}}
        <p class="text-[10px] font-semibold leading-tight text-gray-800">
            Jl. Dr. Ir. H. Soekarno No. 559<br>
            MERR Rungkut Surabaya
        </p>

        <hr class="w-full border-t border-black my-1.5">

        {{-- HEADER TIKET --}}
        <p class="text-[10px] font-medium tracking-wide">-- TIKET ANTREAN --</p>
        <h2 class="text-xs font-bold uppercase tracking-wide leading-tight">
            {{ $loket->gerai->nama_gerai ?? 'GERAI UTAMA' }} (G{{ $loket->gerai_id }})
        </h2>

        {{-- KODE ANTREAN (Menggunakan whitespace-nowrap & ukuran menyesuaikan agar tidak terpotong) --}}
        <div class="my-2">
            <h1 class="text-2xl font-black tracking-tight text-black whitespace-nowrap">
                {{ $antrean->kode_antrean }}
            </h1>
        </div>

        {{-- LOKET TUJUAN --}}
        <div class="mb-1">
            <p class="text-[10px] font-medium tracking-wider text-gray-700 uppercase leading-none">LOKET TUJUAN</p>
            <p class="text-base font-bold text-black uppercase leading-tight mt-0.5">
                LOKET {{ $loket->nomor_loket }}
            </p>
        </div>

        <hr class="w-full border-t border-black my-1.5">

        <p class="text-[10px] text-gray-800 leading-tight">Harap menunggu nomor Anda dipanggil.</p>
        <p class="text-[10px] font-semibold text-gray-900 mt-0.5">
            {{ \Carbon\Carbon::parse($antrean->waktu_ambil)->format('d/m/Y | H:i') }} WIB
        </p>

        <hr class="w-full border-t border-black my-1.5">

        <p class="text-[10px] font-bold text-black">Terima Kasih Atas Kunjungan Anda</p>
    </div>

    {{-- AKSI TOMBOL (AKAN HILANG SAAT DICETAK) --}}
    <div class="no-print flex flex-col gap-3 w-[300px]">
        
        {{-- FORM CETAK & KONFIRMASI --}}
        <form id="formConfirm" action="{{ route('kiosk.tiket.confirm', $antrean->id) }}" method="POST">
            @csrf
            <button type="button" onclick="eksekusiCetak()" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3.5 px-6 rounded-xl shadow-md transition uppercase text-lg">
                CETAK TIKET
            </button>
        </form>

        {{-- FORM BATAL --}}
        <form action="{{ route('kiosk.tiket.cancel', $antrean->id) }}" method="POST">
            @csrf
            <button type="submit" 
                class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 font-extrabold py-3.5 px-6 rounded-xl transition uppercase text-lg">
                BATAL
            </button>
        </form>
    </div>

    <script>
        function eksekusiCetak() {
            window.print();
            setTimeout(function() {
                document.getElementById('formConfirm').submit();
            }, 1000);
        }
    </script>
</body>
</html>