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
| WEB ROUTES
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
// PROTECTED ROUTES
// ===============================
Route::middleware(['auth', 'active'])->group(function () {

    // ====================================================
    // DASHBOARD
    // ====================================================
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])
        ->name('dashboard')
        ->middleware('role:admin,staff,viewer');

    // ====================================================
    // TAB 1: MANAJEMEN KONTRAK (SheetController)
    // ====================================================
    Route::get('/kontrak', [SheetController::class, 'index'])
        ->name('kontrak')
        ->middleware('role:admin,staff');

    Route::post('/kontrak/store', [SheetController::class, 'store'])
        ->name('kontrak.store')
        ->middleware('role:admin,staff');

    Route::put('/kontrak/update', [SheetController::class, 'update'])
        ->name('kontrak.update')
        ->middleware('role:admin,staff');

    Route::delete('/kontrak/delete/{row}', [SheetController::class, 'destroy'])
        ->name('kontrak.destroy')
        ->middleware('role:admin,staff');

    Route::post('/kontrak/sync', [SheetController::class, 'syncManual'])
        ->name('kontrak.sync')
        ->middleware('role:admin,staff');

    // ====================================================
    // TAB 2: LIST KONTRAK
    // ====================================================
    Route::get('/list-kontrak', [ListKontrakController::class, 'index'])
        ->name('list-kontrak.index')
        ->middleware('role:admin,staff,viewer');

    // ====================================================
    // USER MANAGEMENT (ADMIN ONLY)
    // ====================================================
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // ====================================================
    // UPLOAD & EXPORT
    // ====================================================
    Route::get('/upload-export', function () {
        return view('exports.upload_export');
    })
    ->name('upload.export')
    ->middleware('role:admin,staff');

    /**
     * 🔥 ROUTE EXPORT DETAIL KONTRAK (SUDAH DIPERBAIKI)
     * 🔥 SEKARANG MENGGUNAKAN ExportController
     * 🔥 CSV & EXCEL DENGAN KOLOM PENYERAHAN AKAN MUNCUL
     */
    Route::post(
        '/upload-export/kontrak-detail',
        [ExportController::class, 'export']
    )
    ->name('upload.export.kontrak.detail')
    ->middleware('role:admin,staff');

    // ====================================================
    // SETTINGS
    // ====================================================
    Route::get('/settings', [SettingController::class, 'index'])
        ->name('settings')
        ->middleware('role:admin,staff');

    Route::post('/settings', [SettingController::class, 'update'])
        ->name('settings.update')
        ->middleware('role:admin,staff');

    Route::delete('/settings/{id}', [SettingController::class, 'destroy'])
        ->name('settings.destroy')
        ->middleware('role:admin,staff');

    // ====================================================
    // SET YEAR
    // ====================================================
    Route::get('/set-year/{year}', [DashboardController::class, 'setYear'])
        ->name('set.year');

    Route::prefix('list-kontrak')->name('list-kontrak.')->group(function () {
        // Halaman Utama
        Route::get('/', [ListKontrakController::class, 'index'])->name('index');
        
        // Fitur Tambah Data (Ini yang menyebabkan error tadi)
        Route::post('/store', [ListKontrakController::class, 'store'])->name('store');
        
        // Fitur Edit Data
        Route::put('/update', [ListKontrakController::class, 'update'])->name('update');
        
        // Fitur Hapus Data
        Route::delete('/{row}', [ListKontrakController::class, 'destroy'])->name('destroy');
    });
});
