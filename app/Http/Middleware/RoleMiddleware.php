<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // belum login
        if (!$user) {
            return redirect('/login')->with('error', 'Silakan login dulu.');
        }

        // role tidak sesuai
        if (!in_array($user->role, $roles)) {
            abort(403, 'Akses ditolak. Role kamu tidak punya izin.');
        }

        return $next($request);
    }
}
