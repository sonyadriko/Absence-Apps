@php
    $user = auth()->user();
    $userRoles = app(App\Services\RBACService::class)->getUserActiveRoles($user);
    $primaryRole = $userRoles->first();
    $rbac = app(App\Services\RBACService::class);
@endphp

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <div class="sidebar-brand">
                <i class="fas fa-coffee me-2"></i>
                <span class="brand-text">CoffeeAttend</span>
            </div>
        </div>
    </div>

    <div class="sidebar-content">
        <nav class="nav flex-column p-3">
            @include('components.nav-item', [
                'route' => $primaryRole ? $primaryRole->role->name . '.dashboard' : 'employee.dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'label' => 'Dashboard',
                'active' => request()->routeIs('*.dashboard')
            ])

            @if($rbac->userHasPermission($user, 'branch.view.all'))
                <!-- HR Central / System Admin Navigation -->
                @include('components.nav-section', [
                    'title' => 'Management',
                    'items' => [
                        [
                            'route' => 'management.branches.index',
                            'icon' => 'fas fa-store',
                            'label' => 'All Branches',
                            'permission' => 'branch.view.all'
                        ],
                        [
                            'route' => 'management.employees.index',
                            'icon' => 'fas fa-users',
                            'label' => 'All Employees',
                            'permission' => 'employee.view.all'
                        ],
                        [
                            'route' => 'management.attendance.index',
                            'icon' => 'fas fa-clock',
                            'label' => 'Global Attendance',
                            'permission' => 'attendance.view.all'
                        ],
                        [
                            'route' => 'management.roles.index',
                            'icon' => 'fas fa-users-cog',
                            'label' => 'Role Management',
                            'permission' => 'role.manage'
                        ]
                    ]
                ])

            @elseif($rbac->userHasPermission($user, 'branch.view.assigned'))
                <!-- Branch Manager / Pengelola Navigation -->
                @include('components.nav-section', [
                    'title' => 'Branch Management',
                    'items' => [
                        [
                            'route' => 'management.branches.index',
                            'icon' => 'fas fa-store',
                            'label' => 'My Branches',
                            'permission' => 'branch.view.assigned'
                        ],
                        [
                            'route' => 'management.employees.index',
                            'icon' => 'fas fa-users',
                            'label' => 'Staff Management',
                            'permission' => 'employee.view.branch'
                        ],
                        [
                            'route' => 'management.attendance.index',
                            'icon' => 'fas fa-clock',
                            'label' => 'Attendance Overview',
                            'permission' => 'attendance.view.branch'
                        ],
                        [
                            'route' => 'management.schedules.index',
                            'icon' => 'fas fa-calendar',
                            'label' => 'Schedule Management',
                            'permission' => 'schedule.manage.branch'
                        ]
                    ]
                ])

            @else
                <!-- Employee Navigation -->
                @include('components.nav-section', [
                    'title' => 'My Attendance',
                    'items' => [
                        [
                            'route' => 'employee.attendance.checkin',
                            'icon' => 'fas fa-fingerprint',
                            'label' => 'Check In/Out',
                            'permission' => 'attendance.create.own'
                        ],
                        [
                            'route' => 'employee.attendance.index',
                            'icon' => 'fas fa-clock',
                            'label' => 'My Attendance',
                            'permission' => 'attendance.view.own'
                        ],
                        [
                            'route' => 'employee.schedule.index',
                            'icon' => 'fas fa-calendar',
                            'label' => 'My Schedule',
                            'permission' => 'schedule.view.own'
                        ]
                    ]
                ])
            @endif

            <!-- Common Navigation Items -->
            @php
                $commonItems = [];
                
                if($rbac->userHasPermission($user, 'leave.create.own') || $rbac->userHasPermission($user, 'leave.view.branch')) {
                    $commonItems[] = [
                        'route' => 'leaves.index',
                        'icon' => 'fas fa-calendar-alt',
                        'label' => 'Leave Requests',
                        'permission' => 'leave.view.own'
                    ];
                }
                
                if($rbac->userHasPermission($user, 'correction.create.own') || $rbac->userHasPermission($user, 'correction.approve.branch')) {
                    $commonItems[] = [
                        'route' => 'corrections.index',
                        'icon' => 'fas fa-edit',
                        'label' => 'Corrections',
                        'permission' => 'correction.view.own'
                    ];
                }
                
                if($rbac->userHasPermission($user, 'report.view.own') || $rbac->userHasPermission($user, 'report.view.branch')) {
                    $commonItems[] = [
                        'route' => 'reports.index',
                        'icon' => 'fas fa-chart-bar',
                        'label' => 'Reports',
                        'permission' => 'report.view.own'
                    ];
                }
            @endphp

            @if(!empty($commonItems))
                @include('components.nav-section', [
                    'title' => 'Additional',
                    'items' => $commonItems
                ])
            @endif
        </nav>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="p-3 border-top border-light">
            <div class="d-flex align-items-center text-light small">
                <div class="flex-grow-1">
                    <div class="fw-bold">{{ $user->name }}</div>
                    <div class="text-light opacity-75">
                        @if($primaryRole)
                            {{ $primaryRole->role->display_name }}
                        @endif
                    </div>
                </div>
                <div>
                    <button class="btn btn-link text-light p-0" onclick="toggleSidebar()">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar {
    width: 280px;
    background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
    color: white;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebar.collapsed {
    transform: translateX(-280px);
}

.sidebar-header {
    padding: 30px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-brand {
    font-size: 1.3rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
}

.sidebar-content {
    height: calc(100vh - 120px);
    overflow-y: auto;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0,0,0,0.1);
}

.nav-section-title {
    color: rgba(255,255,255,0.7);
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 20px 0 10px 0;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-280px);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('sidebar-collapsed');
    
    // Save state to localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebar_collapsed', isCollapsed);
}

// Restore sidebar state on page load
document.addEventListener('DOMContentLoaded', function() {
    const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    if (isCollapsed) {
        document.getElementById('sidebar').classList.add('collapsed');
        document.querySelector('.main-content').classList.add('sidebar-collapsed');
    }
});
</script>
