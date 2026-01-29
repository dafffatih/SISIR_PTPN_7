@extends('layouts.app')

@section('title', 'Manajemen Kontrak')
@section('page_title', 'Database')

@section('content')
<style>
    /* ... Style Lama (Tidak Berubah) ... */
    .k-container { padding: 22px; font-family: 'Inter', sans-serif; }
    .k-header { margin-bottom: 24px; }
    .k-title { font-size: 24px; font-weight: 700; font-family: 'Inter', sans-serif; color: #0F172A; line-height: 1.2; margin: 0; }
    .k-subtitle { font-size: 14px; color: #64748B; margin-top: 4px; }
    .nav-tabs { display: flex; gap: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
    .nav-item { padding: 12px 4px; font-weight: 600; font-size: 14px; color: #64748b; text-decoration: none; border-bottom: 2px solid transparent; cursor: pointer; }
    .nav-item:hover { color: #7c3aed; }
    .nav-item.active { color: #7c3aed; border-bottom-color: #7c3aed; }
    .k-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .k-toolbar { display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #ffffff; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 12px; }
    .k-search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px; width: 100%; max-width: 400px; transition: all 0.2s; }
    .k-search-box:focus-within { border-color: #7c3aed; background: #fff; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1); }
    .k-search-box input { border: none; background: transparent; outline: none; margin-left: 8px; font-size: 14px; width: 100%; color: #1e293b; }
    .k-btn-add { background: #10b981; color: white; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
    .k-btn-add:hover { background: #059669; transform: translateY(-1px); }
    .k-table-responsive { width: 100%; overflow-x: auto; }
    .k-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    .k-table thead { background: #f8fafc; }
    .k-table th { text-align: left; padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0; }
    .k-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
    .k-table tr:hover { background-color: #fcfcff; }
    .k-badge { padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 11px; }
    .k-badge-green { background: #dcfce7; color: #16a34a; }
    .k-badge-orange { background: #ffedd5; color: #ea580c; }
    .k-btn-icon { width: 32px; height: 32px; display: inline-grid; place-items: center; border-radius: 8px; border: 1px solid #e2e8f0; background: white; cursor: pointer; transition: 0.2s; color: #64748b; }
    .k-btn-icon:hover { background: #f8fafc; color: #7c3aed; border-color: #7c3aed; }
    .k-btn-delete:hover { color: #ef4444; border-color: #fca5a5; background: #fee2e2; }
    .k-footer { padding: 16px; display: flex; justify-content: space-between; align-items: center; background: white; color: #64748b; font-size: 13px; }
    .k-pagination { display: flex; gap: 5px; }
    .k-page-link { padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; text-decoration: none; color: #1e293b; font-weight: 600; }
    .k-page-link.active { background: #7c3aed; color: white; border-color: #7c3aed; }
    .k-page-link.disabled { color: #cbd5e1; pointer-events: none; }
    .k-select-limit { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 14px; color: #1e293b; outline: none; cursor: pointer; }
    .k-select-limit:focus { border-color: #7c3aed; background: #fff; }
    .k-date-input { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 14px; color: #1e293b; outline: none; }
    .k-date-input:focus { border-color: #7c3aed; background: #fff; }
    .k-date-separator { padding: 0 6px; color: #94a3b8; font-weight: 600; }
</style>

<div class="k-container">
    <div class="k-header">
        <h2 class="k-title">Data Kontrak Penjualan</h2>
        <p class="k-subtitle">Kelola dan pantau semua kontrak penjualan Anda secara realtime dari Google Sheets</p>
    </div>
    <div class="nav-tabs">
        <a href="{{ route('kontrak') }}" class="nav-item {{ request()->routeIs('kontrak') ? 'active' : '' }}">List DO</a>
        <a href="{{ route('list-kontrak.index') }}" class="nav-item {{ request()->routeIs('list-kontrak.index') ? 'active' : '' }}">List Kontrak</a>
    </div>

    <div class="k-card">
        <div class="k-toolbar">
          <form action="{{ route('kontrak') }}" method="GET" id="filterForm" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; width: 100%;">
              <div class="k-search-box" style="margin: 0; flex: 1; min-width: 250px;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                  <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor kontrak, pembeli, DO/SI, SAP...">
              </div>
              <div style="display: flex; gap: 6px; align-items: center;">
                <input type="date" name="start_date" class="k-date-input" value="{{ request('start_date') }}" onchange="document.getElementById('filterForm').submit()"> <span class="k-date-separator">-</span>
                <input type="date" name="end_date" class="k-date-input" value="{{ request('end_date') }}" onchange="document.getElementById('filterForm').submit()"> 
              </div>
              <select name="sort" class="k-select-limit" onchange="document.getElementById('filterForm').submit()">
                  <option value="nomor_dosi" {{ request('sort') == 'nomor_dosi' ? 'selected' : '' }}>Urut DO/SI</option>
                  <option value="nomor_kontrak" {{ request('sort') == 'nomor_kontrak' ? 'selected' : '' }}>Urut Nomor Kontrak</option>
                  <option value="tgl_kontrak" {{ request('sort') == 'tgl_kontrak' ? 'selected' : '' }}>Urut Tanggal</option>
              </select>
              <select name="direction" class="k-select-limit" onchange="document.getElementById('filterForm').submit()">
                  <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>↑ Ascending</option>
                  <option value="desc" {{ request('direction', 'desc') == 'desc' ? 'selected' : '' }}>↓ Descending</option>
              </select>
              <select name="per_page" class="k-select-limit" onchange="document.getElementById('filterForm').submit()">
                  @foreach([10, 50, 100, 250, 500, 1000] as $limit)
                      <option value="{{ $limit }}" {{ request('per_page') == $limit ? 'selected' : '' }}>Tampil {{ $limit }}</option>
                  @endforeach
              </select> 
          </form>

            {{-- [BARU] Tombol Sync / Refresh --}}
            <a href="{{ route('kontrak', ['refresh' => 1]) }}" class="k-btn-icon" title="Sync Data Terbaru" style="text-decoration:none; display:flex; align-items:center; justify-content:center; background:#f8fafc; border:1px solid #e2e8f0; width:auto; padding:0 12px; gap:6px; height: 38px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                <span style="font-size:13px; font-weight:600; color:#64748b;">Sync</span>
            </a>

            @if(auth()->user()->role !== 'viewer')
                <button class="k-btn-add" id="btnOpenTambah"><span>＋</span> Tambah Data</button>
            @endif
      </div>

        <div class="k-table-responsive">
            <table class="k-table">
                <thead>
                    <tr>
                        <th>Nomor DO/SI</th>
                        <th>Nomor Kontrak</th>
                        <th>Nama Pembeli</th>
                        <th>Tanggal</th>
                        <th>Volume</th>
                        <th>Harga</th>
                        <th>Total Penyerahan</th>
                        <th>Sisa Penyerahan</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                  @forelse($data as $index => $r)
                      <tr>
                          <td style="font-weight:700;">{{ $r['S'] }}</td>
                          <td>
                                <a href="javascript:void(0)" class="k-link" data-open="modalDetail" data-json="{{ json_encode($r) }}">{{ $r['I'] }}</a>
                                <div style="font-size:11px; color:#94a3b8; margin-top:2px;">{{ $r['Y'] ?? '-' }}</div>
                          </td>
                          <td style="font-weight:600;">{{ $r['J'] }}</td>
                          <td style="color:#64748b;">{{ $r['K'] }}</td>
                          <td style="font-weight:700;">{{ $r['L'] ?? '' }} Kg</td>
                          <td style="white-space: nowrap;">Rp&nbsp;{{ $r['M'] ?? '' }}</td>
                          <td><span class="k-badge k-badge-green">{{ $r['AA'] ?? '' }} Kg</span></td>
                          <td><span class="k-badge k-badge-orange">{{ $r['AB'] ?? '' }} Kg</span></td>
                          <td style="color:#64748b;">-</td>
                          <td style="text-align:center;">
                              <div class="k-actions" style="justify-content:center; display:flex; gap:8px;">
                                <button class="k-btn-icon" title="Lihat" data-open="modalDetail" data-json="{{ json_encode($r) }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>

                                @if(auth()->user()->role !== 'viewer')
                                    {{-- Tombol Edit --}}
                                    <button class="k-btn-icon" title="Edit" data-open="modalEdit" data-json="{{ json_encode($r) }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    {{-- Tombol Hapus --}}
                                    <button class="k-btn-icon k-btn-delete" title="Hapus" 
                                            onclick="openDeleteModal('{{ $r['id'] }}', '{{ $r['I'] }}')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h16zM10 11v6M14 11v6"></path></svg>
                                    </button>
                                @endif
                            </div>
                          </td>
                      </tr>
                  @empty
                      <tr><td colspan="10" style="text-align:center; padding: 40px; color:#94a3b8;">Data kontrak tidak ditemukan.</td></tr>
                  @endforelse
              </tbody>
            </table>
        </div>

        <div class="k-footer">
            <div>Menampilkan <b>{{ $data->firstItem() ?? 0 }}</b> - <b>{{ $data->lastItem() ?? 0 }}</b> dari <b>{{ $data->total() }}</b> data</div>
            <div class="k-pagination">
                @if($data->onFirstPage()) <span class="k-page-link disabled">‹</span> @else <a href="{{ $data->previousPageUrl() }}" class="k-page-link">‹</a> @endif
                @foreach ($data->getUrlRange(max(1, $data->currentPage() - 1), min($data->lastPage(), $data->currentPage() + 1)) as $page => $url)
                    <a href="{{ $url }}" class="k-page-link {{ $page == $data->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                @endforeach
                @if($data->hasMorePages()) <a href="{{ $data->nextPageUrl() }}" class="k-page-link">›</a> @else <span class="k-page-link disabled">›</span> @endif
            </div>
        </div>
    </div>
</div>

{{-- Includes modal --}}
@include('dashboard.kontrak.modal-assets')
@include('dashboard.kontrak.modal-detail')
@if(auth()->user()->role !== 'viewer')
    @include('dashboard.kontrak.modal-tambah')
    @include('dashboard.kontrak.modal-edit')
    @include('dashboard.kontrak.modal-delete')
@endif
<script>
    // 1. Fungsi Buka Modal Delete
    function openDeleteModal(rowId, nomorKontrak) {
        // Set teks peringatan
        const textSpan = document.getElementById('modalTargetName');
        if(textSpan) textSpan.innerText = nomorKontrak;

        // Set action form delete
        const form = document.getElementById('deleteForm');
        if(form) {
            // Route harus sesuai: /kontrak/{id}
            let url = '{{ route("kontrak.destroy", ":id") }}';
            url = url.replace(':id', rowId);
            form.action = url;
        }

        // Tampilkan Modal (tambah class 'show')
        const modal = document.getElementById('deleteModal');
        if(modal) modal.classList.add('show');
    }

    // 2. Fungsi Tutup Modal Delete
    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        if(modal) modal.classList.remove('show');
    }

    // 3. Close ketika klik backdrop
    document.addEventListener('DOMContentLoaded', () => {
        const deleteModal = document.getElementById('deleteModal');
        if(deleteModal){
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    closeDeleteModal();
                }
            });
        }
    });
</script>
@endsection