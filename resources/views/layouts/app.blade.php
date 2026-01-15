<!DOCTYPE html>
<html lang="id">
<head>
  @vite(['resources/css/app.css'])
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'SISIR')</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: #f1f5f9;
      color: #1e293b;
      overflow-x: hidden;
    }

    :root{
      --sidebar-w: 260px;
      --sidebar-w-tablet: 220px;
      --topbar-h: 64px;
    }

    .app-container {
      display: flex;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ======================
       MAIN AREA
    ====================== */
    .main-area{
      flex: 1;
      margin-left: var(--sidebar-w);
      min-height: 100vh;
      transition: margin-left .3s ease;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow-x: hidden;
    }

    /* sidebar collapsed (desktop) */
    .main-area.expanded{
      margin-left: 0;
    }

    /* ======================
       TOPBAR
    ====================== */
    .topbar{
      height: var(--topbar-h);
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 18px;
      position: sticky;
      top: 0;
      z-index: 900;
    }

    .topbar-left{
      display:flex;
      align-items:center;
      gap: 12px;
      min-width: 0;
    }

    /* hamburger */
    .hamburger-btn{
      width: 44px;
      height: 44px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      background: #0f172a;
      color: #fff;
      display: none; /* default hidden */
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }
    .hamburger-btn:hover{ background:#1e293b; }

    .topbar-title{
      font-weight: 800;
      font-size: 16px;
      color: #0f172a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* --- YEAR SELECTOR DI HEADER --- */
    .header-year-selector {
        margin-left: 8px;
        display: flex;
        align-items: center;
    }
    /* Sembunyikan dropdown tahun di layar sangat kecil agar tidak merusak layout */
    @media (max-width: 400px) {
        .header-year-selector { display: none; }
    }

    .topbar-filters{
      display:flex;
      align-items:center;
      gap:10px;
      margin-left: 12px;
    }

    @media (max-width:768px){
      .topbar-filters{ display:none; }
    }

    .topbar-right{
      display:flex;
      align-items:center;
      gap: 10px;
    }

    .user-meta{
      display:flex;
      flex-direction:column;
      line-height:1.1;
      text-align:right;
    }

    .user-name{
      font-weight:700;
      font-size: 13px;
      color:#0f172a;
    }

    .user-role{
      font-size: 11px;
      color:#64748b;
    }

    .user-avatar{
      width: 34px;
      height: 34px;
      border-radius: 999px;
      background: #f97316;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:800;
      font-size: 12px;
      flex-shrink:0;
    }

    /* ======================
       CONTENT
    ====================== */
    .main-content{
      padding: 24px;
      min-height: calc(100vh - var(--topbar-h));
      overflow-x: hidden;
    }

    /* overlay untuk mobile sidebar */
    .overlay{
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      z-index: 850;
      opacity: 0;
      pointer-events: none;
      transition: opacity .3s ease;
    }
    .overlay.active{
      opacity: 1;
      pointer-events: auto;
    }

    /* ======================
       RESPONSIVE
    ====================== */
    @media (max-width: 1024px) and (min-width: 769px){
      .main-area{ margin-left: var(--sidebar-w-tablet); }
    }

    @media (max-width: 768px){
      .main-area{ margin-left: 0 !important; }
      .main-content{ padding: 16px; }
    }

    @media (max-width: 480px){
      .main-content{ padding: 12px; }
    }
  </style>
</head>

<body>
  @php
    $userName = auth()->user()->name ?? 'Admin User';
    $userRole = auth()->user()->role ?? 'admin';

    $initials = collect(explode(' ', trim($userName)))
      ->filter()
      ->map(fn($w) => strtoupper(substr($w, 0, 1)))
      ->take(2)
      ->implode('');

    $roleLabel = [
      'admin'  => 'Administrator',
      'staff'  => 'Staff Pemasaran',
      'viewer' => 'Viewer'
    ][$userRole] ?? $userRole;
  @endphp

  <div class="app-container">
    @include('layouts.sidebar')

    <div class="overlay" id="overlay"></div>

    <div class="main-area" id="mainArea">
      <header class="topbar">
        <div class="topbar-left">
          <button class="hamburger-btn" id="hamburgerBtn" type="button">
            <i class="fas fa-bars"></i>
          </button>

          <div class="topbar-title">@yield('page_title', 'Dashboard')</div>

          {{-- ===== DROPDOWN TAHUN (GLOBAL) ===== --}}
          <div class="header-year-selector">
              <div style="position: relative; display: inline-block;">
                <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 1; font-size:12px;">📅</span>
                
                <select onchange="window.location.href='{{ url('set-year') }}/' + this.value" 
                        style="padding-left: 28px; padding-right: 8px; padding-top: 4px; padding-bottom: 4px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; cursor: pointer; height: 32px; font-size: 13px; color: #334155; outline:none;">
                    
                    {{-- Opsi Default --}}
                    {{-- Teksnya sekarang dinamis: "2027 (Default)" --}}
                    <option value="default" {{ ($sharedCurrentYear ?? 'default') === 'default' ? 'selected' : '' }}>
                        {{ $sharedLatestYear ?? date('Y') }} (Default)
                    </option>
        
                    {{-- Loop Tahun dari Database --}}
                    @foreach($sharedAvailableYears ?? [] as $year)
                        {{-- Jangan tampilkan tahun di list jika sama dengan tahun default (opsional, biar rapi) --}}
                        @if($year != ($sharedLatestYear ?? ''))
                            <option value="{{ $year }}" {{ ($sharedCurrentYear ?? '') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
          </div>
          {{-- ===== END DROPDOWN ===== --}}

          @hasSection('topbar_filters')
            <div class="topbar-filters">
              @yield('topbar_filters')
            </div>
          @endif
        </div>

        <div class="topbar-right">
          <div class="user-meta">
            <div class="user-name">{{ $userName }}</div>
            <div class="user-role">{{ $roleLabel }}</div>
          </div>
          <div class="user-avatar">{{ $initials ?: 'AU' }}</div>
        </div>
      </header>

      <main class="main-content" id="mainContent">
        @yield('content')
      </main>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar       = document.getElementById('sidebar');
      const overlay       = document.getElementById('overlay');
      const mainArea      = document.getElementById('mainArea');
      const mainContent   = document.getElementById('mainContent');
      const hamburger     = document.getElementById('hamburgerBtn');

      function isMobile() {
        return window.innerWidth <= 768;
      }

      function desktopOpen() {
        sidebar.classList.remove('collapsed');
        mainArea.classList.remove('expanded');
        overlay.classList.remove('active');
        syncHamburger();
      }

      function desktopClose() {
        sidebar.classList.add('collapsed');
        mainArea.classList.add('expanded');
        overlay.classList.remove('active');
        syncHamburger();
      }

      function mobileOpen() {
        sidebar.classList.add('open-mobile');
        overlay.classList.add('active');
        syncHamburger();
      }

      function mobileClose() {
        sidebar.classList.remove('open-mobile');
        overlay.classList.remove('active');
        syncHamburger();
      }

      function syncHamburger() {
        if (isMobile()) {
          hamburger.style.display = 'inline-flex';
        } else {
          hamburger.style.display = sidebar.classList.contains('collapsed')
            ? 'inline-flex'
            : 'none';
        }
      }

      hamburger.addEventListener('click', function () {
        if (isMobile()) mobileOpen();
        else desktopOpen();
      });

      overlay.addEventListener('click', function () {
        if (isMobile()) mobileClose();
      });

      mainContent.addEventListener('click', function () {
        if (isMobile() && sidebar.classList.contains('open-mobile')) {
          mobileClose();
        }
      });

      document.querySelectorAll('.sisir-link').forEach(link => {
        link.addEventListener('click', function () {
          if (isMobile()) mobileClose();
        });
      });

      function init() {
        if (isMobile()) {
          sidebar.classList.remove('open-mobile');
          overlay.classList.remove('active');
          mainArea.classList.add('expanded');
        } else {
          sidebar.classList.remove('collapsed');
          mainArea.classList.remove('expanded');
        }
        syncHamburger();
      }

      let t;
      window.addEventListener('resize', function () {
        clearTimeout(t);
        t = setTimeout(init, 150);
      });

      init();

      window.__SISIR__ = {
        collapseDesktop: desktopClose,
        openDesktop: desktopOpen
      };
    });
  </script>
</body>
</html>