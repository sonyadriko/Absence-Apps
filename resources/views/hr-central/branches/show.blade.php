@extends('layouts.app')

@section('title', 'Branch Details - ' . $branch->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-store me-2"></i>{{ $branch->name }}
            </h1>
            <p class="text-muted mb-0">Branch Code: {{ $branch->code }}</p>
        </div>
        
        <div class="btn-group">
            <a href="{{ route('hr-central.branches.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
            <button class="btn btn-warning" onclick="editBranch({{ $branch->id }})">
                <i class="fas fa-edit me-2"></i>Edit Branch
            </button>
            <button class="btn btn-{{ $branch->is_active ? 'secondary' : 'success' }}" 
                    onclick="toggleBranchStatus({{ $branch->id }})">
                <i class="fas fa-{{ $branch->is_active ? 'pause' : 'play' }} me-2"></i>
                {{ $branch->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Basic Information -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Branch Name</label>
                                <p class="form-control-plaintext">{{ $branch->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Branch Code</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-primary">{{ $branch->code }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <p class="form-control-plaintext">{{ $branch->address ?: 'No address provided' }}</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone</label>
                                <p class="form-control-plaintext">
                                    @if($branch->phone)
                                        <i class="fas fa-phone me-2"></i>{{ $branch->phone }}
                                    @else
                                        No phone number
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Timezone</label>
                                <p class="form-control-plaintext">
                                    <i class="fas fa-globe me-2"></i>{{ $branch->timezone }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Location & Geofence
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Latitude</label>
                                <p class="form-control-plaintext">{{ $branch->latitude }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Longitude</label>
                                <p class="form-control-plaintext">{{ $branch->longitude }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Geofence Radius</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-info">{{ $branch->radius }} meters</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-map me-2"></i>
                        <strong>GPS Coordinates:</strong> {{ $branch->latitude }}, {{ $branch->longitude }}
                        <br>
                        <small>Employees must be within {{ $branch->radius }}m radius to check in/out</small>
                    </div>
                </div>
            </div>

            <!-- Operating Hours -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Operating Hours
                    </h5>
                </div>
                <div class="card-body">
                    @if($branch->operating_hours)
                        @php
                            $days = [
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday', 
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday'
                            ];
                        @endphp
                        
                        <div class="row">
                            @foreach($days as $key => $day)
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>{{ $day }}</strong>
                                        <span>
                                            @if(isset($branch->operating_hours[$key]['is_closed']) && $branch->operating_hours[$key]['is_closed'])
                                                <span class="badge bg-danger">Closed</span>
                                            @else
                                                <span class="text-success">
                                                    {{ $branch->operating_hours[$key]['open'] ?? 'N/A' }} - 
                                                    {{ $branch->operating_hours[$key]['close'] ?? 'N/A' }}
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No operating hours configured</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stats & Status -->
        <div class="col-lg-4">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info me-2"></i>Status
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        @if($branch->is_active)
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="fas fa-check-circle me-2"></i>Active
                            </span>
                        @else
                            <span class="badge bg-danger fs-6 px-3 py-2">
                                <i class="fas fa-times-circle me-2"></i>Inactive
                            </span>
                        @endif
                    </div>
                    <p class="text-muted">
                        {{ $branch->is_active ? 'This branch is currently active and operational' : 'This branch is currently inactive' }}
                    </p>
                </div>
            </div>

            <!-- Employee Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Employee Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="h2 mb-2 fw-bold text-primary">{{ $branch->employees_count ?? 0 }}</div>
                        <p class="text-muted">Total Employees</p>
                    </div>
                    
                    @if($branch->employees_count > 0)
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-sm" onclick="viewBranchEmployees({{ $branch->id }})">
                                <i class="fas fa-users me-2"></i>View Employees
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Additional Settings -->
            @if($branch->settings)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Additional Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($branch->settings as $key => $value)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                <span>{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Created Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar me-2"></i>Created Information
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Created:</strong><br>
                        <small class="text-muted">{{ $branch->created_at->format('d M Y, H:i') }}</small>
                    </p>
                    <p class="mb-0">
                        <strong>Last Updated:</strong><br>
                        <small class="text-muted">{{ $branch->updated_at->format('d M Y, H:i') }}</small>
                    </p>
                </div>
            </div>
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

<!-- View Branch Employees Modal -->
<div class="modal fade" id="branchEmployeesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>Branch Employees
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="employeesList">
                    <!-- Employee list will be loaded here -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading employees...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Edit branch form
$('#editBranchForm').on('submit', function(e) {
    e.preventDefault();
    const branchId = $(this).data('branch-id');
    submitForm(this, `/api/hr-central/branches/${branchId}`, 'PUT');
});

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
            // Reload page to show updated status
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .fail(function() {
        showAlert('error', 'Error updating branch status');
    });
}

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
            // Reload page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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

// View branch employees
function viewBranchEmployees(branchId) {
    // Show modal
    $('#branchEmployeesModal').modal('show');
    
    // Reset modal content
    $('#employeesList').html(`
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading employees...</p>
        </div>
    `);
    
    // Load employees data
    $.ajax({
        url: `/api/hr-central/branches/${branchId}/employees`,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success && response.employees.length > 0) {
            let employeesHtml = '<div class="table-responsive"><table class="table table-hover">';
            employeesHtml += '<thead><tr>';
            employeesHtml += '<th>Name</th><th>Employee ID</th><th>Position</th><th>Type</th><th>Status</th><th>Joined Date</th>';
            employeesHtml += '</tr></thead><tbody>';
            
            response.employees.forEach(function(employee) {
                const statusClass = employee.is_active ? 'success' : 'danger';
                const statusText = employee.is_active ? 'Active' : 'Inactive';
                const joinedDate = employee.joined_date ? new Date(employee.joined_date).toLocaleDateString('id-ID') : 'N/A';
                
                employeesHtml += '<tr>';
                employeesHtml += `<td><strong>${employee.full_name || employee.name || 'N/A'}</strong></td>`;
                employeesHtml += `<td><span class="badge bg-primary">${employee.employee_id || 'N/A'}</span></td>`;
                employeesHtml += `<td>${employee.position || 'N/A'}</td>`;
                employeesHtml += `<td><span class="badge bg-info">${employee.employment_type || 'N/A'}</span></td>`;
                employeesHtml += `<td><span class="badge bg-${statusClass}">${statusText}</span></td>`;
                employeesHtml += `<td>${joinedDate}</td>`;
                employeesHtml += '</tr>';
            });
            
            employeesHtml += '</tbody></table></div>';
            $('#employeesList').html(employeesHtml);
        } else {
            $('#employeesList').html(`
                <div class="text-center text-muted">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <p>No employees found for this branch</p>
                </div>
            `);
        }
    })
    .fail(function() {
        $('#employeesList').html(`
            <div class="text-center text-danger">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <p>Error loading employees data</p>
            </div>
        `);
    });
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
