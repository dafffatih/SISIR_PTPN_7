@extends('layouts.app')

@section('title', 'Manajemen Kontrak')
@section('page_title', 'Manajemen Kontrak')

@section('content')
<style>
  /* ===== Page (Kontrak) Only ===== */
  .k-page-title { font-size: 24px; font-weight: 800; color:#0f172a; margin:0 0 6px; }
  .k-page-subtitle { margin:0 0 18px; color:#64748b; font-size: 14px; }

  .k-card {
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(15,23,42,.06);
    overflow:hidden;
  }

  .k-toolbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:16px;
    border-bottom:1px solid #f1f5f9;
    flex-wrap:wrap;
  }

  .k-search {
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 12px;
    border:1px solid #e2e8f0;
    border-radius: 12px;
    min-width: 260px;
    max-width: 520px;
    width: 100%;
    background:#fff;
  }
  .k-search input{
    border:none;
    outline:none;
    width:100%;
    font-size:14px;
    color:#0f172a;
  }
  .k-search input::placeholder{ color:#94a3b8; }

  .k-btn-primary{
    display:inline-flex;
    align-items:center;
    gap:10px;
    border:none;
    cursor:pointer;
    padding:10px 14px;
    border-radius: 12px;
    background:#16a34a;
    color:#fff;
    font-weight:700;
    font-size:14px;
    box-shadow: 0 6px 16px rgba(22,163,74,.16);
    transition:.18s ease;
    white-space:nowrap;
  }
  .k-btn-primary:hover{ transform: translateY(-1px); filter:brightness(.98); }

  .k-table-wrap{ width:100%; overflow:auto; }
  .k-table{
    width:100%;
    border-collapse: separate;
    border-spacing:0;
    min-width: 980px;
  }
  .k-table thead th{
    text-align:left;
    font-size: 11px;
    letter-spacing:.06em;
    text-transform: uppercase;
    color:#64748b;
    background:#f8fafc;
    padding:12px 14px;
    border-bottom:1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 1;
  }
  .k-table tbody td{
    padding:14px;
    border-bottom:1px solid #f1f5f9;
    font-size: 13px;
    color:#0f172a;
    vertical-align: top;
  }
  .k-table tbody tr:hover td{ background:#fcfcff; }

  .k-link{
    color:#7c3aed;
    font-weight:700;
    text-decoration:none;
  }
  .k-link:hover{ text-decoration: underline; }

  .k-muted{ color:#64748b; font-size:12px; margin-top:2px; }

  .k-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:6px 10px;
    border-radius: 999px;
    font-weight:800;
    font-size: 12px;
    line-height: 1;
    white-space:nowrap;
  }
  .k-badge.green{ background:#dcfce7; color:#16a34a; }
  .k-badge.orange{ background:#ffedd5; color:#ea580c; }

  .k-actions{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .k-action-btn{
    width:32px; height:32px;
    border-radius:10px;
    border:1px solid #e2e8f0;
    background:#fff;
    display:grid;
    place-items:center;
    cursor:pointer;
    transition:.15s ease;
  }
  .k-action-btn:hover{ transform: translateY(-1px); background:#f8fafc; }

  .k-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:14px 16px;
    color:#64748b;
    font-size: 13px;
    flex-wrap:wrap;
  }

  .k-pagination{
    display:flex;
    align-items:center;
    gap:8px;
  }
  .k-pagebtn{
    width:34px; height:34px;
    border-radius: 10px;
    border:1px solid #e2e8f0;
    background:#fff;
    cursor:pointer;
    display:grid;
    place-items:center;
    font-weight:800;
    color:#0f172a;
  }
  .k-pagebtn.active{
    background:#7c3aed;
    color:#fff;
    border-color:#7c3aed;
  }

  @media (max-width: 768px){
    .k-search{ max-width: 100%; }
  }
</style>

@php
  // Dummy data sementara (nanti tinggal ganti dari DB)
  $rows = [
    [
      'nomor' => '0528/HO-SUPCO/SIR-LN-I/X/2024',
      'pembeli' => 'Bitung Gunasejahtera',
      'tgl_kontrak' => '15/1/2024',
      'volume' => '50.000 Kg',
      'harga' => 'Rp 32.000',
      'total' => '30.000 Kg',
      'sisa' => '20.000 Kg',
      'jatuh_tempo' => '15/3/2024',
    ],
    [
      'nomor' => '0529/HO-SUPCO/SIR-LN-I/X/2024',
      'pembeli' => 'Wilson Tunggal Perkasa',
      'tgl_kontrak' => '20/1/2024',
      'volume' => '75.000 Kg',
      'harga' => 'Rp 32.500',
      'total' => '45.000 Kg',
      'sisa' => '30.000 Kg',
      'jatuh_tempo' => '20/3/2024',
    ],
    [
      'nomor' => '0103/HO-SUPCO/RSS-LN-I/VIII/2024',
      'pembeli' => 'Singapore Tong Teik',
      'tgl_kontrak' => '1/2/2024',
      'volume' => '100.000 Kg',
      'harga' => 'Rp 31.500',
      'total' => '60.000 Kg',
      'sisa' => '40.000 Kg',
      'jatuh_tempo' => '1/4/2024',
    ],
    [
      'nomor' => '0345/SUPCO/SIR-LN-I/IX/2024',
      'pembeli' => 'Jaya Asri Niaga',
      'tgl_kontrak' => '5/2/2024',
      'volume' => '60.000 Kg',
      'harga' => 'Rp 30.000',
      'total' => '25.000 Kg',
      'sisa' => '35.000 Kg',
      'jatuh_tempo' => '5/4/2024',
    ],
    [
      'nomor' => '0416/HO-SUPCO/SIR-LN-I/VIII/2024',
      'pembeli' => 'Meridian Jati Indonesia',
      'tgl_kontrak' => '10/2/2024',
      'volume' => '80.000 Kg',
      'harga' => 'Rp 29.800',
      'total' => '50.000 Kg',
      'sisa' => '30.000 Kg',
      'jatuh_tempo' => '10/4/2024',
    ],
  ];
@endphp

<h2 class="k-page-title">Data Manajemen Kontrak Penjualan</h2>
<p class="k-page-subtitle">Kelola dan pantau semua kontrak penjualan Anda</p>

<div class="k-card">
  <div class="k-toolbar">
    <div class="k-search">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M21 21l-4.3-4.3m1.8-5.2a7 7 0 11-14 0 7 7 0 0114 0z" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <input type="text" placeholder="Cari nomor kontrak atau nama pembeli...">
    </div>

    <button class="k-btn-primary" id="btnOpenTambah" type="button">
      <span style="font-size:16px; line-height:0;">＋</span>
      Tambah Data
    </button>
  </div>

  <div class="k-table-wrap">
    <table class="k-table">
      <thead>
        <tr>
          <th>Nomor Kontrak</th>
          <th>Nama Pembeli</th>
          <th>Tanggal Kontrak</th>
          <th>Volume</th>
          <th>Harga</th>
          <th>Total Penyerahan</th>
          <th>Sisa Penyerahan</th>
          <th>Jatuh Tempo</th>
          <th style="text-align:center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $r)
          <tr>
            <td>
              <a href="#" class="k-link">{{ $r['nomor'] }}</a>
              <div class="k-muted">SUPCO / SIR</div>
            </td>
            <td>{{ $r['pembeli'] }}</td>
            <td style="color:#64748b;">{{ $r['tgl_kontrak'] }}</td>
            <td>{{ $r['volume'] }}</td>
            <td style="color:#334155;">{{ $r['harga'] }}</td>
            <td><span class="k-badge green">{{ $r['total'] }}</span></td>
            <td><span class="k-badge orange">{{ $r['sisa'] }}</span></td>
            <td style="color:#64748b;">{{ $r['jatuh_tempo'] }}</td>
            <td>
              <div class="k-actions" style="justify-content:center;">
                <button class="k-action-btn" title="Lihat" type="button" data-open="modalDetail">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="#7c3aed" stroke-width="2"/>
                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="#7c3aed" stroke-width="2"/>
                  </svg>
                </button>

                <button class="k-action-btn" title="Edit" type="button" data-open="modalEdit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 20h9" stroke="#f97316" stroke-width="2" stroke-linecap="round"/>
                    <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4L16.5 3.5z" stroke="#f97316" stroke-width="2" stroke-linejoin="round"/>
                  </svg>
                </button>

                <button class="k-action-btn" title="Hapus" type="button">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M3 6h18" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                    <path d="M8 6V4h8v2" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                    <path d="M6 6l1 16h10l1-16" stroke="#ef4444" stroke-width="2" stroke-linejoin="round"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="k-footer">
    <div>Menampilkan 1 - {{ count($rows) }} dari {{ count($rows) }} data</div>
    <div class="k-pagination">
      <button class="k-pagebtn" type="button" title="Prev">‹</button>
      <button class="k-pagebtn active" type="button">1</button>
      <button class="k-pagebtn" type="button" title="Next">›</button>
    </div>
  </div>
</div>

{{-- modal css+js --}}
@include('dashboard.kontrak.modal-assets')

{{-- modal tambah/edit/detail --}}
@include('dashboard.kontrak.modal-tambah')
@include('dashboard.kontrak.modal-edit')
@include('dashboard.kontrak.modal-detail')

@endsection
