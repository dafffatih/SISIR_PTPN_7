<style>
  /* CSS Khusus Modal Delete */
  .modal-backdrop {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.6); /* Gelap transparan */
    display: none; /* Hidden by default */
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
    transition: opacity 0.3s ease;
  }

  .modal-backdrop.show {
    display: flex;
  }

  .modal-content {
    background: white;
    width: 90%;
    max-width: 400px;
    border-radius: 16px;
    padding: 30px 24px;
    text-align: center;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    transform: scale(0.95);
    transition: transform 0.3s ease;
    border: 1px solid #f1f5f9;
  }

  .modal-backdrop.show .modal-content {
    transform: scale(1);
  }

  .modal-icon {
    width: 64px;
    height: 64px;
    background: #FEF2F2; /* Merah muda lembut */
    color: #DC2626;      /* Merah */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
  }

  .modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #1F2937;
    margin-bottom: 8px;
    font-family: 'Inter', sans-serif;
  }

  .modal-text {
    font-size: 14px;
    color: #6B7280;
    margin-bottom: 24px;
    line-height: 1.5;
    font-family: 'Inter', sans-serif;
  }

  .modal-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
  }

  .btn-modal {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
    flex: 1;
    font-family: 'Inter', sans-serif;
  }

  .btn-cancel {
    background: #fff;
    border: 1px solid #D1D5DB;
    color: #374151;
  }

  .btn-cancel:hover {
    background: #F3F4F6;
  }

  .btn-delete {
    background: #DC2626;
    color: white;
    box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.3);
  }

  .btn-delete:hover {
    background: #B91C1C;
    transform: translateY(-1px);
  }
</style>

{{-- HTML STRUCTURE SESUAI PERMINTAAN --}}
<div id="deleteModal" class="modal-backdrop">
  <div class="modal-content">
    
    <div class="modal-icon">
      {{-- Ikon Segitiga Peringatan (SVG pengganti FontAwesome) --}}
      <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    </div>

    <div class="modal-title">Hapus Data Kontrak?</div>
    
    <div class="modal-text">
      Anda yakin ingin menghapus kontrak <strong id="modalTargetName" style="color:#111827;"></strong>? <br>
      Tindakan ini tidak dapat dibatalkan.
    </div>

    <div class="modal-actions">
      <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModal()">
        Batal
      </button>

      <form id="deleteForm" method="POST" action="">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-modal btn-delete">
          Ya, Hapus
        </button>
      </form>
    </div>

  </div>
</div>