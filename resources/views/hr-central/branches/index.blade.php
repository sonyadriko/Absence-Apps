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
                        <div class="h4 mb-0 fw-bold" id="totalBranches">{{ $branches->count() }}</div>
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
    // Initialize DataTables with Bootstrap 5
    $('#branchesDataTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [-1] } // Actions column
        ]
    });
});

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
    
    Utils.setButtonLoading($submitBtn[0], true);
    
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
            Utils.showToast(response.message, 'success');
            $form.closest('.modal').modal('hide');
            location.reload();
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
            Utils.showToast('Please check the form for errors', 'error');
        } else {
            Utils.showToast('An error occurred. Please try again.', 'error');
        }
    })
    .always(function() {
        Utils.setButtonLoading($submitBtn[0], false);
    });
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
            Utils.showToast('Error loading branch data', 'error');
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
            Utils.showToast(response.message, 'success');
            location.reload();
        }
    })
    .fail(function() {
        Utils.showToast('Error updating branch status', 'error');
    });
}

// Export branches
function exportBranches() {
    window.open('/api/hr-central/branches/export', '_blank');
}

// Refresh branches
function refreshBranches() {
    location.reload();
}
</script>
@endpush
