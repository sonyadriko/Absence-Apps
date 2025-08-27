@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
<div class="container-fluid">
    @php
        $rbac = app(App\Services\RBACService::class);
        $userRoles = $rbac->getUserActiveRoles(auth()->user());
        $primaryRole = $userRoles->first();
        $userPermissions = $rbac->getUserPermissions(auth()->user());
    @endphp

    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="background: linear-gradient(135deg, #8B4513, #F4A460); color: white;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-coffee me-2"></i>
                                Welcome back, {{ auth()->user()->name }}!
                            </h2>
                            <p class="mb-2 opacity-90">
                                You're logged in as 
                                @if($primaryRole)
                                    <span class="badge" style="background-color: {{ $primaryRole->role->color }}; color: white;">
                                        {{ $primaryRole->role->display_name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Employee</span>
                                @endif
                            </p>
                            <div class="small opacity-75">
                                <i class="fas fa-clock me-1"></i>{{ now()->format('l, F j, Y - g:i A') }}
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Role Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Your Role Information
                    </h5>
                </div>
                <div class="card-body">
                    @if($primaryRole)
                        <div class="d-flex align-items-start mb-3">
                            <div class="role-color me-3" style="width: 24px; height: 24px; border-radius: 50%; background-color: {{ $primaryRole->role->color }}; margin-top: 4px;"></div>
                            <div>
                                <h6 class="mb-1">{{ $primaryRole->role->display_name }}</h6>
                                <p class="text-muted small mb-2">{{ $primaryRole->role->description }}</p>
                                <div class="small">
                                    <strong>Hierarchy Level:</strong> {{ $primaryRole->role->hierarchy_level }}<br>
                                    <strong>Role Code:</strong> <code>{{ $primaryRole->role->name }}</code>
                                </div>
                            </div>
                        </div>
                        
                        @if($primaryRole->scope_data)
                            <div class="alert alert-info small">
                                <strong><i class="fas fa-filter me-1"></i>Scope Restrictions:</strong><br>
                                @if(isset($primaryRole->scope_data['branches']))
                                    Limited to branch IDs: {{ implode(', ', $primaryRole->scope_data['branches']) }}<br>
                                @endif
                                @if(isset($primaryRole->scope_data['date_range']))
                                    Valid from {{ $primaryRole->scope_data['date_range']['from'] ?? 'N/A' }} to {{ $primaryRole->scope_data['date_range']['until'] ?? 'N/A' }}
                                @endif
                            </div>
                        @endif
                    @else
                        <p class="text-muted">No role assigned. Contact your administrator.</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-key me-2"></i>Your Permissions
                    </h5>
                </div>
                <div class="card-body">
                    @if($userPermissions->count() > 0)
                        <div class="row">
                            @foreach($userPermissions->groupBy(function($item) { return explode('.', $item['permission']->name)[0]; }) as $group => $permissions)
                                <div class="col-6 mb-3">
                                    <h6 class="text-primary">{{ ucfirst($group) }}</h6>
                                    <ul class="list-unstyled small">
                                        @foreach($permissions->take(3) as $permission)
                                            <li>
                                                <i class="fas fa-check text-success me-1"></i>
                                                {{ $permission['permission']->display_name }}
                                            </li>
                                        @endforeach
                                        @if($permissions->count() > 3)
                                            <li class="text-muted">
                                                <i class="fas fa-ellipsis-h me-1"></i>
                                                +{{ $permissions->count() - 3 }} more
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No permissions assigned.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions based on Permissions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($rbac->userHasPermission(auth()->user(), 'attendance.view.own'))
                            <div class="col-md-3 col-sm-6">
                                <a href="#" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-fingerprint d-block fs-2 mb-2"></i>
                                    <div>Check In/Out</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="#" class="btn btn-outline-info w-100 py-3">
                                    <i class="fas fa-clock d-block fs-2 mb-2"></i>
                                    <div>My Attendance</div>
                                </a>
                            </div>
                        @endif
                        
                        @if($rbac->userHasPermission(auth()->user(), 'schedule.view.own'))
                            <div class="col-md-3 col-sm-6">
                                <a href="#" class="btn btn-outline-success w-100 py-3">
                                    <i class="fas fa-calendar d-block fs-2 mb-2"></i>
                                    <div>My Schedule</div>
                                </a>
                            </div>
                        @endif
                        
                        @if($rbac->userHasPermission(auth()->user(), 'leave.create.own'))
                            <div class="col-md-3 col-sm-6">
                                <a href="#" class="btn btn-outline-warning w-100 py-3">
                                    <i class="fas fa-calendar-plus d-block fs-2 mb-2"></i>
                                    <div>Request Leave</div>
                                </a>
                            </div>
                        @endif
                        
                        @if($rbac->userHasPermission(auth()->user(), 'branch.view.all'))
                            <div class="col-md-3 col-sm-6">
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-danger w-100 py-3">
                                    <i class="fas fa-users-cog d-block fs-2 mb-2"></i>
                                    <div>Manage Roles</div>
                                </a>
                            </div>
                        @endif
                        
                        @if($rbac->userHasPermission(auth()->user(), 'branch.view.assigned'))
                            <div class="col-md-3 col-sm-6">
                                <a href="#" class="btn btn-outline-secondary w-100 py-3">
                                    <i class="fas fa-store d-block fs-2 mb-2"></i>
                                    <div>My Branches</div>
                                </a>
                            </div>
                        @endif
                        
                        @if($rbac->userHasPermission(auth()->user(), 'employee.view.branch'))
                            <div class="col-md-3 col-sm-6">
                                <a href="#" class="btn btn-outline-dark w-100 py-3">
                                    <i class="fas fa-users d-block fs-2 mb-2"></i>
                                    <div>Staff Management</div>
                                </a>
                            </div>
                        @endif
                        
                        @if($rbac->userHasPermission(auth()->user(), 'report.view.own') || $rbac->userHasPermission(auth()->user(), 'report.view.branch'))
                            <div class="col-md-3 col-sm-6">
                                <a href="#" class="btn btn-outline-success w-100 py-3">
                                    <i class="fas fa-chart-bar d-block fs-2 mb-2"></i>
                                    <div>Reports</div>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RBAC System Demo -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-magic me-2"></i>Flexible RBAC System Demo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>🎯 What makes this system special:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>100% Dynamic:</strong> Add new roles without coding
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Granular Permissions:</strong> 49 different permissions available
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Scope Restrictions:</strong> Limit by branch, time, or employee
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Hierarchical Approval:</strong> Based on role levels
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Real-time Updates:</strong> Changes take effect immediately
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>🚀 Coffee Shop Specific Features:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-store text-primary me-2"></i>
                                    Multi-outlet management
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-clock text-info me-2"></i>
                                    Flexible shift patterns
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-map-marker-alt text-warning me-2"></i>
                                    GPS geofencing
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-camera text-danger me-2"></i>
                                    Selfie verification
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-chart-pie text-success me-2"></i>
                                    Peak hour coverage tracking
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-lightbulb me-2"></i>Try Creating a New Role!
                        </h6>
                        <p class="mb-2">
                            If you have admin access, you can easily create custom roles like:
                        </p>
                        <div class="row">
                            <div class="col-md-4">
                                <span class="badge bg-primary">Drive-Thru Specialist</span>
                            </div>
                            <div class="col-md-4">
                                <span class="badge bg-success">Night Shift Manager</span>
                            </div>
                            <div class="col-md-4">
                                <span class="badge bg-warning">Regional Trainer</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                Just go to Role Management → Create Custom Role and define permissions!
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.role-color {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.quick-action-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.quick-action-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary-color);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.permission-group {
    background: rgba(139, 69, 19, 0.05);
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Add some interactive elements
    $('.btn-outline-primary, .btn-outline-info, .btn-outline-success, .btn-outline-warning, .btn-outline-danger, .btn-outline-secondary, .btn-outline-dark').hover(
        function() {
            $(this).addClass('shadow-lg');
        },
        function() {
            $(this).removeClass('shadow-lg');
        }
    );
    
    // Show role information tooltip
    $('[title]').tooltip();
    
    // Simulate real-time updates
    setTimeout(function() {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-sync me-2"></i>
                <strong>System Update:</strong> Your permissions have been refreshed automatically.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.main-content').prepend(alertHtml);
        
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 4000);
    }, 3000);
});
</script>
@endpush
