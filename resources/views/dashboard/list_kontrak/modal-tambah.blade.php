{{-- FILE: resources/views/dashboard/list_kontrak/modal-tambah.blade.php --}}
<div class="m-overlay" id="modalTambahList" aria-hidden="true">
  <div class="m-dialog" role="dialog" aria-modal="true">
    <div class="m-header">
      <h3 class="m-title">Tambah Data List Kontrak</h3>
      <p class="m-subtitle">Input manual data kontrak baru ke database</p>
      <button class="m-close" type="button" data-close="modalTambahList">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>

    <form method="POST" action="{{ route('list-kontrak.store') }}" class="m-body">
      @csrf
      
      {{-- SECTION 1: IDENTITAS KONTRAK --}}
      <div class="m-section">
        <div class="m-section-head">
            <div class="m-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
            Identitas Kontrak
        </div>
        <div class="m-grid">
          <div class="m-field"><label>Nomor Kontrak <span class="req">*</span></label><input class="m-input" name="no_kontrak" required placeholder="Contoh: 001/SC/..." /></div>
          <div class="m-field"><label>Nomor SAP</label><input class="m-input" name="no_sap" placeholder="Nomor SAP" /></div>
          <div class="m-field"><label>Pembeli</label><input class="m-input" name="pembeli" placeholder="Nama Buyer" /></div>
          <div class="m-field"><label>Tanggal Kontrak</label><input class="m-input" type="date" name="tgl_kontrak" /></div>
          <div class="m-field"><label>Lokal / Ekspor</label>
            <select class="m-input" name="lokal_ekspor">
                <option value="LOKAL">LOKAL</option>
                <option value="EKSPOR">EKSPOR</option>
            </select>
          </div>
          <div class="m-field"><label>Status EUDR</label><input class="m-input" name="eudr" placeholder="Ya/Tidak/Pending" /></div>
        </div>
      </div>

      {{-- SECTION 2: SPESIFIKASI BARANG --}}
      <div class="m-section">
        <div class="m-section-head">
            <div class="m-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            </div>
            Spesifikasi & Pengiriman
        </div>
        <div class="m-grid">
          <div class="m-field"><label>Kategori</label><input class="m-input" name="kategori" placeholder="Contoh: SIR 20" /></div>
          <div class="m-field"><label>Mutu</label><input class="m-input" name="mutu" /></div>
          <div class="m-field"><label>Simbol</label><input class="m-input" name="simbol" /></div>
          <div class="m-field"><label>Kuantum (Kg)</label><input class="m-input" type="number" name="kuantum" step="0.01" /></div>
          <div class="m-field"><label>Bulan Kontrak</label><input class="m-input" name="bln_kontrak" placeholder="Januari" /></div>
          <div class="m-field"><label>Bulan Shipment</label><input class="m-input" name="bln_shipment" placeholder="Februari" /></div>
        </div>
      </div>

      {{-- SECTION 3: NILAI & HARGA --}}
      <div class="m-section">
        <div class="m-section-head">
            <div class="m-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            Harga & Valuasi
        </div>
        <div class="m-grid">
          <div class="m-field"><label>Penetapan</label><input class="m-input" name="penetapan" /></div>
          <div class="m-field"><label>Kurs (IDR)</label><input class="m-input" type="number" name="kurs" step="any" /></div>
          <div class="m-field"><label>Jatuh Tempo</label><input class="m-input" type="date" name="jatuh_tempo" /></div>
          
          {{-- Mata Uang Asing --}}
          <div class="m-field"><label>Harga (USD)</label><input class="m-input" type="number" name="harga_usd" step="any" placeholder="$" /></div>
          <div class="m-field"><label>Nilai Total (USD)</label><input class="m-input" type="number" name="nilai_usd" step="any" placeholder="$" /></div>
          
          {{-- Rupiah --}}
          <div class="m-field"><label>Harga (Rp)</label><input class="m-input" type="number" name="harga_rp" step="any" placeholder="Rp" /></div>
          <div class="m-field"><label>Nilai Total (Rp)</label><input class="m-input" type="number" name="nilai_rp" step="any" placeholder="Rp" /></div>
          <div class="m-field"></div> {{-- Filler --}}
        </div>
      </div>

      <div class="m-footer">
        <button type="button" class="m-btn" data-close="modalTambahList">Batal</button>
        <button type="submit" class="m-btn m-btn-primary">Simpan Data</button>
      </div>
    </form>
  </div>
</div>