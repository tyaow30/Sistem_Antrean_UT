<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tiket Antrean</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #area-cetak,
            #area-cetak * {
                visibility: visible;
            }

            #area-cetak {
                position: absolute;
                left: 0;
                top: 0;
                width: 58mm;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-2xl shadow-xl text-center max-w-sm w-full border">

        <!-- TIKET -->
        <div id="area-cetak">

            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-widest">
                Nomor Antrean
            </h2>

            <div class="text-5xl font-extrabold text-blue-600 my-5">
                {{ $antrean->kode_antrean }}
            </div>

            <div class="text-gray-700 font-medium">
                <p>
                    Loket {{ $loket->nomor_loket }}
                </p>

                <p class="text-xs text-gray-400 mt-2">
                    Harap menunggu nomor dipanggil
                </p>
            </div>

            <p class="text-xs text-gray-400 mt-4">
                {{ now()->format('d/m/Y H:i') }}
            </p>

        </div>

        <!-- AREA TOMBOL -->
        <div class="no-print mt-8">

            <p class="text-sm text-gray-500 mb-4">
                Nomor Anda sudah dibuat.
                Silakan cetak tiket untuk masuk ke antrean.
            </p>

            <!-- FORM CETAK -->
            <form
                id="formCetak"
                action="{{ route('kiosk.tiket.confirm', $antrean->id) }}"
                method="POST">

                @csrf

                <button
                    type="button"
                    onclick="cetakTiket()"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition">
                    CETAK TIKET
                </button>

            </form>

            <!-- FORM BATAL -->
            <form
                action="{{ route('kiosk.tiket.cancel', $antrean->id) }}"
                method="POST"
                class="mt-3">

                @csrf

                <button
                    type="submit"
                    class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 px-6 rounded-xl transition">
                    BATAL
                </button>

            </form>

            <p class="text-xs text-gray-400 mt-4">
                Jika Anda membatalkan, nomor antrean ini tidak akan digunakan.
            </p>

        </div>

    </div>

    <script>
        function cetakTiket() {

            // Buka dialog print browser
            window.print();

            // Setelah proses print selesai,
            // nomor dikonfirmasi menjadi antrean resmi.
            window.addEventListener('afterprint', function () {

                setTimeout(function () {
                    document.getElementById('formCetak').submit();
                }, 1000);

            }, { once: true });
        }
    </script>

</body>

</html>