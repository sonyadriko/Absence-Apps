@php
    $user = auth()->user();
    $userRoles = app(App\Services\RBACService::class)->getUserActiveRoles($user);
    $primaryRole = $userRoles->first();
@endphp

<nav class="top-navbar">
    <div class="d-flex align-items-center">
        <!-- Sidebar Toggle (Mobile) -->
        <button class="btn btn-link d-lg-none me-3" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page Title -->
        <div class="page-title">
            <h4 class="mb-0">@yield('page-title', 'Dashboard')</h4>
            @hasSection('page-subtitle')
                <small class="text-muted">@yield('page-subtitle')</small>
            @endif
        </div>
    </div>

    <div class="d-flex align-items-center">
        <!-- Search (Optional) -->
        @hasSection('navbar-search')
            <div class="me-3">
                @yield('navbar-search')
            </div>
        @endif

        <!-- Notifications -->
        <div class="dropdown me-3">
            <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell fs-5 text-muted"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count" style="display: none;">
                    <span class="count">0</span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li class="notification-loading text-center p-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="small mt-2">Loading notifications...</div>
                </li>
                <li class="notification-empty text-center p-3" style="display: none;">
                    <i class="fas fa-bell-slash text-muted mb-2"></i>
                    <div class="small text-muted">No notifications</div>
                </li>
                <div id="notification-list"></div>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center small" href="{{ route('notifications.index') }}">View All</a></li>
            </ul>
        </div>
        
        <!-- Theme Toggle -->
        <button class="btn btn-link me-3" onclick="toggleTheme()" title="Toggle Theme">
            <i class="fas fa-moon theme-icon"></i>
        </button>

        <!-- User Menu -->
        <div class="dropdown">
            <button class="btn btn-link dropdown-toggle d-flex align-items-center user-menu-btn" type="button" data-bs-toggle="dropdown">
                <img src="{{ $user->avatar_url ?? 'https://via.placeholder.com/32x32/8B4513/FFFFFF?text=' . substr($user->name, 0, 1) }}" 
                     class="rounded-circle me-2 user-avatar" alt="User Avatar">
                <div class="text-start d-none d-sm-block">
                    <div class="small fw-bold text-dark user-name">{{ $user->name }}</div>
                    <div class="small text-muted user-role">
                        @if($primaryRole)
                            {{ $primaryRole->role->display_name }}
                        @endif
                    </div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end user-menu">
                <li class="dropdown-header">
                    <div class="fw-bold">{{ $user->name }}</div>
                    <div class="small text-muted">{{ $user->email }}</div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('profile.index') }}">
                    <i class="fas fa-user me-2"></i>My Profile
                </a></li>
                <li><a class="dropdown-item" href="{{ route('settings.index') }}">
                    <i class="fas fa-cog me-2"></i>Settings
                </a></li>
                @if($primaryRole && app(App\Services\RBACService::class)->userHasPermission($user, 'attendance.view.own'))
                    <li><a class="dropdown-item" href="{{ route('employee.attendance.index') }}">
                        <i class="fas fa-clock me-2"></i>My Attendance
                    </a></li>
                @endif
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button type="button" class="dropdown-item" onclick="logout()">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.top-navbar {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 999;
    transition: all 0.3s ease;
}

.page-title h4 {
    color: var(--primary-color);
    font-weight: 600;
}

.user-menu-btn {
    text-decoration: none !important;
    border: none !important;
    box-shadow: none !important;
}

.user-avatar {
    width: 32px;
    height: 32px;
    object-fit: cover;
}

.user-menu {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
    min-width: 200px;
}

.user-menu .dropdown-item {
    padding: 10px 20px;
    transition: all 0.3s ease;
}

.user-menu .dropdown-item:hover {
    background: var(--light-color);
    color: var(--primary-color);
}

.notification-dropdown {
    min-width: 320px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-count {
    font-size: 0.7rem;
    padding: 2px 5px;
    min-width: 16px;
    height: 16px;
}

.theme-icon {
    transition: transform 0.3s ease;
}

[data-theme="dark"] .theme-icon {
    transform: rotate(180deg);
}

@media (max-width: 768px) {
    .top-navbar {
        padding: 15px 20px;
    }
    
    .page-title h4 {
        font-size: 1.1rem;
    }
    
    .user-name,
    .user-role {
        display: none;
    }
}

/* Dark theme support */
[data-theme="dark"] .top-navbar {
    background: #2d2d2d;
    color: white;
}

[data-theme="dark"] .page-title h4 {
    color: var(--accent-color);
}

[data-theme="dark"] .user-name {
    color: white;
}

[data-theme="dark"] .user-menu {
    background: #3d3d3d;
    color: white;
}

[data-theme="dark"] .user-menu .dropdown-item {
    color: white;
}

[data-theme="dark"] .user-menu .dropdown-item:hover {
    background: var(--primary-color);
    color: white;
}
</style>

<script>
// Theme toggle functionality
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Update theme icon
    const themeIcon = document.querySelector('.theme-icon');
    themeIcon.className = newTheme === 'dark' ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
}

// Load saved theme
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    const themeIcon = document.querySelector('.theme-icon');
    if (themeIcon) {
        themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
    }
});

// Logout functionality
async function logout() {
    try {
        await API.post('/auth/logout');
        API.removeToken();
        window.location.href = '/login';
    } catch (error) {
        console.error('Logout failed:', error);
        // Force logout even if API call fails
        API.removeToken();
        window.location.href = '/login';
    }
}

// Load notifications
async function loadNotifications() {
    try {
        const response = await API.get('/notifications');
        const notifications = response.data;
        
        const notificationList = document.getElementById('notification-list');
        const notificationCount = document.querySelector('.notification-count');
        const countSpan = document.querySelector('.notification-count .count');
        
        // Hide loading state
        document.querySelector('.notification-loading').style.display = 'none';
        
        if (notifications.length === 0) {
            document.querySelector('.notification-empty').style.display = 'block';
            notificationCount.style.display = 'none';
        } else {
            // Show notification count
            countSpan.textContent = notifications.length;
            notificationCount.style.display = 'block';
            
            // Render notifications
            notificationList.innerHTML = notifications.map(notification => `
                <li><a class="dropdown-item" href="${notification.link || '#'}">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas ${notification.icon || 'fa-info-circle'} text-${notification.type || 'primary'}"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <div class="fw-bold small">${notification.title}</div>
                            <div class="small text-muted">${notification.message}</div>
                            <div class="small text-muted">${notification.created_at}</div>
                        </div>
                    </div>
                </a></li>
            `).join('');
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
        document.querySelector('.notification-loading').style.display = 'none';
        document.querySelector('.notification-empty').style.display = 'block';
    }
}

// Mobile sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    if (API.token) {
        loadNotifications();
    }
});
</script>
