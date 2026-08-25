<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Petugas - Loket {{ $user->loket->nomor_loket ?? '-' }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-6">

    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow mb-6">

            <div>
                <h1 class="text-xl font-bold text-gray-800">
                    Petugas: {{ $user->name }}
                </h1>

                <p class="text-sm text-gray-500">
                    Gerai {{ $user->gerai->nama_gerai ?? '-' }}
                    |
                    <span class="font-bold text-blue-600">
                        LOKET {{ $user->loket->nomor_loket ?? '-' }}
                    </span>
                </p>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf

                <button
                    type="submit"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold"
                >
                    Logout
                </button>
            </form>

        </div>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- ================================================= -->
            <!-- KOLOM 1: PEMANGGILAN AKTIF -->
            <!-- ================================================= -->

            <div class="bg-white p-6 rounded-xl shadow border border-blue-200">

                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">
                    Sedang Dilayani
                </h2>


                @if($antreanSaatIni)

                    <!-- Nomor Antrean -->
                    <div class="text-center my-6">

                        <div class="text-5xl font-black text-blue-600 mb-2">
                            {{ $antreanSaatIni->kode_antrean }}
                        </div>

                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">
                            {{ $antreanSaatIni->status }}
                        </span>

                    </div>


                    <!-- ============================= -->
                    <!-- TOMBOL PANGGIL ULANG -->
                    <!-- ============================= -->

                    <form
                        action="{{ route('petugas.panggil-ulang', $antreanSaatIni->id) }}"
                        method="POST"
                        class="mb-2"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-lg"
                        >
                            PANGGIL ULANG
                        </button>

                    </form>


                    <!-- ============================= -->
                    <!-- TOMBOL SELESAI & LEWATI -->
                    <!-- ============================= -->

                    <div class="grid grid-cols-2 gap-2">

                        <!-- Selesai -->
                        <form
                            action="{{ route('petugas.update-status', $antreanSaatIni->id) }}"
                            method="POST"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="status"
                                value="DONE"
                            >

                            <button
                                type="submit"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-lg"
                            >
                                Selesai
                            </button>

                        </form>


                        <!-- Lewati -->
                        <form
                            action="{{ route('petugas.update-status', $antreanSaatIni->id) }}"
                            method="POST"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="status"
                                value="SKIPPED"
                            >

                            <button
                                type="submit"
                                class="w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 rounded-lg"
                            >
                                Lewati
                            </button>

                        </form>

                    </div>


                @else

                    <!-- ============================= -->
                    <!-- BELUM ADA ANTREAN -->
                    <!-- ============================= -->

                    <div class="text-center py-8 text-gray-400">
                        Belum ada antrean dipanggil
                    </div>


                    <!-- Panggil Next -->
                    <form
                        action="{{ route('petugas.panggil-next') }}"
                        method="POST"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg text-lg"
                        >
                            PANGGIL NEXT
                        </button>

                    </form>

                @endif

            </div>


            <!-- ================================================= -->
            <!-- KOLOM 2: ANTREAN LOKET SAYA -->
            <!-- ================================================= -->

            <div class="bg-white p-6 rounded-xl shadow">

                <h2 class="text-lg font-bold text-gray-800 mb-4">
                    Antrean Loket Ini ({{ $antreanSaya->count() }})
                </h2>


                <div class="space-y-2 max-h-80 overflow-y-auto">

                    @forelse($antreanSaya as $item)

                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg border">

                            <span class="font-bold text-gray-700">
                                {{ $item->kode_antrean }}
                            </span>

                            <span class="text-xs text-gray-400">
                                {{ $item->created_at->format('H:i') }}
                            </span>

                        </div>

                    @empty

                        <p class="text-xs text-gray-400 text-center py-4">
                            Tidak ada antrean menunggu
                        </p>

                    @endforelse

                </div>

            </div>


            <!-- ================================================= -->
            <!-- KOLOM 3: ANTREAN BANTUAN -->
            <!-- ================================================= -->

            <div class="bg-white p-6 rounded-xl shadow border border-amber-200">

                <h2 class="text-lg font-bold text-amber-700 mb-1">
                    Antrean Bantuan
                </h2>

                <p class="text-xs text-gray-400 mb-4">
                    Ambil antrean dari loket lain di gerai ini
                </p>


                <div class="space-y-2 max-h-80 overflow-y-auto">

                    @forelse($antreanBantuan as $item)

                        <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg border border-amber-200">

                            <div>

                                <span class="font-bold text-amber-900 block">
                                    {{ $item->kode_antrean }}
                                </span>

                                <span class="text-xs text-amber-600">
                                    Asal: Loket {{ $item->loketAsal->nomor_loket ?? '-' }}
                                </span>

                            </div>


                            <form
                                action="{{ route('petugas.panggil-bantuan', $item->id) }}"
                                method="POST"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-2 px-3 rounded-lg"
                                >
                                    Bantu
                                </button>

                            </form>

                        </div>

                    @empty

                        <p class="text-xs text-gray-400 text-center py-4">
                            Tidak ada antrean butuh bantuan
                        </p>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

    <script>
        function kirimHeartbeat() {
            fetch("{{ route('petugas.heartbeat') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                console.log("Heartbeat:", data);
            })
            .catch(error => {
                console.error("Heartbeat gagal:", error);
            });
        }

        // Kirim heartbeat pertama kali saat halaman dibuka
        kirimHeartbeat();

        // Kirim heartbeat setiap 10 detik
        setInterval(kirimHeartbeat, 10000);
    </script>

</body>

</html>