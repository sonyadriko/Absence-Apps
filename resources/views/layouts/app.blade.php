<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-head />
    <title>{{ config('app.name', 'Coffee Shop Attendance') }} - @yield('title', 'Dashboard')</title>
    
    <!-- Custom CSS -->
    <style>

        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8f9fa;
            padding-top: 56px;
        }

        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color) !important;
        }

        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease;
            padding-top: 56px;
        }
        
        /* Desktop collapsed sidebar */
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar.collapsed .nav-link span {
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.2s ease;
            display: none;
        }
        
        .sidebar.collapsed .nav-link {
            text-align: center;
            padding: 12px 8px;
            position: relative;
            justify-content: center;
            display: flex;
            align-items: center;
            min-height: 48px;
            margin: 4px 8px;
            border-radius: 12px;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.3em;
            width: auto;
            text-align: center;
        }
        
        .sidebar.collapsed .nav-link:hover::after {
            content: attr(data-title);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--dark-color);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            white-space: nowrap;
            z-index: 1001;
            font-size: 0.9em;
            margin-left: 8px;
            opacity: 1;
            pointer-events: none;
        }
        
        .sidebar.collapsed .nav-link:hover::before {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: var(--dark-color);
            z-index: 1001;
            margin-left: 2px;
        }
        
        .main-content.sidebar-collapsed {
            margin-left: 70px !important;
            width: calc(100% - 70px) !important;
        }
        
        /* Ensure smooth animation */
        .main-content.sidebar-collapsed .container-fluid {
            max-width: none;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            margin: 4px 12px;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.1em;
        }
        
        .sidebar .nav-link span {
            flex: 1;
            font-weight: 500;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: calc(100vh - 56px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: calc(100% - 260px);
            background: #f8f9fa;
        }

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 12px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(139, 69, 19, 0.2);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: linear-gradient(135deg, var(--dark-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
            border: none;
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 3px 12px rgba(139, 69, 19, 0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #34ce57);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #20c997);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #ffca2c);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #212529;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #e55353);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .badge {
            border-radius: 20px;
            padding: 5px 12px;
        }

        .role-badge {
            font-size: 0.8em;
            font-weight: 500;
        }

        .user-menu {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: none;
        }

        .user-menu .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .user-menu .dropdown-item:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--accent-color);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        @media (max-width: 1024px) {
            .sidebar.collapsed .nav-link:hover::after,
            .sidebar.collapsed .nav-link:hover::before {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -260px;
                width: 260px;
                z-index: 1040;
                transition: left 0.3s ease;
                box-shadow: 5px 0 15px rgba(0,0,0,0.3);
                padding-top: 56px;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 15px;
            }
            
            .navbar .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-brand .fas {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 10px;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .btn-group .btn {
                font-size: 0.85rem;
                padding: 6px 12px;
            }
            
            .stats-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .user-menu .dropdown-item {
                padding: 8px 15px;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" style="z-index: 1050;">
        <div class="container-fluid">
        <!-- Sidebar toggle (both mobile & desktop) -->
            <button class="btn me-3" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Brand -->
            <a class="navbar-brand" href="#">
                <i class="fas fa-coffee me-2"></i>
                <span class="d-none d-sm-inline">{{ config('app.name', 'Coffee Shop Attendance') }}</span>
                <span class="d-sm-none">CoffeeShop</span>
            </a>
            
            <!-- Right side menu -->
            <div class="d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                            <span class="visually-hidden">unread notifications</span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><a class="dropdown-item" href="#">New leave request</a></li>
                        <li><a class="dropdown-item" href="#">Attendance correction</a></li>
                        <li><a class="dropdown-item" href="#">System update</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">View All</a></li>
                    </ul>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                        <img src="https://via.placeholder.com/32x32/8B4513/FFFFFF?text={{ substr(auth()->user()->name, 0, 1) }}" 
                             class="rounded-circle me-2" alt="User Avatar">
                        <div class="text-start d-none d-sm-block">
                            <div class="small fw-bold text-dark">{{ auth()->user()->name }}</div>
                            <div class="small text-muted">
                                @if(auth()->user()->employee)
                                    @php
                                        $userRoles = app(App\Services\RBACService::class)->getUserActiveRoles(auth()->user());
                                        $primaryRole = $userRoles->first();
                                    @endphp
                                    @if($primaryRole)
                                        <span class="badge role-badge" style="background-color: {{ $primaryRole->role->color }}">
                                            {{ $primaryRole->role->display_name }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end user-menu">
                        <li><a class="dropdown-item" href="{{ route('employee.profile.index') }}">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="py-3 px-2">
                <div class="nav flex-column">
                    @php
                        $userRoles = app(App\Services\RBACService::class)->getUserActiveRoles(auth()->user());
                        $primaryRole = $userRoles->first();
                        $rbac = app(App\Services\RBACService::class);
                    @endphp
                    
                    <!-- Dashboard -->
                    @php
                        // Map role names to correct route names
                        $roleRouteMap = [
                            'hr_central' => 'hr-central.dashboard',
                            'branch_manager' => 'branch-manager.dashboard',
                            'pengelola' => 'pengelola.dashboard',
                            'system_admin' => 'admin.dashboard',
                            'shift_leader' => 'shift-leader.dashboard',
                            'supervisor' => 'supervisor.dashboard',
                            'senior_barista' => 'employee.dashboard',
                            'employee' => 'employee.dashboard'
                        ];
                        $dashboardRoute = $roleRouteMap[$primaryRole->role->name ?? 'employee'] ?? 'employee.dashboard';
                    @endphp
                    <a href="{{ route($dashboardRoute) }}" 
                       class="nav-link {{ request()->routeIs('*.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <!-- Role-based Navigation -->
                    @if($primaryRole && $rbac->userHasPermission(auth()->user(), 'branch.view.all'))
                        <!-- HR Central / System Admin menus -->
                        <a href="{{ route('hr-central.branches.index') }}" 
                           class="nav-link {{ request()->routeIs('hr-central.branches.*') ? 'active' : '' }}">
                            <i class="fas fa-store"></i><span>All Branches</span>
                        </a>
                        <a href="{{ route('hr-central.employees.index') }}" class="nav-link">
                            <i class="fas fa-users"></i><span>All Employees</span>
                        </a>
                        <a href="{{ route('hr-central.attendance.index') }}" class="nav-link">
                            <i class="fas fa-clock"></i><span>Global Attendance</span>
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="nav-link">
                            <i class="fas fa-users-cog"></i><span>Role Management</span>
                        </a>
                    @elseif($primaryRole && $rbac->userHasPermission(auth()->user(), 'branch.view.assigned'))
                        <!-- Branch Manager / Pengelola menus -->
                        <a href="{{ route('branch-manager.branches.index') }}" class="nav-link">
                            <i class="fas fa-store"></i><span>My Branches</span>
                        </a>
                        <a href="{{ route('branch-manager.employees.index') }}" class="nav-link">
                            <i class="fas fa-users"></i><span>Staff Management</span>
                        </a>
                        <a href="{{ route('branch-manager.attendance.index') }}" class="nav-link">
                            <i class="fas fa-clock"></i><span>Attendance Overview</span>
                        </a>
                        <a href="{{ route('branch-manager.schedules.index') }}" class="nav-link">
                            <i class="fas fa-calendar"></i><span>Schedule Management</span>
                        </a>
                    @else
                        <!-- Employee menus -->
                        <a href="{{ route('employee.attendance.checkin') }}" class="nav-link">
                            <i class="fas fa-fingerprint"></i><span>Check In/Out</span>
                        </a>
                        <a href="{{ route('employee.attendance.index') }}" class="nav-link">
                            <i class="fas fa-clock"></i><span>My Attendance</span>
                        </a>
                        <a href="{{ route('employee.schedule.index') }}" class="nav-link">
                            <i class="fas fa-calendar"></i><span>My Schedule</span>
                        </a>
                    @endif
                    
                    <!-- Common menus for all roles -->
                    @if($rbac->userHasPermission(auth()->user(), 'leave.create.own') || $rbac->userHasPermission(auth()->user(), 'leave.view.branch'))
                        <a href="{{ route('leaves.index') }}" class="nav-link">
                            <i class="fas fa-calendar-alt"></i><span>Leave Requests</span>
                        </a>
                    @endif
                    
                    @if($rbac->userHasPermission(auth()->user(), 'report.view.own') || $rbac->userHasPermission(auth()->user(), 'report.view.branch'))
                        <a href="{{ route('reports.index') }}" class="nav-link">
                            <i class="fas fa-chart-bar"></i><span>Reports</span>
                        </a>
                    @endif
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content flex-grow-1">
            <x-alerts />
            
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <x-scripts />
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.getElementById('sidebarToggle');
            
            if (!sidebar || !mainContent || !toggleBtn) {
                console.warn('Sidebar elements not found');
                return;
            }
            
            // Initialize sidebar state
            function initializeSidebar() {
                if (window.innerWidth > 768) {
                    // Desktop mode
                    sidebar.classList.remove('show');
                    const savedState = localStorage.getItem('sidebar-collapsed');
                    if (savedState === 'true') {
                        sidebar.classList.add('collapsed');
                        mainContent.classList.add('sidebar-collapsed');
                    }
                } else {
                    // Mobile mode
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('sidebar-collapsed');
                }
            }
            
            // Sidebar toggle for both desktop and mobile
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (window.innerWidth > 768) {
                    // Desktop: collapse/expand sidebar
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('sidebar-collapsed');
                    
                    // Debug: log state
                    console.log('Sidebar collapsed:', sidebar.classList.contains('collapsed'));
                    console.log('Main content classes:', mainContent.className);
                    
                    // Save state to localStorage
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebar-collapsed', isCollapsed);
                } else {
                    // Mobile: show/hide sidebar
                    sidebar.classList.toggle('show');
                    
                    // Add/remove body overlay
                    if (sidebar.classList.contains('show')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                }
            });
            
            // Add data-title attributes for tooltips
            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                const span = link.querySelector('span');
                if (span) {
                    link.setAttribute('data-title', span.textContent.trim());
                }
            });
            
            // Close mobile sidebar when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                    if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                        sidebar.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                }
            });
            
            // Close mobile sidebar when pressing escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
            
            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    const wasShowingMobile = sidebar.classList.contains('show');
                    
                    if (window.innerWidth > 768) {
                        // Desktop: remove mobile classes
                        sidebar.classList.remove('show');
                        document.body.style.overflow = '';
                        
                        // Restore saved desktop state
                        const savedState = localStorage.getItem('sidebar-collapsed');
                        if (savedState === 'true') {
                            sidebar.classList.add('collapsed');
                            mainContent.classList.add('sidebar-collapsed');
                        } else {
                            sidebar.classList.remove('collapsed');
                            mainContent.classList.remove('sidebar-collapsed');
                        }
                    } else {
                        // Mobile: remove desktop classes
                        sidebar.classList.remove('collapsed');
                        mainContent.classList.remove('sidebar-collapsed');
                        
                        // If sidebar was visible and we switched to mobile, keep it visible
                        if (wasShowingMobile) {
                            sidebar.classList.add('show');
                        }
                    }
                }, 150);
            });
            
            // Initialize on page load
            initializeSidebar();
            
            // Add smooth transitions after initialization to prevent flash
            setTimeout(() => {
                sidebar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                mainContent.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            }, 100);
            
            // Add active state management for navigation links
            const currentPath = window.location.pathname;
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && (currentPath === href || currentPath.startsWith(href + '/'))) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>
