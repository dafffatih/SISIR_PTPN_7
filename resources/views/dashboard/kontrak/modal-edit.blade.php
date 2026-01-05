<div class="m-overlay" id="modalEdit" aria-hidden="true">
  <div class="m-dialog" role="dialog" aria-modal="true">
    <div class="m-header">
      <h3 class="m-title">Edit Kontrak Penjualan</h3>
      <p class="m-subtitle">Perbarui informasi kontrak</p>
      <button class="m-close" type="button" data-close="modalEdit" aria-label="Close">
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
            <input class="m-input" name="nomor_kontrak" value="0528/HO-SUPCO/SIR-LN-I/X/2024" />
          </div>

          <div class="m-field">
            <label>Nama Pembeli <span class="req">*</span></label>
            <input class="m-input" name="nama_pembeli" value="Bitung Gunasejahtera" />
          </div>

          <div class="m-field m-date-wrap">
            <label>Tanggal Kontrak <span class="req">*</span></label>
            <input class="m-input" type="date" name="tgl_kontrak" value="2024-01-15" />
            <span class="m-date-ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" stroke="#64748b" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
          </div>

          <div class="m-field">
            <label>Volume (Kg) <span class="req">*</span></label>
            <input class="m-input" name="volume" value="50000" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Harga (IDR) <span class="req">*</span></label>
            <input class="m-input" name="harga" value="32000" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Nilai (IDR)</label>
            <input class="m-input" name="nilai" value="750000000" inputmode="numeric" />
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
            <input class="m-input" type="date" name="tgl_bayar" value="2024-02-15" />
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
            <input class="m-input" name="mutu" value="Grade A" />
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
            </svg>
          </div>
          Informasi Pengiriman &amp; SAP
        </div>

        <div class="m-grid">
          <div class="m-field">
            <label>Nomor DO/SI</label>
            <input class="m-input" name="nomor_dosi" value="DO-2024-001" />
          </div>

          <div class="m-field m-date-wrap">
            <label>Tanggal DO/SI</label>
            <input class="m-input" type="date" name="tgl_dosi" value="2024-01-20" />
            <span class="m-date-ico">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" stroke="#64748b" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
          </div>

          <div class="m-field">
            <label>Port</label>
            <input class="m-input" name="port" value="Jakarta" />
          </div>

          <div class="m-field">
            <label>Kontrak SAP</label>
            <input class="m-input" name="kontrak_sap" value="SAP-KTR-001" />
          </div>

          <div class="m-field">
            <label>DP SAP</label>
            <input class="m-input" name="dp_sap" value="DP-001" />
          </div>

          <div class="m-field">
            <label>SO SAP</label>
            <input class="m-input" name="so_sap" value="SO-001" />
          </div>

          <div class="m-field">
            <label>Sisa Awal (Kg)</label>
            <input class="m-input" name="sisa_awal" value="50000" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Total Dilayani (Kg)</label>
            <input class="m-input" name="total_dilayani" value="30000" inputmode="numeric" />
          </div>

          <div class="m-field">
            <label>Sisa Akhir (Kg)</label>
            <input class="m-input" name="sisa_akhir" value="20000" inputmode="numeric" />
          </div>
        </div>
      </div>

      <div class="m-footer">
        <button type="button" class="m-btn" data-close="modalEdit">Batal</button>
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
