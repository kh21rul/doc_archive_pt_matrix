<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Document Archive' }}</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <i class="fas fa-book-open"></i>
                    <span class="logo-text">Doc Archive</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="/dashboard" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/dashboard/documents"
                    class="nav-item {{ request()->is('dashboard/documents') ? 'active' : '' }}">
                    <i class="fas fa-folder-open"></i>
                    <span>Semua Dokumen</span>
                </a>
                <a href="/dashboard/divisions"
                    class="nav-item {{ request()->is('dashboard/divisions') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>Divisi</span>
                </a>
                {{-- <a href="#" class="nav-item">
                    <i class="fas fa-upload"></i>
                    <span>Upload Dokumen</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistik</span>
                </a> --}}
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <p class="user-name">{{ Auth::user()->name ?? 'User' }}</p>
                        <p class="user-email">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Navbar -->
            <nav class="navbar-dashboard">
                <button class="sidebar-toggle mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="navbar-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Cari dokumen...">
                </div>
                <div class="navbar-actions">
                    <button class="navbar-btn">
                        <i class="fas fa-bell"></i>
                        <span class="badge-notification">3</span>
                    </button>
                    <button class="navbar-btn">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="page-content">
                {{ $slot }}
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @livewireScripts

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileToggle = document.getElementById('mobileToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        sidebarToggle?.addEventListener('click', toggleSidebar);
        mobileToggle?.addEventListener('click', toggleSidebar);

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            }
        });
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f6f6f6;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            left: -280px;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-container i {
            font-size: 2rem;
            color: #c2a25d;
        }

        .logo-text {
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
        }

        .sidebar-toggle {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            padding: 5px;
            display: none;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #c2a25d;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(194, 162, 93, 0.1);
            color: white;
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            transform: scaleY(1);
        }

        .nav-item i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .nav-item span {
            font-weight: 500;
            font-size: 0.95rem;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: #c2a25d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .user-avatar i {
            font-size: 1.3rem;
            color: white;
        }

        .user-details {
            flex: 1;
            overflow: hidden;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            margin: 2px 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-logout {
            width: 100%;
            padding: 12px;
            background: rgba(231, 76, 60, 0.2);
            color: white;
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-logout:hover {
            background: #e74c3c;
            border-color: #e74c3c;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Navbar */
        .navbar-dashboard {
            background: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .mobile-toggle {
            display: none;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
            padding: 5px 10px;
        }

        .navbar-search {
            flex: 1;
            max-width: 500px;
            position: relative;
        }

        .navbar-search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .navbar-search input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .navbar-search input:focus {
            outline: none;
            border-color: #c2a25d;
            box-shadow: 0 0 0 3px rgba(194, 162, 93, 0.1);
        }

        .navbar-actions {
            display: flex;
            gap: 10px;
        }

        .navbar-btn {
            width: 45px;
            height: 45px;
            background: #f8f9fa;
            border: none;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            color: #666;
        }

        .navbar-btn:hover {
            background: #c2a25d;
            color: white;
        }

        .badge-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* Page Content */
        .page-content {
            flex: 1;
            padding: 30px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -280px;
            }

            .sidebar.collapsed {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .sidebar-toggle {
                display: block;
            }

            .navbar-search {
                max-width: none;
            }

            .page-content {
                padding: 20px 15px;
            }
        }

        @media (max-width: 576px) {
            .navbar-dashboard {
                padding: 15px;
            }

            .navbar-search {
                display: none;
            }

            .logo-text {
                font-size: 1.2rem;
            }
        }
    </style>
</body>

</html>
