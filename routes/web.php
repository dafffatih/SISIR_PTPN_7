<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SheetController;
use App\Http\Controllers\UserController;

// Halaman awal
Route::get('/', function () {
    return Auth::check() ? redirect('/dashboard') : redirect('/login');
});

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Semua fitur wajib login
Route::middleware(['auth'])->group(function () {

    // Dashboard (semua role)
    Route::get('/dashboard', [SheetController::class, 'dashboard'])
        ->name('dashboard')
        ->middleware('role:admin,staff,viewer');

    // Manajemen Kontrak (admin + staff)
    Route::get('/kontrak', [SheetController::class, 'index'])
        ->name('kontrak')
        ->middleware('role:admin,staff');

    Route::post('/kontrak/store', [SheetController::class, 'store'])
        ->name('kontrak.store')
        ->middleware('role:admin,staff');

    // NOTE: kalau update kamu pakai row/id, sebaiknya /kontrak/update/{row}
    Route::put('/kontrak/update', [SheetController::class, 'update'])
        ->name('kontrak.update')
        ->middleware('role:admin,staff');

    Route::delete('/kontrak/delete/{row}', [SheetController::class, 'destroy'])
        ->name('kontrak.destroy')
        ->middleware('role:admin,staff');

    // =========================
    // USER MANAGEMENT (admin only)
    // =========================
    Route::middleware('role:admin')->group(function () {

        // halaman user management (ambil data dari DB)
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');

        // tambah user
        Route::post('/users', [UserController::class, 'store'])
            ->name('users.store');

        // update user
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->name('users.update');

        // hapus user
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy');
    });

    // Upload & Export (admin + staff)
    Route::get('/upload-export', function () {
        return view('dashboard.upload_export');
    })->name('upload.export')->middleware('role:admin,staff');
});
