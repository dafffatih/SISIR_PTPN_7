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

  /* ====== Topbar ====== */
  .set-topbar{
    position: sticky;
    top: 0;
    z-index: 5;

    background: #fff;
    border-bottom: 1px solid var(--border);
    padding: 14px 18px;
  }

  .set-topbar-inner{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 14px;
  }

  .set-top-left{
    display:flex;
    align-items:center;
    gap: 12px;
    min-width: 0;
  }

  .set-hamburger{
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: #fff;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
  }
  .set-hamburger:hover{ background: #fafafa; }

  .set-search{
    width: min(520px, 55vw);
    position: relative;
    min-width: 200px;
  }
  .set-search i{
    position:absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 14px;
  }
  .set-search input{
    width: 100%;
    border: 1px solid var(--border);
    background: #fff;
    border-radius: 12px;
    padding: 11px 12px 11px 36px;
    outline: none;
    font-size: 14px;
    color: var(--text);
  }
  .set-search input:focus{
    border-color: rgba(249,115,22,.55);
    box-shadow: 0 0 0 3px rgba(249,115,22,.12);
  }

  .set-top-right{
    display:flex;
    align-items:center;
    gap: 10px;
    flex: 0 0 auto;
  }

  .icon-btn{
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: #fff;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    position: relative;
  }
  .icon-btn:hover{ background: #fafafa; }
  .notif-dot{
    position:absolute;
    right: 10px;
    top: 10px;
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: var(--orange);
  }
  .icon-btn i{ color:#111827; }

  /* ====== Content ====== */
  .set-container{
    padding: 22px 22px 34px;
  }

  .set-title{
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: #111827;
    letter-spacing: .1px;
  }
  .set-subtitle{
    margin: 6px 0 0;
    color: var(--muted);
    font-size: 14px;
  }

  .set-card{
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px;
    margin-top: 18px;
  }

  .set-card h3{
    margin: 2px 0 14px;
    font-size: 16px;
    font-weight: 800;
    color: #111827;
  }

  .field-label{
    display:block;
    font-size: 13px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
  }

  .field-input{
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 12px;
    font-size: 14px;
    outline: none;
    color: #111827;
    background: #fff;
  }
  .field-input:focus{
    border-color: rgba(249,115,22,.55);
    box-shadow: 0 0 0 3px rgba(249,115,22,.12);
  }

  .field-help{
    margin-top: 8px;
    color: #9ca3af;
    font-size: 12px;
  }

  .info-box{
    margin-top: 14px;
    background: #f3f4f6;
    border-radius: 10px;
    padding: 14px;
    color: #111827;
    font-size: 13px;
    line-height: 1.55;
  }

  .btn-orange{
    margin-top: 16px;
    background: var(--orange);
    border: 0;
    color: #fff;
    font-weight: 800;
    font-size: 14px;
    padding: 12px 22px;
    border-radius: 10px;
    cursor: pointer;
  }
  .btn-orange:hover{ filter: brightness(.97); }

  .status-pill{
    display:inline-flex;
    align-items:center;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 800;
    border-radius: 999px;
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #eceef2;
  }

  /* spacing to look like screenshot */
  .set-card + .set-card{ margin-top: 18px; }

  /* responsive */
  @media (max-width: 640px){
    .set-container{ padding: 18px 14px 28px; }
    .set-search{ width: 58vw; min-width: 160px; }
  }
</style>

<div class="set-page">


  {{-- Main content --}}
  <div class="set-container">
    <h1 class="set-title">Spreadsheet Integration</h1>
    <p class="set-subtitle">Configure realtime data connection with Google Spreadsheet</p>

    <div class="set-card">
      <h3>Google Spreadsheet Configuration</h3>

      <label class="field-label">Spreadsheet ID</label>
      <input class="field-input" type="text" value="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms" />

      <div class="field-help">Spreadsheet ID can be found in the Google Sheets URL</div>

      <div class="info-box">
        This ID is used to connect the system with Google Sheets via API for realtime data synchronization.
      </div>

      <button class="btn-orange" type="button">Save &amp; Connect</button>
    </div>

    <div class="set-card">
      <h3>Connection Status</h3>
      <span class="status-pill">Disconnected</span>
    </div>

  </div>
</div>
@endsection
