@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')

@section('content')
  <style>
    /* ... Style Header & Table (SAMA SEPERTI SEBELUMNYA, TIDAK DIUBAH) ... */
    .page-header { font-family: 'Inter', sans-serif; padding: 24px; display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { font-size: 24px; font-weight: 700; font-family: 'Inter', sans-serif; color: #0F172A; margin: 0 0 4px; line-height: 1.2; }
    .page-subtitle { font-size: 14px; color: #64748b; margin: 0; }
    .btn-add-user { padding: 12px 18px; background: #f97316; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: .2s; white-space: nowrap; }
    .btn-add-user:hover { background: #ea580c; transform: translateY(-1px); }
    .users-table-container { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 0; margin-left: 24px; margin-right: 24px; width: auto; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .users-table{ width: 100%; min-width: 980px; border-collapse: collapse; padding: 24px; table-layout: auto; }
    .users-table thead { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .users-table th { padding: 14px 24px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; white-space: nowrap; }
    .users-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; white-space: nowrap; }
    .users-table tbody tr:hover { background: #fafafa; }
    .user-info { display: flex; align-items: center; gap: 12px; min-width: 240px; }
    .avatar { width: 36px; height: 36px; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; color: #fff; background: #f97316; flex-shrink: 0; }
    .role-badge { padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #f1f5f9; color: #334155; display: inline-block; }
    .status-badge { padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; display: inline-block; }
    .status-active { background: #dcfce7; color: #166534; }
    .status-inactive { background: #f1f5f9; color: #64748b; }
    .action-buttons { display: flex; gap: 8px; align-items: center; }
    .btn-icon { width: 34px; height: 34px; border: none; background: transparent; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: .2s; color: #64748b; }
    .btn-icon:hover { background: #f1f5f9; }
    .btn-delete:hover { background: #fef2f2; color: #ef4444; }
    .btn-edit:hover { color: #3b82f6; }
    .alert { padding: 12px 14px; border-radius: 12px; margin-bottom: 14px; border: 1px solid transparent; background: #f8fafc; color: #0f172a; font-size: 14px; }
    .alert-success { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .alert-error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    
    /* ... Style Modal (SAMA) ... */
    .modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 9999; }
    .modal { width: 100%; max-width: 520px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 20px 50px rgba(2,6,23,.25); overflow: hidden; max-height: 85vh; display: flex; flex-direction: column; }
    .modal-header { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-shrink: 0; }
    .modal-title { font-weight: 800; color: #0f172a; font-size: 16px; margin: 0; }
    .modal-body { padding: 16px 18px; display: grid; gap: 12px; overflow: auto; -webkit-overflow-scrolling: touch; }
    .modal-footer { padding: 16px 18px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; flex-shrink: 0; }
    .btn { border: none; border-radius: 12px; padding: 10px 14px; cursor: pointer; font-weight: 700; font-size: 14px; transition: .2s; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
    .btn-secondary { background: #f1f5f9; color: #0f172a; }
    .btn-secondary:hover { background: #e2e8f0; }
    .btn-primary { background: #f97316; color: #fff; }
    .btn-primary:hover { background: #ea580c; transform: translateY(-1px); }
    .form-group { display: grid; gap: 6px; }
    .label { font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
    .input, .select { width: 100%; padding: 11px 12px; border-radius: 12px; border: 1px solid #e2e8f0; outline: none; font-size: 14px; color: #0f172a; background: #fff; }
    .input:focus, .select:focus { border-color: #fb923c; box-shadow: 0 0 0 4px rgba(251,146,60,.2); }
    .help { font-size: 12px; color: #94a3b8; margin: 0; }

    @media (max-width: 768px) {
      .page-header { margin-bottom: 16px; padding: 16px; }
      .page-title { font-size: 20px; }
      .page-subtitle { font-size: 13px; }
      .btn-add-user { width: 100%; justify-content: center; }
      .users-table-container { margin-left: 16px; margin-right: 16px; }
      .users-table-container::after{ display:block; }
      .users-table th, .users-table td{ padding: 10px 12px; font-size: 13px; }
      .user-info { min-width: 220px; }
      .avatar { width: 32px; height: 32px; font-size: 12px; }
    }
    @media (max-width: 480px) {
      .modal-header, .modal-body, .modal-footer { padding: 14px; }
      .btn { width: 100%; justify-content: center; }
    }

    /* ===== NEW STYLES FOR VALIDATION ===== */
    /* Highlight merah untuk input error */
    .input.input-error, .select.input-error {
        border-color: #ef4444;
        background-color: #fef2f2;
    }
    .input.input-error:focus, .select.input-error:focus {
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
    }

    /* Teks error kecil di bawah input */
    .text-error {
        color: #ef4444;
        font-size: 12px;
        font-weight: 600;
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .text-error::before {
        content: "⚠";
        font-size: 10px;
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

  {{-- HAPUS ALERT ERROR GLOBAL ($errors->any) DI SINI. --}}
  {{-- Hanya tampilkan Success/General Error --}}

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
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

  <div class="users-table-container">
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
            $lastLogin = $u->last_login_at ? \Carbon\Carbon::parse($u->last_login_at)->format('d/m/Y') : '-';
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
              <span class="role-badge">{{ $roleLabel[$u->role] ?? $u->role }}</span>
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
            <td colspan="6" style="padding:18px; color:#64748b;">Belum ada data user.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ================= MODAL CREATE ================= --}}
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
            {{-- Tambahkan @error logic untuk class dan pesan --}}
            <input 
                class="input @error('name') input-error @enderror" 
                name="name" 
                placeholder="Nama lengkap" 
                value="{{ old('name') }}" 
                required
            >
            @error('name')
                <div class="text-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <div class="label">Username</div>
            <input 
                class="input @error('username') input-error @enderror" 
                name="username" 
                placeholder="contoh: staff_pemasaran" 
                value="{{ old('username') }}" 
                required
            >
            @error('username')
                <div class="text-error">{{ $message }}</div>
            @enderror
            <p class="help">Hanya huruf/angka/underscore/dash (alpha_dash).</p>
          </div>

          <div class="form-group">
            <div class="label">Password</div>
            <input 
                class="input @error('password') input-error @enderror" 
                type="password" 
                name="password" 
                placeholder="Minimal 6 karakter" 
                required
            >
            @error('password')
                <div class="text-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <div class="label">Role</div>
            <select class="select @error('role') input-error @enderror" name="role" required>
              <option value="admin"  {{ old('role')==='admin' ? 'selected' : '' }}>Administrator</option>
              <option value="staff"  {{ old('role')==='staff' ? 'selected' : '' }}>Staff Pemasaran</option>
              <option value="viewer" {{ old('role')==='viewer' ? 'selected' : '' }}>Viewer</option>
            </select>
            @error('role')
                <div class="text-error">{{ $message }}</div>
            @enderror
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

  {{-- ================= MODAL EDIT ================= --}}
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
        
        {{-- Hidden Input untuk menyimpan ID user saat validasi gagal, agar JS tahu ID mana yg direstore --}}
        <input type="hidden" name="user_id" id="edit_user_id" value="{{ old('user_id') }}">

        <div class="modal-body">
          <div class="form-group">
            <div class="label">Nama</div>
            {{-- Value: Cek apakah ada old value (saat error), jika tidak biarkan kosong (nanti diisi JS) --}}
            <input 
                class="input @error('name', 'userUpdate') input-error @enderror" 
                id="edit_name" 
                name="name" 
                value="{{ old('name') }}" 
                required
            >
            @error('name')
                <div class="text-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <div class="label">Username</div>
            <input 
                class="input @error('username') input-error @enderror" 
                id="edit_username" 
                name="username" 
                value="{{ old('username') }}" 
                required
            >
            @error('username')
                <div class="text-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <div class="label">Password (opsional)</div>
            <input 
                class="input @error('password') input-error @enderror" 
                type="password" 
                id="edit_password" 
                name="password" 
                placeholder="Kosongkan jika tidak diubah"
            >
            @error('password')
              <div class="text-error">{{ $message }}</div>
            @enderror
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
    // --- MODAL FUNCTIONS ---
    function openCreateModal() {
      const el = document.getElementById('createModal');
      if (el) el.style.display = 'flex';
    }
    function closeCreateModal() {
      const el = document.getElementById('createModal');
      if (el) el.style.display = 'none';
    }

    // Fungsi buka edit modal saat klik tombol tabel
    function openEditModal(el) {
      if(!el) return; // safety check
      
      const id = el.dataset.id;
      const name = el.dataset.name;
      const username = el.dataset.username;
      const role = el.dataset.role;
      const status = el.dataset.status;
      const lockRole = el.dataset.lockRole === '1';

      populateEditModal(id, name, username, role, status, lockRole);
    }

    // Fungsi internal populate (dipisahkan agar bisa dipanggil saat error handling juga)
    function populateEditModal(id, name, username, role, status, lockRole) {
      const editModal = document.getElementById('editModal');
      const editForm = document.getElementById('editForm');
      if (!editModal || !editForm) return;

      // Set Action URL
      editForm.action = "{{ url('/users') }}/" + id;

      // Set Values
      document.getElementById('edit_user_id').value = id; // Penting untuk persistency
      document.getElementById('edit_name').value = name || '';
      document.getElementById('edit_username').value = username || '';
      document.getElementById('edit_password').value = '';
      document.getElementById('edit_role').value = role || 'viewer';
      document.getElementById('edit_status').value = status || 'active';

      // Role Locking Logic
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
      if (el) el.style.display = 'none';
    }

    // Close on click outside
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', function(e){
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });

    // Close on Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeCreateModal();
        closeEditModal();
      }
    });

    // --- ERROR HANDLING LOGIC (THE MAGIC) ---
    document.addEventListener("DOMContentLoaded", function() {
        // Cek apakah ada error validasi dari Laravel
        @if ($errors->any())
            
            // Cek method apa yang sebelumnya dikirim (POST = Create, PUT = Edit)
            @if(old('_method') === 'PUT')
                // === KASUS EDIT ERROR ===
                // Kita perlu membuka kembali modal Edit.
                // Masalahnya: Kita butuh ID user untuk set action URL form.
                // Solusinya: Kita ambil dari old('user_id') yang kita pasang hidden input tadi.
                
                var oldId = "{{ old('user_id') }}";
                var oldName = "{{ old('name') }}"; // Blade akan render string
                var oldUsername = "{{ old('username') }}";
                
                // Gunakan nilai dari old() karena itu yang diinput user terakhir kali (yang salah)
                // Role dan Status juga harusnya dari old(), tapi default dari form select blade old() sudah menangani 'selected'
                
                if(oldId) {
                    const editModal = document.getElementById('editModal');
                    const editForm = document.getElementById('editForm');
                    
                    // Set display dulu agar elemen ada ukurannya (opsional)
                    editModal.style.display = 'flex';
                    
                    // Restore form action
                    editForm.action = "{{ url('/users') }}/" + oldId;
                    
                    // Khusus role lock logic, kita asumsikan jika role sebelumnya 'admin' dan ID=1 (bisa ditambah logic JS jika perlu kompleks)
                    // Tapi basicnya, error validation akan merender ulang value input via HTML 
                    // Jadi kita hanya perlu memastikan Modal terbuka.
                }

            @else
                // === KASUS CREATE ERROR ===
                // Defaultnya method POST atau null, berarti Create Modal
                openCreateModal();
            @endif

        @endif
    });
  </script>
@endsection