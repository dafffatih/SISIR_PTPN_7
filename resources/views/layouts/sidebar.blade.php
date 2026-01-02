@php
$role = auth()->user()->role ?? null;

$menus = [
    [
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'icon'  => 'fas fa-th-large',
        'roles' => ['admin','staff','viewer']
    ],
    [
        'label' => 'Manajemen Kontrak',
        'route' => 'kontrak',
        'icon'  => 'fas fa-file-contract',
        'roles' => ['admin','staff']
    ],
    [
        'label' => 'User Management',
        'route' => 'users',
        'icon'  => 'fas fa-users',
        'roles' => ['admin']
    ],
    [
        'label' => 'Upload & Export',
        'route' => 'upload.export',
        'icon'  => 'fas fa-cloud-upload-alt',
        'roles' => ['admin','staff']
    ],
];
@endphp

<style>
/* Base Sidebar Styles */
.sisir-sidebar {
    width: 260px;
    min-height: 100vh;
    background: #1e293b;
    color: #ffffff;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    transition: transform 0.3s ease;
}

/* State: Sidebar collapsed (hidden) */
.sisir-sidebar.collapsed {
    transform: translateX(-100%);
}

.sisir-sidebar-content {
    flex: 1;
    padding: 24px 16px;
    overflow-y: auto;
}

.sisir-brand-container {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
    padding: 0 8px;
}

.sisir-logo {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.sisir-brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.sisir-brand-title {
    font-weight: 700;
    font-size: 22px;
    letter-spacing: 0.5px;
    color: #ffffff;
}

.sisir-brand-subtitle {
    font-weight: 400;
    font-size: 13px;
    color: #94a3b8;
    margin-top: 2px;
}

.sisir-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.sisir-link {
    text-decoration: none;
    color: #e2e8f0;
    padding: 12px 16px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: transparent;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 400;
}

.sisir-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff;
}

.sisir-link.is-active {
    background: linear-gradient(90deg, #f97316 0%, #ef4444 100%);
    color: #ffffff;
    font-weight: 500;
}

.sisir-link i {
    width: 20px;
    text-align: center;
    font-size: 16px;
}

.sisir-logout-container {
    padding: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: auto;
}

.sisir-logout-form {
    width: 100%;
}

.sisir-logout-btn {
    width: 100%;
    padding: 12px 16px;
    border-radius: 8px;
    background: #334155;
    color: #ffffff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.sisir-logout-btn:hover {
    background: #475569;
}

.sisir-logout-btn i {
    font-size: 16px;
}

/* Scrollbar styling */
.sisir-sidebar-content::-webkit-scrollbar {
    width: 6px;
}

.sisir-sidebar-content::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.sisir-sidebar-content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

.sisir-sidebar-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Hamburger Menu Toggle Button - Untuk SEMUA Device */
.hamburger-menu-toggle {
    display: flex;
    position: fixed;
    top: 16px;
    left: 16px;
    z-index: 1001;
    background: #1e293b;
    color: #ffffff;
    border: none;
    width: 48px;
    height: 48px;
    border-radius: 8px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.hamburger-menu-toggle:hover {
    background: #334155;
    transform: scale(1.05);
}

.hamburger-menu-toggle:active {
    transform: scale(0.95);
}

/* Icon animation */
.hamburger-menu-toggle i {
    transition: transform 0.3s ease;
}

.hamburger-menu-toggle.active i {
    transform: rotate(90deg);
}

/* Overlay for mobile */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.sidebar-overlay.active {
    display: block;
    opacity: 1;
    pointer-events: auto;
}

/* Responsive Styles */

/* Desktop (min 1025px) - Overlay tidak diperlukan */
@media (min-width: 1025px) {
    .sidebar-overlay {
        display: none !important;
    }
}

/* Tablet (768px - 1024px) */
@media (max-width: 1024px) and (min-width: 769px) {
    .sisir-sidebar {
        width: 220px;
    }
    
    .sisir-brand-title {
        font-size: 20px;
    }
    
    .sisir-link {
        padding: 10px 14px;
        font-size: 13px;
    }
}

/* Mobile & Small Tablet (max 768px) */
@media (max-width: 768px) {
    .sidebar-overlay {
        display: block;
    }
    
    .sisir-sidebar {
        transform: translateX(-100%);
    }
    
    .sisir-sidebar.active {
        transform: translateX(0);
    }
    
    .sisir-sidebar.collapsed {
        transform: translateX(-100%);
    }
}

/* Small Mobile (max 480px) */
@media (max-width: 480px) {
    .sisir-sidebar {
        width: 280px;
        max-width: 85vw;
    }
    
    .sisir-brand-title {
        font-size: 20px;
    }
    
    .sisir-brand-subtitle {
        font-size: 12px;
    }
    
    .sisir-link {
        padding: 12px 14px;
        font-size: 14px;
    }
}
</style>

<!-- Hamburger Menu Toggle Button - Untuk SEMUA Device -->
<button class="hamburger-menu-toggle" id="hamburgerToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay untuk Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sisir-sidebar" id="sidebar">
    <div class="sisir-sidebar-content">
        <!-- Brand / Logo -->
        <div class="sisir-brand-container">
            <div class="sisir-logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="sisir-brand-text">
                <div class="sisir-brand-title">SISIR</div>
                <div class="sisir-brand-subtitle">PTPN 1 Regional 7</div>
            </div>
        </div>

        <!-- Navigation Menu -->
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

    <!-- Logout Button (Fixed at bottom) -->
    <div class="sisir-logout-container">
        <form method="POST" action="{{ route('logout') }}" class="sisir-logout-form">
            @csrf
            <button type="submit" class="sisir-logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const hamburgerToggle = document.getElementById('hamburgerToggle');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Fungsi untuk toggle sidebar
    function toggleSidebar() {
        const isCollapsed = sidebar.classList.contains('collapsed');
        
        if (isCollapsed) {
            // Buka sidebar
            sidebar.classList.remove('collapsed');
            sidebar.classList.add('active');
            hamburgerToggle.classList.add('active');
            
            // Tampilkan overlay di mobile
            if (window.innerWidth <= 768) {
                overlay.classList.add('active');
            }
        } else {
            // Tutup sidebar
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('active');
            hamburgerToggle.classList.remove('active');
            overlay.classList.remove('active');
        }
    }
    
    // Event listener untuk hamburger button
    hamburgerToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
    
    // Event listener untuk overlay (hanya tutup sidebar di mobile)
    overlay.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('active');
            hamburgerToggle.classList.remove('active');
            overlay.classList.remove('active');
        }
    });
    
    // Close sidebar ketika klik menu item di mobile
    if (window.innerWidth <= 768) {
        const menuLinks = document.querySelectorAll('.sisir-link');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('active');
                hamburgerToggle.classList.remove('active');
                overlay.classList.remove('active');
            });
        });
    }
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Jika resize ke desktop, tutup overlay
            if (window.innerWidth > 768) {
                overlay.classList.remove('active');
            }
            
            // Update menu link listeners
            const menuLinks = document.querySelectorAll('.sisir-link');
            menuLinks.forEach(link => {
                // Remove existing listener
                const newLink = link.cloneNode(true);
                link.parentNode.replaceChild(newLink, link);
            });
            
            // Re-add listener untuk mobile
            if (window.innerWidth <= 768) {
                const menuLinks = document.querySelectorAll('.sisir-link');
                menuLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        sidebar.classList.add('collapsed');
                        sidebar.classList.remove('active');
                        hamburgerToggle.classList.remove('active');
                        overlay.classList.remove('active');
                    });
                });
            }
        }, 250);
    });
    
    // Set initial state berdasarkan ukuran layar
    function setInitialState() {
        if (window.innerWidth <= 768) {
            // Mobile: sidebar tertutup by default
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('active');
            hamburgerToggle.classList.remove('active');
        } else {
            // Desktop: sidebar terbuka by default
            sidebar.classList.remove('collapsed');
            sidebar.classList.add('active');
            hamburgerToggle.classList.add('active');
        }
    }
    
    // Jalankan saat load
    setInitialState();
});
</script>