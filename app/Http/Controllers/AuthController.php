<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter; // Pastikan ini ada
use Illuminate\Support\Str;                 // Pastikan ini ada
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login
     */
    public function showLogin()
    {
        // Jika sudah login, arahkan sesuai role
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    /**
     * Menangani proses autentikasi (Secure Version)
     */
    public function login(Request $request)
    {
        // 1. Validasi Input
        // 'alpha_dash' hanya mengizinkan huruf, angka, strip (-), dan underscore (_)
        // Ini efektif mencegah XSS (seperti <script>) dan SQL Injection dasar.
        $credentials = $request->validate([
            'username' => ['required', 'string', 'alpha_dash'], 
            'password' => ['required', 'string'],
        ], [
            'username.required'   => 'Username wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan underscore.',
            'password.required'   => 'Password tidak boleh kosong.',
        ]);

        // 2. Tentukan Key untuk Rate Limiter (gabungan Username + IP)
        // Str::lower memastikan 'Admin' dan 'admin' dihitung sebagai user yang sama
        $throttleKey = Str::lower($credentials['username']) . '|' . $request->ip();

        // 3. Cek Rate Limiter (Brute Force Protection)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) { // Max 5 kali salah
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', "Terlalu banyak percobaan login. Silakan coba lagi dalam $seconds detik.");
        }

        // 4. Cek Status User (Active/Inactive) sebelum attempt login
        $user = User::where('username', $credentials['username'])->first();
        
        // Jika user ada TAPI statusnya inactive
        if ($user && ($user->status ?? 'active') === 'inactive') {
            return back()
                ->with('error', 'Akun kamu sedang nonaktif. Hubungi admin.')
                ->withInput();
        }

        // 5. Coba Login ke Database
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            // --- LOGIN SUKSES ---
            
            // Reset hitungan gagal login karena sudah berhasil
            RateLimiter::clear($throttleKey);
            
            // Regenerate Session ID (Mencegah Session Fixation)
            $request->session()->regenerate();

            // Catat waktu login terakhir
            User::where('id', Auth::id())->update([
                'last_login_at' => now(),
            ]);

            return $this->redirectByRole(Auth::user()->role);
        }

        // --- LOGIN GAGAL ---
        
        // Catat 1 kegagalan di Rate Limiter
        // Kunci akan diblokir selama 60 detik jika sudah gagal 5 kali
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