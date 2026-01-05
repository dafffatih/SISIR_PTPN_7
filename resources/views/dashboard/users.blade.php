@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')

@section('content')
  <style>
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

    .users-table-container {
      background: #fff;
      border-radius: 14px;
      overflow-x: auto;
      border: 1px solid #e2e8f0;
    }

    .users-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 760px;
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
    }

    .users-table tbody tr:hover { background: #fafafa; }

    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 240px;
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

    .action-buttons { display: flex; gap: 8px; }

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

    @media (max-width: 768px) {
      .page-title { font-size: 22px; }
      .btn-add-user { width: 100%; justify-content: center; }
    }
  </style>

  @php
    // sementara dummy, nanti tinggal ganti dari DB: $users = \App\Models\User::all();
    $users = [
      ['name' => 'Administrator SISIR', 'username' => 'admin',  'role' => 'admin',  'status' => 'active',   'last_login' => '12/5/2025'],
      ['name' => 'Staff Pemasaran',     'username' => 'staff',  'role' => 'staff',  'status' => 'active',   'last_login' => '11/5/2025'],
      ['name' => 'Viewer Regional 7',   'username' => 'viewer', 'role' => 'viewer', 'status' => 'inactive', 'last_login' => '28/4/2025'],
    ];

    $roleLabel = [
      'admin'  => 'Administrator',
      'staff'  => 'Staff Pemasaran',
      'viewer' => 'Viewer',
    ];

    $initials = function ($name) {
      $parts = preg_split('/\s+/', trim($name));
      $a = strtoupper(substr($parts[0] ?? '', 0, 1));
      $b = strtoupper(substr($parts[1] ?? ($parts[0] ?? ''), 0, 1));
      return $a . $b;
    };
  @endphp

  <div class="page-header">
    <div>
      <h1 class="page-title">User Management</h1>
      <p class="page-subtitle">Kelola user dan role (khusus admin)</p>
    </div>

    <button class="btn-add-user" type="button">
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
        @foreach($users as $u)
          <tr>
            <td>
              <div class="user-info">
                <div class="avatar">{{ $initials($u['name']) }}</div>
                <span>{{ $u['name'] }}</span>
              </div>
            </td>

            <td>{{ $u['username'] }}</td>

            <td>
              <span class="role-badge">
                {{ $roleLabel[$u['role']] ?? $u['role'] }}
              </span>
            </td>

            <td>
              <span class="status-badge {{ $u['status'] === 'active' ? 'status-active' : 'status-inactive' }}">
                {{ ucfirst($u['status']) }}
              </span>
            </td>

            <td>{{ $u['last_login'] }}</td>

            <td>
              <div class="action-buttons">
                <button class="btn-icon btn-edit" type="button" title="Edit">
                  <i class="fas fa-pen"></i>
                </button>
                <button class="btn-icon btn-delete" type="button" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
