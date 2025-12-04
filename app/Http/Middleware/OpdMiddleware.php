<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class OpdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ✅ Cek apakah user sudah login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // ✅ Cek apakah user punya role 'opd'
        if (!$user->hasRole('opd')) {
            // ✅ LOGOUT & CLEAR SESSION jika bukan role OPD
            Cache::forget('filament.user.' . $user->id);
            Cache::forget('filament.navigation.' . $user->id);
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->withErrors(['error' => 'Anda tidak memiliki akses ke panel OPD.']);
        }

        // ✅ OPTIONAL: Cek apakah user punya opd_code (jika diperlukan)
        if (empty($user->opd_code)) {
            Cache::forget('filament.user.' . $user->id);
            Auth::logout();
            $request->session()->invalidate();
            
            return redirect()->route('login')
                ->withErrors(['error' => 'Akun Anda belum terdaftar pada OPD tertentu.']);
        }

        return $next($request);
    }
}