<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Coffee Shop Attendance') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #8B4513;
            --secondary-color: #D2B48C;
            --accent-color: #F4A460;
            --dark-color: #5D2A0A;
            --light-color: #F5F5DC;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color) !important;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        .main-content {
            padding: 20px;
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
            padding: 8px 20px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
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

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 56px;
                left: -250px;
                width: 250px;
                z-index: 1040;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <!-- Mobile menu toggle -->
            <button class="btn d-lg-none me-3" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Brand -->
            <a class="navbar-brand" href="#">
                <i class="fas fa-coffee me-2"></i>
                {{ config('app.name', 'Coffee Shop Attendance') }}
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
            <div class="p-3">
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
                        Dashboard
                    </a>
                    
                    <!-- Role-based Navigation -->
                    @if($primaryRole && $rbac->userHasPermission(auth()->user(), 'branch.view.all'))
                        <!-- HR Central / System Admin menus -->
                        <a href="{{ route('hr-central.branches.index') }}" class="nav-link">
                            <i class="fas fa-store"></i>All Branches
                        </a>
                        <a href="{{ route('hr-central.employees.index') }}" class="nav-link">
                            <i class="fas fa-users"></i>All Employees
                        </a>
                        <a href="{{ route('hr-central.attendance.index') }}" class="nav-link">
                            <i class="fas fa-clock"></i>Global Attendance
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="nav-link">
                            <i class="fas fa-users-cog"></i>Role Management
                        </a>
                    @elseif($primaryRole && $rbac->userHasPermission(auth()->user(), 'branch.view.assigned'))
                        <!-- Branch Manager / Pengelola menus -->
                        <a href="{{ route('branch-manager.branches.index') }}" class="nav-link">
                            <i class="fas fa-store"></i>My Branches
                        </a>
                        <a href="{{ route('branch-manager.employees.index') }}" class="nav-link">
                            <i class="fas fa-users"></i>Staff Management
                        </a>
                        <a href="{{ route('branch-manager.attendance.index') }}" class="nav-link">
                            <i class="fas fa-clock"></i>Attendance Overview
                        </a>
                        <a href="{{ route('branch-manager.schedules.index') }}" class="nav-link">
                            <i class="fas fa-calendar"></i>Schedule Management
                        </a>
                    @else
                        <!-- Employee menus -->
                        <a href="{{ route('employee.attendance.checkin') }}" class="nav-link">
                            <i class="fas fa-fingerprint"></i>Check In/Out
                        </a>
                        <a href="{{ route('employee.attendance.index') }}" class="nav-link">
                            <i class="fas fa-clock"></i>My Attendance
                        </a>
                        <a href="{{ route('employee.schedule.index') }}" class="nav-link">
                            <i class="fas fa-calendar"></i>My Schedule
                        </a>
                    @endif
                    
                    <!-- Common menus for all roles -->
                    @if($rbac->userHasPermission(auth()->user(), 'leave.create.own') || $rbac->userHasPermission(auth()->user(), 'leave.view.branch'))
                        <a href="{{ route('leaves.index') }}" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>Leave Requests
                        </a>
                    @endif
                    
                    @if($rbac->userHasPermission(auth()->user(), 'report.view.own') || $rbac->userHasPermission(auth()->user(), 'report.view.branch'))
                        <a href="{{ route('reports.index') }}" class="nav-link">
                            <i class="fas fa-chart-bar"></i>Reports
                        </a>
                    @endif
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content flex-grow-1" style="margin-left: 0;">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    </script>
    
    @stack('scripts')
</body>
</html>
