# SISIR Project - Implementasi Sinkronisasi Google Drive ke Database

## ✅ Perubahan yang Telah Dilakukan

### 1. **Update GoogleSheetService** [app/Services/GoogleSheetService.php]
- ✅ Injected `Google_Service_Drive` alongside existing Sheets service
- ✅ Added `Google_Service_Drive::DRIVE_READONLY` scope to scopes
- ✅ Updated service property names: `$sheetsService` dan `$driveService`
- ✅ Created method `listSpreadsheetsInFolder($folderId)` yang:
  - Menggunakan Google Drive API untuk list files
  - Query: `mimeType = 'application/vnd.google-apps.spreadsheet' and trashed = false and '{folderId}' in parents`
  - Return array berisi `[id, name]` untuk setiap spreadsheet
- ✅ Updated `getData()` untuk menerima optional `$spreadsheetId` parameter

### 2. **Create Migration untuk Tabel 'kontraks'** [database/migrations/2026_01_07_100000_create_kontraks_table.php]
```
Columns:
- id (primary)
- nomor_kontrak (string, unique, index)
- loex (string, nullable)
- nama_pembeli (string, nullable)
- tgl_kontrak (date, nullable)
- volume (decimal 15,2, nullable)
- harga (decimal 15,2, nullable)
- nilai (decimal 15,2, nullable)
- total_layan (decimal 15,2, nullable)
- sisa_akhir (decimal 15,2, nullable)
- origin_file (string, nullable)
- timestamps
```

### 3. **Create Kontrak Eloquent Model** [app/Models/Kontrak.php]
- Model dengan fillable untuk semua fields
- Cast types untuk date dan decimal fields
- Ready untuk use dengan updateOrCreate()

### 4. **Create Artisan Command SyncDriveFolder** [app/Console/Commands/SyncDriveFolder.php]
- Signature: `php artisan sync:drive-folder`
- Hardcoded folder ID: `16YHxer1RjXTmoNTY6NoUC5BjGJXzt2ja`
- Features:
  - ✅ Inject GoogleSheetService
  - ✅ Call `listSpreadsheetsInFolder()` untuk list semua spreadsheet
  - ✅ Loop melalui setiap file dengan progress bar
  - ✅ Call `getData($fileId)` untuk fetch data dari spreadsheet
  - ✅ Map columns sesuai mapping di SheetController (columns 0-52)
  - ✅ Clean number format (remove dots dari thousands separator)
  - ✅ Parse date dari format d/m/Y
  - ✅ Use `Kontrak::updateOrCreate()` untuk avoid duplicates
  - ✅ Save filename ke `origin_file` column
  - ✅ Error handling dengan try-catch per row
  - ✅ Progress bar untuk visual feedback

### 5. **Add syncManual() Method ke SheetController** [app/Http/Controllers/SheetController.php]
```php
public function syncManual()
{
    try {
        Artisan::call('sync:drive-folder');
        return back()->with('success', 'Sinkronisasi data dari Google Drive berhasil!');
    } catch (\Exception $e) {
        return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
    }
}
```

### 6. **Add Route untuk Sync** [routes/web.php]
- POST route: `/kontrak/sync`
- Controller: `SheetController@syncManual`
- Middleware: `auth`, `active`, `role:admin,staff`
- Named route: `kontrak.sync`

### 7. **Add Sync Button ke Dashboard View** [resources/views/dashboard/kontrak/index.blade.php]
- ✅ Added `.k-btn-sync` CSS class dengan styling blue button
- ✅ Button dengan FontAwesome icon `fa-sync`
- ✅ Form pointing ke `kontrak.sync` route dengan POST method
- ✅ JavaScript `handleSyncClick()` function yang:
  - Mengubah text ke "Sedang Sinkronisasi..."
  - Disable button untuk prevent double clicks
  - Submit form

### 8. **Refactor SheetController::index()** [app/Http/Controllers/SheetController.php]
- ✅ Dari fetch Google Sheets API → fetch dari Kontrak Eloquent model
- ✅ Pagination: `Kontrak::paginate($perPage)`
- ✅ Search filter pada `nomor_kontrak` dan `nama_pembeli`
- ✅ Order by `created_at` descending
- ✅ Updated view untuk use Eloquent model properties (`$r->nomor_kontrak` instead of `$r['nomor_kontrak']`)

## 🚀 Cara Menggunakan

### Manual Sync dari Dashboard:
1. Buka halaman `/kontrak`
2. Klik button "Sync Database" di toolbar
3. Button akan berubah jadi "Sedang Sinkronisasi..." dan disable
4. Proses akan running di backend dan fetch semua spreadsheet dari folder
5. Setelah selesai, user akan kembali ke page dengan success message

### Manual Sync dari Terminal:
```bash
php artisan sync:drive-folder
```

## 📋 Struktur Mapping Spreadsheet

Column mapping dari SheetController (same structure):
```
H (index 7)   → loex
I (index 8)   → nomor_kontrak
J (index 9)   → nama_pembeli
K (index 10)  → tgl_kontrak (d/m/Y format)
L (index 11)  → volume
M (index 12)  → harga
N (index 13)  → nilai
AA (index 26) → total_layan
AB (index 27) → sisa_akhir
```

## ✨ Features

✅ Google Drive API integration untuk list spreadsheets  
✅ Otomatis sync dari Google Drive ke database  
✅ Prevent duplicates dengan `updateOrCreate()`  
✅ Error handling per row dengan detail message  
✅ Progress bar untuk long-running task  
✅ Manual trigger dari dashboard button  
✅ Auto format number (remove thousands separator dots)  
✅ Auto parse date dari format d/m/Y ke Y-m-d  
✅ Track origin file untuk audit purposes  
✅ Pagination dan search dari database (bukan API)  

## 🔍 Notes

- Migration belum di-run. Jalankan: `php artisan migrate`
- Ensure Google Drive API credentials sudah tersimpan di `storage/app/google/service-account.json`
- Folder ID hardcoded: `16YHxer1RjXTmoNTY6NoUC5BjGJXzt2ja` (update jika berbeda)
- Command bisa dijadwalkan dengan Laravel Scheduler jika diperlukan regular syncing
- Data di database sekarang menjadi source of truth (bukan Google Sheets)

## 📝 Next Steps (Optional)

Jika diperlukan:
1. Setup Laravel Scheduler untuk auto-sync periodic
2. Add audit log untuk track sync history
3. Add validation rules per field di Kontrak model
4. Add soft delete untuk kontrak yang dihapus
5. Add relationship dengan user untuk track who synced
