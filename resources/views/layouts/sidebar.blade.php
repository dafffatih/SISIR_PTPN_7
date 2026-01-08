@php
$role = auth()->user()->role ?? null;

$menus = [
    ['label' => 'Dashboard',         'route' => 'dashboard',      'icon' => 'fas fa-th-large',        'roles' => ['admin','staff','viewer']],
    ['label' => 'Manajemen Kontrak', 'route' => 'kontrak',        'icon' => 'fas fa-file-contract',   'roles' => ['admin','staff']],
    ['label' => 'User Management',   'route' => 'users.index',    'icon' => 'fas fa-users',           'roles' => ['admin']],
    ['label' => 'Upload & Export',   'route' => 'upload.export',  'icon' => 'fas fa-cloud-upload-alt','roles' => ['admin','staff']],
];
@endphp

<style>
  /* =========================
     SIDEBAR CORE
  ========================= */
  .sisir-sidebar{
    width: var(--sidebar-w, 260px);
    min-width: var(--sidebar-w, 260px);

    min-height: 100vh;
    height: 100vh;
    height: 100dvh; /* ✅ mobile safe */

    background: #1e293b;
    color: #fff;

    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;

    display: flex;
    flex-direction: column;

    transition: transform .3s ease, width .3s ease;
    will-change: transform;

    box-sizing: border-box;
    flex-shrink: 0;

    /* ✅ penting biar sidebar gak bikin overflow horizontal */
    overflow-x: hidden;
  }

  /* tablet width pakai variable yang sama dengan app.blade.php */
  @media (max-width: 1024px) and (min-width: 769px){
    .sisir-sidebar{
      width: var(--sidebar-w-tablet, 220px);
      min-width: var(--sidebar-w-tablet, 220px);
    }
  }

  /* mobile width (off-canvas) */
  @media (max-width: 480px){
    .sisir-sidebar{
      width: 280px;
      max-width: 85vw;
    }
  }

  /* =========================
     STATE: COLLAPSED (desktop)
  ========================= */
  .sisir-sidebar.collapsed{
    transform: translateX(-100%);
  }

  /* =========================
     STATE: OFF-CANVAS (mobile)
  ========================= */
  @media (max-width: 768px){
    .sisir-sidebar{
      transform: translateX(-100%);
    }
    .sisir-sidebar.open-mobile{
      transform: translateX(0);
    }
  }

  /* =========================
     CONTENT
  ========================= */
  .sisir-sidebar-content{
    flex: 1;
    padding: 24px 16px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }

  /* brand */
  .sisir-brand-container{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 24px;
    padding: 0 8px;
  }

  .sisir-brand-left{
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }

  .sisir-logo{
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .sisir-brand-text{
    display: flex;
    flex-direction: column;
    line-height: 1.1;
    min-width: 0;
  }
  .sisir-brand-title{
    font-weight: 800;
    font-size: 18px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .sisir-brand-subtitle{
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* tombol collapse desktop */
  .sisir-collapse-btn{
    border: none;
    background: rgba(255,255,255,.08);
    color: #fff;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: .2s;
  }
  .sisir-collapse-btn:hover{ background: rgba(255,255,255,.14); }

  @media (max-width: 768px){
    .sisir-collapse-btn{ display: none; }
  }

  /* nav */
  .sisir-nav{
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 0 6px;
  }
  .sisir-link{
    text-decoration: none;
    color: #e2e8f0;
    padding: 12px 14px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: .2s;
    font-size: 16px;
  }
  .sisir-link:hover{ background: rgba(255,255,255,.06); color: #fff; }
  .sisir-link.is-active{
    background: linear-gradient(90deg,#f97316 0%,#ef4444 100%);
    color: #fff;
    font-weight: 600;
  }
  .sisir-link i{ width: 20px; text-align: center; }

  /* logout */
  .sisir-logout-container{
    padding: 16px;
    border-top: 1px solid rgba(255,255,255,.1);
  }
  .sisir-logout-btn{
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    background: #334155;
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 600;
    transition: .2s;
  }
  .sisir-logout-btn:hover{ background:#475569; }
</style>

<aside class="sisir-sidebar" id="sidebar">
  <div class="sisir-sidebar-content">
    <div class="sisir-brand-container">
      <div class="sisir-brand-left">
        <div class="sisir-logo">
          <img src="{{ asset('images/SisirLogo.png') }}" alt="Logo Sisir" style="max-height: 40px;">
        </div>

        <div class="sisir-brand-text">
          <div class="sisir-brand-title">SISIR</div>
          <div class="sisir-brand-subtitle">PTPN 1 Regional 7</div>
        </div>
      </div>

      <button class="sisir-collapse-btn" type="button" title="Collapse Sidebar"
        onclick="window.__SISIR__ && window.__SISIR__.collapseDesktop && window.__SISIR__.collapseDesktop()">
        <i class="fas fa-angle-left"></i>
      </button>
    </div>

    <nav class="sisir-nav">
      @foreach($menus as $m)
        @if(in_array($role, $m['roles']))
          <a href="{{ route($m['route']) }}"
             class="sisir-link {{ request()->routeIs($m['route']) ? 'is-active' : '' }}">
            <i class="{{ $m['icon'] }}"></i>
            <span>{{ $m['label'] }}</span>
          </a>
        @endif
      @endforeach
    </nav>
  </div>

  <div class="sisir-logout-container">
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="sisir-logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </button>
    </form>
  </div>
</aside>
