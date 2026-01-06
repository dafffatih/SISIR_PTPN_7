<div class="m-overlay" id="modalTambah" aria-hidden="true">
  <div class="m-dialog">
    <div class="m-header">
      <h3 class="m-title">Tambah Kontrak Penjualan</h3>
      <p class="m-subtitle">Input data kontrak manual</p>
      <button class="m-close" type="button" data-close="modalTambah">✕</button>
    </div>

    <form method="POST" action="{{ route('kontrak.store') }}" class="m-body">
      @csrf
      <div class="m-section">
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
        <button type="button" class="m-btn" data-close="modalTambah">Batal</button>
        <button type="submit" class="m-btn m-btn-primary">Simpan Data Baru</button>
      </div>
    </form>
  </div>
</div>