<div class="m-overlay" id="modalEdit" aria-hidden="true">
  <div class="m-dialog" role="dialog" aria-modal="true">
    <div class="m-header">
      <h3 class="m-title">Edit Kontrak Penjualan</h3>
      <p class="m-subtitle">Perbarui informasi kontrak</p>
      <button class="m-close" type="button" data-close="modalEdit">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>

    <form method="POST" action="{{ route('kontrak.update') }}" class="m-body">
      @csrf
      @method('PUT')
      <input type="hidden" name="row_index" id="edit_row_index">

      <div class="m-section">
        <div class="m-section-head">Informasi Kontrak</div>
        <div class="m-grid">
          <div class="m-field"><label>LO/EX</label><input class="m-input" name="loex" /></div>
          <div class="m-field"><label>Nomor Kontrak</label><input class="m-input" name="nomor_kontrak" /></div>
          <div class="m-field"><label>Nama Pembeli</label><input class="m-input" name="nama_pembeli" /></div>
          <div class="m-field"><label>Tgl. Kontrak</label><input class="m-input" type="date" name="tgl_kontrak" /></div>
          <div class="m-field"><label>Volume</label><input class="m-input" name="volume" /></div>
          <div class="m-field"><label>Harga</label><input class="m-input" name="harga" /></div>
          <div class="m-field"><label>Nilai</label><input class="m-input" name="nilai" /></div>
          <div class="m-field"><label>Incl PPN</label><input class="m-input" name="inc_ppn" /></div>
          <div class="m-field"><label>Tgl Bayar</label><input class="m-input" type="date" name="tgl_bayar" /></div>
          <div class="m-field"><label>Unit</label><input class="m-input" name="unit" /></div>
          <div class="m-field"><label>Mutu</label><input class="m-input" name="mutu" /></div>
        </div>
      </div>

      <div class="m-section">
        <div class="m-section-head">Informasi Pengiriman & SAP</div>
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
        <button type="submit" class="m-btn m-btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>