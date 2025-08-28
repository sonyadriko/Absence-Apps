@extends('layouts.app')

@section('title', 'Employee Details - ' . $employee->full_name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-user me-2"></i>{{ $employee->full_name }}
            </h1>
            <p class="text-muted mb-0">Employee ID: {{ $employee->employee_number }}</p>
        </div>
        
        <div class="btn-group">
            <a href="{{ route('hr-central.employees.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
            <button class="btn btn-warning" onclick="editEmployee({{ $employee->id }})">
                <i class="fas fa-edit me-2"></i>Edit Employee
            </button>
            <button class="btn btn-{{ $employee->status === 'active' ? 'secondary' : 'success' }}" 
                    onclick="toggleEmployeeStatus({{ $employee->id }})">
                <i class="fas fa-{{ $employee->status === 'active' ? 'pause' : 'play' }} me-2"></i>
                {{ $employee->status === 'active' ? 'Deactivate' : 'Activate' }}
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
                                <label class="form-label fw-bold">Full Name</label>
                                <p class="form-control-plaintext">{{ $employee->full_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Employee Number</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-primary">{{ $employee->employee_number }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <p class="form-control-plaintext">
                                    @if($employee->email)
                                        <i class="fas fa-envelope me-2"></i>{{ $employee->email }}
                                    @else
                                        No email provided
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone</label>
                                <p class="form-control-plaintext">
                                    @if($employee->phone)
                                        <i class="fas fa-phone me-2"></i>{{ $employee->phone }}
                                    @else
                                        No phone number
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    @if($employee->address)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <p class="form-control-plaintext">{{ $employee->address }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Employment Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>Employment Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Position</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-info">{{ $employee->position->name ?? 'N/A' }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Primary Branch</label>
                                <p class="form-control-plaintext">
                                    @if($employee->primaryBranch)
                                        <a href="{{ route('hr-central.branches.show', $employee->primaryBranch) }}" 
                                           class="text-decoration-none">
                                            <i class="fas fa-store me-2"></i>{{ $employee->primaryBranch->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Employment Type</label>
                                <p class="form-control-plaintext">
                                    @php
                                        $employmentTypeClass = match($employee->employment_type) {
                                            'full_time' => 'primary',
                                            'part_time' => 'info',
                                            'contract' => 'warning',
                                            'intern' => 'secondary',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $employmentTypeClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $employee->employment_type)) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Hire Date</label>
                                <p class="form-control-plaintext">
                                    <i class="fas fa-calendar me-2"></i>
                                    {{ $employee->hire_date ? $employee->hire_date->format('d M Y') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Department</label>
                                <p class="form-control-plaintext">{{ $employee->department ?: 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Hourly Rate</label>
                                <p class="form-control-plaintext">
                                    @if($employee->hourly_rate)
                                        <strong class="text-success">${{ number_format($employee->hourly_rate, 2) }}/hour</strong>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            @if($employee->emergency_contact_name || $employee->emergency_contact_phone)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-phone-alt me-2"></i>Emergency Contact
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($employee->emergency_contact_name)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Contact Name</label>
                                <p class="form-control-plaintext">{{ $employee->emergency_contact_name }}</p>
                            </div>
                        </div>
                        @endif
                        @if($employee->emergency_contact_phone)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Contact Phone</label>
                                <p class="form-control-plaintext">
                                    <i class="fas fa-phone me-2"></i>{{ $employee->emergency_contact_phone }}
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Status & Stats -->
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
                        @php
                            $statusClass = match($employee->status) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'terminated' => 'danger',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }} fs-6 px-3 py-2">
                            <i class="fas fa-{{ $employee->status === 'active' ? 'check-circle' : ($employee->status === 'inactive' ? 'pause-circle' : 'times-circle') }} me-2"></i>
                            {{ ucfirst($employee->status) }}
                        </span>
                    </div>
                    <p class="text-muted">
                        {{ $employee->status === 'active' ? 'This employee is currently active' : 
                           ($employee->status === 'inactive' ? 'This employee is currently inactive' : 'This employee has been terminated') }}
                    </p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Quick Stats
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tenure:</span>
                        <strong>
                            @if($employee->hire_date)
                                {{ $employee->hire_date->diffForHumans(null, true) }}
                            @else
                                N/A
                            @endif
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Last Updated:</span>
                        <small class="text-muted">{{ $employee->updated_at->format('d M Y') }}</small>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-info btn-sm">
                            <i class="fas fa-clock me-2"></i>View Attendance History
                        </button>
                        <button class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-calendar me-2"></i>View Schedule
                        </button>
                        <button class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-calendar-alt me-2"></i>View Leave History
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Employee
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editEmployeeForm">
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
                        <i class="fas fa-save me-2"></i>Update Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Edit employee form
$('#editEmployeeForm').on('submit', function(e) {
    e.preventDefault();
    const employeeId = $(this).data('employee-id');
    submitForm(this, `/api/hr-central/employees/${employeeId}`, 'PUT');
});

// Edit employee
function editEmployee(id) {
    $.get(`/hr-central/employees/${id}/edit`)
        .done(function(data) {
            $('#editFormContent').html(data);
            $('#editEmployeeForm').data('employee-id', id);
            $('#editEmployeeModal').modal('show');
        })
        .fail(function() {
            showAlert('error', 'Error loading employee data');
        });
}

// Toggle employee status
function toggleEmployeeStatus(id) {
    $.ajax({
        url: `/api/hr-central/employees/${id}/toggle-status`,
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
        showAlert('error', 'Error updating employee status');
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
    
    $('.container-fluid').prepend(alert);
    
    setTimeout(() => {
        alert.fadeOut();
    }, 5000);
}
</script>
@endpush
