{{-- FILE: resources/views/dashboard/list_kontrak/modal-delete.blade.php --}}
<style>
  .modal-backdrop {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.6);
    display: none; align-items: center; justify-content: center;
    z-index: 9999; backdrop-filter: blur(2px); transition: opacity 0.3s ease;
  }
  .modal-backdrop.show { display: flex; }
  .modal-content {
    background: white; width: 90%; max-width: 400px;
    border-radius: 16px; padding: 30px 24px; text-align: center;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    transform: scale(0.95); transition: transform 0.3s ease;
  }
  .modal-backdrop.show .modal-content { transform: scale(1); }
  .modal-icon {
    width: 64px; height: 64px; background: #FEF2F2; color: #DC2626;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
  }
  .modal-title { font-size: 20px; font-weight: 700; color: #1F2937; margin-bottom: 8px; }
  .modal-text { font-size: 14px; color: #6B7280; margin-bottom: 24px; line-height: 1.5; }
  .modal-actions { display: flex; gap: 12px; justify-content: center; }
  .btn-modal { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; flex: 1; border: 1px solid transparent; }
  .btn-cancel { background: #fff; border-color: #D1D5DB; color: #374151; }
  .btn-cancel:hover { background: #F3F4F6; }
  .btn-delete { background: #DC2626; color: white; }
  .btn-delete:hover { background: #B91C1C; }
</style>

<div id="deleteModalList" class="modal-backdrop">
  <div class="modal-content">
    <div class="modal-icon">
      <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    </div>
    <div class="modal-title">Hapus Data?</div>
    <div class="modal-text">
      Anda yakin ingin menghapus data kontrak <strong id="delete_target_name" style="color:#111827;"></strong>? <br>
      Tindakan ini tidak dapat dibatalkan.
    </div>
    <div class="modal-actions">
      <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModalList()">Batal</button>
      <form id="deleteFormList" method="POST" action="">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-modal btn-delete">Ya, Hapus</button>
      </form>
    </div>
  </div>
</div>