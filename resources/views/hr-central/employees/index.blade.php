@extends('layouts.app')

@section('title', 'All Employees')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2"></i>All Employees
            </h1>
            <p class="text-muted mb-0">Manage employee information and details</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createEmployeeModal">
                <i class="fas fa-plus me-2"></i>Add Employee
            </button>
            <button class="btn btn-outline-secondary" onclick="exportEmployees()">
                <i class="fas fa-download me-2"></i>Export
            </button>
        </div>
    </div>


    <!-- Employees Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users fa-2x me-3"></i>
                        <div>
                            <div class="h4 mb-0" id="totalEmployees">{{ $employees->count() }}</div>
                            <small>Total Employees</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-check fa-2x me-3"></i>
                        <div>
                            <div class="h4 mb-0" id="activeEmployees">{{ $employees->where('status', 'active')->count() }}</div>
                            <small>Active Employees</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-pause fa-2x me-3"></i>
                        <div>
                            <div class="h4 mb-0" id="inactiveEmployees">{{ $employees->where('status', 'inactive')->count() }}</div>
                            <small>Inactive Employees</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-times fa-2x me-3"></i>
                        <div>
                            <div class="h4 mb-0" id="terminatedEmployees">{{ $employees->where('status', 'terminated')->count() }}</div>
                            <small>Terminated</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="card">
        <div class="card-body">
            <div id="employeesTableContainer">
                @include('hr-central.employees.partials.table', ['employees' => $employees])
            </div>
        </div>
    </div>
</div>

<!-- Create Employee Modal -->
<div class="modal fade" id="createEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Employee
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createEmployeeForm">
                @csrf
                <div class="modal-body">
                    @include('hr-central.employees.partials.form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Create Employee
                    </button>
                </div>
            </form>
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
// Initialize page
$(document).ready(function() {
    // Initialize DataTables with Bootstrap 5
    $('#employeesTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']]
    });
});

// Create employee form
$('#createEmployeeForm').on('submit', function(e) {
    e.preventDefault();
    submitForm(this, '/api/hr-central/employees', 'POST');
});

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
            Utils.showToast('Error loading employee data', 'error');
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
            Utils.showToast(response.message, 'success');
            location.reload();
        }
    })
    .fail(function() {
        Utils.showToast('Error updating employee status', 'error');
    });
}

// Delete employee
function deleteEmployee(id) {
    if (confirm('Are you sure you want to delete this employee?')) {
        $.ajax({
            url: `/api/hr-central/employees/${id}`,
            method: 'DELETE',
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
            Utils.showToast('Error deleting employee', 'error');
        });
    }
}

// Export employees
function exportEmployees() {
    window.location.href = '/api/hr-central/employees/export';
}

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
</script>
@endpush
