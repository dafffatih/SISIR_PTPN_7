<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SheetController;     
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ListKontrakController;
use App\Http\Controllers\ExportController;   

/*
|--------------------------------------------------------------------------
| WEB ROUTES (STABIL & AMAN)
|--------------------------------------------------------------------------
*/

// Halaman awal
Route::get('/', function () {
    return Auth::check() ? redirect('/dashboard') : redirect('/login');
});

// ===============================
// AUTH ROUTES
// ===============================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ===============================
// PROTECTED ROUTES (Login Wajib)
// ===============================
Route::middleware(['auth', 'active'])->group(function () {

    // 1. DASHBOARD (Semua Role: Admin, Staff, Viewer)
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])
        ->name('dashboard');
        // Middleware role tidak perlu ditulis ulang karena logic di controller/middleware sudah handle login

    // 2. SET YEAR (Semua Role)
    Route::get('/set-year/{year}', [DashboardController::class, 'setYear'])
        ->name('set.year');


    // =================================================================
    // ZONA KHUSUS ADMIN & STAFF (VIEWER DILARANG MASUK SINI)
    // =================================================================
    Route::middleware(['role:admin,staff'])->group(function () {
        
        // --- MANAJEMEN KONTRAK (SheetController) ---
        Route::get('/kontrak', [SheetController::class, 'index'])->name('kontrak');
        Route::post('/kontrak/store', [SheetController::class, 'store'])->name('kontrak.store');
        Route::put('/kontrak/update', [SheetController::class, 'update'])->name('kontrak.update');
        Route::delete('/kontrak/delete/{row}', [SheetController::class, 'destroy'])->name('kontrak.destroy');
        Route::post('/kontrak/sync', [SheetController::class, 'syncManual'])->name('kontrak.sync');

        // --- LIST KONTRAK (ListKontrakController) ---
        // PENTING: Saya menaruh blok ini DI SINI agar Viewer tidak bisa akses
        Route::get('/list-kontrak', [ListKontrakController::class, 'index'])->name('list-kontrak.index');
        
        // Group CRUD List Kontrak (Prefix tetap dipertahankan agar tidak error)
        Route::prefix('list-kontrak')->name('list-kontrak.')->group(function () {
            // Index dihapus dari sini karena sudah didefinisikan di baris atas (line 60)
            Route::post('/store', [ListKontrakController::class, 'store'])->name('store');
            Route::put('/update', [ListKontrakController::class, 'update'])->name('update');
            Route::delete('/{row}', [ListKontrakController::class, 'destroy'])->name('destroy');
        });

        // --- UPLOAD & EXPORT ---
        Route::get('/upload-export', function () {
            return view('exports.upload_export');
        })->name('upload.export');

        Route::post('/upload-export/kontrak-detail', [ExportController::class, 'export'])
            ->name('upload.export.kontrak.detail');

        // --- SETTINGS ---
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::delete('/settings/{id}', [SettingController::class, 'destroy'])->name('settings.destroy');
    });

    // =================================================================
    // ZONA SUPER ADMIN (Hanya Admin)
    // =================================================================
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

});