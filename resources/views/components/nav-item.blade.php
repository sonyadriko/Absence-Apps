@props([
    'route' => '',
    'icon' => 'fas fa-circle',
    'label' => '',
    'active' => false,
    'permission' => null,
    'badge' => null,
    'badgeColor' => 'danger'
])

@php
    $hasPermission = true;
    if ($permission && auth()->check()) {
        $rbac = app(App\Services\RBACService::class);
        $hasPermission = $rbac->userHasPermission(auth()->user(), $permission);
    }
    
    $isActive = $active || request()->routeIs($route) || request()->routeIs($route . '.*');
    $url = $route ? route($route) : '#';
@endphp

@if($hasPermission)
    <div class="nav-item">
        <a href="{{ $url }}" 
           class="nav-link {{ $isActive ? 'active' : '' }}" 
           {{ $attributes }}>
            
            <div class="d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center">
                    <i class="{{ $icon }}"></i>
                    <span class="nav-label">{{ $label }}</span>
                </div>
                
                @if($badge)
                    <span class="badge bg-{{ $badgeColor }} rounded-pill">{{ $badge }}</span>
                @endif
            </div>
        </a>
    </div>
@endif

<style>
.nav-item {
    margin: 3px 0;
}

.nav-link {
    color: rgba(255,255,255,0.9);
    padding: 12px 15px;
    margin: 2px 0;
    border-radius: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.nav-link:hover,
.nav-link.active {
    background: rgba(255,255,255,0.1);
    color: white;
    transform: translateX(3px);
}

.nav-link i {
    width: 18px;
    text-align: center;
    margin-right: 12px;
    font-size: 0.9rem;
}

.nav-label {
    font-size: 0.9rem;
    font-weight: 500;
}

.nav-link .badge {
    font-size: 0.7rem;
    padding: 3px 6px;
}
</style>
