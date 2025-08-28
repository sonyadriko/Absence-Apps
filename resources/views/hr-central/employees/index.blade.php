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

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="searchForm" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by name, employee number, email...">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="terminated">Terminated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="branch" class="form-label">Branch</label>
                    <select class="form-select" id="branch" name="branch">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="employment_type" class="form-label">Type</label>
                    <select class="form-select" id="employment_type" name="employment_type">
                        <option value="">All Types</option>
                        <option value="full_time">Full Time</option>
                        <option value="part_time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="intern">Intern</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary d-block" onclick="clearSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </form>
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
                            <div class="h4 mb-0" id="totalEmployees">{{ $employees->total() }}</div>
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
    // Set URL parameters from current request
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('branch')) {
        $('#branch').val(urlParams.get('branch'));
    }
    if (urlParams.get('status')) {
        $('#status').val(urlParams.get('status'));
    }
    if (urlParams.get('employment_type')) {
        $('#employment_type').val(urlParams.get('employment_type'));
    }
    if (urlParams.get('search')) {
        $('#search').val(urlParams.get('search'));
    }
});

// Search form submission
$('#searchForm').on('submit', function(e) {
    e.preventDefault();
    loadEmployees();
});

// Filter change events
$('#status, #branch, #employment_type').on('change', function() {
    loadEmployees();
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

// Load employees with AJAX
function loadEmployees() {
    const formData = $('#searchForm').serialize();
    
    $.get('/api/hr-central/employees?' + formData)
        .done(function(response) {
            updateEmployeesTable(response.employees);
            updatePagination(response.pagination);
            updateStatistics(response.statistics || response.employees);
        })
        .fail(function() {
            showAlert('error', 'Error loading employees');
        });
}

// Update employees table
function updateEmployeesTable(employees) {
    let tableHtml = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Hire Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    if (employees.length === 0) {
        tableHtml += `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <p>No employees found</p>
                </td>
            </tr>
        `;
    } else {
        employees.forEach(function(employee) {
            const statusClass = {
                'active': 'success',
                'inactive': 'warning',
                'terminated': 'danger'
            }[employee.status] || 'secondary';
            
            const employmentTypeClass = {
                'full_time': 'primary',
                'part_time': 'info',
                'contract': 'warning',
                'intern': 'secondary'
            }[employee.employment_type] || 'secondary';
            
            tableHtml += `
                <tr>
                    <td>
                        <div>
                            <strong>${employee.full_name}</strong>
                            <br>
                            <small class="text-muted">${employee.employee_number}</small>
                            <br>
                            <small class="text-muted">${employee.email || 'No email'}</small>
                        </div>
                    </td>
                    <td>${employee.position?.name || 'N/A'}</td>
                    <td>${employee.primary_branch?.name || 'N/A'}</td>
                    <td><span class="badge bg-${statusClass}">${employee.status.charAt(0).toUpperCase() + employee.status.slice(1)}</span></td>
                    <td><span class="badge bg-${employmentTypeClass}">${employee.employment_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></td>
                    <td>${employee.hire_date ? new Date(employee.hire_date).toLocaleDateString('id-ID') : 'N/A'}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-info" onclick="viewEmployee(${employee.id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-warning" onclick="editEmployee(${employee.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-${employee.status === 'active' ? 'secondary' : 'success'}" 
                                    onclick="toggleEmployeeStatus(${employee.id})" 
                                    title="${employee.status === 'active' ? 'Deactivate' : 'Activate'}">
                                <i class="fas fa-${employee.status === 'active' ? 'pause' : 'play'}"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteEmployee(${employee.id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    tableHtml += '</tbody></table></div>';
    $('#employeesTableContainer').html(tableHtml);
}

// Update pagination
function updatePagination(pagination) {
    // For now, we'll just append pagination info to the table container
    if (pagination && pagination.total > pagination.per_page) {
        let paginationHtml = `
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <small class="text-muted">
                        Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to ${Math.min(pagination.current_page * pagination.per_page, pagination.total)} of ${pagination.total} results
                    </small>
                </div>
                <div>
                    <nav aria-label="Employee pagination">
                        <ul class="pagination pagination-sm mb-0">
        `;
        
        // Previous button
        if (pagination.current_page > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${pagination.current_page - 1})">Previous</a></li>`;
        } else {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
        }
        
        // Page numbers (simplified - just show current and nearby pages)
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.last_page, pagination.current_page + 2);
        
        if (startPage > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(1)">1</a></li>`;
            if (startPage > 2) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === pagination.current_page) {
                paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${i})">${i}</a></li>`;
            }
        }
        
        if (endPage < pagination.last_page) {
            if (endPage < pagination.last_page - 1) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${pagination.last_page})">${pagination.last_page}</a></li>`;
        }
        
        // Next button
        if (pagination.current_page < pagination.last_page) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${pagination.current_page + 1})">Next</a></li>`;
        } else {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
        }
        
        paginationHtml += `
                        </ul>
                    </nav>
                </div>
            </div>
        `;
        
        $('#employeesTableContainer').append(paginationHtml);
    }
}

// Load specific page
function loadPage(page) {
    const formData = $('#searchForm').serialize() + '&page=' + page;
    
    $.get('/api/hr-central/employees?' + formData)
        .done(function(response) {
            updateEmployeesTable(response.employees);
            updatePagination(response.pagination);
            updateStatistics(response.statistics || response.employees);
        })
        .fail(function() {
            showAlert('error', 'Error loading employees');
        });
}

// Update statistics
function updateStatistics(data) {
    let stats;
    
    // Check if data is statistics object from API or array of employees
    if (data && data.total !== undefined) {
        // Data is statistics object from API response
        stats = data;
    } else if (Array.isArray(data)) {
        // Data is array of employees - calculate from current page data
        stats = {
            total: data.length,
            active: data.filter(emp => emp.status === 'active').length,
            inactive: data.filter(emp => emp.status === 'inactive').length,
            terminated: data.filter(emp => emp.status === 'terminated').length
        };
    } else {
        // Fallback - no update
        return;
    }
    
    // Update the statistics cards
    $('#totalEmployees').text(stats.total || 0);
    $('#activeEmployees').text(stats.active || 0);
    $('#inactiveEmployees').text(stats.inactive || 0);
    $('#terminatedEmployees').text(stats.terminated || 0);
}

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

// View employee
function viewEmployee(id) {
    window.location.href = `/hr-central/employees/${id}`;
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
            loadEmployees();
        }
    })
    .fail(function() {
        showAlert('error', 'Error updating employee status');
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
                showAlert('success', response.message);
                loadEmployees();
            }
        })
        .fail(function() {
            showAlert('error', 'Error deleting employee');
        });
    }
}

// Export employees
function exportEmployees() {
    window.location.href = '/api/hr-central/employees/export';
}

// Clear search
function clearSearch() {
    $('#searchForm')[0].reset();
    window.location.href = window.location.pathname;
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
            loadEmployees();
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
