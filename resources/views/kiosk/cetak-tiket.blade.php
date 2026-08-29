<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Tiket Antrean</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS Khusus Printer Thermal */
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
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            @page {
                size: 80mm auto; /* Ubah ke 58mm auto jika memakai printer 58mm */
                margin: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

    {{-- CARD PREVIEW TIKET --}}
    <div class="ticket-card bg-white w-[340px] p-6 rounded-2xl shadow-md text-center text-black flex flex-col items-center mb-6">
        
        {{-- LOGO --}}
        <img src="{{ asset('images/logo-UT-1.png') }}" alt="Universitas Terbuka" class="h-10 w-auto object-contain mb-2">

        {{-- ALAMAT --}}
        <p class="text-[11px] font-semibold leading-tight text-gray-800 px-2">
            Jl. Dr. Ir. H. Soekarno No. 559<br>
            MERR Rungkut Surabaya
        </p>

        <hr class="w-full border-t border-black my-3">

        {{-- HEADER TIKET --}}
        <p class="text-xs font-medium tracking-wide">-- TIKET ANTREAN --</p>
        <h2 class="text-base font-bold uppercase tracking-wide">
            {{ $loket->gerai->nama_gerai ?? 'GERAI UTAMA' }} (G{{ $loket->gerai_id }})
        </h2>

        {{-- KODE ANTREAN --}}
        <div class="my-4">
            <h1 class="text-4xl font-black tracking-tighter text-black">
                {{ $antrean->kode_antrean }}
            </h1>
        </div>

        {{-- LOKET TUJUAN --}}
        <div class="mb-3">
            <p class="text-xs font-medium tracking-wider text-gray-700 uppercase">LOKET TUJUAN</p>
            <p class="text-lg font-bold text-black uppercase">
                LOKET {{ $loket->nomor_loket }}
            </p>
        </div>

        <hr class="w-full border-t border-black my-2">

        <p class="text-xs text-gray-800">Harap menunggu nomor Anda dipanggil.</p>
        <p class="text-xs font-semibold text-gray-900 mt-1">
            {{ \Carbon\Carbon::parse($antrean->waktu_ambil)->format('d/m/Y | H:i') }} WIB
        </p>

        <hr class="w-full border-t border-black my-2">

        <p class="text-xs font-bold text-black mt-1">Terima Kasih Atas Kunjungan Anda</p>
    </div>

    {{-- AKSI TOMBOL (AKAN HILANG SAAT DICETAK) --}}
    <div class="no-print flex flex-col gap-3 w-[340px]">
        
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
            // 1. Munculkan jendela print thermal
            window.print();

            // 2. Kirim konfirmasi status ke database & redirect ke kiosk utama setelah cetak
            setTimeout(function() {
                document.getElementById('formConfirm').submit();
            }, 1000);
        }
    </script>
</body>
</html>