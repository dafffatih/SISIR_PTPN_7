<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login
     */
    public function showLogin()
    {
        // Jika user sudah login, langsung lempar ke dashboard
        if (Auth::check()) {
            return redirect()->intended('dashboard');
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

        // 2. Ambil data remember me
        $remember = $request->has('remember');

        // 3. Proses login
        if (Auth::attempt($credentials, $remember)) {
            // Jika berhasil, buat ulang session untuk keamanan
            $request->session()->regenerate();

            // Arahkan ke dashboard atau halaman yang dituju sebelumnya
            return redirect()->intended('dashboard');
        }

        // 4. Jika gagal, balikkan ke login dengan pesan error
        return back()->with('error', 'Username atau password salah!')->withInput();
    }

    /**
     * Menangani proses logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Hapus session dan buat token baru
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}