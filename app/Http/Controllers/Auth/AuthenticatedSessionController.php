<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // ✅ CLEAR SEMUA CACHE SEBELUM LOGIN BARU
        Cache::flush();
        
        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->hasRole('opd')) {
            return redirect()->intended('/opd');
        }

        if ($user->hasRole('verifikator')) {
            return redirect()->intended('/verifikator');
        }

        if ($user->hasRole('monitoring')) {
            return redirect()->intended('/monitoring');
        }

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $userId = auth()->id();
        
        if ($userId) {
            Cache::forget('filament.user.' . $userId);
            Cache::forget('filament.navigation.' . $userId);
        }
        
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Cache::flush();

        return redirect('/');
    }
}