@extends('layouts.app')

@section('title', 'List Kontrak')
@section('page_title', 'Database')

@section('content')
<style>
    /* CSS Styling tetap sama */
    .k-container { padding: 20px; font-family: 'Inter', sans-serif; }
    .k-header { margin-bottom: 24px; }
    .k-title { font-size: 24px; font-weight: 700; color: #0F172A; margin: 0; }
    .k-subtitle { font-size: 14px; color: #64748B; margin-top: 4px; }
    .k-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .k-toolbar { display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #ffffff; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 12px; }
    .k-search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px; width: 100%; max-width: 300px; flex:1; }
    .k-search-box input { border: none; background: transparent; outline: none; margin-left: 8px; font-size: 14px; width: 100%; color: #1e293b; }
    .k-date-input { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 14px; color: #1e293b; outline: none; }
    .k-select-limit { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 14px; color: #1e293b; outline: none; cursor: pointer; }
    .k-table-responsive { width: 100%; overflow-x: auto; }
    .k-table { width: 100%; border-collapse: collapse; min-width: 1400px; }
    .k-table thead { background: #f8fafc; }
    .k-table th { text-align: left; padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .k-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
    .k-table tr:hover { background-color: #fcfcff; }
    .k-badge { padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 11px; }
    .k-badge-blue { background: #dbeafe; color: #1e40af; }
    .k-badge-purple { background: #f3e8ff; color: #7e22ce; }
    .k-btn-icon { width: 32px; height: 32px; display: inline-grid; place-items: center; border-radius: 8px; border: 1px solid #e2e8f0; background: white; cursor: pointer; transition: 0.2s; color: #64748b; }
    .k-btn-icon:hover { background: #f8fafc; color: #7c3aed; border-color: #7c3aed; }
    .k-btn-delete:hover { color: #ef4444; border-color: #fca5a5; background: #fee2e2; }
    .k-footer { padding: 16px; display: flex; justify-content: space-between; align-items: center; background: white; color: #64748b; font-size: 13px; }
    .k-pagination { display: flex; gap: 5px; }
    .k-page-link { padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; text-decoration: none; color: #1e293b; font-weight: 600; }
    .k-page-link.active { background: #7c3aed; color: white; border-color: #7c3aed; }
    .k-page-link.disabled { color: #cbd5e1; pointer-events: none; }
    .nav-tabs { display: flex; gap: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
    .nav-item { padding: 12px 4px; font-weight: 600; font-size: 14px; color: #64748b; text-decoration: none; border-bottom: 2px solid transparent; cursor: pointer; }
    .nav-item:hover { color: #7c3aed; }
    .nav-item.active { color: #7c3aed; border-bottom-color: #7c3aed; }
    .k-btn-add { background: #10b981; color: white; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
    .k-btn-add:hover { background: #059669; transform: translateY(-1px); }
</style>

@include('dashboard.kontrak.modal-assets') 

<div class="k-container">
    <div class="k-header">
        <h2 class="k-title">Data List Kontrak</h2>
        <p class="k-subtitle">Kelola data list kontrak tahunan</p>
    </div>

    <div class="nav-tabs">
        <a href="{{ route('kontrak') }}" class="nav-item">List DO</a>
        <a href="{{ route('list-kontrak.index') }}" class="nav-item active">List Kontrak</a>
    </div>

    <div class="k-card">
        <div class="k-toolbar">
            <form action="{{ route('list-kontrak.index') }}" method="GET" id="filterForm" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; width: 100%;">
                <div class="k-search-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Kontrak, Pembeli...">
                </div>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <input type="date" name="start_date" class="k-date-input" value="{{ request('start_date') }}" onchange="document.getElementById('filterForm').submit()"> 
                    <span style="color:#94a3b8">-</span>
                    <input type="date" name="end_date" class="k-date-input" value="{{ request('end_date') }}" onchange="document.getElementById('filterForm').submit()"> 
                </div>
                <select name="sort" class="k-select-limit" onchange="document.getElementById('filterForm').submit()">
                    <option value="" disabled>Urutkan</option>
                    <option value="no_kontrak" {{ request('sort') == 'no_kontrak' ? 'selected' : '' }}>No Kontrak</option>
                    <option value="tgl_kontrak" {{ request('sort', 'tgl_kontrak') == 'tgl_kontrak' ? 'selected' : '' }}>Tanggal</option>
                    <option value="kuantum" {{ request('sort') == 'kuantum' ? 'selected' : '' }}>Kuantum</option>
                </select>
                <select name="direction" class="k-select-limit" onchange="document.getElementById('filterForm').submit()">
                    <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>↑ Ascending</option>
                    <option value="desc" {{ request('direction', 'desc') == 'desc' ? 'selected' : '' }}>↓ Descending</option>
                </select>
                <select name="per_page" class="k-select-limit" onchange="document.getElementById('filterForm').submit()">
                    @foreach([10, 50, 100, 250, 500, 1000] as $limit)
                        <option value="{{ $limit }}" {{ request('per_page') == $limit ? 'selected' : '' }}>{{ $limit }} Data</option>
                    @endforeach
                </select>
                <button type="submit" style="display:none"></button>
            </form>
            <button class="k-btn-add" id="btnOpenTambahList"><span>＋</span> Tambah Data</button>
        </div>

        <div class="k-table-responsive">
            <table class="k-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor Kontrak</th>
                        <th>Pembeli</th>
                        <th>Kategori</th>
                        <th>Mutu</th>
                        <th>Tanggal</th>
                        <th>Shipment</th>
                        <th>Kuantum (Kg)</th>
                        <th>Harga (USD/Rp)</th>
                        <th>Nilai Total</th>
                        <th>No SAP</th>
                        <th>Status</th>
                        <th style="width:120px; text-align:center;">Aksi</th> 
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $r)
                        <tr>
                            <td>{{ $r['no'] }}</td>
                            <td style="font-weight:700; color:#7c3aed;">
                                {{ $r['no_kontrak'] }}
                                @if($r['eudr']) <div style="font-size:10px; color:#15803d;">{{ $r['eudr'] }}</div> @endif
                            </td>
                            <td style="font-weight:600;">{{ $r['pembeli'] }}</td>
                            <td>{{ $r['kategori'] }}</td>
                            <td>{{ $r['mutu'] }}</td>
                            <td>{{ $r['tgl_kontrak'] }}</td>
                            <td>{{ $r['bln_shipment'] }}</td>
                            <td>{{ number_format((float)str_replace(['.',','],['','.'],$r['kuantum']), 0, ',', '.') }}</td>
                            <td>
                                @if(!empty($r['harga_usd'])) USD {{ $r['harga_usd'] }}
                                @else Rp {{ number_format((float)str_replace(['.',','],['','.'],$r['harga_rp']), 0, ',', '.') }}
                                @endif
                            </td>
                            <td>
                                @if(!empty($r['nilai_usd'])) USD {{ number_format((float)str_replace(['.',','],['','.'],$r['nilai_usd']), 2, ',', '.') }}
                                @else Rp {{ number_format((float)str_replace(['.',','],['','.'],$r['nilai_rp']), 0, ',', '.') }}
                                @endif
                            </td>
                            <td>{{ $r['no_sap'] }}</td>
                            <td>
                                <span class="k-badge {{ $r['lokal_ekspor'] == 'EKSPOR' ? 'k-badge-purple' : 'k-badge-blue' }}">
                                    {{ $r['lokal_ekspor'] }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex; gap:6px; justify-content:center;">
                                    <button class="k-btn-icon" title="Lihat Detail" data-open="modalDetailList" data-json="{{ json_encode($r) }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                    
                                    {{-- BUTTON EDIT (ID: modalEditList) --}}
                                    <button class="k-btn-icon" title="Edit Data" data-open="modalEditList" data-json="{{ json_encode($r) }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>

                                    {{-- BUTTON DELETE (Panggil openDeleteModalList) --}}
                                    <button class="k-btn-icon k-btn-delete" title="Hapus Data"
                                            onclick="openDeleteModalList('{{ $r['row'] }}', '{{ $r['no_kontrak'] }}')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h16zM10 11v6M14 11v6"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="13" style="text-align:center; padding: 40px; color:#94a3b8;">Data List Kontrak tidak ditemukan.</td></tr>
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

{{-- INCLUDE SEMUA MODAL KHUSUS LIST KONTRAK --}}
@include('dashboard.list_kontrak.modal-detail')
@include('dashboard.list_kontrak.modal-tambah')
@include('dashboard.list_kontrak.modal-edit')   {{-- Pastikan ini file baru (ID: modalEditList) --}}
@include('dashboard.list_kontrak.modal-delete') {{-- Pastikan ini file baru (ID: deleteModalList) --}}

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Fungsi Global Buka/Tutup Modal
    const openModal = (id) => {
        const el = document.getElementById(id);
        if(el) el.classList.add('show');
    };
    const closeModal = (id) => {
        const el = document.getElementById(id);
        if(el) el.classList.remove('show');
    };

    // 1. Logic Modal Tambah
    const btnTambah = document.getElementById('btnOpenTambahList');
    if(btnTambah){
        btnTambah.addEventListener('click', () => {
            const form = document.querySelector('#modalTambahList form');
            if(form) form.reset();
            openModal('modalTambahList');
        });
    }

    // 2. Logic Modal Detail & Edit (Delegasi Event)
    document.querySelectorAll('[data-open]').forEach(btn => {
      btn.addEventListener('click', () => {
        const modalId = btn.getAttribute('data-open');
        const rawData = btn.getAttribute('data-json');
        
        if (rawData) {
          const data = JSON.parse(rawData);

          // === POPULATE MODAL DETAIL ===
          if (modalId === 'modalDetailList') {
              const map = {
                'list_header_title': data.no_kontrak || '-',
                'list_no_kontrak': data.no_kontrak || '-',
                'list_no_sap': data.no_sap || '-',
                'list_pembeli': data.pembeli || '-',
                'list_tgl_kontrak': data.tgl_kontrak || '-',
                'list_kategori': data.kategori || '-',
                'list_mutu': data.mutu || '-',
                'list_bln_kontrak': data.bln_kontrak || '-',
                'list_bln_shipment': data.bln_shipment || '-',
                'list_lokal_ekspor': data.lokal_ekspor || '-',
                'list_eudr': data.eudr || '-',
                'list_kuantum': data.kuantum + ' Kg',
                'list_simbol': data.simbol || '-',
                'list_penetapan': data.penetapan || '-',
                'list_kurs': data.kurs ? 'Rp ' + data.kurs : '-',
                'list_harga_usd': data.harga_usd ? '$ ' + data.harga_usd : '-',
                'list_nilai_usd': data.nilai_usd ? '$ ' + data.nilai_usd : '-',
                'list_harga_rp': data.harga_rp ? 'Rp ' + data.harga_rp : '-',
                'list_nilai_rp': data.nilai_rp ? 'Rp ' + data.nilai_rp : '-',
                'list_jatuh_tempo': data.jatuh_tempo || '-'
              };
              Object.entries(map).forEach(([id, value]) => {
                const el = document.getElementById(id);
                if (el) el.innerText = value;
              });
          }

          // === POPULATE MODAL EDIT ===
          if (modalId === 'modalEditList') {
              document.getElementById('edit_list_row_index').value = data.row;
              document.getElementById('edit_no_kontrak').value = data.no_kontrak || '';
              document.getElementById('edit_no_sap').value = data.no_sap || '';
              document.getElementById('edit_pembeli').value = data.pembeli || '';
              
              // PERBAIKAN: Gunakan 'tgl_input' (format Y-m-d) agar terbaca input date
              document.getElementById('edit_tgl_kontrak').value = data.tgl_input || '';
              
              document.getElementById('edit_kategori').value = data.kategori || '';
              document.getElementById('edit_mutu').value = data.mutu || '';
              document.getElementById('edit_simbol').value = data.simbol || '';
              
              let kuantumClean = (data.kuantum + '').replace(/\./g, '').replace(/,/g, '.');
              document.getElementById('edit_kuantum').value = parseFloat(kuantumClean) || '';

              document.getElementById('edit_bln_kontrak').value = data.bln_kontrak || '';
              document.getElementById('edit_bln_shipment').value = data.bln_shipment || '';
              document.getElementById('edit_lokal_ekspor').value = data.lokal_ekspor || 'LOKAL';
              document.getElementById('edit_eudr').value = data.eudr || '';
              document.getElementById('edit_penetapan').value = data.penetapan || '';
              
              // PERBAIKAN: Gunakan 'jatuh_tempo_in' (format Y-m-d)
              document.getElementById('edit_jatuh_tempo').value = data.jatuh_tempo_in || '';

              // Clean Number
              const clean = (val) => (val + '').replace(/[^\d.,]/g, '').replace(/\./g, '').replace(',', '.');
              document.getElementById('edit_kurs').value = clean(data.kurs);
              document.getElementById('edit_harga_usd').value = clean(data.harga_usd);
              document.getElementById('edit_nilai_usd').value = clean(data.nilai_usd);
              document.getElementById('edit_harga_rp').value = clean(data.harga_rp);
              document.getElementById('edit_nilai_rp').value = clean(data.nilai_rp);
          }
        }
        
        openModal(modalId);
      });
    });

    // Logic Tutup Modal
    document.querySelectorAll('[data-close]').forEach(btn => {
      btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close')));
    });
});

// 3. Logic Hapus Data (Global Scope)
function openDeleteModalList(rowId, noKontrak) {
    const textSpan = document.getElementById('delete_target_name');
    if(textSpan) textSpan.innerText = noKontrak;

    const form = document.getElementById('deleteFormList');
    if(form) {
        let url = '{{ route("list-kontrak.destroy", ":id") }}';
        url = url.replace(':id', rowId);
        form.action = url;
    }

    const modal = document.getElementById('deleteModalList');
    if(modal) modal.classList.add('show');
}

function closeDeleteModalList() {
    const modal = document.getElementById('deleteModalList');
    if(modal) modal.classList.remove('show');
}
</script>
@endsection