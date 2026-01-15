@extends('layouts.app')

@section('title', 'Export')
@section('page_title', 'Export')

@section('content')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

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

/* WRAP */
.ue-wrap{
  max-width:1400px;
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

/* HEADER */
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

/* BODY GRID (HORIZONTAL) */
.ue-card-body{
  padding:22px 24px 26px;
}

.ue-form-grid{
  display:grid;
  grid-template-columns: 1.2fr 2fr 1.3fr;
  gap:22px;
  align-items:end;
}

@media (max-width:1100px){
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
}

.ue-input:focus{
  outline:none;
  border-color:var(--primary);
  box-shadow:0 0 0 3px rgba(15,118,110,.15);
}

/* TABS */
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

/* DATE */
.ue-date-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
}

.ue-date-wrap{
  position:relative;
}

.ue-date-icon{
  position:absolute;
  right:14px;
  top:50%;
  transform:translateY(-50%);
  color:#94a3b8;
}

/* BUTTON */
.ue-btn{
  width:100%;
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

.ue-btn:hover{
  transform:translateY(-1px);
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
          Export detail kontrak penjualan berdasarkan rentang tanggal
        </div>
      </div>
    </div>

    <div class="ue-card-body">
      <form method="POST" action="{{ route('upload.export.kontrak.detail') }}">
        @csrf

        <div class="ue-form-grid">

          {{-- FORMAT --}}
          <div>
            <div class="ue-label">Format File</div>
            <div class="ue-tabs">
              <button type="button" class="ue-tab active" data-format="excel">Excel</button>
              <button type="button" class="ue-tab" data-format="csv">CSV</button>
            </div>
            <input type="hidden" name="format" id="exportFormat" value="excel">
          </div>

          {{-- DATE --}}
          <div>
            <div class="ue-label">Rentang Tanggal</div>
            <div class="ue-date-grid">
              <div class="ue-date-wrap">
                <input id="startDate" name="start_date" class="ue-input" required>
                <i class="fas fa-calendar-alt ue-date-icon"></i>
              </div>
              <div class="ue-date-wrap">
                <input id="endDate" name="end_date" class="ue-input" required>
                <i class="fas fa-calendar-alt ue-date-icon"></i>
              </div>
            </div>
          </div>

          {{-- ACTION --}}
          <div>
            <div class="ue-label">&nbsp;</div>
            <button class="ue-btn" type="submit">
              <i class="fas fa-download"></i>
              Export Data
            </button>
          </div>

        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  document.querySelectorAll('.ue-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.ue-tab').forEach(x => x.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('exportFormat').value = btn.dataset.format;
    });
  });

  flatpickr("#startDate",{ dateFormat:"m/d/Y", defaultDate:"01/01/2025" });
  flatpickr("#endDate",{ dateFormat:"m/d/Y", defaultDate:"01/31/2025" });

});
</script>

@endsection
