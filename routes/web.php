<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SheetController; // Controller Lama (Folder kontrak)
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ListKontrakController; // Controller Baru (Folder list_kontrak)

// Halaman awal
Route::get('/', function () {
    return Auth::check() ? redirect('/dashboard') : redirect('/login');
});

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Middleware Group
Route::middleware(['auth', 'active'])->group(function () {

    // 1. Dashboard
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])
        ->name('dashboard')
        ->middleware('role:admin,staff,viewer');

    // ====================================================
    // TAB 1: MANAJEMEN KONTRAK (SheetController)
    // ====================================================
    // PERBAIKAN: Name dikembalikan ke 'kontrak' agar view lama tidak error
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
    // TAB 2: LIST KONTRAK (ListKontrakController)
    // ====================================================
    // Route baru untuk folder list_kontrak
    Route::get('/list-kontrak', [ListKontrakController::class, 'index'])
        ->name('list-kontrak.index')
        ->middleware('role:admin,staff,viewer'); 


    // ====================================================
    // USER MANAGEMENT (admin only)
    // ====================================================
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Upload & Export
    Route::get('/upload-export', function () {
        return view('dashboard.upload_export');
    })->name('upload.export')->middleware('role:admin,staff');

    // ====================================================
    // SETTINGS
    // ====================================================
    Route::get('/settings', [SettingController::class, 'index'])
        ->name('settings')
        ->middleware('role:admin,staff,viewer'); 

    Route::post('/settings', [SettingController::class, 'update'])
        ->name('settings.update')
        ->middleware('role:admin,staff');
        
    Route::delete('/settings/{id}', [SettingController::class, 'destroy'])
        ->name('settings.destroy')
        ->middleware('role:admin,staff');
});