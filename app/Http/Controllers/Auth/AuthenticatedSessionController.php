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
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            $role = strtoupper(Auth::user()->role ?? '');
            if ($role === 'ADMIN') {
                return redirect()->route('admin.dashboard');
            }
            if ($role === 'PETUGAS') {
                return redirect()->route('petugas.dashboard');
            }
            return redirect()->route('kiosk.index');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        $user->update([
            'active_session_id' => $request->session()->getId()
        ]);

        $role = strtoupper($user->role ?? '');

        if ($role === 'ADMIN') {
            return redirect()->route('admin.dashboard');
        }

        if ($role === 'PETUGAS') {
            return redirect()->route('petugas.dashboard');
        }

        return redirect()->route('kiosk.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            if (strtoupper($user->role ?? '') === 'PETUGAS' && $user->assigned_loket_id) {
                Loket::where('id', $user->assigned_loket_id)->update([
                    'status' => 'INACTIVE',
                    'active_petugas_id' => null,
                ]);
            }

            $user->update([
                'active_session_id' => null,
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}