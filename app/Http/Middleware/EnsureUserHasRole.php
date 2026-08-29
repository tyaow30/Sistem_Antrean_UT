<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Cek apakah user sudah login
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // 2. Cek apakah role user cocok (pakai strtoupper agar aman dari beda kapital)
        if (strtoupper($request->user()->role) !== strtoupper($role)) {
            // Arahkan aman ke Kiosk/Landing Page (BUKAN ke /dashboard biar gak looping)
            return redirect()->route('kiosk.index')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}