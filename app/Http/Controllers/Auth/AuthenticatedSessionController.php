<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Loket;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Cek khusus untuk PETUGAS
        if ($user->role === 'PETUGAS') {

            // Kalau akun sedang dipakai di perangkat/browser lain
            if ($user->active_session_id !== null) {

                // Hapus session login yang baru saja dibuat
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->with('error', 'Akun petugas ini sedang digunakan di perangkat lain. Silakan logout terlebih dahulu.');
            }

            // Simpan session ID login saat ini
            $user->update([
                'active_session_id' => $request->session()->getId(),
            ]);
        }

        if ($user->role === 'ADMIN') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'PETUGAS') {
            return redirect()->route('petugas.dashboard');
        }

        return redirect('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Jika user yang logout adalah PETUGAS
        if ($user && $user->role === 'PETUGAS') {

            // Nonaktifkan loket petugas
            if ($user->assigned_loket_id) {
                Loket::where('id', $user->assigned_loket_id)->update([
                    'status' => 'INACTIVE',
                    'active_petugas_id' => null,
                ]);
            }

            // Hapus session login aktif
            $user->update([
                'active_session_id' => null,
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}