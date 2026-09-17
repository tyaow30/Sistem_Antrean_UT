<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Tiket Antrean - PELMA UT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        @media print {
            @page { 
                size: 80mm auto; 
                margin: 0; 
            } 
            html, body { 
                width: 80mm !important;
                height: auto !important;
                margin: 0 !important; 
                padding: 0 !important; 
                background: #ffffff !important; 
                display: block !important;
            }
            .no-print { 
                display: none !important; 
            }
            .ticket-box { 
                width: 80mm !important; 
                max-width: 80mm !important; 
                box-shadow: none !important; 
                border: none !important; 
                border-radius: 0 !important;
                padding: 4mm 3mm !important; 
                margin: 0 !important; 
            }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-between font-['Plus_Jakarta_Sans'] p-4 md:p-8 text-slate-800">

    <!-- HEADER LOGO UT -->
    <header class="w-full text-center pt-2 pb-4 no-print">
        <img src="{{ asset('images/logosby.png') }}" alt="Universitas Terbuka Surabaya" class="h-16 mx-auto object-contain">
    </header>

    <!-- CONTAINER STRUK TIKET -->
    <div class="ticket-box bg-white p-6 rounded-3xl shadow-xl w-full max-w-[340px] text-center border border-slate-200 my-auto">
        
        <!-- LOGO STAMP TIKET -->
        <div class="flex justify-center mb-2">
            <img src="{{ asset('images/sbykeci.png') }}" alt="Logo UT" class="h-10 object-contain mx-auto">
        </div>
        
        <div class="mb-2">
            <p class="text-[10px] font-bold text-slate-600 leading-tight">
                Jl. Dr. Ir. H. Soekarno No. 559<br>MERR Rungkut Surabaya
            </p>
        </div>

        <div class="border-b border-slate-400 my-3"></div>

        <div class="my-1">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">-- TIKET ANTREAN --</p>
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide mt-0.5">
                {{ $antrean->serviceAwal->nama_layanan ?? 'PELMA' }}
            </h3>
        </div>

        <!-- NOMOR ANTREAN UTAMA -->
        <div class="py-2">
            <span class="block text-5xl md:text-6xl font-black text-slate-900 tracking-tight leading-none">
                L{{ $loket->nomor_loket }} - {{ sprintf('%03d', $antrean->nomor_antrean) }}
            </span>
        </div>

        <!-- LOKET TUJUAN -->
        <div class="my-2">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">LOKET TUJUAN</p>
            <p class="text-lg font-black text-slate-900 uppercase tracking-tight">
                {{ $loket->nomor_loket }}
            </p>
        </div>

        <div class="border-b border-slate-400 my-3"></div>

        <p class="text-[10px] text-slate-500 font-medium">
            Harap menunggu nomor Anda dipanggil.
        </p>
        <p class="text-[10px] font-bold text-slate-700 mt-0.5">
            {{ \Carbon\Carbon::parse($antrean->waktu_ambil)->format('d/m/Y') }} | {{ \Carbon\Carbon::parse($antrean->waktu_ambil)->format('H:i') }} WIB
        </p>

        <div class="border-b border-slate-400 my-3"></div>

        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">
            Terima Kasih Atas Kunjungan Anda
        </p>
    </div>

    <!-- TOMBOL AKSI: BATAL & CETAK TIKET (HANYA MUNCUL DI LAYAR) -->
    <div class="no-print mt-6 w-full max-w-[340px] flex items-center gap-4">
        
        <!-- FORM BATAL -->
        <form action="{{ route('kiosk.tiket.cancel', $antrean->id) }}" method="POST" class="w-1/3">
            @csrf
            <button type="submit" 
                class="w-full bg-red-600 hover:bg-red-700 text-white font-black py-4 rounded-2xl shadow-lg transition duration-200 text-center text-lg tracking-wide uppercase">
                BATAL
            </button>
        </form>

        <!-- FORM CETAK / KONFIRMASI -->
        <form action="{{ route('kiosk.tiket.confirm', $antrean->id) }}" method="POST" class="w-2/3">
            @csrf
            <button type="submit" onclick="window.print()" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-yellow-300 font-black py-4 rounded-2xl shadow-lg transition duration-200 text-center text-lg tracking-wide uppercase">
                CETAK TIKET
            </button>
        </form>

    </div>

    <!-- FOOTER -->
    <footer class="w-full text-center py-4 text-xs font-semibold text-slate-400 no-print">
        &copy; {{ date('Y') }} Universitas Terbuka. All rights reserved.
    </footer>

</body>
</html>