@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-users-cog me-2"></i>Role Management
            </h1>
            <p class="text-muted mb-0">Create and manage dynamic roles for your coffee shop system</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                <i class="fas fa-plus me-2"></i>Create Custom Role
            </button>
            <button class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#templateModal">
                    <i class="fas fa-magic me-2"></i>Use Template
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="exportRoles()">
                    <i class="fas fa-download me-2"></i>Export Roles
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="refreshRoles()">
                    <i class="fas fa-sync me-2"></i>Refresh
                </a></li>
            </ul>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold" id="totalRoles">{{ $stats['total_roles'] }}</div>
                        <div class="text-muted small">Total Roles</div>
                    </div>
                    <div class="fs-2 text-primary">
                        <i class="fas fa-users-cog"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-success" id="systemRoles">{{ $stats['system_roles'] }}</div>
                        <div class="text-muted small">System Roles</div>
                    </div>
                    <div class="fs-2 text-success">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info" id="customRoles">{{ $stats['custom_roles'] }}</div>
                        <div class="text-muted small">Custom Roles</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-warning" id="totalPermissions">{{ $stats['total_permissions'] }}</div>
                        <div class="text-muted small">Permissions</div>
                    </div>
                    <div class="fs-2 text-warning">
                        <i class="fas fa-key"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>All Roles
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="rolesTable">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Hierarchy Level</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTableBody">
                        @foreach($roles as $role)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="role-color me-2" style="width: 12px; height: 12px; border-radius: 50%; background-color: {{ $role->color }}"></div>
                                    <div>
                                        <div class="fw-bold">{{ $role->display_name }}</div>
                                        <div class="small text-muted">{{ $role->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">Level {{ $role->hierarchy_level }}</span>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $role->permissions_count ?? 0 }} permissions</span>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $role->users_count ?? 0 }} users</span>
                            </td>
                            <td>
                                @if($role->is_system_role)
                                    <span class="badge bg-success">System</span>
                                @else
                                    <span class="badge bg-warning">Custom</span>
                                @endif
                            </td>
                            <td>
                                @if($role->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewRole({{ $role->id }})" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if(!$role->is_system_role)
                                        <button class="btn btn-outline-warning" onclick="editRole({{ $role->id }})" title="Edit Role">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteRole({{ $role->id }}, '{{ $role->display_name }}')" title="Delete Role">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-outline-secondary" disabled title="System roles cannot be modified">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Create Custom Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createRoleForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="roleName" class="form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="roleName" name="name" placeholder="e.g., area_manager" required>
                                <div class="form-text">Use lowercase with underscores (e.g., area_manager)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="displayName" class="form-label">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="displayName" name="display_name" placeholder="e.g., Area Manager" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hierarchyLevel" class="form-label">Hierarchy Level <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="hierarchyLevel" name="hierarchy_level" min="1" max="99" placeholder="1-99" required>
                                <div class="form-text">Higher number = more authority (System roles use 100+)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="roleColor" class="form-label">Color <span class="text-danger">*</span></label>
                                <input type="color" class="form-control form-control-color" id="roleColor" name="color" value="#17a2b8" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief description of this role's purpose"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Permissions <span class="text-danger">*</span></label>
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <div id="permissionsCheckboxes">
                                <!-- Permissions will be loaded via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-magic me-2"></i>Create from Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="templateForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="template" class="form-label">Choose Template</label>
                        <select class="form-select" id="template" name="template" required>
                            <option value="">Select a template...</option>
                            <option value="coffee_manager">Coffee Shop Manager</option>
                            <option value="barista_senior">Senior Barista</option>
                            <option value="cashier_lead">Lead Cashier</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="templateName" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="templateName" name="name" placeholder="e.g., jakarta_manager" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-magic me-2"></i>Create from Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load permissions for create modal
    loadPermissions();
    
    // Initialize DataTable
    $('#rolesTable').DataTable({
        ordering: true,
        searching: true,
        paging: true,
        info: true,
        columnDefs: [
            { orderable: false, targets: [6] } // Actions column
        ]
    });
});

// Load all roles
function loadRoles() {
    $.get('/api/admin/roles', function(data) {
        updateStats(data);
        populateRolesTable(data.roles);
    }).fail(function(xhr) {
        console.error('Failed to load roles:', xhr.responseText);
        showAlert('error', 'Failed to load roles');
    });
}

// Update statistics
function updateStats(data) {
    $('#totalRoles').text(data.roles.length);
    $('#systemRoles').text(data.roles.filter(role => role.is_system_role).length);
    $('#customRoles').text(data.roles.filter(role => !role.is_system_role).length);
    $('#totalPermissions').text(data.permissions.length);
}

// Populate roles table
function populateRolesTable(roles) {
    const tbody = $('#rolesTableBody');
    tbody.empty();
    
    roles.forEach(role => {
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="role-color me-2" style="width: 12px; height: 12px; border-radius: 50%; background-color: ${role.color}"></div>
                        <div>
                            <div class="fw-bold">${role.display_name}</div>
                            <div class="small text-muted">${role.name}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-secondary">Level ${role.hierarchy_level}</span>
                </td>
                <td>
                    <span class="badge bg-info">${role.permissions_count || 0} permissions</span>
                </td>
                <td>
                    <span class="badge bg-primary">${role.user_roles_count || 0} users</span>
                </td>
                <td>
                    ${role.is_system_role 
                        ? '<span class="badge bg-success">System</span>' 
                        : '<span class="badge bg-warning">Custom</span>'}
                </td>
                <td>
                    ${role.is_active 
                        ? '<span class="badge bg-success">Active</span>' 
                        : '<span class="badge bg-danger">Inactive</span>'}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewRole(${role.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${!role.is_system_role ? `
                            <button class="btn btn-outline-warning" onclick="editRole(${role.id})" title="Edit Role">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteRole(${role.id}, '${role.display_name}')" title="Delete Role">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : `
                            <button class="btn btn-outline-secondary" disabled title="System roles cannot be modified">
                                <i class="fas fa-lock"></i>
                            </button>
                        `}
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Load permissions for checkboxes
function loadPermissions() {
    $.get('/api/admin/roles', function(data) {
        const container = $('#permissionsCheckboxes');
        container.empty();
        
        // Group permissions by group
        const groups = {};
        data.permissions.forEach(permission => {
            const group = permission.group || 'general';
            if (!groups[group]) {
                groups[group] = [];
            }
            groups[group].push(permission);
        });
        
        // Create checkboxes grouped
        Object.keys(groups).forEach(groupName => {
            const groupDiv = $(`
                <div class="mb-3">
                    <h6 class="text-primary">${groupName.charAt(0).toUpperCase() + groupName.slice(1)}</h6>
                    <div class="group-permissions"></div>
                </div>
            `);
            
            groups[groupName].forEach(permission => {
                groupDiv.find('.group-permissions').append(`
                    <div class="form-check form-check-inline me-3">
                        <input class="form-check-input" type="checkbox" id="perm_${permission.id}" name="permissions[]" value="${permission.name}">
                        <label class="form-check-label small" for="perm_${permission.id}" title="${permission.description}">
                            ${permission.display_name}
                        </label>
                    </div>
                `);
            });
            
            container.append(groupDiv);
        });
    });
}

// Create role form submission
$('#createRoleForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: $('#roleName').val(),
        display_name: $('#displayName').val(),
        description: $('#description').val(),
        color: $('#roleColor').val(),
        hierarchy_level: parseInt($('#hierarchyLevel').val()),
        permissions: []
    };
    
    // Get selected permissions
    $('#permissionsCheckboxes input:checked').each(function() {
        formData.permissions.push($(this).val());
    });
    
    if (formData.permissions.length === 0) {
        showAlert('error', 'Please select at least one permission');
        return;
    }
    
    $.post('/api/admin/roles', formData)
        .done(function(response) {
            $('#createRoleModal').modal('hide');
            showAlert('success', response.message);
            setTimeout(() => window.location.reload(), 1500);
        })
        .fail(function(xhr) {
            const errors = xhr.responseJSON?.errors || {};
            let errorMessage = 'Failed to create role';
            
            if (Object.keys(errors).length > 0) {
                errorMessage = Object.values(errors).flat().join(', ');
            }
            
            showAlert('error', errorMessage);
        });
});

// Create from template form submission
$('#templateForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        template: $('#template').val(),
        name: $('#templateName').val()
    };
    
    $.post('/api/admin/roles/template', formData)
        .done(function(response) {
            $('#templateModal').modal('hide');
            showAlert('success', response.message);
            setTimeout(() => window.location.reload(), 1500);
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON?.message || 'Failed to create role from template';
            showAlert('error', error);
        });
});

// Delete role
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
            setTimeout(() => window.location.reload(), 1500);
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON?.message || 'Failed to delete role';
            showAlert('error', error);
        });
    }
}

// Export roles
function exportRoles() {
    window.open('/api/admin/roles/export', '_blank');
}

// Refresh roles
function refreshRoles() {
    window.location.reload();
}

// Show alert
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

// View role details
function viewRole(roleId) {
    window.location.href = `/admin/roles/${roleId}`;
}

// Edit role
function editRole(roleId) {
    // For now, just show an alert. We can implement edit modal later
    showAlert('info', 'Edit functionality will be implemented in the next version. For now, you can delete and recreate the role.');
}

// Auto-generate display name from role name
$('#roleName').on('input', function() {
    const name = $(this).val();
    const displayName = name.replace(/_/g, ' ')
                           .split(' ')
                           .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                           .join(' ');
    $('#displayName').val(displayName);
});
</script>
@endpush
