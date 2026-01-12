<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    // Menampilkan Halaman Settings
    public function index()
    {
        // Ambil semua setting yang berhubungan dengan google sheet
        // Urutkan agar rapi
        $settings = Setting::where('key', 'like', 'google_sheet_id%')
                           ->orderBy('key', 'asc')
                           ->get();
        
        return view('dashboard.settings', compact('settings'));
    }

    // Menyimpan / Update Data
    public function update(Request $request)
    {
        // Validasi Input
        $request->validate([
            'key_input'   => 'required|string|max:255', // Contoh: google_sheet_id_2026
            'sheet_input' => 'required|string'          // Link atau ID
        ]);

        // 1. Logika Pembersihan ID (Ambil ID dari URL Panjang)
        // Link: https://docs.google.com/spreadsheets/d/1BxiMVs.../edit
        $inputUrl = $request->input('sheet_input');
        $cleanId = $inputUrl;
        
        // Regex untuk mengambil teks di antara '/d/' dan '/' berikutnya
        if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $inputUrl, $matches)) {
            $cleanId = $matches[1];
        }

        // 2. Ambil Nama Key (dan pastikan tidak ada spasi)
        // Str::slug memastikan input "google sheet id 2026" otomatis jadi "google_sheet_id_2026"
        $keyName = Str::slug($request->input('key_input'), '_'); 

        // 3. Simpan ke Database (Update jika ada, Buat baru jika belum ada)
        Setting::updateOrCreate(
            ['key' => $keyName], 
            ['value' => $cleanId]
        );

        return redirect()->back()->with('success', "Berhasil menyimpan konfigurasi: $keyName");
    }

    public function destroy($id)
    {
        // Cari data berdasarkan ID, jika tidak ketemu akan error 404 (aman)
        $setting = Setting::findOrFail($id);
        
        // Simpan nama key untuk pesan notifikasi
        $keyName = $setting->key;

        // Eksekusi hapus
        $setting->delete();

        return redirect()->back()->with('success', "Konfigurasi '$keyName' berhasil dihapus.");
    }
}