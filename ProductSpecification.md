# Product Specification Document
## SISIR PTPN 7 - Sistem Informasi Sinkronisasi dan Rekap Kontrak

---

## 📋 Deskripsi Produk

**SISIR PTPN 7** adalah sistem informasi berbasis web yang dirancang untuk mengelola dan menyinkronisasi data kontrak penjualan dari Google Sheets ke database lokal. Sistem ini dibangun menggunakan **Laravel** dan terintegrasi dengan **Google Drive API** serta **Google Sheets API** untuk memungkinkan sinkronisasi data secara real-time.

---

## 🎯 Tujuan Sistem

1. **Sentralisasi Data Kontrak** - Mengumpulkan semua data kontrak dari berbagai spreadsheet di Google Drive ke satu database terpusat
2. **Otomasi Sinkronisasi** - Memungkinkan sinkronisasi data secara manual atau terjadwal dari Google Sheets
3. **Dashboard Analitik** - Menyediakan visualisasi data untuk analisis penjualan dan stok
4. **Manajemen Pengguna** - Kontrol akses berbasis peran (role-based access control)
5. **Export Data** - Kemampuan untuk mengekspor data ke format Excel/CSV

---

## 🏗️ Arsitektur Sistem

### Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| **Backend Framework** | Laravel 11 |
| **Frontend** | Blade Templates + JavaScript |
| **Database** | SQLite / MySQL |
| **External APIs** | Google Drive API, Google Sheets API |
| **Export Library** | Maatwebsite Excel |
| **Authentication** | Laravel Built-in Auth |

### Struktur Direktori Utama

```
SISIR_PTPN_7/
├── app/
│   ├── Console/Commands/     # Artisan Commands (SyncDriveFolder)
│   ├── Exports/              # Excel Export Classes
│   ├── Http/Controllers/     # Application Controllers
│   ├── Models/               # Eloquent Models
│   └── Services/             # Business Logic Services
├── database/
│   └── migrations/           # Database Migrations
├── resources/views/
│   ├── auth/                 # Login Views
│   ├── dashboard/            # Dashboard Views
│   ├── exports/              # Export Views
│   └── layouts/              # Layout Templates
└── routes/
    └── web.php               # Web Routes
```

---

## 👥 Peran Pengguna (User Roles)

| Role | Akses |
|------|-------|
| **Admin** | Full access - Dashboard, Manajemen Kontrak, Manajemen User, Settings, Export |
| **Staff** | Dashboard, Manajemen Kontrak, Settings, Export |
| **Viewer** | Dashboard (read-only), List Kontrak (read-only) |

---

## 📦 Fitur Utama

### 1. Dashboard Analitik
**Route:** `/dashboard`  
**Controller:** `DashboardController@dashboard`

**Fungsionalitas:**
- 📊 Visualisasi data penjualan dengan grafik interaktif
- 📈 Statistik Top 5 Pembeli dan Top 5 Produk
- 📅 Filter berdasarkan tahun
- 💰 Ringkasan nilai kontrak dan volume
- 🗓️ Daily Selling Price monitoring
- 📦 Tabel Stok Bebas

---

### 2. Manajemen Kontrak
**Route:** `/kontrak`  
**Controller:** `SheetController`

**Fungsionalitas:**
- ✅ **CRUD Operations** - Create, Read, Update, Delete kontrak
- 🔄 **Sync Database** - Sinkronisasi data dari Google Drive
- 🔍 **Search & Filter** - Pencarian berdasarkan nomor kontrak dan nama pembeli
- 📄 **Pagination** - Navigasi data dengan pagination
- 🖼️ **Modal Views** - Modal untuk tambah, edit, dan detail kontrak

**Data Model - Kontrak:**

| Field | Type | Deskripsi |
|-------|------|-----------|
| `id` | bigint | Primary Key |
| `nomor_kontrak` | string | Nomor kontrak (unique, indexed) |
| `loex` | string | Kode LOEX |
| `nama_pembeli` | string | Nama pembeli |
| `tgl_kontrak` | date | Tanggal kontrak |
| `volume` | decimal(15,2) | Volume kontrak |
| `harga` | decimal(15,2) | Harga per unit |
| `nilai` | decimal(15,2) | Total nilai kontrak |
| `inc_ppn` | string | Include PPN |
| `tgl_bayar` | date | Tanggal pembayaran |
| `unit` | string | Unit/Satuan |
| `mutu` | string | Mutu produk |
| `nomor_dosi` | string | Nomor DOSI |
| `tgl_dosi` | date | Tanggal DOSI |
| `port` | string | Port pengiriman |
| `kontrak_sap` | string | Nomor kontrak SAP |
| `dp_sap` | string | DP SAP |
| `so_sap` | string | SO SAP |
| `kode_do` | string | Kode DO |
| `sisa_awal` | decimal(15,2) | Sisa awal |
| `total_layan` | decimal(15,2) | Total layanan |
| `sisa_akhir` | decimal(15,2) | Sisa akhir |
| `jatuh_tempo` | date | Tanggal jatuh tempo |
| `origin_file` | string | Nama file asal |

---

### 3. List Kontrak (Read-Only)
**Route:** `/list-kontrak`  
**Controller:** `ListKontrakController@index`

**Fungsionalitas:**
- 📋 Tampilan daftar kontrak untuk viewer
- 🔍 Pencarian dan filter
- 👁️ View-only mode (tidak ada aksi edit/delete)

---

### 4. Sinkronisasi Google Drive
**Command:** `php artisan sync:drive-folder`  
**Route:** `POST /kontrak/sync`

**Fungsionalitas:**
- 📂 List semua spreadsheet di folder Google Drive
- ⬇️ Fetch data dari setiap spreadsheet
- 🔄 `updateOrCreate()` untuk mencegah duplikasi
- 📊 Progress bar untuk monitoring
- ⚠️ Error handling per baris data
- 📝 Tracking origin file untuk audit

**Column Mapping dari Google Sheets:**

| Column | Index | Field Database |
|--------|-------|----------------|
| H | 7 | loex |
| I | 8 | nomor_kontrak |
| J | 9 | nama_pembeli |
| K | 10 | tgl_kontrak |
| L | 11 | volume |
| M | 12 | harga |
| N | 13 | nilai |
| AA | 26 | total_layan |
| AB | 27 | sisa_akhir |

---

### 5. Export Data
**Route:** `/upload-export`  
**Controller:** `SheetController@exportDetailKontrak`, `ExportController`

**Fungsionalitas:**
- 📊 Export data kontrak ke Excel (.xlsx)
- 📄 Export data kontrak ke CSV
- 🎯 Filter data sebelum export
- 📅 Export berdasarkan periode

---

### 6. Manajemen User (Admin Only)
**Route:** `/users`  
**Controller:** `UserController`

**Fungsionalitas:**
- ➕ Tambah user baru
- ✏️ Edit user existing
- 🗑️ Hapus user
- 🔐 Assign role (admin/staff/viewer)
- ✅ Aktivasi/Deaktivasi user

**Data Model - User:**

| Field | Type |
|-------|------|
| `id` | bigint |
| `name` | string |
| `email` | string |
| `password` | hashed string |
| `role` | enum (admin, staff, viewer) |
| `status` | boolean |
| `last_login` | timestamp |

---

### 7. Settings
**Route:** `/settings`  
**Controller:** `SettingController`

**Fungsionalitas:**
- ⚙️ Konfigurasi sistem
- 🗓️ Set tahun aktif untuk filter data
- 📝 Pengaturan umum aplikasi

---

## 🔐 Middleware & Security

| Middleware | Fungsi |
|------------|--------|
| `auth` | Memastikan user sudah login |
| `active` | Memastikan akun user aktif |
| `role:admin,staff,viewer` | Kontrol akses berdasarkan role |

---

## 🌐 API Integration

### Google APIs Configuration

**File:** `storage/app/google/service-account.json`

**Scopes yang Digunakan:**
- `Google_Service_Sheets::SPREADSHEETS`
- `Google_Service_Drive::DRIVE_READONLY`

**Folder ID:** `16YHxer1RjXTmoNTY6NoUC5BjGJXzt2ja`

---

## 🗄️ Database Migrations

| Migration | Deskripsi |
|-----------|-----------|
| `create_users_table` | Tabel users dengan authentication |
| `create_cache_table` | Cache untuk Laravel |
| `create_jobs_table` | Queue jobs |
| `add_status_last_login_to_users_table` | Tambah kolom status dan last_login |
| `create_kontraks_table` | Tabel utama kontrak |
| `add_columns_to_kontraks_table` | Tambah kolom tambahan ke kontrak |
| `create_settings_table` | Tabel settings |

---

## 🚀 Cara Menjalankan

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- Laravel 11
- Google Service Account dengan akses ke Sheets & Drive

### Installation

```bash
# Clone repository
git clone <repository-url>

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Run seeders (optional)
php artisan db:seed

# Build assets
npm run build

# Start development server
php artisan serve
```

### Sync Data dari Google Drive

```bash
# Via Terminal
php artisan sync:drive-folder

# Via Dashboard
# Klik button "Sync Database" di halaman /kontrak
```

---

## 📱 Screenshots & Views

| Halaman | File View |
|---------|-----------|
| Dashboard | `resources/views/dashboard/index.blade.php` |
| Manajemen Kontrak | `resources/views/dashboard/kontrak/index.blade.php` |
| List Kontrak | `resources/views/dashboard/list_kontrak/` |
| Settings | `resources/views/dashboard/settings.blade.php` |
| User Management | `resources/views/dashboard/users.blade.php` |
| Upload & Export | `resources/views/dashboard/upload_export.blade.php` |
| Login | `resources/views/auth/login.blade.php` |

---

## 📝 Future Enhancements (Optional)

- [ ] Setup Laravel Scheduler untuk auto-sync periodic
- [ ] Add audit log untuk track sync history
- [ ] Add validation rules per field di Kontrak model
- [ ] Add soft delete untuk kontrak yang dihapus
- [ ] Add relationship dengan user untuk track who synced
- [ ] Implementasi notifikasi email
- [ ] Mobile-responsive improvements
- [ ] API endpoints untuk integrasi pihak ketiga

---

## 📞 Support

Untuk pertanyaan atau dukungan teknis, silakan hubungi tim pengembang.

---

*Dokumen ini terakhir diperbarui: Januari 2026*
