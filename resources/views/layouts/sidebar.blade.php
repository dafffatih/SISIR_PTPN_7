@php
$role = auth()->user()->role ?? null;

$menus = [
    // 1. Dashboard
    [
        'label' => 'Dashboard',         
        'route' => 'dashboard',      
        'icon'  => 'fas fa-th-large',         
        'roles' => ['admin','staff','viewer']
    ],

    // 2. Database (Parent untuk Kontrak & List Kontrak)
    [
        'label' => 'Database', 
        'route' => 'kontrak', // Link default saat diklik (ke Manajemen Kontrak)       
        'icon'  => 'fas fa-file-contract',    
        'roles' => ['admin','staff'],
        // KUNCI RAHASIA: Menu ini akan aktif jika route saat ini adalah 'kontrak' ATAU 'list-kontrak'
        'active_routes' => ['kontrak*', 'list-kontrak*'] 
    ],

    // 3. User Management
    [
        'label' => 'User Management',   
        'route' => 'users.index',    
        'icon'  => 'fas fa-users',            
        'roles' => ['admin']
    ],

    // 4. Export
    [
        'label' => 'Export',   
        'route' => 'upload.export',  
        'icon'  => 'fas fa-cloud-upload-alt', 
        'roles' => ['admin','staff']
    ],

    // 5. Settings
    [
        'label' => 'Settings',          
        'route' => 'settings',       
        'icon'  => 'fas fa-cog',              
        'roles' => ['admin','staff']
    ],
];
@endphp

<style>
  /* =========================
      SIDEBAR CORE (SAMA SEPERTI SEBELUMNYA)
  ========================= */
  .sisir-sidebar{
    width: var(--sidebar-w, 260px);
    min-width: var(--sidebar-w, 260px);
    min-height: 100vh; height: 100vh; height: 100dvh;
    background: #1e293b; color: #fff;
    position: fixed; top: 0; left: 0; z-index: 1000;
    display: flex; flex-direction: column;
    transition: transform .3s ease, width .3s ease;
    will-change: transform;
    box-sizing: border-box; flex-shrink: 0; overflow-x: hidden;
  }

  @media (max-width: 1024px) and (min-width: 769px){
    .sisir-sidebar{ width: var(--sidebar-w-tablet, 220px); min-width: var(--sidebar-w-tablet, 220px); }
  }
  @media (max-width: 480px){
    .sisir-sidebar{ width: 280px; max-width: 85vw; }
  }

  .sisir-sidebar.collapsed{ transform: translateX(-100%); }

  @media (max-width: 768px){
    .sisir-sidebar{ transform: translateX(-100%); }
    .sisir-sidebar.open-mobile{ transform: translateX(0); }
  }

  .sisir-sidebar-content{
    flex: 1; padding: 24px 16px; overflow-y: auto; -webkit-overflow-scrolling: touch;
  }

  /* BRAND */
  .sisir-brand-container{
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; margin-bottom: 24px; padding: 0 8px;
  }
  .sisir-brand-center{
    flex: 1; display: flex; flex-direction: column; align-items: center;
    justify-content: center; text-align: center; gap: 8px; min-width: 0; padding-right: 6px;
  }
  .sisir-logo{
    width: 150px; max-width: 100%; height: auto;
    display: flex; align-items: center; justify-content: center;
  }
  .sisir-logo img{ width: 100%; height: auto; object-fit: contain; display: block; }
  .sisir-brand-subtitle{
    font-size: 12px; color: #94a3b8; font-weight: 600; letter-spacing: .2px;
    white-space: normal; overflow: visible; text-overflow: unset;
    line-height: 1.35; max-width: 220px;
  }

  /* COLLAPSE BTN */
  .sisir-collapse-btn{
    border: none; background: rgba(255,255,255,.08); color: #fff;
    width: 38px; height: 38px; border-radius: 10px; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    transition: .2s; flex-shrink: 0; margin-top: 2px;
  }
  .sisir-collapse-btn:hover{ background: rgba(255,255,255,.14); }
  @media (max-width: 768px){ .sisir-collapse-btn{ display: none; } }

  /* NAV */
  .sisir-nav{ display: flex; flex-direction: column; gap: 6px; padding: 0 6px; }

  .sisir-link{
    text-decoration: none; color: #e2e8f0; padding: 12px 14px; border-radius: 10px;
    display: flex; align-items: center; gap: 12px; transition: .2s; font-size: 16px;
  }
  .sisir-link:hover{ background: rgba(255,255,255,.06); color: #fff; }

  /* ACTIVE STATE */
  .sisir-link.is-active{
    background: linear-gradient(90deg,#f97316 0%,#ef4444 100%);
    color: #fff; font-weight: 600;
  }
  .sisir-link i{ width: 20px; text-align: center; }

 /* =========================
   PTPN BRAND (ABOVE LOGOUT)
========================= */
.sisir-ptpn-container{
  padding: 18px 16px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-top: auto;
  border-top: 1px solid rgba(255,255,255,0.08);
}

.sisir-ptpn-logo{
  width: 42px;
  height: auto;
  opacity: 0.95;
  flex-shrink: 0;
}

/* TEXT */
.sisir-ptpn-text{
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  line-height: 1.25;
}

.ptpn-title{
  font-size: 14px;
  font-weight: 700;
  color: #f8fafc;
  letter-spacing: 0.6px;
}

.ptpn-subtitle{
  font-size: 11.5px;
  font-weight: 600;
  color: #94a3b8;
  letter-spacing: 1px;
  text-transform: uppercase;
}




  /* LOGOUT */
  .sisir-logout-container{ padding: 16px; border-top: 1px solid rgba(255,255,255,.1); }
  .sisir-logout-btn{
    width: 100%; padding: 12px 14px; border-radius: 10px;
    background: #334155; color: #fff; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-weight: 600; transition: .2s;
  }
  .sisir-logout-btn:hover{ background:#475569; }
</style>

<aside class="sisir-sidebar" id="sidebar">
  <div class="sisir-sidebar-content">

    {{-- BRAND HEADER --}}
    <div class="sisir-brand-container">
      <div class="sisir-brand-center">
        <div class="sisir-logo">
          <img src="{{ asset('images/SisirLogo.png') }}" alt="SISIR">
        </div>
        <div class="sisir-brand-subtitle"> Sales and Inventories Statistic of Rubber</div>
      </div>
      <button class="sisir-collapse-btn" type="button" title="Collapse Sidebar"
        onclick="window.__SISIR__ && window.__SISIR__.collapseDesktop && window.__SISIR__.collapseDesktop()">
        <i class="fas fa-angle-left"></i>
      </button>
    </div>

    {{-- NAV MENU --}}
    <nav class="sisir-nav">
      @foreach($menus as $m)
        @if(in_array($role, $m['roles']))
          
          @php
            // LOGIKA ACTIVE: 
            // Cek apakah ada 'active_routes' di array (kasus Database), jika ada pakai itu.
            // Jika tidak ada, pakai default route biasa.
            $isActive = false;
            if (isset($m['active_routes'])) {
                $isActive = request()->routeIs($m['active_routes']);
            } else {
                $isActive = request()->routeIs($m['route'] . '*');
            }
          @endphp

          <a href="{{ route($m['route']) }}"
             class="sisir-link {{ $isActive ? 'is-active' : '' }}">
            <i class="{{ $m['icon'] }}"></i>
            <span>{{ $m['label'] }}</span>
          </a>
        @endif
      @endforeach
    </nav>

  </div>

    {{-- LOGO PTPN 7 --}}
    <div class="sisir-ptpn-container">
  <img src="{{ asset('images/ptpn7.png') }}" alt="PTPN 7" class="sisir-ptpn-logo">

  <div class="sisir-ptpn-text">
    <div class="ptpn-title">PTPN I</div>
    <div class="ptpn-subtitle">REGIONAL 7</div>
  </div>
</div>



  {{-- LOGOUT --}}
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