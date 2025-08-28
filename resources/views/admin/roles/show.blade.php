@extends('layouts.app')

@section('title', 'Role Details - ' . $role->display_name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary me-3" title="Back to Roles">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="h3 mb-0 d-flex align-items-center">
                    <div class="role-color me-3" style="width: 16px; height: 16px; border-radius: 50%; background-color: {{ $role->color }}"></div>
                    {{ $role->display_name }}
                    @if($role->is_system_role)
                        <span class="badge bg-success ms-2">System</span>
                    @else
                        <span class="badge bg-warning ms-2">Custom</span>
                    @endif
                </h1>
                <p class="text-muted mb-0">{{ $role->description ?? 'No description available' }}</p>
            </div>
        </div>
        
        <div class="btn-group">
            @if(!$role->is_system_role)
                <button class="btn btn-warning" onclick="editRole({{ $role->id }})">
                    <i class="fas fa-edit me-2"></i>Edit Role
                </button>
                <button class="btn btn-danger" onclick="deleteRole({{ $role->id }}, '{{ $role->display_name }}')">
                    <i class="fas fa-trash me-2"></i>Delete Role
                </button>
            @else
                <button class="btn btn-secondary" disabled>
                    <i class="fas fa-lock me-2"></i>System Role
                </button>
            @endif
        </div>
    </div>

    <!-- Role Information Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold">{{ $role->hierarchy_level }}</div>
                        <div class="text-muted small">Hierarchy Level</div>
                    </div>
                    <div class="fs-2 text-secondary">
                        <i class="fas fa-layer-group"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info">{{ $role->permissions->count() }}</div>
                        <div class="text-muted small">Permissions</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-key"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-primary">{{ $role->userRoles->count() }}</div>
                        <div class="text-muted small">Assigned Users</div>
                    </div>
                    <div class="fs-2 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold {{ $role->is_active ? 'text-success' : 'text-danger' }}">
                            {{ $role->is_active ? 'Active' : 'Inactive' }}
                        </div>
                        <div class="text-muted small">Status</div>
                    </div>
                    <div class="fs-2 {{ $role->is_active ? 'text-success' : 'text-danger' }}">
                        <i class="fas {{ $role->is_active ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Permissions -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-key me-2"></i>Role Permissions
                        <span class="badge bg-info ms-2">{{ $role->permissions->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($role->permissions->count() > 0)
                        @foreach($allPermissions as $group => $permissions)
                            @php
                                $rolePermissions = $role->permissions->where('group', $group);
                            @endphp
                            @if($rolePermissions->count() > 0)
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-folder me-2"></i>{{ ucfirst($group) }}
                                        <span class="badge bg-light text-dark ms-2">{{ $rolePermissions->count() }}/{{ $permissions->count() }}</span>
                                    </h6>
                                    <div class="row">
                                        @foreach($rolePermissions as $permission)
                                            <div class="col-md-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    <div>
                                                        <div class="fw-medium">{{ $permission->display_name }}</div>
                                                        <small class="text-muted">{{ $permission->name }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-key fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No permissions assigned</h5>
                            <p class="text-muted">This role has no permissions assigned to it.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Assigned Users -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Assigned Users
                        <span class="badge bg-primary ms-2">{{ $role->userRoles->count() }}</span>
                    </h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @if($role->userRoles->count() > 0)
                        @foreach($role->userRoles as $userRole)
                            @php
                                $user = $userRole->user;
                                $employee = $user->employee;
                            @endphp
                            <div class="d-flex align-items-center mb-3 p-2 rounded" style="background-color: #f8f9fa;">
                                <div class="avatar-sm me-3">
                                    @if($user->profile_photo)
                                        <img src="{{ asset('storage/' . $user->profile_photo) }}" class="rounded-circle" width="40" height="40" alt="{{ $user->name }}">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px; background-color: {{ $role->color }}; color: white; font-weight: bold;">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-medium">{{ $user->name }}</div>
                                    <small class="text-muted">{{ $user->email }}</small>
                                    @if($employee)
                                        <br><small class="text-info">{{ $employee->employee_number }}</small>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">
                                        @if($userRole->effective_from)
                                            Since {{ \Carbon\Carbon::parse($userRole->effective_from)->format('M Y') }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No users assigned</h6>
                            <p class="text-muted small">This role is not currently assigned to any users.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Role Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Role Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted small">Role Name</label>
                            <div class="fw-medium">{{ $role->name }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small">Display Name</label>
                            <div class="fw-medium">{{ $role->display_name }}</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">Hierarchy Level</label>
                            <div class="fw-medium">{{ $role->hierarchy_level }}</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">Color</label>
                            <div class="d-flex align-items-center">
                                <div class="me-2" style="width: 20px; height: 20px; border-radius: 4px; background-color: {{ $role->color }}; border: 1px solid #dee2e6;"></div>
                                <code>{{ $role->color }}</code>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">Type</label>
                            <div>
                                @if($role->is_system_role)
                                    <span class="badge bg-success">System</span>
                                @else
                                    <span class="badge bg-warning">Custom</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">Status</label>
                            <div>
                                @if($role->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small">Created</label>
                            <div class="fw-medium">{{ $role->created_at->format('M d, Y \at H:i') }}</div>
                        </div>
                        @if($role->updated_at != $role->created_at)
                            <div class="col-12">
                                <label class="form-label text-muted small">Last Updated</label>
                                <div class="fw-medium">{{ $role->updated_at->format('M d, Y \at H:i') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Edit role function
function editRole(roleId) {
    showAlert('info', 'Edit functionality will be implemented in the next version. For now, you can delete and recreate the role.');
}

// Delete role function
function deleteRole(roleId, roleName) {
    if (confirm(`Are you sure you want to delete the role "${roleName}"?`)) {
        $.ajax({
            url: `/api/admin/roles/${roleId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function(response) {
            showAlert('success', response.message);
            setTimeout(() => window.location.href = '{{ route("admin.roles.index") }}', 1500);
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON?.message || 'Failed to delete role';
            showAlert('error', error);
        });
    }
}

// Show alert function
function showAlert(type, message) {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'info': 'alert-info',
        'warning': 'alert-warning'
    }[type] || 'alert-info';
    
    const icon = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle',
        'warning': 'fas fa-exclamation-triangle'
    }[type] || 'fas fa-info-circle';
    
    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('.main-content').prepend(alert);
    
    setTimeout(() => {
        alert.fadeOut();
    }, 5000);
}
</script>
@endpush
