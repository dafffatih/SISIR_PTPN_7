<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'SISIR')</title>
  
  <!-- Font Awesome untuk ikon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: #f1f5f9;
      color: #1e293b;
    }
    
    .app-container {
      display: flex;
      min-height: 100vh;
    }
    
    .main-content {
      flex: 1;
      padding: 24px;
      margin-left: 260px;
      transition: margin-left 0.3s ease;
      min-height: 100vh;
    }
    
    /* Ketika sidebar collapsed (tertutup) */
    .main-content.expanded {
      margin-left: 0;
    }
    
    /* Responsive adjustments */
    @media (max-width: 1024px) and (min-width: 769px) {
      .main-content {
        margin-left: 220px;
        padding: 20px;
      }
      
      .main-content.expanded {
        margin-left: 0;
      }
    }
    
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 80px 16px 16px 16px;
        width: 100%;
      }
      
      .main-content.expanded {
        margin-left: 0;
      }
    }
    
    @media (max-width: 480px) {
      .main-content {
        padding: 80px 12px 12px 12px;
      }
    }
  </style>
</head>
<body>
  <div class="app-container">
    @include('layouts.sidebar')

    <main class="main-content" id="mainContent">
      @yield('content')
    </main>
  </div>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const mainContent = document.getElementById('mainContent');
      const sidebar = document.getElementById('sidebar');
      const hamburgerToggle = document.getElementById('hamburgerToggle');
      
      // Sync main content margin dengan status sidebar (untuk desktop)
      if (hamburgerToggle) {
        hamburgerToggle.addEventListener('click', function() {
          // Hanya adjust margin di desktop
          if (window.innerWidth > 768) {
            setTimeout(function() {
              if (sidebar.classList.contains('collapsed')) {
                mainContent.classList.add('expanded');
              } else {
                mainContent.classList.remove('expanded');
              }
            }, 50);
          }
        });
      }
      
      // Set initial state
      if (window.innerWidth <= 768) {
        mainContent.classList.add('expanded');
      } else {
        mainContent.classList.remove('expanded');
      }
      
      // Handle resize
      window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
          mainContent.classList.add('expanded');
        } else {
          if (sidebar && sidebar.classList.contains('collapsed')) {
            mainContent.classList.add('expanded');
          } else {
            mainContent.classList.remove('expanded');
          }
        }
      });
    });
  </script>
</body>
</html>