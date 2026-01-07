<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (Auth::user()->status ?? 'active') === 'inactive') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('error', 'Akun kamu sedang nonaktif.');
        }

        return $next($request);
    }
}
