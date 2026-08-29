<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSingleSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Cek jika user sedang login
        if ($user) {
            // Jika active_session_id di DB tidak sama dengan session ID browser saat ini
            if ($user->active_session_id && $user->active_session_id !== $request->session()->getId()) {
                
                // Logout paksa
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Akun Anda telah dikeluarkan karena login di perangkat/browser lain.');
            }
        }

        return $next($request);
    }
}