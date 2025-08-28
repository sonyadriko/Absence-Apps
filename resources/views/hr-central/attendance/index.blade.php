@extends('layouts.app')

@section('title', 'Attendance Management - HR Central')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1">Attendance Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('hr-central.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Attendance</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="btn-export">
                <i class="fas fa-download"></i> Export Report
            </button>
            <button type="button" class="btn btn-primary" onclick="refreshData()">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title mb-3">Filters & Search</h6>
            <form id="filter-form" class="row g-3">
                <div class="col-md-3">
                    <label for="branch-filter" class="form-label">Branch</label>
                    <select class="form-select" id="branch-filter" name="branch_id">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date-filter" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date-filter" name="date" value="{{ $selectedDate }}">
                </div>
                <div class="col-md-3">
                    <label for="employee-filter" class="form-label">Employee</label>
                    <select class="form-select" id="employee-filter" name="employee_id">
                        <option value="">All Employees</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-times"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="stats-cards">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="icon-circle bg-primary text-white me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h3 class="mb-0" id="total-employees">{{ $attendanceData['summary']['total_employees'] ?? 0 }}</h3>
                            <small class="text-muted">Total Employees</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="icon-circle bg-success text-white me-3">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <h3 class="mb-0" id="present-employees">{{ $attendanceData['summary']['present'] ?? 0 }}</h3>
                            <small class="text-muted">Present Today</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="icon-circle bg-warning text-white me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="mb-0" id="late-employees">{{ $attendanceData['summary']['late'] ?? 0 }}</h3>
                            <small class="text-muted">Late Arrivals</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="icon-circle bg-info text-white me-3">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div>
                            <h3 class="mb-0" id="attendance-rate">{{ $attendanceData['summary']['attendance_rate'] ?? 0 }}%</h3>
                            <small class="text-muted">Attendance Rate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">Daily Attendance - {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive" id="attendance-table-container">
                <table class="table table-striped table-hover" id="attendance-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Position</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Work Hours</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-tbody">
                        @if($attendanceData['events']->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                        <p>No attendance data found for the selected criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($attendanceData['events'] as $attendance)
                                <tr>
                                    <td>{{ $attendance->employee->employee_number ?? 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <span class="avatar-title bg-primary rounded-circle">
                                                    {{ substr($attendance->employee->user->name ?? 'U', 0, 1) }}
                                                </span>
                                            </div>
                                            {{ $attendance->employee->user->name ?? 'Unknown' }}
                                        </div>
                                    </td>
                                    <td>{{ $attendance->branch->name ?? 'Unknown' }}</td>
                                    <td>{{ $attendance->employee->department ?? 'N/A' }}</td>
                                    <td>
                                        @if($attendance->check_in)
                                            <span class="badge bg-{{ ($attendance->late_minutes > 0) ? 'warning' : 'success' }}">
                                                {{ $attendance->check_in->format('H:i:s') }}
                                                @if($attendance->late_minutes > 0)
                                                    <i class="fas fa-exclamation-triangle ms-1"></i>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->check_out)
                                            <span class="badge bg-{{ ($attendance->early_minutes > 0) ? 'warning' : 'info' }}">
                                                {{ $attendance->check_out->format('H:i:s') }}
                                                @if($attendance->early_minutes > 0)
                                                    <i class="fas fa-exclamation-triangle ms-1"></i>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->total_work_minutes > 0)
                                            {{ number_format($attendance->total_work_minutes / 60, 1) }}h
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->check_in)
                                            @if($attendance->check_out)
                                                <span class="badge bg-success">Complete</span>
                                            @else
                                                <span class="badge bg-primary">Present</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Absent</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="viewEmployeeDetails({{ $attendance->employee->id }})"><i class="fas fa-eye me-2"></i>View Details</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="viewAttendanceHistory({{ $attendance->employee->id }})"><i class="fas fa-history me-2"></i>History</a></li>
                                                @if($attendance->notes)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item" href="#" onclick="viewNotes('{{ $attendance->notes }}')"><i class="fas fa-sticky-note me-2"></i>View Notes</a></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Employee Details Modal -->
<div class="modal fade" id="employeeDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="employee-details-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Selfie Modal -->
<div class="modal fade" id="selfieModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Selfie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="selfie-content">
                <!-- Selfie image will be loaded here -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.avatar-sm {
    width: 32px;
    height: 32px;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge {
    font-size: 0.75rem;
}
</style>
@endpush

@push('scripts')
<script>
// Setup CSRF token for AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(document).ready(function() {
    // Initialize filters
    loadEmployeesByBranch();
    
    // Filter form submission
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });
    
    // Branch change event
    $('#branch-filter').on('change', function() {
        loadEmployeesByBranch();
    });
    
    // Export button
    $('#btn-export').on('click', function() {
        exportReport();
    });
});

function loadEmployeesByBranch() {
    const branchId = $('#branch-filter').val();
    const $employeeSelect = $('#employee-filter');
    
    // Reset employee dropdown
    $employeeSelect.html('<option value="">All Employees</option>');
    
    if (!branchId) {
        return;
    }
    
    // Show loading
    $employeeSelect.prop('disabled', true);
    
    $.ajax({
        url: '{{ route("hr-central.attendance.employees-by-branch") }}',
        method: 'GET',
        data: { branch_id: branchId },
        success: function(response) {
            if (response.success) {
                response.data.forEach(function(employee) {
                    $employeeSelect.append(
                        `<option value="${employee.id}">${employee.employee_id} - ${employee.name}</option>`
                    );
                });
            }
        },
        error: function() {
            alert('Failed to load employees');
        },
        complete: function() {
            $employeeSelect.prop('disabled', false);
        }
    });
}

function applyFilters() {
    const formData = $('#filter-form').serialize();
    window.location.href = '{{ route("hr-central.attendance.index") }}?' + formData;
}

function resetFilters() {
    window.location.href = '{{ route("hr-central.attendance.index") }}';
}

function refreshData() {
    const currentUrl = new URL(window.location);
    window.location.href = currentUrl.toString();
}

function exportReport() {
    const formData = $('#filter-form').serialize();
    const exportUrl = '{{ route("hr-central.attendance.export") }}?' + formData;
    window.open(exportUrl, '_blank');
}

function viewEmployeeDetails(employeeId) {
    const date = $('#date-filter').val();
    
    $('#employee-details-content').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    $('#employeeDetailsModal').modal('show');
    
    // Load employee details via AJAX
    $.ajax({
        url: `/api/hr-central/employees/${employeeId}/attendance`,
        method: 'GET',
        data: { date: date },
        success: function(response) {
            if (response.success) {
                displayEmployeeDetails(response.data);
            } else {
                $('#employee-details-content').html(`
                    <div class="alert alert-danger">
                        Failed to load employee details
                    </div>
                `);
            }
        },
        error: function() {
            $('#employee-details-content').html(`
                <div class="alert alert-danger">
                    Error loading employee details
                </div>
            `);
        }
    });
}

function displayEmployeeDetails(data) {
    let historyHtml = '';
    
    if (data.recent_history && data.recent_history.length > 0) {
        data.recent_history.forEach(function(record) {
            const statusClass = record.status === 'present' ? 'bg-success' : 'bg-secondary';
            const lateClass = record.late_minutes > 0 ? 'text-warning' : 'text-success';
            
            historyHtml += `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>${record.date}</strong>
                        <br>
                        <small class="text-muted">
                            In: ${record.check_in || '-'} | Out: ${record.check_out || '-'}
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="badge ${statusClass}">${record.status || 'Unknown'}</span>
                        <br>
                        <small class="${lateClass}">
                            ${record.late_minutes > 0 ? `Late: ${record.late_minutes}min` : 'On Time'}
                        </small>
                    </div>
                </div>
            `;
        });
    } else {
        historyHtml = '<div class="text-center text-muted py-4">No recent attendance history found</div>';
    }
    
    const attendance = data.attendance;
    const detailsHtml = `
        <div class="row">
            <div class="col-md-6">
                <h6>Employee Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>ID:</strong></td><td>${data.employee.employee_id || 'N/A'}</td></tr>
                    <tr><td><strong>Name:</strong></td><td>${data.employee.name || 'Unknown'}</td></tr>
                    <tr><td><strong>Branch:</strong></td><td>${data.employee.branch || 'Unknown'}</td></tr>
                    <tr><td><strong>Department:</strong></td><td>${data.employee.department || 'N/A'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Attendance for ${data.date}</h6>
                <table class="table table-sm">
                    <tr><td><strong>Check In:</strong></td><td>${attendance ? (attendance.check_in || '-') : 'No Record'}</td></tr>
                    <tr><td><strong>Check Out:</strong></td><td>${attendance ? (attendance.check_out || '-') : 'No Record'}</td></tr>
                    <tr><td><strong>Work Hours:</strong></td><td>${attendance ? (attendance.work_hours || 0) + 'h' : '0h'}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>
                        ${attendance ? `<span class="badge bg-${attendance.status === 'present' ? 'success' : 'secondary'}">${attendance.status || 'Unknown'}</span>` : '<span class="badge bg-secondary">Absent</span>'}
                    </td></tr>
                    ${attendance && attendance.late_minutes > 0 ? `<tr><td><strong>Late:</strong></td><td class="text-warning">${attendance.late_minutes} minutes</td></tr>` : ''}
                    ${attendance && attendance.notes ? `<tr><td><strong>Notes:</strong></td><td>${attendance.notes}</td></tr>` : ''}
                </table>
            </div>
        </div>
        <hr>
        <h6>Recent History (Last 7 Days)</h6>
        <div class="mt-3">
            ${historyHtml}
        </div>
    `;
    
    $('#employee-details-content').html(detailsHtml);
}

function viewAttendanceHistory(employeeId) {
    // Redirect to employee attendance history page
    window.open(`/hr-central/employees/${employeeId}#attendance`, '_blank');
}

function viewNotes(notes) {
    // Create a simple alert or modal to show notes
    alert('Attendance Notes:\n\n' + notes);
}

function viewSelfie(eventId) {
    $('#selfie-content').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    $('#selfieModal').modal('show');
    
    // Load selfie via AJAX
    $.ajax({
        url: `/api/attendance/events/${eventId}/selfie`,
        method: 'GET',
        success: function(response) {
            if (response.success && response.data.image) {
                $('#selfie-content').html(`
                    <img src="${response.data.image}" class="img-fluid rounded" alt="Attendance Selfie">
                    <p class="mt-2 text-muted small">Taken at ${new Date(response.data.event_time).toLocaleString()}</p>
                `);
            } else {
                $('#selfie-content').html(`
                    <div class="alert alert-warning">
                        Selfie not available for this event
                    </div>
                `);
            }
        },
        error: function() {
            $('#selfie-content').html(`
                <div class="alert alert-danger">
                    Failed to load selfie
                </div>
            `);
        }
    });
}
</script>
@endpush
