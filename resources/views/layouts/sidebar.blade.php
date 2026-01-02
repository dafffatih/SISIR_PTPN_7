@php
  $role = auth()->user()->role ?? null;

  $menus = [
    ['label' => 'Dashboard',         'route' => 'dashboard',     'roles' => ['admin','staff','viewer']],
    ['label' => 'Manajemen Kontrak', 'route' => 'kontrak',       'roles' => ['admin','staff']],
    ['label' => 'User Management',   'route' => 'users',         'roles' => ['admin']],
    ['label' => 'Upload & Export',   'route' => 'upload.export', 'roles' => ['admin','staff']],
  ];
@endphp

<style>
  .sisir-sidebar {
    width: 260px;
    min-height: 100vh;
    background: #0f172a;
    color: #ffffff;
    padding: 16px;
    box-sizing: border-box;
  }
  .sisir-brand {
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 18px;
    line-height: 1.2;
  }
  .sisir-subtitle {
    font-weight: 400;
    font-size: 12px;
    opacity: .8;
  }
  .sisir-nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .sisir-link {
    text-decoration: none;
    color: #ffffff;
    padding: 10px 12px;
    border-radius: 10px;
    display: block;
    background: transparent;
  }
  .sisir-link.is-active {
    background: #f97316;
  }
  .sisir-logout {
    margin-top: 18px;
  }
  .sisir-btn {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    background: #1f2937;
    color: #ffffff;
    border: none;
    cursor: pointer;
  }
</style>

<aside class="sisir-sidebar">
  <div class="sisir-brand">
    SISIR<br>
    <span class="sisir-subtitle">PTPN 1 Regional 7</span>
  </div>

  <nav class="sisir-nav">
    @foreach($menus as $m)
      @if(in_array($role, $m['roles']))
        <a href="{{ route($m['route']) }}"
           class="sisir-link {{ request()->routeIs($m['route']) ? 'is-active' : '' }}">
          {{ $m['label'] }}
        </a>
      @endif
    @endforeach
  </nav>

  <form method="POST" action="{{ route('logout') }}" class="sisir-logout">
    @csrf
    <button type="submit" class="sisir-btn">Logout</button>
  </form>
</aside>
