<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Tiket - PELMA UT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* PERBAIKAN KHUSUS PRINT PREVIEW CHROME / PRINTER THERMAL */
        @media print {
            @page { 
                size: 80mm auto; /* Mengikuti panjang struk asli */
                margin: 0; 
            } 
            
            /* Reset penuh flexbox & height layar agar tidak bikin 30 halaman */
            html, body { 
                width: 80mm !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important; 
                padding: 0 !important; 
                background: #ffffff !important; 
                display: block !important;
                overflow: visible !important;
            }
            
            /* Sembunyikan elemen non-cetak */
            .no-print { 
                display: none !important; 
            }
            
            /* Paksa kotak tiket masuk dalam 1 halaman utuh */
            .ticket-box { 
                width: 80mm !important; 
                max-width: 80mm !important; 
                box-shadow: none !important; 
                border: none !important; 
                border-radius: 0 !important;
                padding: 4mm 3mm !important; 
                margin: 0 !important; 
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            img {
                max-width: 100% !important;
                display: block !important;
                margin: 0 auto !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col items-center justify-center font-['Plus_Jakarta_Sans'] p-4 text-slate-800">

    <!-- CONTAINER TIKET -->
    <div class="ticket-box bg-white p-6 rounded-2xl shadow-xl w-full max-w-[340px] text-center border border-slate-200">
        
        <!-- LOGO UT -->
        <div class="flex justify-center mb-2">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Logo UT" class="h-10 object-contain mx-auto">
        </div>
        
        <div class="mb-2">
            <h2 class="text-sm font-extrabold text-slate-900 tracking-tight uppercase">UNIVERSITAS TERBUKA</h2>
            <p class="text-[10px] text-slate-500 font-medium leading-tight">
                Jl. Dr. Ir. H. Soekarno No. 559, MERR Rungkut, Surabaya
            </p>
        </div>

        <div class="border-b border-dashed border-slate-400 my-2"></div>

        <div class="my-1">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">-- TIKET ANTREAN --</p>
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide mt-0.5">
                {{ $antrean->serviceAwal->nama_layanan ?? 'LAYANAN PELMA' }}
            </h3>
        </div>

        <!-- NOMOR ANTREAN UTAMA -->
        <div class="py-1 my-1">
            <span class="block text-6xl font-black text-slate-900 tracking-tight leading-none">
                {{ sprintf('%03d', $antrean->nomor_antrean) }}
            </span>
        </div>

        <!-- LOKET TUJUAN -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 my-2">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">LOKET TUJUAN</p>
            <p class="text-xl font-black text-slate-900 uppercase tracking-tight mt-0.5">
                LOKET {{ $loket->nomor_loket }}
            </p>
        </div>

        <p class="text-[10px] text-slate-500 font-medium mt-2">
            Harap menunggu nomor Anda dipanggil
        </p>
        <p class="text-[11px] font-bold text-slate-700">
            {{ \Carbon\Carbon::parse($antrean->waktu_ambil)->format('d/m/Y') }} &bull; {{ \Carbon\Carbon::parse($antrean->waktu_ambil)->format('H:i') }} WIB
        </p>

        <div class="border-b border-dashed border-slate-400 my-2"></div>

        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            TERIMA KASIH ATAS KUNJUNGAN ANDA
        </p>
    </div>

    <!-- TOMBOL CETAK (HANYA MUNCUL DI LAYAR) -->
    <div class="no-print mt-6 w-full max-w-[340px]">
        <button type="button" onclick="window.print()" 
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3.5 px-4 rounded-xl shadow-lg transition duration-200 flex items-center justify-center gap-2 text-base tracking-wide uppercase">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            PRINT TIKET
        </button>
    </div>

</body>
</html>