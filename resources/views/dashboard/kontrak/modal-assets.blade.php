<style>
  /* ===== Modal System (Match desain gambar) ===== */
  .m-overlay{
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    display:none;
    align-items:center;
    justify-content:center;
    padding: 28px 18px;
    z-index: 9999;
  }
  .m-overlay.show{ display:flex; }

  .m-dialog{
    width: 100%;
    max-width: 1050px;
    background:#fff;
    border-radius: 14px;
    overflow:hidden;
    box-shadow: 0 18px 60px rgba(2,6,23,.35);
  }

  .m-header{
    padding: 16px 20px;
    background: linear-gradient(90deg, #ef4444, #f97316);
    color:#fff;
    position:relative;
  }
  .m-title{ margin:0; font-size: 18px; font-weight: 800; }
  .m-subtitle{ margin:4px 0 0; font-size: 12px; opacity:.9; }

  .m-close{
    position:absolute; right: 14px; top: 12px;
    width: 36px; height: 36px;
    display:grid; place-items:center;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,.25);
    background: rgba(255,255,255,.08);
    cursor:pointer;
  }
  .m-close:hover{ background: rgba(255,255,255,.14); }

  .m-body{
    padding: 18px 20px 10px;
    max-height: calc(100vh - 170px);
    overflow:auto;
    background:#fff;
  }

  .m-section{
    background:#f8fafc;
    border: 1px solid #eef2f7;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 14px;
  }

  .m-section-head{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom: 12px;
    color:#0f172a;
    font-weight:800;
    font-size: 13px;
  }
  .m-icon{
    width: 28px; height: 28px;
    border-radius: 10px;
    background: #ede9fe;
    display:grid; place-items:center;
    border: 1px solid #e9d5ff;
  }

  .m-grid{
    display:grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
  }

  .m-field label{
    display:block;
    font-size: 12px;
    color:#0f172a;
    font-weight: 700;
    margin-bottom: 6px;
  }
  .m-field .req{ color:#ef4444; font-weight:900; margin-left:4px; }

  .m-input{
    width:100%;
    padding: 11px 12px;
    border-radius: 10px;
    border: 1px solid #dbe2ea;
    background:#fff;
    font-size: 13px;
    color:#0f172a;
    outline:none;
  }
  .m-input:focus{
    border-color:#a78bfa;
    box-shadow: 0 0 0 3px rgba(167,139,250,.18);
  }

  .m-date-wrap{ position:relative; }
  .m-date-ico{
    position:absolute; right: 10px; top: 50%;
    transform: translateY(-50%);
    pointer-events:none;
    opacity:.7;
  }

  .m-footer{
    display:flex;
    justify-content:flex-end;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid #eef2f7;
    background:#fff;
  }

  .m-btn{
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 800;
    font-size: 13px;
    border: 1px solid #dbe2ea;
    background:#fff;
    cursor:pointer;
    color:#0f172a;
  }
  .m-btn:hover{ background:#f8fafc; }

  .m-btn-primary{
    border-color: transparent;
    background:#16a34a;
    color:#fff;
    display:inline-flex;
    align-items:center;
    gap:10px;
    box-shadow: 0 8px 18px rgba(22,163,74,.18);
  }
  .m-btn-primary:hover{ filter: brightness(.98); }

  .m-btn-purple{
    border-color: transparent;
    background:#7c3aed;
    color:#fff;
    box-shadow: 0 8px 18px rgba(124,58,237,.18);
  }

  /* Detail modal */
  .m-two-col{
    display:grid;
    grid-template-columns: 1.15fr .85fr;
    gap: 14px;
  }
  .m-info-card{
    background:#f8fafc;
    border: 1px solid #eef2f7;
    border-radius: 12px;
    padding: 14px;
  }
  .m-info-title{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom: 12px;
    font-weight: 900;
    color:#0f172a;
    font-size: 13px;
  }
  .m-kv{ width:100%; border-collapse:collapse; }
  .m-kv tr td{
    padding: 10px 0;
    border-bottom: 1px solid #eef2f7;
    font-size: 12.5px;
    color:#0f172a;
    vertical-align:top;
  }
  .m-kv tr td:first-child{
    width: 44%;
    color:#475569;
    font-weight: 700;
    padding-right: 14px;
  }
  .m-kv tr:last-child td{ border-bottom:none; }

  @media (max-width: 980px){
    .m-grid{ grid-template-columns: 1fr 1fr; }
    .m-two-col{ grid-template-columns: 1fr; }
  }
  @media (max-width: 640px){
    .m-grid{ grid-template-columns: 1fr; }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const openModal = (id) => document.getElementById(id)?.classList.add('show');
    const closeModal = (id) => document.getElementById(id)?.classList.remove('show');

    // Klik tombol dengan data-open (Detail & Edit)
    document.querySelectorAll('[data-open]').forEach(btn => {
      btn.addEventListener('click', () => {
        const modalId = btn.getAttribute('data-open');
        const rawData = btn.getAttribute('data-json');
        
        if (rawData) {
          const data = JSON.parse(rawData);
          fillModalData(modalId, data);
        }
        
        openModal(modalId);
      });
    });

    // Fungsi untuk mengisi data ke modal (MENGGUNAKAN VARIABEL HURUF)
    function fillModalData(modalId, data) {
      // Helper: format number with thousand separator (dot) and no decimals
      function formatNumberRaw(value) {
        if (value === null || value === undefined || value === '') return '-';
        // if contains letters, return as-is
        if (typeof value === 'string' && /[A-Za-z]/.test(value)) return value;
        const n = Number(String(value).replace(/[^0-9\-\.]/g, '').replace(/\./g, ''));
        if (isNaN(n)) return value;
        return n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
      }

      function detectCurrencyAndFormat(value) {
        if (!value && value !== 0) return '-';
        // If already contains currency symbol
        if (typeof value === 'string' && /\$|USD|EUR|Rp|IDR/.test(value)) return value;
        // Remove non numeric except dot/comma
        let cleaned = String(value).trim();
        // Try to convert strings like "100.000.00" or "1.234.567,89"
        cleaned = cleaned.replace(/\./g, '').replace(/,/g, '.');
        const num = Number(cleaned);
        if (isNaN(num)) return value;
        // Default to Indonesian Rupiah
        return 'Rp ' + num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
      }

      if (modalId === 'modalDetail') {
        const modal = document.getElementById('modalDetail');

        // Set nomor kontrak di subtitle
        document.getElementById('detail_nomor_kontrak').innerText = data.I || '-';

        // Populate fields with formatting
        const map = {
          'detail_H': data.H || '-',
          'detail_I': data.I || '-',
          'detail_J': data.J || '-',
          'detail_K': data.K || '-',
          'detail_L': (data.L || data.L === 0) ? formatNumberRaw(data.L) + ' Kg' : '-',
          'detail_M': (data.M || data.M === 0) ? detectCurrencyAndFormat(data.M) : '-',
          'detail_N': (data.N || data.N === 0) ? detectCurrencyAndFormat(data.N) : '-',
          'detail_O': data.O || '-',
          'detail_P': data.P || '-',
          'detail_Q': data.Q || '-',
          'detail_R': data.R || '-',
          'detail_S': data.S || '-',
          'detail_T': data.T || '-',
          'detail_U': data.U || '-',
          'detail_V': data.V || '-',
          'detail_W': data.W || '-',
          'detail_X': data.X || '-',
          'detail_Y': data.Y || '-',
          'detail_Z': (data.Z || data.Z === 0) ? formatNumberRaw(data.Z) + ' Kg' : '-',
          'detail_AA': (data.AA || data.AA === 0) ? formatNumberRaw(data.AA) + ' Kg' : '-',
          'detail_AB': (data.AB || data.AB === 0) ? formatNumberRaw(data.AB) + ' Kg' : '-',
          'detail_BA': data.BA || '-',
        };

        Object.entries(map).forEach(([id, value]) => {
          const el = document.getElementById(id);
          if (el) el.innerText = value;
        });
      }

      // Fill Edit Modal
      if (modalId === 'modalEdit') {
          const form = document.getElementById('modalEdit').querySelector('form');

          // Set id
          form.querySelector('[name="id"]').value = data.id || data.row;

          // Fill Manual Fields (raw strings)
          form.querySelector('[name="loex"]').value = data.H || '';
          form.querySelector('[name="nomor_kontrak"]').value = data.I || '';
          form.querySelector('[name="nama_pembeli"]').value = data.J || '';
          form.querySelector('[name="tgl_kontrak"]').value = data.K || '';
          form.querySelector('[name="volume"]').value = data.L || '';
          form.querySelector('[name="harga"]').value = data.M || '';
          form.querySelector('[name="nilai"]').value = data.N || '';
          form.querySelector('[name="inc_ppn"]').value = data.O || '';
          form.querySelector('[name="tgl_bayar"]').value = data.P || '';
          form.querySelector('[name="unit"]').value = data.Q || '';
          form.querySelector('[name="mutu"]').value = data.R || '';
          form.querySelector('[name="nomor_dosi"]').value = data.S || '';
          form.querySelector('[name="tgl_dosi"]').value = data.T || '';
          form.querySelector('[name="port"]').value = data.U || '';
          form.querySelector('[name="kontrak_sap"]').value = data.V || '';
          form.querySelector('[name="dp_sap"]').value = data.W || '';
          form.querySelector('[name="so_sap"]').value = data.X || '';
          form.querySelector('[name="jatuh_tempo"]').value = data.BA || '';
      }
    }

    // Tombol Tambah (Membersihkan form)
    const btnTambah = document.getElementById('btnOpenTambah');
    if (btnTambah) {
        btnTambah.addEventListener('click', () => {
            const form = document.getElementById('modalTambah').querySelector('form');
            form.reset();
            openModal('modalTambah');
        });
    }

    // Close buttons logic
    document.querySelectorAll('[data-close]').forEach(btn => {
      btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close')));
    });

    // Close on overlay click
    document.querySelectorAll('.m-overlay').forEach(ov => {
      ov.addEventListener('click', (e) => {
        if (e.target === ov) ov.classList.remove('show');
      });
    });

    // ESC -> close
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.m-overlay.show').forEach(ov => ov.classList.remove('show'));
      }
    });
  });
</script>