<div class="m-overlay" id="modalTambah" aria-hidden="true">
  <div class="m-dialog" role="dialog" aria-modal="true">
    <div class="m-header">
      <h3 class="m-title">Tambah Kontrak Penjualan Baru</h3>
      <p class="m-subtitle">Perbarui informasi kontrak</p>
      <button class="m-close" type="button" data-close="modalTambah" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 6l12 12M18 6L6 18" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <form method="POST" action="#" class="m-body">
      @csrf

      <div class="m-section">
        <div class="m-section-head">
          <div class="m-icon">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" stroke="#7c3aed" stroke-width="2"/>
              <path d="M14 3v4h4" stroke="#7c3aed" stroke-width="2"/>
            </svg>
          </div>
          Informasi Kontrak
        </div>

        <div class="m-grid">
          <div class="m-field">
            <label>LO/EX</label>
            <input class="m-input" name="loex" value="LO" />
          </div>

          <div class="m-field">
            <label>Nomor Kontrak <span class="req">*</span></label>
            <input class="m-input" name="nomor_kontrak" />
          </div>

          <div class="m-field">
            <label>Nama Pembeli <span class="req">*</span></label>
            <input class="m-input" name="nama_pembeli" />
          </div>

          <div class="m-field m-date-wrap">
            <label>Tanggal Kontrak <span class="req">*</span></label>
            <input class="m-input" type="date" name="tgl_kontrak" />
            <span class="m-date-ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" stroke="#64748b" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
          </div>

          <div class="m-field">
            <label>Volume (Kg) <span class="req">*</span></label>
            <input class="m-input" name="volume" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Harga (IDR) <span class="req">*</span></label>
            <input class="m-input" name="harga" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Nilai (IDR)</label>
            <input class="m-input" name="nilai" value="0" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Inc PPN</label>
            <select class="m-input" name="inc_ppn">
              <option selected>Ya</option>
              <option>Tidak</option>
            </select>
          </div>

          <div class="m-field m-date-wrap">
            <label>Tanggal Bayar</label>
            <input class="m-input" type="date" name="tgl_bayar" />
            <span class="m-date-ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" stroke="#64748b" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
          </div>

          <div class="m-field">
            <label>Unit</label>
            <input class="m-input" name="unit" value="Kg" />
          </div>

          <div class="m-field">
            <label>Mutu</label>
            <input class="m-input" name="mutu" />
          </div>

          <div></div>
        </div>
      </div>

      <div class="m-section">
        <div class="m-section-head">
          <div class="m-icon">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M3 7h12v10H3V7z" stroke="#7c3aed" stroke-width="2"/>
              <path d="M15 10h4l2 2v5h-6V10z" stroke="#7c3aed" stroke-width="2"/>
              <path d="M7 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" stroke="#7c3aed" stroke-width="2"/>
              <path d="M17 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" stroke="#7c3aed" stroke-width="2"/>
            </svg>
          </div>
          Informasi Pengiriman &amp; SAP
        </div>

        <div class="m-grid">
          <div class="m-field">
            <label>Nomor DO/SI</label>
            <input class="m-input" name="nomor_dosi" />
          </div>

          <div class="m-field m-date-wrap">
            <label>Tanggal DO/SI</label>
            <input class="m-input" type="date" name="tgl_dosi" />
            <span class="m-date-ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" stroke="#64748b" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
          </div>

          <div class="m-field">
            <label>Port</label>
            <input class="m-input" name="port" />
          </div>

          <div class="m-field">
            <label>Kontrak SAP</label>
            <input class="m-input" name="kontrak_sap" />
          </div>

          <div class="m-field">
            <label>DP SAP</label>
            <input class="m-input" name="dp_sap" />
          </div>

          <div class="m-field">
            <label>SO SAP</label>
            <input class="m-input" name="so_sap" />
          </div>

          <div class="m-field">
            <label>Sisa Awal (Kg)</label>
            <input class="m-input" name="sisa_awal" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Total Dilayani (Kg)</label>
            <input class="m-input" name="total_dilayani" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Sisa Akhir (Kg)</label>
            <input class="m-input" name="sisa_akhir" inputmode="numeric" />
          </div>
        </div>
      </div>

      <div class="m-footer">
        <button type="button" class="m-btn" data-close="modalTambah">Batal</button>
        <button type="submit" class="m-btn m-btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 3h11l3 3v15a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" stroke="#fff" stroke-width="2"/>
            <path d="M7 21v-8h10v8" stroke="#fff" stroke-width="2"/>
            <path d="M7 3v6h8V3" stroke="#fff" stroke-width="2"/>
          </svg>
          Simpan Data
        </button>
      </div>
    </form>
  </div>
</div>
