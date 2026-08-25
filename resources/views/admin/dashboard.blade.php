<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrator</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-6">

    <div class="max-w-6xl mx-auto space-y-6">

        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}
        <div class="flex justify-between items-center bg-white p-6 rounded-2xl shadow">

            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Panel Administrator
                </h1>

                <p class="text-sm text-gray-500">
                    Kelola operasional dan rekapitulasi antrean
                </p>
            </div>

            {{-- Tombol Buka / Tutup Sesi --}}
            <form action="{{ route('admin.toggle-sesi') }}" method="POST">
                @csrf

                @if(isset($sesi) && $sesi->is_open)

                    <button
                        type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-3 rounded-xl shadow transition"
                    >
                        TUTUP SESI HARI INI
                    </button>

                @else

                    <button
                        type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl shadow transition"
                    >
                        BUKA SESI HARI INI
                    </button>

                @endif
            </form>

        </div>


        {{-- ========================================================= --}}
        {{-- NOTIFICATION --}}
        {{-- ========================================================= --}}

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl">
                {{ session('error') }}
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- STATISTIK --}}
        {{-- ========================================================= --}}

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-500">
                <p class="text-xs text-gray-400 font-bold uppercase">
                    Total Tiket
                </p>

                <p class="text-3xl font-black text-gray-800 mt-1">
                    {{ $totalTiket ?? 0 }}
                </p>
            </div>


            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-amber-500">
                <p class="text-xs text-gray-400 font-bold uppercase">
                    Menunggu
                </p>

                <p class="text-3xl font-black text-amber-600 mt-1">
                    {{ $menunggu ?? 0 }}
                </p>
            </div>


            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-emerald-500">
                <p class="text-xs text-gray-400 font-bold uppercase">
                    Selesai Dilayani
                </p>

                <p class="text-3xl font-black text-emerald-600 mt-1">
                    {{ $selesai ?? 0 }}
                </p>
            </div>


            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-rose-500">
                <p class="text-xs text-gray-400 font-bold uppercase">
                    Dilewati / Batal
                </p>

                <p class="text-3xl font-black text-rose-600 mt-1">
                    {{ $dilewati ?? 0 }}
                </p>
            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- MASTER DATA GERAI & LOKET --}}
        {{-- ========================================================= --}}

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- ===================================================== --}}
            {{-- TAMBAH GERAI --}}
            {{-- ===================================================== --}}

            <div class="bg-white p-6 rounded-xl shadow-sm">

                <h3 class="text-lg font-bold mb-4 text-gray-800">
                    Tambah Gerai Baru
                </h3>

                <form
                    action="{{ route('admin.gerai.store') }}"
                    method="POST"
                    class="flex gap-3"
                >

                    @csrf

                    <input
                        type="text"
                        name="nama_gerai"
                        placeholder="Nama Gerai (misal: Gerai Utama)"
                        class="border border-gray-300 rounded-lg p-2.5 flex-1 focus:ring-2 focus:ring-blue-500 outline-none"
                        required
                    >

                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-semibold transition"
                    >
                        Simpan
                    </button>

                </form>

            </div>


            {{-- ===================================================== --}}
            {{-- DAFTAR GERAI --}}
            {{-- ===================================================== --}}

            <div class="bg-white p-6 rounded-xl shadow-sm">

                <h3 class="text-lg font-bold mb-4 text-gray-800">
                    Daftar Gerai
                </h3>

                <div class="space-y-3">

                    @forelse($geraiList as $g)

                        <div class="flex items-center justify-between border rounded-lg p-4">

                            <div>

                                <p class="font-semibold text-gray-800">
                                    {{ $g->nama_gerai }}
                                </p>

                                <p class="text-sm text-gray-500">
                                    {{ $g->loket->count() }} Loket
                                </p>

                                @if($g->is_active)
                                    <span class="text-xs text-green-600 font-semibold">
                                        ● Aktif
                                    </span>
                                @else
                                    <span class="text-xs text-red-500 font-semibold">
                                        ● Nonaktif
                                    </span>
                                @endif

                            </div>


                            <div class="flex gap-2">

                                {{-- EDIT GERAI --}}
                                <form
                                    action="{{ route('admin.gerai.update', $g->id) }}"
                                    method="POST"
                                    class="flex gap-2"
                                >

                                    @csrf
                                    @method('PUT')

                                    <input
                                        type="text"
                                        name="nama_gerai"
                                        value="{{ $g->nama_gerai }}"
                                        class="border border-gray-300 rounded-lg px-3 py-2"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-semibold"
                                    >
                                        Edit
                                    </button>

                                </form>


                                {{-- AKTIF / NONAKTIF GERAI --}}
                                <form
                                    action="{{ route('admin.gerai.toggle', $g->id) }}"
                                    method="POST"
                                >

                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="{{ $g->is_active
                                            ? 'bg-red-500 hover:bg-red-600'
                                            : 'bg-green-500 hover:bg-green-600'
                                        }} text-white px-4 py-2 rounded-lg font-semibold"
                                    >

                                        {{ $g->is_active ? 'Nonaktifkan' : 'Aktifkan' }}

                                    </button>

                                </form>

                            </div>

                        </div>

                    @empty

                        <p class="text-gray-400 text-center py-4">
                            Belum ada gerai.
                        </p>

                    @endforelse

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- TAMBAH LOKET --}}
            {{-- ===================================================== --}}

            <div class="bg-white p-6 rounded-xl shadow-sm">

                <h3 class="text-lg font-bold mb-4 text-gray-800">
                    Tambah Loket Baru
                </h3>

                <form
                    action="{{ route('admin.loket.store') }}"
                    method="POST"
                    class="flex gap-3"
                >

                    @csrf

                    <select
                        name="gerai_id"
                        class="border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none"
                        required
                    >

                        <option value="">
                            -- Pilih Gerai --
                        </option>

                        @foreach($geraiList as $g)

                            <option value="{{ $g->id }}">
                                {{ $g->nama_gerai }}
                            </option>

                        @endforeach

                    </select>


                    <input
                        type="number"
                        name="nomor_loket"
                        placeholder="Nomor Loket"
                        class="border border-gray-300 rounded-lg p-2.5 flex-1 focus:ring-2 focus:ring-green-500 outline-none"
                        required
                        min="1"
                    >


                    <button
                        type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg font-semibold transition"
                    >
                        Simpan
                    </button>

                </form>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- DAFTAR LOKET --}}
        {{-- ========================================================= --}}

        <div class="bg-white p-6 rounded-xl shadow-sm">

            <h3 class="text-lg font-bold mb-4 text-gray-800">
                Daftar Loket
            </h3>

            <div class="space-y-3">

                @forelse($geraiList as $g)

                    @foreach($g->loket as $l)

                        <div class="flex items-center justify-between border rounded-lg p-4">

                            <div>

                                <p class="font-semibold text-gray-800">
                                    {{ $g->nama_gerai }}
                                    - Loket {{ $l->nomor_loket }}
                                </p>

                                <p class="text-sm text-gray-500">
                                    Status: {{ $l->status }}
                                </p>

                                @if($l->active_petugas_id)
                                    <p class="text-xs text-blue-600 mt-1">
                                        Sedang digunakan petugas
                                    </p>
                                @endif

                            </div>


                            {{-- EDIT LOKET --}}
                            <form
                                action="{{ route('admin.loket.update', $l->id) }}"
                                method="POST"
                                class="flex gap-2"
                            >

                                @csrf
                                @method('PUT')

                                <input
                                    type="hidden"
                                    name="gerai_id"
                                    value="{{ $l->gerai_id }}"
                                >

                                <input
                                    type="number"
                                    name="nomor_loket"
                                    value="{{ $l->nomor_loket }}"
                                    min="1"
                                    required
                                    class="border border-gray-300 rounded-lg px-3 py-2 w-28"
                                >

                                <button
                                    type="submit"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-semibold"
                                >
                                    Edit
                                </button>

                            </form>

                        </div>

                    @endforeach

                @empty

                    <p class="text-gray-400 text-center py-4">
                        Belum ada loket.
                    </p>

                @endforelse

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- TAMBAH PETUGAS --}}
        {{-- ========================================================= --}}

        <div class="bg-white p-6 rounded-xl shadow-sm">

            <h3 class="text-lg font-bold mb-4 text-gray-800">
                Tambah & Penugasan Petugas
            </h3>

            <form
                action="{{ route('admin.petugas.store') }}"
                method="POST"
                class="grid grid-cols-1 md:grid-cols-3 gap-4"
            >

                @csrf

                <input
                    type="text"
                    name="name"
                    placeholder="Nama Lengkap"
                    class="border border-gray-300 p-2.5 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >

                <input
                    type="email"
                    name="email"
                    placeholder="Email Petugas"
                    class="border border-gray-300 p-2.5 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Password Min. 6 Karakter"
                    class="border border-gray-300 p-2.5 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >


                <select
                    name="assigned_gerai_id"
                    class="border border-gray-300 p-2.5 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >

                    <option value="">
                        -- Tugaskan ke Gerai --
                    </option>

                    @foreach($geraiList as $g)

                        <option value="{{ $g->id }}">
                            {{ $g->nama_gerai }}
                        </option>

                    @endforeach

                </select>


                <select
                    name="assigned_loket_id"
                    class="border border-gray-300 p-2.5 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >

                    <option value="">
                        -- Tugaskan ke Loket --
                    </option>

                    @foreach($geraiList as $g)

                        @foreach($g->loket as $l)

                            <option value="{{ $l->id }}">
                                {{ $g->nama_gerai }}
                                - Loket {{ $l->nomor_loket }}
                            </option>

                        @endforeach

                    @endforeach

                </select>


                <button
                    type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold p-2.5 rounded-lg transition"
                >
                    Simpan Petugas
                </button>

            </form>

        </div>


        {{-- ========================================================= --}}
        {{-- TABEL PETUGAS --}}
        {{-- ========================================================= --}}

        <div class="bg-white p-6 rounded-xl shadow-sm">

            <div class="flex items-center justify-between mb-4">

                <div>

                    <h3 class="text-lg font-bold text-gray-800">
                        Daftar Petugas Terdaftar
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        Kelola status aktif petugas pada loket masing-masing.
                    </p>

                </div>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left border-collapse">

                    <thead>

                        <tr class="border-b bg-gray-50 text-sm text-gray-600">

                            <th class="p-3">
                                Nama Petugas
                            </th>

                            <th class="p-3">
                                Email
                            </th>

                            <th class="p-3">
                                Penugasan Gerai
                            </th>

                            <th class="p-3">
                                Penugasan Loket
                            </th>

                            <th class="p-3">
                                Status
                            </th>

                            <th class="p-3 text-center">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        @forelse($petugasList as $p)

                            @php

                                $loketPetugas = $p->assignedLoket;

                                $isActive =
                                    $loketPetugas &&
                                    $loketPetugas->status === 'ACTIVE' &&
                                    (int) $loketPetugas->active_petugas_id === (int) $p->id;

                            @endphp


                            <tr class="hover:bg-gray-50 text-sm">

                                {{-- NAMA --}}
                                <td class="p-3 font-semibold text-gray-800">

                                    {{ $p->name }}

                                </td>


                                {{-- EMAIL --}}
                                <td class="p-3 text-gray-600">

                                    {{ $p->email }}

                                </td>


                                {{-- GERAI --}}
                                <td class="p-3 text-gray-700">

                                    {{ $p->assignedGerai->nama_gerai ?? '-' }}

                                </td>


                                {{-- LOKET --}}
                                <td class="p-3 text-gray-700">

                                    @if($p->assignedLoket)

                                        Loket {{ $p->assignedLoket->nomor_loket }}

                                    @else

                                        -

                                    @endif

                                </td>


                                {{-- STATUS --}}
                                <td class="p-3">

                                    @if($isActive)

                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">

                                            ● ACTIVE

                                        </span>

                                    @else

                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">

                                            ● INACTIVE

                                        </span>

                                    @endif

                                </td>


                                {{-- AKSI --}}
                                <td class="p-3 text-center">

                                    @if($p->assignedLoket)

                                        <form
                                            action="{{ route('admin.petugas.toggle', $p->id) }}"
                                            method="POST"
                                        >

                                            @csrf
                                            @method('PATCH')


                                            @if($isActive)

                                                <button
                                                    type="submit"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition"
                                                >
                                                    Nonaktifkan
                                                </button>

                                            @else

                                                <button
                                                    type="submit"
                                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition"
                                                >
                                                    Aktifkan
                                                </button>

                                            @endif

                                        </form>

                                    @else

                                        <span class="text-gray-400 text-xs">
                                            Belum ada loket
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="p-4 text-center text-gray-400"
                                >
                                    Belum ada data petugas terdaftar.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</body>

</html>