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
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password tidak boleh kosong.',
        ]);

        // ✅ CEK STATUS SEBELUM LOGIN (blok jika inactive)
        $user = User::where('username', $credentials['username'])->first();

        if ($user && ($user->status ?? 'active') === 'inactive') {
            return back()
                ->with('error', 'Akun kamu sedang nonaktif. Hubungi admin.')
                ->withInput();
        }

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // ✅ Catat last login
            User::where('id', Auth::id())->update([
                'last_login_at' => now(),
            ]);

            return $this->redirectByRole(Auth::user()->role);
        }

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
