@extends('layouts.app')

@section('title', 'Manajemen Kontrak')
@section('page_title', 'Manajemen Kontrak')

@section('content')
<style>
    /* Container & Wrapper */
    .k-container { padding: 20px; font-family: 'Inter', sans-serif; }
    .k-header { margin-bottom: 24px; }
    .k-title { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; }
    .k-subtitle { font-size: 14px; color: #64748b; margin-top: 4px; }

    /* Card Style */
    .k-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    /* Toolbar Section */
    .k-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 12px;
    }

    /* Search Input */
    .k-search-box {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 12px;
        width: 100%;
        max-width: 400px;
        transition: all 0.2s;
    }
    .k-search-box:focus-within { border-color: #7c3aed; background: #fff; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1); }
    .k-search-box input {
        border: none;
        background: transparent;
        outline: none;
        margin-left: 8px;
        font-size: 14px;
        width: 100%;
        color: #1e293b;
    }

    /* Primary Button */
    .k-btn-add {
        background: #10b981;
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s;
    }
    .k-btn-add:hover { background: #059669; transform: translateY(-1px); }

    /* Table Style */
    .k-table-responsive { width: 100%; overflow-x: auto; }
    .k-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    .k-table thead { background: #f8fafc; }
    .k-table th {
        text-align: left;
        padding: 12px 16px;
        font-size: 11px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        border-bottom: 1px solid #e2e8f0;
    }
    .k-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
    .k-table tr:hover { background-color: #fcfcff; }

    /* Badge Styles */
    .k-badge { padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 11px; }
    .k-badge-green { background: #dcfce7; color: #16a34a; }
    .k-badge-orange { background: #ffedd5; color: #ea580c; }

    /* Action Buttons */
    .k-btn-icon {
        width: 32px;
        height: 32px;
        display: inline-grid;
        place-items: center;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        transition: 0.2s;
        color: #64748b;
    }
    .k-btn-icon:hover { background: #f8fafc; color: #7c3aed; border-color: #7c3aed; }
    .k-btn-delete:hover { color: #ef4444; border-color: #fca5a5; background: #fee2e2; }

    /* Footer & Pagination */
    .k-footer { padding: 16px; display: flex; justify-content: space-between; align-items: center; background: white; color: #64748b; font-size: 13px; }
    .k-pagination { display: flex; gap: 5px; }
    .k-page-link {
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        text-decoration: none;
        color: #1e293b;
        font-weight: 600;
    }
    .k-page-link.active { background: #7c3aed; color: white; border-color: #7c3aed; }
    .k-page-link.disabled { color: #cbd5e1; pointer-events: none; }
    .k-select-limit {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 14px;
        color: #1e293b;
        outline: none;
        cursor: pointer;
    }
    .k-select-limit:focus { border-color: #7c3aed; background: #fff; }

    /* Secondary Button */
    .k-btn-sync {
        background: #3b82f6;
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s;
    }
    .k-btn-sync:hover { background: #2563eb; transform: translateY(-1px); }
    .k-btn-sync:disabled { background: #cbd5e1; cursor: not-allowed; transform: none; }

    /* Date filter inputs */
    .k-date-input {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 14px;
        color: #1e293b;
        outline: none;
    }
    .k-date-input:focus { border-color: #7c3aed; background: #fff; }
    .k-date-separator {
        padding: 0 6px;
        color: #94a3b8;
        font-weight: 600;
    }
</style>

<div class="k-container">
    <div class="k-header">
        <h2 class="k-title">Data Manajemen Kontrak Penjualan</h2>
        <p class="k-subtitle">Kelola dan pantau semua kontrak penjualan Anda secara realtime dari Google Sheets</p>
    </div>

    <div class="k-card">
        <div class="k-toolbar">
          <form action="{{ route('kontrak') }}" method="GET" id="filterForm" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; width: 100%;">
              
              <div class="k-search-box" style="margin: 0; flex: 1; min-width: 250px;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="8"></circle>
                      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                  <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor kontrak, pembeli, DO/SI, SAP...">
              </div>

              <!-- Date Range Filter -->
              <div style="display: flex; gap: 6px; align-items: center;">
                  <input type="date" name="start_date" class="k-date-input" value="{{ request('start_date') }}" placeholder="Dari tanggal" title="Tanggal awal">
                  <span class="k-date-separator">-</span>
                  <input type="date" name="end_date" class="k-date-input" value="{{ request('end_date') }}" placeholder="Sampai tanggal" title="Tanggal akhir">
              </div>

              <select name="sort" class="k-select-limit" onchange="document.getElementById('filterForm').submit()" title="Urutkan berdasarkan">
                  <option value="nomor_dosi" {{ request('sort') == 'nomor_dosi' ? 'selected' : '' }}>Urut DO/SI</option>
                  <option value="nomor_kontrak" {{ request('sort') == 'nomor_kontrak' ? 'selected' : '' }}>Urut Nomor Kontrak</option>
                  <option value="tgl_kontrak" {{ request('sort') == 'tgl_kontrak' ? 'selected' : '' }}>Urut Tanggal</option>
              </select>

              <select name="direction" class="k-select-limit" onchange="document.getElementById('filterForm').submit()" title="Arah urutan">
                  <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>↑ Ascending</option>
                  <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>↓ Descending</option>
              </select>

              <select name="per_page" class="k-select-limit" onchange="document.getElementById('filterForm').submit()" title="Data per halaman">
                  @foreach([10, 50, 100, 250, 500, 1000] as $limit)
                      <option value="{{ $limit }}" {{ request('per_page') == $limit ? 'selected' : '' }}>
                          Tampil {{ $limit }}
                      </option>
                  @endforeach
              </select> 
              <button type="submit" style="display:none"></button>
          </form>

          <button class="k-btn-add" id="btnOpenTambah">
              <span>＋</span> Tambah Data
          </button>
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
                                                            <a href="javascript:void(0)" class="k-link" 
                                                                data-open="modalDetail" 
                                                                data-json="{{ json_encode($r) }}">{{ $r['I'] }}</a>
                                                            <div style="font-size:11px; color:#94a3b8; margin-top:2px;">{{ $r['Y'] ?? '-' }}</div>
                                                    </td>
                          <td style="font-weight:600;">{{ $r['J'] }}</td>
                          <td style="color:#64748b;">{{ $r['K'] }}</td>
                          <td style="font-weight:700;">{{ $r['L'] ?? '' }} Kg</td>
                          <td style="white-space: nowrap;">
                            Rp&nbsp;{{ $r['M'] ?? '' }}
                          </td>
                          <td><span class="k-badge k-badge-green">{{ $r['AA'] ?? '' }} Kg</span></td>
                          <td><span class="k-badge k-badge-orange">{{ $r['AB'] ?? '' }} Kg</span></td>
                          <td style="color:#64748b;">-</td>
                          <td style="text-align:center;">
                              <div class="k-actions" style="justify-content:center; display:flex; gap:8px;">
                                  <button class="k-btn-icon" title="Lihat" 
                                          data-open="modalDetail" 
                                          data-json="{{ json_encode($r) }}">
                                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                  </button>
                                  <button class="k-btn-icon" title="Edit" 
                                          data-open="modalEdit" 
                                          data-json="{{ json_encode($r) }}">
                                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                  </button>
                                  <button class="k-btn-icon k-btn-delete" title="Hapus" 
                                          onclick="handleDeleteClick({{ $r['id'] }}, '{{ $r['I'] }}')">
                                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h16zM10 11v6M14 11v6"></path></svg>
                                  </button>
                              </div>
                          </td>
                      </tr>
                  @empty
                      <tr>
                          <td colspan="9" style="text-align:center; padding: 40px; color:#94a3b8;">Data kontrak tidak ditemukan.</td>
                      </tr>
                  @endforelse
              </tbody>
            </table>
        </div>

        <div class="k-footer">
            <div>
                Menampilkan <b>{{ $data->firstItem() ?? 0 }}</b> - <b>{{ $data->lastItem() ?? 0 }}</b> dari <b>{{ $data->total() }}</b> data
            </div>
            
            <div class="k-pagination">
                @if($data->onFirstPage())
                    <span class="k-page-link disabled">‹</span>
                @else
                    <a href="{{ $data->previousPageUrl() }}" class="k-page-link">‹</a>
                @endif

                @foreach ($data->getUrlRange(max(1, $data->currentPage() - 1), min($data->lastPage(), $data->currentPage() + 1)) as $page => $url)
                    <a href="{{ $url }}" class="k-page-link {{ $page == $data->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                @endforeach

                @if($data->hasMorePages())
                    <a href="{{ $data->nextPageUrl() }}" class="k-page-link">›</a>
                @else
                    <span class="k-page-link disabled">›</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Includes modal --}}
@include('dashboard.kontrak.modal-assets')
@include('dashboard.kontrak.modal-tambah')
@include('dashboard.kontrak.modal-edit')
@include('dashboard.kontrak.modal-detail')

<script>
    function handleDeleteClick(rowId, nomorKontrak) {
        const confirmDelete = confirm(`Apakah Anda yakin ingin menghapus kontrak nomor ${nomorKontrak}?\n\nTindakan ini tidak dapat dibatalkan.`);
        
        if (confirmDelete) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("kontrak.destroy", ":id") }}'.replace(':id', rowId);
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endsection
