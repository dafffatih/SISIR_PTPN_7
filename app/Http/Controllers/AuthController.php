<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter; 
use Illuminate\Support\Str;                 
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    /**
     * Menangani proses autentikasi
     */
    public function login(Request $request)
    {
        // Validasi Input
        $credentials = $request->validate([
            'username' => ['required', 'string', 'alpha_dash'], 
            'password' => ['required', 'string'],
        ], [
            'username.required'   => 'Username wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan underscore.',
            'password.required'   => 'Password tidak boleh kosong.',
        ]);
        $throttleKey = Str::lower($credentials['username']) . '|' . $request->ip();

        // Brute Force Protection
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) { 
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', "Terlalu banyak percobaan login. Silakan coba lagi dalam $seconds detik.");
        }
        $user = User::where('username', $credentials['username'])->first();
        if ($user && ($user->status ?? 'active') === 'inactive') {
            return back()
                ->with('error', 'Akun kamu sedang nonaktif. Hubungi admin.')
                ->withInput();
        }
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            User::where('id', Auth::id())->update([
                'last_login_at' => now(),
            ]);

            return $this->redirectByRole(Auth::user()->role);
        }
        RateLimiter::hit($throttleKey, 60);

        return back()
            ->with('error', 'Username atau password salah!')
            ->withInput();
    }

    /**
     * Menangani proses logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Redirect berdasarkan role user
     */
    private function redirectByRole(string $role)
    {
        switch ($role) {
            case 'admin':
            case 'staff':
            case 'viewer':
                return redirect('/dashboard');
            default:
                Auth::logout();
                return redirect('/login')->with('error', 'Role tidak dikenali.');
        }
    }
}