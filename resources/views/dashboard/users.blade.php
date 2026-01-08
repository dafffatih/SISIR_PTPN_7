@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')

@section('content')
  <style>
    /* =========================
       PAGE HEADER
    ========================= */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 24px;
      flex-wrap: wrap;
      gap: 16px;
    }

    .page-title {
      font-size: 28px;
      font-weight: 700;
      color: #1e293b;
      margin: 0 0 4px;
    }

    .page-subtitle {
      font-size: 14px;
      color: #64748b;
      margin: 0;
    }

    .btn-add-user {
      padding: 12px 18px;
      background: #f97316;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: .2s;
      white-space: nowrap;
    }
    .btn-add-user:hover { background: #ea580c; transform: translateY(-1px); }

    /* =========================
       IMPORTANT:
       - ONLY TABLE AREA SCROLLS HORIZONTALLY
       - PAGE MUST NOT SCROLL HORIZONTALLY
    ========================= */
    .users-table-card{
      background:#fff;
      border:1px solid #e2e8f0;
      border-radius:14px;
      overflow:hidden;              /* ✅ card tidak ikut melebar */
    }

    .users-table-scroll{
      width:100%;
      max-width:100%;
      overflow-x:auto;              /* ✅ cuma area ini yang geser */
      overflow-y:hidden;
      -webkit-overflow-scrolling:touch;
      overscroll-behavior-x:contain;
      position:relative;
    }

    /* hint geser (mobile) */
    .users-table-scroll::after{
      content:"Geser →";
      position:sticky;
      left:0;
      bottom:0;
      display:none;
      font-size:12px;
      color:#94a3b8;
      padding:10px 12px;
      background:linear-gradient(90deg, rgba(241,245,249,.95) 0%, rgba(241,245,249,0) 100%);
      width:max-content;
      border-top-right-radius:12px;
      pointer-events:none;
    }

    .users-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 980px;            /* ✅ ini bikin kolom kanan harus di-scroll */
    }

    .users-table thead {
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
    }

    .users-table th {
      padding: 14px 16px;
      text-align: left;
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: .5px;
      white-space: nowrap;
    }

    .users-table td {
      padding: 14px 16px;
      border-bottom: 1px solid #f1f5f9;
      font-size: 14px;
      color: #334155;
      white-space: nowrap;
      vertical-align: middle;
    }

    .users-table tbody tr:hover { background: #fafafa; }

    /* ===== Sticky NAME (kolom kiri tetap terlihat) ===== */
    .users-table th:first-child,
    .users-table td:first-child{
      position: sticky;
      left: 0;
      z-index: 4;
      background: #fff;
      box-shadow: 10px 0 18px rgba(2,6,23,.06);
    }
    .users-table thead th:first-child{
      background:#f8fafc;
      z-index: 6;
    }

    /* ===== Sticky ACTIONS (kolom kanan tetap terlihat) ===== */
    .users-table th:last-child,
    .users-table td:last-child{
      position: sticky;
      right: 0;
      z-index: 3;
      background: #fff;
      box-shadow: -10px 0 18px rgba(2,6,23,.06);
    }
    .users-table thead th:last-child{
      background:#f8fafc;
      z-index: 6;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 260px;
    }

    .avatar {
      width: 36px;
      height: 36px;
      border-radius: 999px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 800;
      color: #fff;
      background: #f97316;
      flex-shrink: 0;
    }

    .role-badge {
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      background: #f1f5f9;
      color: #334155;
      display: inline-block;
    }

    .status-badge {
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      display: inline-block;
    }
    .status-active { background: #dcfce7; color: #166534; }
    .status-inactive { background: #f1f5f9; color: #64748b; }

    .action-buttons { display: flex; gap: 8px; align-items: center; }

    .btn-icon {
      width: 34px;
      height: 34px;
      border: none;
      background: transparent;
      border-radius: 10px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: .2s;
      color: #64748b;
    }
    .btn-icon:hover { background: #f1f5f9; }
    .btn-delete:hover { background: #fef2f2; color: #ef4444; }
    .btn-edit:hover { color: #3b82f6; }

    /* Alerts */
    .alert {
      padding: 12px 14px;
      border-radius: 12px;
      margin-bottom: 14px;
      border: 1px solid transparent;
      background: #f8fafc;
      color: #0f172a;
      font-size: 14px;
    }
    .alert-success { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .alert-error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

    /* ===== Modal ===== */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .45);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 18px;
      z-index: 9999;
    }
    .modal {
      width: 100%;
      max-width: 520px;
      background: #fff;
      border-radius: 16px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 20px 50px rgba(2,6,23,.25);
      overflow: hidden;
      max-height: 85vh;
      display: flex;
      flex-direction: column;
    }
    .modal-header {
      padding: 16px 18px;
      border-bottom: 1px solid #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-shrink: 0;
    }
    .modal-title {
      font-weight: 800;
      color: #0f172a;
      font-size: 16px;
      margin: 0;
    }
    .modal-body {
      padding: 16px 18px;
      display: grid;
      gap: 12px;
      overflow: auto;
      -webkit-overflow-scrolling: touch;
    }
    .modal-footer {
      padding: 16px 18px;
      border-top: 1px solid #f1f5f9;
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      flex-wrap: wrap;
      flex-shrink: 0;
    }
    .btn {
      border: none;
      border-radius: 12px;
      padding: 10px 14px;
      cursor: pointer;
      font-weight: 700;
      font-size: 14px;
      transition: .2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
    }
    .btn-secondary { background: #f1f5f9; color: #0f172a; }
    .btn-secondary:hover { background: #e2e8f0; }
    .btn-primary { background: #f97316; color: #fff; }
    .btn-primary:hover { background: #ea580c; transform: translateY(-1px); }

    .form-group { display: grid; gap: 6px; }
    .label { font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
    .input, .select {
      width: 100%;
      padding: 11px 12px;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      outline: none;
      font-size: 14px;
      color: #0f172a;
      background: #fff;
    }
    .input:focus, .select:focus { border-color: #fb923c; box-shadow: 0 0 0 4px rgba(251,146,60,.2); }

    .help {
      font-size: 12px;
      color: #94a3b8;
      margin: 0;
    }

    /* ===== Responsive tweaks ===== */
    @media (max-width: 768px) {
      .page-header { margin-bottom: 16px; }
      .page-title { font-size: 20px; }
      .page-subtitle { font-size: 13px; }
      .btn-add-user { width: 100%; justify-content: center; }

      .users-table-scroll::after{ display:block; }

      .users-table th, .users-table td{
        padding: 10px 12px;
        font-size: 13px;
      }
      .user-info { min-width: 220px; }
      .avatar { width: 32px; height: 32px; font-size: 12px; }
    }

    @media (max-width: 480px) {
      .modal-header, .modal-body, .modal-footer { padding: 14px; }
      .btn { width: 100%; justify-content: center; }
    }
  </style>

  @php
    $protectedAdminId = 1;

    $initials = function ($name) {
      $parts = preg_split('/\s+/', trim($name));
      $a = strtoupper(substr($parts[0] ?? '', 0, 1));
      $b = strtoupper(substr($parts[1] ?? ($parts[0] ?? ''), 0, 1));
      return $a . $b;
    };
  @endphp

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-error">
      <div style="font-weight:800; margin-bottom:6px;">Validasi gagal:</div>
      <ul style="margin:0; padding-left:18px;">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="page-header">
    <div>
      <h1 class="page-title">User Management</h1>
      <p class="page-subtitle">Kelola user dan role (khusus admin)</p>
    </div>

    <button class="btn-add-user" type="button" onclick="openCreateModal()">
      <i class="fas fa-plus"></i>
      Tambah User
    </button>
  </div>

  <!-- ✅ hanya area ini yang bisa scroll horizontal -->
  <div class="users-table-card">
    <div class="users-table-scroll">
      <table class="users-table">
        <thead>
          <tr>
            <th>NAME</th>
            <th>USERNAME</th>
            <th>ROLE</th>
            <th>STATUS</th>
            <th>LAST LOGIN</th>
            <th>ACTIONS</th>
          </tr>
        </thead>

        <tbody>
          @forelse($users as $u)
            @php
              $status = $u->status ?? 'active';

              $lastLogin = '-';
              if (isset($u->last_login_at) && $u->last_login_at) {
                try {
                  $lastLogin = \Carbon\Carbon::parse($u->last_login_at)->format('d/m/Y');
                } catch (\Throwable $e) {
                  $lastLogin = '-';
                }
              }

              $lockRole = ($u->id == $protectedAdminId) ? '1' : '0';
            @endphp

            <tr>
              <td>
                <div class="user-info">
                  <div class="avatar">{{ $initials($u->name) }}</div>
                  <span>{{ $u->name }}</span>
                </div>
              </td>

              <td>{{ $u->username }}</td>

              <td>
                <span class="role-badge">
                  {{ $roleLabel[$u->role] ?? $u->role }}
                </span>
              </td>

              <td>
                <span class="status-badge {{ $status === 'active' ? 'status-active' : 'status-inactive' }}">
                  {{ ucfirst($status) }}
                </span>
              </td>

              <td>{{ $lastLogin }}</td>

              <td>
                <div class="action-buttons">
                  <button
                    class="btn-icon btn-edit"
                    type="button"
                    title="Edit"
                    data-id="{{ $u->id }}"
                    data-name="{{ $u->name }}"
                    data-username="{{ $u->username }}"
                    data-role="{{ $u->role }}"
                    data-status="{{ $status }}"
                    data-lock-role="{{ $lockRole }}"
                    onclick="openEditModal(this)"
                  >
                    <i class="fas fa-pen"></i>
                  </button>

                  <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Yakin hapus user ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn-icon btn-delete" type="submit" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="padding:18px; color:#64748b;">
                Belum ada data user.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div id="pageFlags" data-has-errors="{{ $errors->any() ? '1' : '0' }}" style="display:none;"></div>

  {{-- =========================
      MODAL: CREATE USER
  ========================= --}}
  <div class="modal-backdrop" id="createModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="createTitle">
      <div class="modal-header">
        <h3 class="modal-title" id="createTitle">Tambah User</h3>
        <button class="btn-icon" type="button" onclick="closeCreateModal()" aria-label="Close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <div class="label">Nama</div>
            <input class="input" name="name" placeholder="Nama lengkap" value="{{ old('name') }}" required>
          </div>

          <div class="form-group">
            <div class="label">Username</div>
            <input class="input" name="username" placeholder="contoh: staff_pemasaran" value="{{ old('username') }}" required>
            <p class="help">Hanya huruf/angka/underscore/dash (alpha_dash).</p>
          </div>

          <div class="form-group">
            <div class="label">Password</div>
            <input class="input" type="password" name="password" placeholder="Minimal 6 karakter" required>
          </div>

          <div class="form-group">
            <div class="label">Role</div>
            <select class="select" name="role" required>
              <option value="admin"  {{ old('role')==='admin' ? 'selected' : '' }}>Administrator</option>
              <option value="staff"  {{ old('role')==='staff' ? 'selected' : '' }}>Staff Pemasaran</option>
              <option value="viewer" {{ old('role')==='viewer' ? 'selected' : '' }}>Viewer</option>
            </select>
          </div>

          <div class="form-group">
            <div class="label">Status</div>
            <select class="select" name="status" required>
              <option value="active"   {{ old('status','active')==='active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ old('status')==='inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- =========================
      MODAL: EDIT USER
  ========================= --}}
  <div class="modal-backdrop" id="editModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
      <div class="modal-header">
        <h3 class="modal-title" id="editTitle">Edit User</h3>
        <button class="btn-icon" type="button" onclick="closeEditModal()" aria-label="Close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form id="editForm" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-body">
          <div class="form-group">
            <div class="label">Nama</div>
            <input class="input" id="edit_name" name="name" required>
          </div>

          <div class="form-group">
            <div class="label">Username</div>
            <input class="input" id="edit_username" name="username" required>
          </div>

          <div class="form-group">
            <div class="label">Password (opsional)</div>
            <input class="input" type="password" id="edit_password" name="password" placeholder="Kosongkan jika tidak diubah">
            <p class="help">Kalau tidak ingin ganti password, biarkan kosong.</p>
          </div>

          <div class="form-group">
            <div class="label">Role</div>
            <select class="select" id="edit_role" name="role" required>
              <option value="admin">Administrator</option>
              <option value="staff">Staff Pemasaran</option>
              <option value="viewer">Viewer</option>
            </select>

            <input type="hidden" id="edit_role_hidden" name="role_hidden" value="">

            <p class="help" id="role_lock_hint" style="display:none;">
              Role Administrator SISIR tidak bisa diubah.
            </p>
          </div>

          <div class="form-group">
            <div class="label">Status</div>
            <select class="select" id="edit_status" name="status" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openCreateModal() {
      const el = document.getElementById('createModal');
      if (!el) return;
      el.style.display = 'flex';
    }
    function closeCreateModal() {
      const el = document.getElementById('createModal');
      if (!el) return;
      el.style.display = 'none';
    }

    function openEditModal(el) {
      const id = el.dataset.id;
      const name = el.dataset.name;
      const username = el.dataset.username;
      const role = el.dataset.role;
      const status = el.dataset.status;
      const lockRole = el.dataset.lockRole === '1';

      const editModal = document.getElementById('editModal');
      const editForm = document.getElementById('editForm');

      if (!editModal || !editForm) return;

      editForm.action = "{{ url('/users') }}/" + id;

      document.getElementById('edit_name').value = name || '';
      document.getElementById('edit_username').value = username || '';
      document.getElementById('edit_password').value = '';
      document.getElementById('edit_role').value = role || 'viewer';
      document.getElementById('edit_status').value = status || 'active';

      const roleSelect = document.getElementById('edit_role');
      const roleHint = document.getElementById('role_lock_hint');
      const roleHidden = document.getElementById('edit_role_hidden');

      if (lockRole) {
        roleSelect.disabled = true;
        roleSelect.value = 'admin';
        roleHidden.value = 'admin';
        roleHint.style.display = 'block';
      } else {
        roleSelect.disabled = false;
        roleHidden.value = '';
        roleHint.style.display = 'none';
      }

      editModal.style.display = 'flex';
    }

    function closeEditModal() {
      const el = document.getElementById('editModal');
      if (!el) return;
      el.style.display = 'none';
    }

    const createModalEl = document.getElementById('createModal');
    if (createModalEl) {
      createModalEl.addEventListener('click', function(e){
        if (e.target === this) closeCreateModal();
      });
    }

    const editModalEl = document.getElementById('editModal');
    if (editModalEl) {
      editModalEl.addEventListener('click', function(e){
        if (e.target === this) closeEditModal();
      });
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeCreateModal();
        closeEditModal();
      }
    });

    const flagsEl = document.getElementById('pageFlags');
    const hasErrors = flagsEl ? flagsEl.dataset.hasErrors === '1' : false;
    if (hasErrors) {
      openCreateModal();
    }
  </script>
@endsection
