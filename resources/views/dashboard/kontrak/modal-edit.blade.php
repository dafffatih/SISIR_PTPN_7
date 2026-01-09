<div class="m-overlay" id="modalEdit" aria-hidden="true">
  <div class="m-dialog" role="dialog" aria-modal="true">
    <div class="m-header">
      <h3 class="m-title">Edit Kontrak Penjualan</h3>
      <p class="m-subtitle">Perbarui informasi kontrak yang sudah ada</p>
      <button class="m-close" type="button" data-close="modalEdit">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>

    <form method="POST" action="{{ route('kontrak.update') }}" class="m-body">
      @csrf
      @method('PUT')
      {{-- Input hidden untuk menyimpan nomor baris atau ID --}}
      <input type="hidden" name="row_index" id="edit_row_index">
      <input type="hidden" name="id" id="edit_id">

      {{-- Bagian 1: Informasi Kontrak --}}
      <div class="m-section">
        <div class="m-section-head">
            <div class="m-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
            Informasi Kontrak
        </div>
        <div class="m-grid">
          <div class="m-field"><label>LO/EX</label><input class="m-input" name="loex" /></div>
          <div class="m-field"><label>Nomor Kontrak</label><input class="m-input" name="nomor_kontrak" /></div>
          <div class="m-field"><label>Nama Pembeli</label><input class="m-input" name="nama_pembeli" /></div>
          <div class="m-field"><label>Tgl. Kontrak</label><input class="m-input" type="date" name="tgl_kontrak" /></div>
          <div class="m-field"><label>Volume (Kg)</label><input class="m-input" name="volume" /></div>
          <div class="m-field"><label>Harga</label><input class="m-input" name="harga" /></div>
          <div class="m-field"><label>Nilai Total</label><input class="m-input" name="nilai" /></div>
          <div class="m-field"><label>Incl PPN</label><input class="m-input" name="inc_ppn" /></div>
          <div class="m-field"><label>Tgl Bayar</label><input class="m-input" type="date" name="tgl_bayar" /></div>
          <div class="m-field"><label>Unit</label><input class="m-input" name="unit" /></div>
          <div class="m-field"><label>Mutu</label><input class="m-input" name="mutu" /></div>
        </div>
      </div>

      {{-- Bagian 2: Informasi Pengiriman & SAP --}}
      <div class="m-section">
        <div class="m-section-head">
            <div class="m-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            </div>
            Informasi Pengiriman & SAP
        </div>
        <div class="m-grid">
          <div class="m-field"><label>Nomor DO/SI</label><input class="m-input" name="nomor_dosi" /></div>
          <div class="m-field"><label>Tgl DO/SI</label><input class="m-input" type="date" name="tgl_dosi" /></div>
          <div class="m-field"><label>PORT</label><input class="m-input" name="port" /></div>
          <div class="m-field"><label>Kontrak SAP</label><input class="m-input" name="kontrak_sap" /></div>
          <div class="m-field"><label>DP SAP</label><input class="m-input" name="dp_sap" /></div>
          <div class="m-field"><label>SO SAP</label><input class="m-input" name="so_sap" /></div>
          <div class="m-field"><label>Tanggal Jatuh Tempo</label><input class="m-input" type="date" name="jatuh_tempo" /></div>
        </div>
      </div>

      <div class="m-footer">
        <button type="button" class="m-btn" data-close="modalEdit">Batal</button>
        <button type="submit" class="m-btn m-btn-purple">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>