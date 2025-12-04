<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifikatorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->hasRole('verifikator')) {
            // ✅ LOGOUT & CLEAR SESSION
            Cache::forget('filament.user.' . $user->id);
            Cache::forget('filament.navigation.' . $user->id);
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->withErrors(['error' => 'Anda tidak memiliki akses ke panel Verifikator.']);
        }

        return $next($request);
    }
}