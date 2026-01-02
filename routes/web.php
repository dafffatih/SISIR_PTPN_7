<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Halaman awal: Jika belum login ke halaman login, jika sudah ke dashboard
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

// Auth Route
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard & Fitur Lain (Semua bisa akses setelah login)
Route::middleware('auth')->group(function () {
    
    Route::get('/dashboard', function () {
        return view('dashboard.dashboard'); // Kita akan buat file ini
    })->name('dashboard');

    Route::get('/pemasaran', function () {
        return "Halaman Data Pemasaran";
    });

    Route::get('/users', function () {
        return "Halaman Manajemen User";
    });
});