@extends('layouts.app')

@section('title', 'Upload & Export')
@section('page_title', 'Upload & Export')

@section('content')

{{-- FLATPICKR (datepicker) CDN --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
  /* ===== Wrapper agar pas seperti screenshot ===== */
  .ue-wrap{
    max-width: 1320px;
    margin: 0 auto;
  }

  .ue-page-title{
    font-size: 22px;
    font-weight: 800;
    color:#0f172a;
    margin: 0 0 4px;
  }
  .ue-page-subtitle{
    font-size: 12.5px;
    color:#64748b;
    margin: 0 0 16px;
  }

  /* ===== 2 card sejajar ===== */
  .ue-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
  }
  @media (max-width: 1024px){
    .ue-grid{ grid-template-columns: 1fr; }
  }

  /* ===== Card style mirip screenshot ===== */
  .ue-card{
    background:#ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 0 rgba(15,23,42,.04);
  }

  .ue-card-head{
    display:flex;
    align-items:center;
    gap:12px;
    padding: 14px 16px;
    border-bottom: 1px solid #eef2f7;
  }
  .ue-icon{
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
  }
  .ue-head-title{
    font-size: 14px;
    font-weight: 800;
    color:#0f172a;
    line-height:1.1;
    margin:0;
  }
  .ue-head-desc{
    font-size: 12px;
    color:#64748b;
    margin-top:2px;
  }

  .ue-card-body{
    padding: 14px 16px 16px;
  }

  .ue-label{
    display:block;
    font-size: 12px;
    font-weight: 700;
    color:#334155;
    margin: 10px 0 8px;
  }

  .ue-select, .ue-input{
    width:100%;
    height: 40px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background:#fff;
    padding: 0 12px;
    font-size: 13px;
    color:#0f172a;
    outline:none;
  }
  .ue-select:focus, .ue-input:focus{
    border-color:#cbd5e1;
    box-shadow: 0 0 0 3px rgba(148,163,184,.25);
  }

  /* ===== Dropzone ===== */
  .ue-drop{
    margin-top: 8px;
    height: 148px;
    border-radius: 12px;
    border: 2px dashed #d1d5db;
    background: #ffffff;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    position: relative;
  }
  .ue-drop-inner{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:6px;
    color:#64748b;
  }
  .ue-drop-icon{
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background:#f1f5f9;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#64748b;
    font-size: 16px;
  }
  .ue-drop-inner .main{
    font-size: 12.5px;
    font-weight: 700;
    color:#334155;
  }
  .ue-drop-inner .sub{
    font-size: 11px;
    color:#64748b;
  }

  /* ===== Buttons ===== */
  .ue-btn{
    width:100%;
    height: 40px;
    border-radius: 10px;
    border: none;
    font-weight: 800;
    font-size: 13px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
  }

  /* Upload button abu seperti screenshot */
  .ue-btn-upload{
    margin-top: 14px;
    background:#9ca3af;
    color:#fff;
  }

  /* Export button oranye seperti screenshot */
  .ue-btn-export{
    margin-top: 14px;
    background:#d97706;
    color:#fff;
  }
  .ue-btn-export:hover{ filter: brightness(.96); }

  /* ===== Tabs format (Excel aktif) ===== */
  .ue-tabs{
    display:grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
    margin-top: 8px;
  }

  .ue-tab{
    height: 36px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #f1f5f9;
    color:#334155;
    font-weight: 800;
    font-size: 12px;
    cursor:pointer;
  }
  .ue-tab.active{
    background: #0f766e;
    color:#fff;
    border-color:#0f766e;
  }

  /* ===== Date range (2 kolom) ===== */
  .ue-row2{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 10px;
  }
  @media (max-width: 520px){
    .ue-row2{ grid-template-columns: 1fr; }
  }

  .ue-date-wrap{
    position: relative;
  }
  .ue-date-icon{
    position:absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color:#0f172a;
    font-size: 14px;
    opacity:.9;
    pointer-events: none; /* icon hanya visual, klik input tetap bisa */
  }
  .ue-input.pad-right{
    padding-right: 34px;
  }

  /* ===== Checkbox ===== */
  .ue-checks{
    margin-top: 10px;
    display:flex;
    flex-direction:column;
    gap: 10px;
  }
  .ue-checks label{
    display:flex;
    align-items:center;
    gap:10px;
    font-size: 13px;
    color:#334155;
  }
  .ue-checks input[type="checkbox"]{
    width: 15px;
    height: 15px;
    accent-color: #2563eb; /* biru */
  }

  /* Flatpickr biar warnanya netral */
  .flatpickr-calendar{
    border-radius: 12px !important;
    box-shadow: 0 18px 45px rgba(15,23,42,.14) !important;
    border: 1px solid #e2e8f0 !important;
  }
</style>

<div class="ue-wrap">
  <div class="ue-page-title">Upload &amp; Export</div>
  <div class="ue-page-subtitle">PTPN 1 Regional 7 - Rubber Trading Analytics</div>

  <div class="ue-grid">
    {{-- =================== Upload Data =================== --}}
    <div class="ue-card">
      <div class="ue-card-head">
        <div class="ue-icon" style="background:#ecfdf5;">
          <i class="fas fa-cloud-upload-alt" style="color:#10b981;"></i>
        </div>
        <div>
          <p class="ue-head-title">Upload Data</p>
          <div class="ue-head-desc">Upload file CSV atau Excel</div>
        </div>
      </div>

      <div class="ue-card-body">
        <label class="ue-label">Jenis Data</label>
        <select class="ue-select">
          <option selected>Volume</option>
          <option>Kontrak</option>
          <option>Transaksi</option>
        </select>

        <label class="ue-label" style="margin-top:14px;">File</label>
        <div class="ue-drop">
          <div class="ue-drop-inner">
            <div class="ue-drop-icon">
              <i class="far fa-file"></i>
            </div>
            <div class="main">Klik untuk pilih file atau drag &amp; drop</div>
            <div class="sub">CSV, XLS, XLSX (Max 10MB)</div>
          </div>
        </div>

        <button type="button" class="ue-btn ue-btn-upload">
          <i class="fas fa-upload"></i>
          Upload File
        </button>
      </div>
    </div>

    {{-- =================== Export Data =================== --}}
    <div class="ue-card">
      <div class="ue-card-head">
        <div class="ue-icon" style="background:#fff7ed;">
          <i class="fas fa-file-export" style="color:#f59e0b;"></i>
        </div>
        <div>
          <p class="ue-head-title">Export Data</p>
          <div class="ue-head-desc">Download laporan dalam berbagai format</div>
        </div>
      </div>

      <div class="ue-card-body">
        <label class="ue-label">Format</label>

        <div class="ue-tabs" id="ueTabs">
          <button type="button" class="ue-tab active" data-format="excel">Excel</button>
          <button type="button" class="ue-tab" data-format="pdf">PDF</button>
          <button type="button" class="ue-tab" data-format="csv">CSV</button>
        </div>

        <div class="ue-row2">
          <div>
            <label class="ue-label">Tanggal Mulai</label>
            <div class="ue-date-wrap">
              <input id="startDate" type="text" class="ue-input pad-right" value="01/01/2025">
              <i class="fas fa-calendar-alt ue-date-icon"></i>
            </div>
          </div>

          <div>
            <label class="ue-label">Tanggal Akhir</label>
            <div class="ue-date-wrap">
              <input id="endDate" type="text" class="ue-input pad-right" value="01/31/2025">
              <i class="fas fa-calendar-alt ue-date-icon"></i>
            </div>
          </div>
        </div>

        <label class="ue-label" style="margin-top:14px;">Opsi Export</label>
        <div class="ue-checks">
          <label><input type="checkbox" checked> Include Charts</label>
          <label><input type="checkbox" checked> Include Tables</label>
        </div>

        <button type="button" class="ue-btn ue-btn-export">
          <i class="fas fa-download"></i>
          Export Data
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Tabs
    const tabsWrap = document.getElementById('ueTabs');
    if (tabsWrap) {
      tabsWrap.querySelectorAll('.ue-tab').forEach(btn => {
        btn.addEventListener('click', () => {
          tabsWrap.querySelectorAll('.ue-tab').forEach(x => x.classList.remove('active'));
          btn.classList.add('active');
        });
      });
    }

    // Datepicker (MM/DD/YYYY seperti screenshot)
    const start = flatpickr("#startDate", {
      dateFormat: "m/d/Y",
      defaultDate: "01/01/2025",
      allowInput: true
    });

    const end = flatpickr("#endDate", {
      dateFormat: "m/d/Y",
      defaultDate: "01/31/2025",
      allowInput: true
    });

    // Optional: kalau start > end, auto geser end
    document.getElementById('startDate')?.addEventListener('change', () => {
      const s = start.selectedDates?.[0];
      const e = end.selectedDates?.[0];
      if (s && e && s > e) end.setDate(s, true);
    });
  });
</script>
@endsection
