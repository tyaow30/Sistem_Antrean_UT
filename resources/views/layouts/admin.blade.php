<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PELMA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#F3F4F6] font-sans antialiased overflow-x-hidden" x-data="{ isSidebar: false }">

    <div class="min-h-screen flex flex-col relative">

        <!-- 1. TOPBAR HEADER (Ukuran & Tinggi Selalu Konsisten) -->
        <header class="bg-[#0B3B82] text-white h-20 px-4 flex items-center justify-between shadow-md relative z-20 w-full flex-shrink-0">
            <div class="flex items-center space-x-3">
                <!-- Navigasi Menu Topbar (Hanya muncul saat bukan mode Sidenav) -->
                <div 
                    x-show="!isSidebar" 
                    x-transition:enter="transition ease-out duration-200 delay-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="flex items-center space-x-3"
                >
                    <div class="bg-white p-1 rounded-xl shadow-md inline-flex items-center justify-center cursor-pointer hover:scale-105 transition-transform" @click="isSidebar = true" title="Klik untuk Buka Sidenavbar">
                        <img src="{{ asset('images/logosbykecil.png') }}" alt="Logo" class="h-10 w-auto object-contain">
                    </div>
                    
                    <a href="{{ route('admin.dashboard') }}" class="p-2.5 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-[#FACC15] text-[#0B3B82]' : 'bg-[#1D4ED8] text-white hover:bg-blue-600' }}" title="Dashboard"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M4 13h6a1 1 0 001-1V4a1 1 0 00-1-1H4a1 1 0 00-1 1v8a1 1 0 001 1zm0 7h6a1 1 0 001-1v-4a1 1 0 00-1-1H4a1 1 0 00-1 1v4a1 1 0 001 1zm10 0h6a1 1 0 001-1v-8a1 1 0 00-1-1h-6a1 1 0 00-1 1v8a1 1 0 001 1zm0-17v4a1 1 0 001 1h6a1 1 0 001-1V3a1 1 0 00-1-1h-6a1 1 0 00-1 1z"/></svg></a>
                    <a href="{{ route('admin.loket.index') }}" class="p-2.5 rounded-xl transition flex items-center justify-center {{ request()->routeIs('admin.loket.*') ? 'bg-[#FACC15] text-[#0B3B82]' : 'bg-[#1D4ED8] text-white hover:bg-blue-600' }}" title="Manajemen Loket"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9"/></svg></a>
                    <a href="{{ route('admin.layanan.index') }}" class="p-2.5 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.layanan.*') ? 'bg-[#FACC15] text-[#0B3B82]' : 'bg-[#1D4ED8] text-white hover:bg-blue-600' }}" title="Manajemen Layanan"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg></a>
                    <a href="{{ route('admin.petugas.index') }}" class="p-2.5 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.petugas.*') ? 'bg-[#FACC15] text-[#0B3B82]' : 'bg-[#1D4ED8] text-white hover:bg-blue-600' }}" title="Manajemen Petugas"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></a>
                    <a href="{{ route('admin.rekap.index') }}" class="p-2.5 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.rekap.*') ? 'bg-[#FACC15] text-[#0B3B82]' : 'bg-[#1D4ED8] text-white hover:bg-blue-600' }}" title="Rekap Laporan"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></a>
                </div>
            </div>

            <!-- PROFILE & LOGOUT -->
            <div class="flex items-center space-x-3 ml-auto">
                <div class="w-10 h-10 rounded-full bg-[#2563EB] flex items-center justify-center text-white font-bold text-lg shadow">A</div>
                <span class="font-bold text-lg hidden sm:inline">Hai, Admin!</span>
                <a x-show="!isSidebar" href="/logout" class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-xl ml-2 transition-colors duration-200" title="Log Out"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></a>
            </div>
        </header>

        <!-- AREA UTAMA (Sidenavbar + Main Content) -->
        <div class="flex-1 flex relative">

            <!-- 2. OVERLAY BACKDROP (Memberi efek redup di belakang Sidenavbar) -->
            <div 
                x-show="isSidebar" 
                x-cloak
                @click="isSidebar = false"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/30 z-30"
            ></div>

            <!-- 3. SIDENAVBAR KUNING (Menutup dari kiri tanpa mengubah dimensi Topbar) -->
            <aside 
                x-show="isSidebar" 
                x-cloak
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed top-0 left-0 bottom-0 w-64 bg-[#FACC15] flex flex-col justify-between p-4 shadow-2xl z-40"
            >
                <div>
                    <!-- LOGO & PANAH TOGGLE -->
                    <div class="flex items-center justify-between pb-4 border-b border-yellow-600/20 mb-6">
                        <img src="{{ asset('images/logosby.png') }}" alt="Logo UT" class="h-10 object-contain" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/8/8c/Logo_Universitas_Terbuka.png'">
                        <button @click="isSidebar = false" class="p-2 rounded-full hover:bg-yellow-400 text-gray-800 transition-colors duration-200" title="Tutup Sidenavbar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                        </button>
                    </div>

                    <!-- NAVIGATION LINKS -->
                    <nav class="space-y-3">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 p-3 rounded-xl font-bold transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-[#1D4ED8] text-white shadow-md' : 'text-gray-900 hover:bg-yellow-400' }}">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M4 13h6a1 1 0 001-1V4a1 1 0 00-1-1H4a1 1 0 00-1 1v8a1 1 0 001 1zm0 7h6a1 1 0 001-1v-4a1 1 0 00-1-1H4a1 1 0 00-1 1v4a1 1 0 001 1zm10 0h6a1 1 0 001-1v-8a1 1 0 00-1-1h-6a1 1 0 00-1 1v8a1 1 0 00１ １zm０－１７v４a１ １ ０ ００１ １h６a１ １ ０ ００１－１V３a１ １ ０ ００－１－１h－６a１ １ ０ ００－１ １z"/></svg>
                            <span>Dashboard</span>
                        </a>

                        <!-- Manajemen Loket -->
                        <a href="{{ route('admin.loket.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-2xl font-bold text-gray-900 hover:bg-yellow-300 transition {{ request()->routeIs('admin.loket.*') ? 'bg-blue-600 text-white hover:bg-blue-700' : '' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9"/>
                            </svg>
                            <span>Manajemen Loket</span>
                        </a>

                        <a href="{{ route('admin.layanan.index') }}" class="flex items-center space-x-3 p-3 rounded-xl font-bold transition-all duration-200 {{ request()->routeIs('admin.layanan.*') ? 'bg-[#1D4ED8] text-white shadow-md' : 'text-gray-900 hover:bg-yellow-400' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                            <span>Manajemen Layanan</span>
                        </a>

                        <a href="{{ route('admin.petugas.index') }}" class="flex items-center space-x-3 p-3 rounded-xl font-bold transition-all duration-200 {{ request()->routeIs('admin.petugas.*') ? 'bg-[#1D4ED8] text-white shadow-md' : 'text-gray-900 hover:bg-yellow-400' }}">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <span>Manajemen Petugas</span>
                        </a>

                        <a href="{{ route('admin.rekap.index') }}" class="flex items-center space-x-3 p-3 rounded-xl font-bold transition-all duration-200 {{ request()->routeIs('admin.rekap.*') ? 'bg-[#1D4ED8] text-white shadow-md' : 'text-gray-900 hover:bg-yellow-400' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Rekap Laporan</span>
                        </a>
                    </nav>
                </div>

                <!-- LOGOUT -->
                <a href="/logout" class="flex items-center justify-center space-x-2 bg-[#DC2626] text-white font-bold p-3 rounded-xl hover:bg-red-700 transition-colors duration-200 mt-6">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>LOG OUT</span>
                </a>
            </aside>

            <!-- 4. CONTENT AREA -->
            <main class="p-6 flex-1 w-full min-w-0">
                @yield('content')
            </main>

        </div>

        <footer class="text-center p-4 text-xs text-gray-500 font-medium">
            &copy; {{ date('Y') }} Universitas Terbuka. All rights reserved.
        </footer>
    </div>
</body>
</html>