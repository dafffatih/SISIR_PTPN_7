@extends('layouts.app')

@section('title', 'Export')
@section('page_title', 'Export')

@section('content')

<style>
:root{
  --primary:#0f766e;
  --primary-soft:#ecfeff;
  --accent:#f59e0b;
  --text-dark:#0f172a;
  --text-muted:#64748b;
  --border:#e2e8f0;
  --bg:#f8fafc;
}

body{ background:var(--bg); }

.ue-wrap{
  max-width:1200px;
  margin:0 auto;
}

.ue-title{
  font-size:26px;
  font-weight:900;
  color:var(--text-dark);
}

.ue-subtitle{
  font-size:13px;
  color:var(--text-muted);
  margin-bottom:22px;
}

/* CARD */
.ue-card{
  background:#fff;
  border-radius:18px;
  border:1px solid var(--border);
  box-shadow:0 14px 40px rgba(15,23,42,.06);
}

.ue-card-head{
  display:flex;
  align-items:center;
  gap:14px;
  padding:20px 24px;
  border-bottom:1px dashed #e5e7eb;
}

.ue-icon{
  width:46px;
  height:46px;
  border-radius:14px;
  background:var(--primary-soft);
  display:flex;
  align-items:center;
  justify-content:center;
}

.ue-icon i{
  font-size:18px;
  color:var(--primary);
}

.ue-head-title{
  font-size:16px;
  font-weight:900;
  margin:0;
}

.ue-head-desc{
  font-size:12.5px;
  color:var(--text-muted);
}

/* BODY */
.ue-card-body{
  padding:22px 24px 28px;
}

/* GRID */
.ue-form-grid{
  display:grid;
  grid-template-columns: 1fr 1fr auto;
  gap:20px;
  align-items:end;
}

@media (max-width:900px){
  .ue-form-grid{
    grid-template-columns:1fr;
  }
}

/* FORM */
.ue-label{
  font-size:12px;
  font-weight:800;
  margin-bottom:6px;
  color:#334155;
}

.ue-input{
  width:100%;
  height:44px;
  border-radius:12px;
  border:1px solid var(--border);
  padding:0 14px;
  font-size:13px;
  background:#f8fafc;
}

.ue-input[readonly]{
  cursor:not-allowed;
  color:#475569;
}

/* FORMAT TABS */
.ue-tabs{
  display:flex;
  gap:10px;
}

.ue-tab{
  flex:1;
  height:40px;
  border-radius:12px;
  border:1px solid var(--border);
  background:#f1f5f9;
  font-weight:900;
  font-size:12px;
  cursor:pointer;
}

.ue-tab.active{
  background:var(--primary);
  color:#fff;
  border-color:var(--primary);
}

/* BUTTON */
.ue-btn{
  min-width:180px;
  height:46px;
  border-radius:14px;
  border:none;
  font-weight:900;
  font-size:13px;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  background:linear-gradient(135deg,#f59e0b,#d97706);
  color:#fff;
  box-shadow:0 12px 26px rgba(245,158,11,.4);
}

@media (max-width:900px){
  .ue-btn{ width:100%; }
}

/* INFO */
.ue-info{
  font-size:12px;
  color:#475569;
  background:#f1f5f9;
  border:1px dashed #cbd5f5;
  padding:10px 14px;
  border-radius:12px;
  margin-top:18px;
}
</style>

<div class="ue-wrap">
  <div class="ue-title">Export</div>
  <div class="ue-subtitle">
    PTPN I Regional 7 – Rubber Trading Analytics
  </div>

  <div class="ue-card">
    <div class="ue-card-head">
      <div class="ue-icon">
        <i class="fas fa-file-export"></i>
      </div>
      <div>
        <p class="ue-head-title">Export Data</p>
        <div class="ue-head-desc">
          Export detail kontrak sesuai tahun yang sedang aktif
        </div>
      </div>
    </div>

    <div class="ue-card-body">

      @if ($errors->any())
        <div style="color:red; margin-bottom:15px;">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('upload.export.kontrak.detail') }}">
        @csrf

        {{-- FORMAT --}}
        <input type="hidden" name="format" id="exportFormat" value="excel">

        <div class="ue-form-grid">

          {{-- FORMAT FILE --}}
          <div>
            <div class="ue-label">Format File</div>
            <div class="ue-tabs">
              <button type="button" class="ue-tab active" data-format="excel">Excel</button>
              <button type="button" class="ue-tab" data-format="csv">CSV</button>
            </div>
          </div>

          {{-- TAHUN AKTIF (READ ONLY) --}}
          <div>
            <div class="ue-label">Tahun Aktif</div>
            <input
              class="ue-input"
              value="{{ session('selected_year', 'Tahun Terbaru') }}"
              readonly
            >
          </div>

          {{-- ACTION --}}
          <div>
            <button class="ue-btn" type="submit">
              <i class="fas fa-download"></i>
              Export Data
            </button>
          </div>

        </div>

        <div class="ue-info">
          📌 Data yang diexport mengikuti <b>tahun yang sedang dipilih di header</b>.
          Untuk mengganti tahun, silakan ubah pilihan tahun di header aplikasi.
        </div>

      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.ue-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.ue-tab').forEach(x => x.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('exportFormat').value = btn.dataset.format;
    });
  });
});
</script>

@endsection
