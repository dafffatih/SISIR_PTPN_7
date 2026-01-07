<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Menangani proses autentikasi
     */
    public function login(Request $request)
    {
        // 1. Validasi input
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password tidak boleh kosong.',
        ]);

        // 2. Remember me
        $remember = $request->has('remember');

        // 3. Proses login
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // ✅ CATAT LAST LOGIN (AMAN DARI INTELEPHENSE)
            User::where('id', Auth::id())->update([
                'last_login_at' => now(),
            ]);

            // 4. Redirect sesuai role
            return $this->redirectByRole(Auth::user()->role);
        }

        // 5. Jika gagal login
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
