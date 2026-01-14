<div class="m-overlay" id="modalDetailList" aria-hidden="true">
  <div class="m-dialog" role="dialog" aria-modal="true" style="max-width: 980px;">
    <div class="m-header">
      <h3 class="m-title">Detail List Kontrak</h3>
      <p class="m-subtitle" id="list_header_title">-</p>
      <button class="m-close" type="button" data-close="modalDetailList">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>

    <div class="m-body">
      <div class="m-two-col">
        <div class="m-info-card">
          <div class="m-info-title">Informasi Umum</div>
          <table class="m-kv">
            <tr><td>Nomor Kontrak</td><td id="list_no_kontrak" style="font-weight:bold; color:#7c3aed">-</td></tr>
            <tr><td>Nomor SAP</td><td id="list_no_sap">-</td></tr>
            <tr><td>Pembeli</td><td id="list_pembeli">-</td></tr>
            <tr><td>Tanggal Kontrak</td><td id="list_tgl_kontrak">-</td></tr>
            <tr><td>Kategori</td><td id="list_kategori">-</td></tr>
            <tr><td>Mutu</td><td id="list_mutu">-</td></tr>
            <tr><td>Bulan Kontrak</td><td id="list_bln_kontrak">-</td></tr>
            <tr><td>Bulan Shipment</td><td id="list_bln_shipment">-</td></tr>
            <tr><td>Jenis</td><td id="list_lokal_ekspor">-</td></tr>
            <tr><td>Status EUDR</td><td id="list_eudr">-</td></tr>
          </table>
        </div>

        <div class="m-info-card">
          <div class="m-info-title">Informasi Nilai & Harga</div>
          <table class="m-kv">
            <tr><td>Kuantum (Kg)</td><td id="list_kuantum">-</td></tr>
            <tr><td>Simbol</td><td id="list_simbol">-</td></tr>
            <tr><td>Penetapan</td><td id="list_penetapan">-</td></tr>
            <tr><td>Kurs (Rp)</td><td id="list_kurs">-</td></tr>
            
            <tr><td colspan="2" style="border-bottom:none; padding-top:15px; font-weight:800; color:#0f172a;">Valuasi USD</td></tr>
            <tr><td style="padding-left:10px;">Harga USD</td><td id="list_harga_usd">-</td></tr>
            <tr><td style="padding-left:10px;">Nilai USD</td><td id="list_nilai_usd">-</td></tr>

            <tr><td colspan="2" style="border-bottom:none; padding-top:10px; font-weight:800; color:#0f172a;">Valuasi IDR</td></tr>
            <tr><td style="padding-left:10px;">Harga Rp</td><td id="list_harga_rp">-</td></tr>
            <tr><td style="padding-left:10px;">Nilai Rp</td><td id="list_nilai_rp">-</td></tr>

            <tr><td style="padding-top:15px;">Jatuh Tempo Pembayaran</td><td id="list_jatuh_tempo" style="padding-top:15px; color:#ef4444; font-weight:bold;">-</td></tr>
          </table>
        </div>
      </div>
    </div>

    <div class="m-footer">
      <button type="button" class="m-btn m-btn-purple" data-close="modalDetailList">Tutup</button>
    </div>
  </div>
</div>