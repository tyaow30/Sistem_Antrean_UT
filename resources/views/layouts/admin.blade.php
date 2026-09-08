<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Sistem Antrean</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            yellow: '#FFDC5F',
                            darkblue: '#0A4595',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 flex min-h-screen font-sans antialiased">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-brand-darkblue text-white flex flex-col shadow-xl z-10">
        <!-- Logo Area -->
        <div class="p-6 flex flex-col items-center border-b border-white/10">
            <img src="{{ asset('images/logo-UT-2.png') }}" alt="Logo" class="h-12 w-auto object-contain mb-3 bg-white p-1 rounded">
            <h2 class="text-lg font-bold tracking-wide text-center">ADMIN PANEL</h2>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-6 space-y-2">
            <!-- Menu Dashboard -->
            <a href="{{ route('admin.dashboard') }}" 
               class="flex items-center px-4 py-3 {{ request()->routeIs('admin.dashboard') ? 'bg-brand-yellow text-brand-darkblue font-bold shadow-sm' : 'text-white hover:bg-white/10 font-medium' }} rounded-xl transition-all">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>

            <!-- Menu Manajemen Layanan -->
            <a href="{{ route('admin.layanan.index') }}" 
               class="flex items-center px-4 py-3 {{ request()->routeIs('admin.layanan.index') ? 'bg-brand-yellow text-brand-darkblue font-bold shadow-sm' : 'text-white hover:bg-white/10 font-medium' }} rounded-xl transition-all">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Manajemen Layanan
            </a>

            <!-- Menu Rekap Laporan -->
            <a href="{{ route('admin.rekap.index') }}" 
               class="flex items-center px-4 py-3 {{ request()->routeIs('admin.rekap.index') ? 'bg-brand-yellow text-brand-darkblue font-bold shadow-sm' : 'text-white hover:bg-white/10 font-medium' }} rounded-xl transition-all">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Rekap Laporan
            </a>
        </nav>

        <!-- Logout Button Area -->
        <div class="p-4 border-t border-white/10">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    LOG OUT
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 h-screen overflow-y-auto bg-slate-50">
        <!-- Top Header Panel -->
        <header class="bg-white px-8 py-5 flex justify-between items-center shadow-sm">
            <h1 class="text-2xl font-bold text-slate-800">@yield('header_title', 'Dashboard')</h1>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-brand-yellow flex items-center justify-center font-bold text-brand-darkblue">
                    A
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">Administrator</p>
                    <p class="text-xs text-slate-500">Super Admin</p>
                </div>
            </div>
        </header>

        <!-- Dynamic Content -->
        <div class="p-8">
            @yield('content')
        </div>
    </main>

</body>
</html>