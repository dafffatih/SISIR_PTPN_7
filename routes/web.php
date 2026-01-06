<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SheetController;

// Halaman awal
Route::get('/', function () {
    return Auth::check() ? redirect('/dashboard') : redirect('/login');
});

// Auth Routes
// Route::get('/sheets', [SheetController::class, 'index']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Semua fitur harus login
Route::middleware(['auth'])->group(function () {

    // Semua role boleh akses dashboard
    Route::get('/dashboard', function () {
        return view('dashboard.dashboard');
    })->name('dashboard')->middleware('role:admin,staff,viewer');

    // Admin + Staff boleh akses Manajemen Kontrak
    Route::get('/kontrak', [SheetController::class, 'index'])
        ->name('kontrak')
        ->middleware(['auth', 'role:admin,staff']);

    // TAMBAHKAN ROUTE CRUD DI BAWAH INI:
    Route::post('/kontrak/store', [SheetController::class, 'store'])
        ->name('kontrak.store')
        ->middleware(['auth', 'role:admin,staff']);

    Route::put('/kontrak/update', [SheetController::class, 'update'])
        ->name('kontrak.update')
        ->middleware(['auth', 'role:admin,staff']);

    Route::delete('/kontrak/delete/{row}', [SheetController::class, 'destroy'])
        ->name('kontrak.destroy')
        ->middleware(['auth', 'role:admin,staff']);

    // Admin only boleh akses User Management
    Route::get('/users', function () {
        return view('dashboard.users');
    })->name('users')->middleware('role:admin');

    // Admin + Staff boleh akses Upload & Export
    Route::get('/upload-export', function () {
        return view('dashboard.upload_export');
    })->name('upload.export')->middleware('role:admin,staff');
});
