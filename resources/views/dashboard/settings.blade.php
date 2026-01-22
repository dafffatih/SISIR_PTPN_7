{{-- resources/views/dashboard/settings.blade.php --}}
@extends('layouts.app')

@section('title', 'Settings')
@section('page_title', 'Settings')

@section('content')
<style>
  /* ====== Base (match screenshot) ====== */
  :root{
    --bg: #f7f8fb;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #6b7280;
    --border: #e5e7eb;
    --soft: #f3f4f6;

    --orange: #f97316;
    --orange-soft: #fff1e6;

    --radius: 14px;
  }

  .set-page{
    background: var(--bg);
    min-height: calc(100vh - 40px);
    padding: 0;
  }

  /* ====== Content ====== */
  .set-container{
  padding: 22px 22px 34px;
  max-width: 1000px;
  margin: 0 auto;
  font-family: 'Inter', sans-serif; /* ⬅️ SAMAKAN */
  }


  .set-title{
  margin: 0;
  font-size: 24px;        /* sama dengan Dashboard */
  font-weight: 700;       /* sama */
  color: #0F172A;         /* sama */
  line-height: 1.2;
  letter-spacing: 0;      /* Dashboard tidak pakai spacing */
 }

  .set-subtitle{
  margin: 4px 0 0;
  font-size: 14px;
  color: #64748B;
 }


  .set-card{
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    margin-top: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }

  .set-card h3{
    margin: 0 0 18px;
    font-size: 18px;
    font-weight: 800;
    color: #111827;
    border-bottom: 1px solid var(--border);
    padding-bottom: 12px;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .field-label{
    display:block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
  }

  .field-input{
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 14px;
    outline: none;
    color: #111827;
    background: #fff;
    transition: all 0.2s;
  }
  .field-input:focus{
    border-color: var(--orange);
    box-shadow: 0 0 0 3px rgba(249,115,22,.12);
  }

  .field-help{
    margin-top: 6px;
    color: #9ca3af;
    font-size: 13px;
  }

  .info-box{
    margin-top: 20px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    padding: 16px;
    color: #1e40af;
    font-size: 14px;
    line-height: 1.6;
  }
  .info-box code {
    background: rgba(255,255,255,0.7);
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
    color: #1e3a8a;
    font-family: monospace;
  }

  .btn-orange{
    background: var(--orange);
    border: 0;
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    padding: 12px 24px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
    display: inline-block;
  }
  .btn-orange:hover{ background: #ea580c; }

  /* Table Styles for List */
  .table-responsive {
    overflow-x: auto;
  }
  .settings-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 14px;
  }
  .settings-table th {
    text-align: left;
    padding: 12px;
    background: #f9fafb;
    color: #6b7280;
    font-weight: 600;
    border-bottom: 1px solid var(--border);
  }
  .settings-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
    color: #111827;
  }
  .settings-table tr:last-child td { border-bottom: none; }

  .alert {
    padding: 14px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 600;
  }
  .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

  /* ===== MODAL STYLES ===== */
  .modal-backdrop {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.6); /* Gelap transparan */
    z-index: 9999;
    display: none; /* Hidden by default */
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
  }

  .modal-content {
    background: #fff;
    width: 100%;
    max-width: 400px;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    text-align: center;
    animation: modalPop 0.2s ease-out;
  }

  @keyframes modalPop {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
  }

  .modal-icon {
    width: 50px; height: 50px;
    background: #fee2e2;
    color: #ef4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 16px;
  }

  .modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
  }

  .modal-text {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 24px;
    line-height: 1.5;
  }

  .modal-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
  }

  .btn-modal {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: .2s;
    width: 100%;
  }

  .btn-cancel {
    background: #fff;
    border: 1px solid #e5e7eb;
    color: #374151;
  }
  .btn-cancel:hover { background: #f9fafb; }

  .btn-delete {
    background: #ef4444;
    color: white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }
  .btn-delete:hover { background: #dc2626; }

  /* responsive */
  @media (max-width: 640px){
    .set-container{ padding: 18px 14px 28px; }
  }
</style>

<div class="set-page">
  <div class="set-container">
    
    <h1 class="set-title">Pengaturan Integrasi</h1>
    <p class="set-subtitle">Konfigurasi koneksi data realtime dengan Google Spreadsheet</p>

    {{-- Flash Messages --}}
    @if(session('success'))
      <div class="alert alert-success" style="margin-top: 20px;">
        {{ session('success') }}
      </div>
    @endif

    {{-- Form Tambah/Edit --}}
    <div class="set-card">
      <h3>Tambah / Update Spreadsheet</h3>

      <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        
        <div class="form-group">
          <label class="field-label">Nama Kunci (Key)</label>
          <input class="field-input" type="text" name="key_input" 
                 placeholder="Contoh: google_sheet_id_2026" required />
          <div class="field-help">
            Ketik nama kunci konfigurasi di sini.
          </div>
        </div>

        <div class="form-group">
          <label class="field-label">Link atau ID Spreadsheet</label>
          <input class="field-input" type="text" name="sheet_input" 
                 placeholder="https://docs.google.com/spreadsheets/d/1BxiMVs..." required />
          <div class="field-help">Copy-paste URL lengkap dari browser, sistem akan otomatis mengambil ID-nya.</div>
        </div>

        {{-- NOTE KHUSUS YANG DIMINTA --}}
        <div class="info-box">
          <div style="display:flex; gap:12px;">
            <i class="fas fa-info-circle" style="font-size: 20px; margin-top: 2px;"></i>
            <div>
              <strong>PENTING: Aturan Penamaan</strong><br>
              Agar fitur filter tahun berfungsi otomatis, mohon gunakan format: 
              <code>google_sheet_id_TAHUN</code>.<br>
              Contoh untuk tahun 2026: <code>google_sheet_id_2026</code>.<br>
              Contoh untuk default: <code>google_sheet_id</code>.
            </div>
          </div>
        </div>

        <button class="btn-orange" type="submit" style="margin-top: 10px;">
          <i class="fas fa-save" style="margin-right: 6px;"></i> Simpan Koneksi
        </button>
      </form>
    </div>

    {{-- List Data Tersimpan --}}
    <div class="set-card">
      <h3>Daftar Spreadsheet Tersimpan</h3>
      
      <div class="table-responsive">
        <table class="settings-table">
          <thead>
            <tr>
              <th width="30%">Nama Kunci (Key)</th>
              <th width="45%">Spreadsheet ID</th>
              <th width="15%">Terakhir Diupdate</th>
              {{-- Kolom Aksi Baru --}}
              <th width="10%" style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {{-- Loop data settings dari controller --}}
            @forelse($settings ?? [] as $set)
              <tr>
                <td style="font-weight: 600; color: #4b5563;">{{ $set->key }}</td>
                <td style="font-family: monospace; font-size: 13px;">{{ Str::limit($set->value, 25) }}</td>
                <td style="color: #9ca3af; font-size: 13px;">{{ $set->updated_at->format('d M Y') }}</td>
                
                {{-- Tombol Hapus --}}
                <td style="text-align:center">
                    <button type="button" 
                            onclick="openDeleteModal('{{ route('settings.destroy', $set->id) }}', '{{ $set->key }}')"
                            style="border:none; background:none; cursor:pointer; padding:5px;">
                        <i class="fas fa-trash" style="color: #ef4444; font-size: 16px;" title="Hapus"></i>
                    </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" style="text-align: center; padding: 20px; color: #9ca3af;">
                  Belum ada konfigurasi tersimpan di Database. <br>
                  (Sistem saat ini mungkin menggunakan .env)
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
{{-- POPUP MODAL HAPUS --}}
<div id="deleteModal" class="modal-backdrop">
  <div class="modal-content">
    
    <div class="modal-icon">
      <i class="fas fa-exclamation-triangle"></i>
    </div>

    <div class="modal-title">Hapus Konfigurasi?</div>
    <div class="modal-text">
      Anda yakin ingin menghapus <strong id="modalTargetName"></strong>? <br>
      Tindakan ini tidak dapat dibatalkan.
    </div>

    <div class="modal-actions">
      <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModal()">
        Batal
      </button>

      <form id="deleteForm" method="POST" action="">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-modal btn-delete">
          Ya, Hapus
        </button>
      </form>
    </div>

  </div>
</div>

{{-- SCRIPT PENGENDALI MODAL --}}
<script>
  // Fungsi Buka Modal
  function openDeleteModal(actionUrl, keyName) {
    // 1. Set URL action pada form
    document.getElementById('deleteForm').action = actionUrl;
    
    // 2. Tampilkan nama key di teks pesan
    document.getElementById('modalTargetName').textContent = keyName;
    
    // 3. Munculkan modal
    document.getElementById('deleteModal').style.display = 'flex';
  }

  // Fungsi Tutup Modal
  function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
  }

  // Tutup jika user klik di area gelap (luar box)
  window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }
</script>
@endsection