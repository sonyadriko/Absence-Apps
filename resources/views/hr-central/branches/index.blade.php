@extends('layouts.app')

@section('title', 'Branch Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-store me-2"></i>Branch Management
            </h1>
            <p class="text-muted mb-0">Manage all coffee shop branches</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBranchModal">
                <i class="fas fa-plus me-2"></i>Add New Branch
            </button>
            <button class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="exportBranches()">
                    <i class="fas fa-download me-2"></i>Export Branches
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="refreshBranches()">
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
                        <div class="h4 mb-0 fw-bold" id="totalBranches">{{ $branches->total() }}</div>
                        <div class="text-muted small">Total Branches</div>
                    </div>
                    <div class="fs-2 text-primary">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-success" id="activeBranches">{{ $branches->where('is_active', true)->count() }}</div>
                        <div class="text-muted small">Active Branches</div>
                    </div>
                    <div class="fs-2 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-warning" id="inactiveBranches">{{ $branches->where('is_active', false)->count() }}</div>
                        <div class="text-muted small">Inactive Branches</div>
                    </div>
                    <div class="fs-2 text-warning">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info" id="totalEmployees">0</div>
                        <div class="text-muted small">Total Employees</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="searchForm" class="row g-3" method="GET" action="{{ route('hr-central.branches.index') }}">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search branches..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="clearSearch()">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Branches Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>All Branches
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive" id="branchesTable">
                @include('hr-central.branches.partials.table', ['branches' => $branches])
            </div>
        </div>
    </div>
</div>

<!-- Create Branch Modal -->
<div class="modal fade" id="createBranchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Add New Branch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createBranchForm">
                @csrf
                <div class="modal-body">
                    @include('hr-central.branches.partials.form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create Branch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Branch Modal -->
<div class="modal fade" id="editBranchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Branch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBranchForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div id="editFormContent">
                        <!-- Form content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Branch
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
    // Don't call loadBranches() on page load - use existing data from server
    // loadBranches(); // This will be called only when searching/filtering
    
    // Initialize DataTable if not already initialized
    if (!$.fn.DataTable.isDataTable('#branchesDataTable')) {
        $('#branchesDataTable').DataTable({
            ordering: true,
            searching: false, // We'll use our custom search
            paging: false, // We'll use Laravel pagination
            info: false,
            columnDefs: [
                { orderable: false, targets: [-1] } // Actions column
            ]
        });
    }
});

// Load branches with search/filter
function loadBranches() {
    const formData = $('#searchForm').serialize();
    
    $.get('/api/hr-central/branches', formData)
        .done(function(data) {
            updateBranchesTable(data.branches);
            updateStats(data);
        })
        .fail(function() {
            showAlert('error', 'Error loading branches');
        });
}

// Update branches table
function updateBranchesTable(branches) {
    const tbody = $('#branchesDataTable tbody');
    tbody.empty();
    
    if (branches.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-store fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted">No branches found</h5>
                    <p class="text-muted">No branches match your search criteria.</p>
                </td>
            </tr>
        `);
        return;
    }
    
    branches.forEach(branch => {
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="branch-icon me-2">
                            <i class="fas fa-store text-primary"></i>
                        </div>
                        <div>
                            <div class="fw-bold">${branch.name}</div>
                            <div class="small text-muted">${branch.code}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="small">
                        ${branch.address ? branch.address.substring(0, 50) + (branch.address.length > 50 ? '...' : '') : 'No address'}
                        ${branch.phone ? '<br><i class="fas fa-phone me-1"></i>' + branch.phone : ''}
                    </div>
                </td>
                <td>
                    <div class="small">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        ${branch.latitude}, ${branch.longitude}
                        <br>
                        <i class="fas fa-circle me-1"></i>${branch.radius}m radius
                    </div>
                </td>
                <td>
                    ${branch.is_active 
                        ? '<span class="badge bg-success">Active</span>' 
                        : '<span class="badge bg-danger">Inactive</span>'}
                </td>
                <td>
                    <span class="badge bg-info">${branch.employees_count || 0} employees</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info" onclick="viewBranch(${branch.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="editBranch(${branch.id})" title="Edit Branch">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-${branch.is_active ? 'secondary' : 'success'}" 
                                onclick="toggleBranchStatus(${branch.id})" 
                                title="${branch.is_active ? 'Deactivate' : 'Activate'} Branch">
                            <i class="fas fa-${branch.is_active ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteBranch(${branch.id}, '${branch.name}')" title="Delete Branch">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Update statistics
function updateStats(data) {
    if (data && data.branches) {
        const totalBranches = data.branches.length;
        const activeBranches = data.branches.filter(branch => branch.is_active).length;
        const inactiveBranches = totalBranches - activeBranches;
        const totalEmployees = data.branches.reduce((sum, branch) => sum + (branch.employees_count || 0), 0);
        
        $('#totalBranches').text(totalBranches);
        $('#activeBranches').text(activeBranches);
        $('#inactiveBranches').text(inactiveBranches);
        $('#totalEmployees').text(totalEmployees);
    }
}

// Search form will use regular form submission, not AJAX
// $('#searchForm').on('submit', function(e) {
//     e.preventDefault();
//     loadBranches();
// });

// Create branch form
$('#createBranchForm').on('submit', function(e) {
    e.preventDefault();
    submitForm(this, '/api/hr-central/branches', 'POST');
});

// Edit branch form
$('#editBranchForm').on('submit', function(e) {
    e.preventDefault();
    const branchId = $(this).data('branch-id');
    submitForm(this, `/api/hr-central/branches/${branchId}`, 'PUT');
});

// Submit form via AJAX
function submitForm(form, url, method) {
    const $form = $(form);
    const $submitBtn = $form.find('button[type="submit"]');
    
    // Show loading state
    $submitBtn.prop('disabled', true);
    $submitBtn.find('i').removeClass().addClass('fas fa-spinner fa-spin');
    
    // Clear previous errors
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').remove();
    
    const formData = new FormData(form);
    if (method === 'PUT') {
        formData.append('_method', 'PUT');
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            showAlert('success', response.message);
            $form.closest('.modal').modal('hide');
            loadBranches();
            $form[0].reset();
        }
    })
    .fail(function(xhr) {
        if (xhr.status === 422) {
            const errors = xhr.responseJSON.errors;
            Object.keys(errors).forEach(function(field) {
                const $field = $form.find(`[name="${field}"]`);
                $field.addClass('is-invalid');
                $field.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
            });
            showAlert('error', 'Please check the form for errors');
        } else {
            showAlert('error', 'An error occurred. Please try again.');
        }
    })
    .always(function() {
        $submitBtn.prop('disabled', false);
        $submitBtn.find('i').removeClass().addClass('fas fa-save');
    });
}

// Clear search
function clearSearch() {
    $('#searchForm')[0].reset();
    // Reload the page to show all branches
    window.location.href = '{{ route('hr-central.branches.index') }}';
}

// Edit branch
function editBranch(id) {
    $.get(`/hr-central/branches/${id}/edit`)
        .done(function(data) {
            $('#editFormContent').html(data);
            $('#editBranchForm').data('branch-id', id);
            $('#editBranchModal').modal('show');
        })
        .fail(function() {
            showAlert('error', 'Error loading branch data');
        });
}

// Toggle branch status
function toggleBranchStatus(id) {
    $.ajax({
        url: `/api/hr-central/branches/${id}/toggle-status`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            showAlert('success', response.message);
            loadBranches();
        }
    })
    .fail(function() {
        showAlert('error', 'Error updating branch status');
    });
}

// Export branches
function exportBranches() {
    window.open('/api/hr-central/branches/export', '_blank');
}

// Refresh branches
function refreshBranches() {
    // Reload the page to refresh all data
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
</script>
@endpush
