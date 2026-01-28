{{-- FILE: resources/views/dashboard/list_kontrak/modal-edit.blade.php --}}
<div class="m-overlay" id="modalEditList" aria-hidden="true">
  <div class="m-dialog" role="dialog" aria-modal="true">
    <div class="m-header">
      <h3 class="m-title">Edit Data List Kontrak</h3>
      <p class="m-subtitle">Perbarui informasi kontrak yang dipilih</p>
      <button class="m-close" type="button" data-close="modalEditList">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>

    <form method="POST" action="{{ route('list-kontrak.update') }}" class="m-body">
      @csrf
      @method('PUT')
      <input type="hidden" name="row_index" id="edit_list_row_index">

      {{-- SECTION 1: IDENTITAS --}}
      <div class="m-section">
        <div class="m-section-head">
            <div class="m-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></div>
            Identitas Kontrak
        </div>
        <div class="m-grid">
          <div class="m-field"><label>Nomor Kontrak</label><input class="m-input" name="no_kontrak" id="edit_no_kontrak" /></div>
          <div class="m-field"><label>Nomor SAP</label><input class="m-input" name="no_sap" id="edit_no_sap" /></div>
          <div class="m-field"><label>Pembeli</label><input class="m-input" name="pembeli" id="edit_pembeli" /></div>
          <div class="m-field"><label>Tanggal Kontrak</label><input class="m-input" type="date" name="tgl_kontrak" id="edit_tgl_kontrak" /></div>
          <div class="m-field"><label>Lokal / Ekspor</label>
            <select class="m-input" name="lokal_ekspor" id="edit_lokal_ekspor">
                <option value="LOKAL">LOKAL</option>
                <option value="EKSPOR">EKSPOR</option>
            </select>
          </div>
          <div class="m-field"><label>Status EUDR</label><input class="m-input" name="eudr" id="edit_eudr" /></div>
        </div>
      </div>

      {{-- SECTION 2: SPESIFIKASI --}}
      <div class="m-section">
        <div class="m-section-head">
            <div class="m-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect></svg></div>
            Spesifikasi
        </div>
        <div class="m-grid">
          <div class="m-field"><label>Kategori</label><input class="m-input" name="kategori" id="edit_kategori" /></div>
          <div class="m-field"><label>Mutu</label><input class="m-input" name="mutu" id="edit_mutu" /></div>
          <div class="m-field"><label>Simbol</label><input class="m-input" name="simbol" id="edit_simbol" /></div>
          <div class="m-field"><label>Kuantum (Kg)</label><input class="m-input" type="number" step="0.01" name="kuantum" id="edit_kuantum" /></div>
          <div class="m-field"><label>Bulan Kontrak</label><input class="m-input" name="bln_kontrak" id="edit_bln_kontrak" /></div>
          <div class="m-field"><label>Bulan Shipment</label><input class="m-input" name="bln_shipment" id="edit_bln_shipment" /></div>
        </div>
      </div>

      {{-- SECTION 3: NILAI --}}
      <div class="m-section">
        <div class="m-section-head">
            <div class="m-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>
            Valuasi
        </div>
        <div class="m-grid">
          <div class="m-field"><label>Penetapan</label><input class="m-input" name="penetapan" id="edit_penetapan" /></div>
          <div class="m-field"><label>Kurs</label><input class="m-input" type="number" step="any" name="kurs" id="edit_kurs" /></div>
          <div class="m-field"><label>Jatuh Tempo</label><input class="m-input" type="date" name="jatuh_tempo" id="edit_jatuh_tempo" /></div>
          <div class="m-field"><label>Harga USD</label><input class="m-input" type="number" step="any" name="harga_usd" id="edit_harga_usd" /></div>
          <div class="m-field"><label>Nilai USD</label><input class="m-input" type="number" step="any" name="nilai_usd" id="edit_nilai_usd" /></div>
          <div class="m-field"><label>Harga Rp</label><input class="m-input" type="number" step="any" name="harga_rp" id="edit_harga_rp" /></div>
          <div class="m-field"><label>Nilai Rp</label><input class="m-input" type="number" step="any" name="nilai_rp" id="edit_nilai_rp" /></div>
        </div>
      </div>

      <div class="m-footer">
        <button type="button" class="m-btn" data-close="modalEditList">Batal</button>
        <button type="submit" class="m-btn m-btn-purple">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>