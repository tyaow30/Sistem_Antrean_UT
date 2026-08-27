<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Antrean Digital - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            blue: '#0A4595',
                            yellow: '#FFDC5F',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white min-h-screen flex flex-col justify-between items-center p-6 antialiased">

    {{-- KONTEN UTAMA --}}
    <div class="w-full max-w-xl my-auto flex flex-col items-center">
        
        {{-- LOGO UNIVERSITAS TERBUKA --}}
        <img src="{{ asset('images/logo-UT-1.png') }}" alt="Universitas Terbuka" class="h-16 w-auto object-contain mb-6">

        {{-- JUDUL SISTEM --}}
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight text-center mb-8">
            Sistem Antrean Digital
        </h1>

        {{-- CARD FORM LOGIN --}}
        <div class="w-full bg-brand-blue rounded-3xl p-8 sm:p-10 shadow-xl">
            <h2 class="text-2xl font-bold text-white text-center mb-8">
                Masuk ke Akun Anda
            </h2>

            {{-- ERROR VALIDASI --}}
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-500/20 border border-red-400 text-white text-xs font-medium">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                {{-- INPUT EMAIL --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-white mb-2">
                        Email
                    </label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus 
                        autocomplete="username"
                        class="w-full bg-white text-slate-800 rounded-full px-5 py-3 text-sm outline-none font-medium transition focus:ring-4 focus:ring-yellow-300/50"
                    >
                </div>

                {{-- INPUT PASSWORD --}}
                <div>
                    <label for="password" class="block text-sm font-semibold text-white mb-2">
                        Password
                    </label>
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        class="w-full bg-white text-slate-800 rounded-full px-5 py-3 text-sm outline-none font-medium transition focus:ring-4 focus:ring-yellow-300/50"
                    >
                </div>

                {{-- TOMBOL LOG IN --}}
                <div class="pt-4 text-center">
                    <button 
                        type="submit" 
                        class="w-48 bg-brand-yellow hover:bg-yellow-300 text-brand-blue py-3 rounded-full font-black text-lg shadow-md transition-all uppercase tracking-wider"
                    >
                        LOG IN
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- FOOTER --}}
    <footer class="text-xs text-slate-500 font-medium py-4 text-center">
        © 2026 Universitas Terbuka. All rights reserved.
    </footer>

</body>
</html>