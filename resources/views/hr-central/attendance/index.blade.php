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
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary" id="btn-export">
                <i class="fas fa-download"></i> 
                <span class="d-none d-sm-inline">Export Report</span>
            </button>
            <button type="button" class="btn btn-primary" onclick="refreshData()">
                <i class="fas fa-sync"></i> 
                <span class="d-none d-sm-inline">Refresh</span>
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title mb-3">Filters & Search</h6>
            <form id="filter-form" class="row g-3">
                <div class="col-lg-3 col-md-6">
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
                <div class="col-lg-3 col-md-6">
                    <label for="date-filter" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date-filter" name="date" value="{{ $selectedDate }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="employee-filter" class="form-label">Employee</label>
                    <select class="form-select" id="employee-filter" name="employee_id">
                        <option value="">All Employees</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-search"></i> 
                        <span class="d-none d-sm-inline">Filter</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()" title="Reset Filters">
                        <i class="fas fa-times"></i>
                        <span class="d-none d-lg-inline">Reset</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="stats-cards">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-gradient-primary text-white me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-0 fw-bold" id="total-employees">{{ $attendanceData['summary']['total_employees'] ?? 0 }}</h3>
                            <small class="text-muted">Total Employees</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-gradient-success text-white me-3">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-0 fw-bold" id="present-employees">{{ $attendanceData['summary']['present'] ?? 0 }}</h3>
                            <small class="text-muted">Present Today</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-gradient-warning text-white me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-0 fw-bold" id="late-employees">{{ $attendanceData['summary']['late'] ?? 0 }}</h3>
                            <small class="text-muted">Late Arrivals</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-gradient-info text-white me-3">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-0 fw-bold" id="attendance-rate">{{ $attendanceData['summary']['attendance_rate'] ?? 0 }}%</h3>
                            <small class="text-muted">Attendance Rate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">Daily Attendance - {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</h6>
                <span class="badge bg-light text-dark">{{ $attendanceData['events']->count() }} Records</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" id="attendance-table-container">
                <table class="table table-hover mb-0" id="attendance-table">
                    <thead class="table-dark">
                        <tr>
                            <th class="border-0">Employee ID</th>
                            <th class="border-0">Name</th>
                            <th class="border-0 d-none d-md-table-cell">Branch</th>
                            <th class="border-0 d-none d-lg-table-cell">Position</th>
                            <th class="border-0">Check In</th>
                            <th class="border-0 d-none d-sm-table-cell">Check Out</th>
                            <th class="border-0 d-none d-md-table-cell">Work Hours</th>
                            <th class="border-0">Status</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-tbody">
                        @if($attendanceData['events']->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-calendar-times fa-3x mb-3 text-muted opacity-50"></i>
                                        <h6 class="fw-semibold mb-2">No Attendance Data Found</h6>
                                        <p class="mb-0">No attendance records found for the selected criteria.</p>
                                        <small class="text-muted">Try changing the filters or selecting a different date.</small>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($attendanceData['events'] as $attendance)
                                <tr class="attendance-row">
                                    <td class="fw-semibold text-primary">{{ $attendance->employee->employee_number ?? 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2 position-relative">
                                                @if($attendance->employee->user->profile_photo ?? false)
                                                    <img src="{{ asset('storage/' . $attendance->employee->user->profile_photo) }}" 
                                                         class="avatar-img rounded-circle" 
                                                         alt="{{ $attendance->employee->user->name ?? 'User' }}">
                                                @else
                                                    <span class="avatar-title bg-gradient-{{ $loop->index % 4 == 0 ? 'primary' : ($loop->index % 4 == 1 ? 'success' : ($loop->index % 4 == 2 ? 'warning' : 'info')) }} text-white rounded-circle fw-bold">
                                                        <i class="fas fa-user"></i>
                                                    </span>
                                                @endif
                                                <!-- Online/Offline Status -->
                                                @if($attendance->check_in && !$attendance->check_out)
                                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle status-indicator">
                                                        <span class="visually-hidden">Online</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $attendance->employee->user->name ?? 'Unknown' }}</div>
                                                <small class="text-muted d-block d-md-none">{{ $attendance->branch->name ?? 'Unknown' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">{{ $attendance->branch->name ?? 'Unknown' }}</td>
                                    <td class="d-none d-lg-table-cell">{{ $attendance->employee->department ?? 'N/A' }}</td>
                                    <td>
                                        @if($attendance->check_in)
                                            <div class="time-badge-container">
                                                <span class="badge bg-{{ ($attendance->late_minutes > 0) ? 'warning' : 'success' }} time-badge">
                                                    {{ $attendance->check_in->format('H:i') }}
                                                    @if($attendance->late_minutes > 0)
                                                        <i class="fas fa-exclamation-triangle ms-1"></i>
                                                    @endif
                                                </span>
                                                @if($attendance->late_minutes > 0)
                                                    <small class="text-warning d-block">+{{ $attendance->late_minutes }}min</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="d-none d-sm-table-cell">
                                        @if($attendance->check_out)
                                            <div class="time-badge-container">
                                                <span class="badge bg-{{ ($attendance->early_minutes > 0) ? 'warning' : 'info' }} time-badge">
                                                    {{ $attendance->check_out->format('H:i') }}
                                                    @if($attendance->early_minutes > 0)
                                                        <i class="fas fa-exclamation-triangle ms-1"></i>
                                                    @endif
                                                </span>
                                                @if($attendance->early_minutes > 0)
                                                    <small class="text-warning d-block">-{{ $attendance->early_minutes }}min</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        @if($attendance->total_work_minutes > 0)
                                            <span class="fw-semibold">{{ number_format($attendance->total_work_minutes / 60, 1) }}h</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->check_in)
                                            @if($attendance->check_out)
                                                <span class="badge bg-success rounded-pill">Complete</span>
                                            @else
                                                <span class="badge bg-primary rounded-pill">Present</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary rounded-pill">Absent</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 action-buttons">
                                            <!-- View Details Button -->
                                            <button type="button" class="btn btn-sm btn-outline-primary action-btn" 
                                                    onclick="viewEmployeeDetails({{ $attendance->employee->id }})" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- History Button -->
                                            <button type="button" class="btn btn-sm btn-outline-info action-btn" 
                                                    onclick="viewAttendanceHistory({{ $attendance->employee->id }})" 
                                                    title="View History">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            
                                            @if($attendance->notes)
                                            <!-- Notes Button -->
                                            <button type="button" class="btn btn-sm btn-outline-warning action-btn" 
                                                    onclick="viewNotes('{{ addslashes($attendance->notes) }}')" 
                                                    title="View Notes">
                                                <i class="fas fa-sticky-note"></i>
                                            </button>
                                            @endif
                                            
                                            @if($attendance->selfie_photo)
                                            <!-- Selfie Button -->
                                            <button type="button" class="btn btn-sm btn-outline-success action-btn" 
                                                    onclick="viewSelfie({{ $attendance->id }})" 
                                                    title="View Selfie">
                                                <i class="fas fa-camera"></i>
                                            </button>
                                            @endif
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user-circle me-2"></i>
                    Employee Attendance Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="employee-details-content">
                    <!-- Content will be loaded here -->
                </div>
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
/* Gradient backgrounds */
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
}

/* Stats cards */
.stats-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border-radius: 12px;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

.icon-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Avatar */
.avatar-sm {
    width: 36px;
    height: 36px;
}

.avatar-lg {
    width: 64px;
    height: 64px;
}

.avatar-xl {
    width: 80px;
    height: 80px;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    position: relative;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
}

/* Status indicator */
.status-indicator {
    width: 12px;
    height: 12px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(40, 167, 69, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
}

/* Avatar variations */
.avatar-primary .avatar-title {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.avatar-success .avatar-title {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.avatar-warning .avatar-title {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
}

.avatar-info .avatar-title {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
}

.avatar-danger .avatar-title {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

/* Table improvements */
.table th {
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.attendance-row {
    transition: background-color 0.2s ease-in-out;
}

.attendance-row:hover {
    background-color: rgba(0, 123, 255, 0.05) !important;
}

/* Action buttons */
.action-buttons {
    justify-content: center;
}

.action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s ease-in-out;
}

.action-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Time badges */
.time-badge {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.4rem 0.6rem;
}

.time-badge-container {
    text-align: center;
}

/* Badge improvements */
.badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.4rem 0.8rem;
}

.badge.rounded-pill {
    padding: 0.4rem 1rem;
}

/* Card improvements */
.card {
    border-radius: 12px;
    transition: box-shadow 0.2s ease-in-out;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .container-fluid {
        padding: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .stats-card .card-body {
        padding: 1.25rem 1rem;
    }
    
    .icon-circle {
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
    }
    
    h3 {
        font-size: 1.5rem;
    }
    
    .action-buttons {
        flex-wrap: wrap;
        gap: 0.25rem !important;
    }
    
    .time-badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.5rem;
    }
}

@media (max-width: 576px) {
    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem;
    }
    
    .d-flex.gap-2 {
        width: 100%;
        justify-content: stretch;
    }
    
    .d-flex.gap-2 .btn {
        flex: 1;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

/* Loading states */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

/* Department-based avatar colors */
.avatar-hr { background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); }
.avatar-finance { background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%); }
.avatar-it { background: linear-gradient(135deg, #20c997 0%, #1aa179 100%); }
.avatar-marketing { background: linear-gradient(135deg, #e83e8c 0%, #d91a72 100%); }
.avatar-operations { background: linear-gradient(135deg, #6610f2 0%, #520dc2 100%); }
.avatar-sales { background: linear-gradient(135deg, #198754 0%, #146c43 100%); }

/* Avatar hover effects */
.avatar-sm:hover, .avatar-lg:hover {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

/* Professional icons for different roles */
.role-manager::before { content: "\f0e3"; font-family: "Font Awesome 5 Free"; }
.role-employee::before { content: "\f007"; font-family: "Font Awesome 5 Free"; }
.role-admin::before { content: "\f013"; font-family: "Font Awesome 5 Free"; }
.role-hr::before { content: "\f0c0"; font-family: "Font Awesome 5 Free"; }

/* History items */
.history-item {
    transition: all 0.2s ease-in-out;
    background: #f8f9fa;
}

.history-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.history-container {
    max-height: 400px;
    overflow-y: auto;
}

/* Scrollbar styling */
.history-container::-webkit-scrollbar {
    width: 4px;
}

.history-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.history-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.history-container::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

.stats-card, .card {
    animation: fadeIn 0.3s ease-out;
}

.history-item {
    animation: slideIn 0.3s ease-out;
}

/* Button loading state */
.btn.loading {
    pointer-events: none;
    opacity: 0.6;
}

.btn.loading::after {
    content: "";
    display: inline-block;
    width: 1rem;
    height: 1rem;
    margin-left: 0.5rem;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
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
    
    // Modern loading state
    $('#employee-details-content').html(`
        <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mb-0">Loading employee details...</p>
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
                    <div class="p-4 text-center">
                        <div class="mb-3">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                        </div>
                        <h6 class="fw-bold">Unable to Load Details</h6>
                        <p class="text-muted mb-3">Failed to load employee details</p>
                        <button class="btn btn-primary" onclick="viewEmployeeDetails(${employeeId})">
                            <i class="fas fa-redo me-2"></i>Try Again
                        </button>
                    </div>
                `);
            }
        },
        error: function() {
            $('#employee-details-content').html(`
                <div class="p-4 text-center">
                    <div class="mb-3">
                        <i class="fas fa-times-circle fa-3x text-danger"></i>
                    </div>
                    <h6 class="fw-bold text-danger">Connection Error</h6>
                    <p class="text-muted mb-3">Unable to connect to server. Please check your connection.</p>
                    <button class="btn btn-danger" onclick="viewEmployeeDetails(${employeeId})">
                        <i class="fas fa-redo me-2"></i>Retry
                    </button>
                </div>
            `);
        }
    });
}

function displayEmployeeDetails(data) {
    let historyHtml = '';
    
    if (data.recent_history && data.recent_history.length > 0) {
        data.recent_history.forEach(function(record) {
            const statusClass = record.status === 'present' ? 'success' : 'secondary';
            const timeClass = record.late_minutes > 0 ? 'warning' : 'success';
            
            historyHtml += `
                <div class="history-item p-3 border border-light rounded-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar-day text-primary me-2"></i>
                                <strong class="text-dark">${record.date}</strong>
                                <span class="badge bg-${statusClass} rounded-pill ms-2">${record.status || 'Unknown'}</span>
                            </div>
                            <div class="row text-sm">
                                <div class="col-6">
                                    <small class="text-muted d-block">Check In</small>
                                    <span class="fw-semibold text-${timeClass}">${record.check_in || '-'}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Check Out</small>
                                    <span class="fw-semibold">${record.check_out || '-'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            ${record.late_minutes > 0 ? `<small class="text-warning fw-semibold"><i class="fas fa-clock me-1"></i>Late ${record.late_minutes}min</small>` : '<small class="text-success"><i class="fas fa-check me-1"></i>On Time</small>'}
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        historyHtml = `
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-2x text-muted opacity-50 mb-3"></i>
                <p class="text-muted mb-0">No recent attendance history found</p>
            </div>
        `;
    }
    
    const attendance = data.attendance;
    const statusBadgeClass = attendance ? (attendance.status === 'present' ? 'success' : 'secondary') : 'secondary';
    const workHours = attendance && attendance.work_hours ? attendance.work_hours : '0';
    
    const detailsHtml = `
        <div class="p-4">
            <!-- Employee Info Card -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-lg me-3 position-relative">
                                    ${data.employee.profile_photo ? 
                                        `<img src="/storage/${data.employee.profile_photo}" class="avatar-img rounded-circle" alt="${data.employee.name || 'User'}">` : 
                                        `<span class="avatar-title bg-gradient-primary text-white rounded-circle fs-3 fw-bold">
                                            <i class="fas fa-user-tie"></i>
                                        </span>`
                                    }
                                    <!-- Status Badge -->
                                    <span class="position-absolute bottom-0 end-0 badge bg-primary rounded-circle p-2">
                                        <i class="fas fa-id-badge" style="font-size: 0.7rem;"></i>
                                    </span>
                                </div>
                                <div>
                                    <h5 class="mb-1 fw-bold">${data.employee.name || 'Unknown'}</h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-id-card me-1"></i>
                                        ${data.employee.employee_id || 'N/A'}
                                    </p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Branch</small>
                                    <strong>${data.employee.branch || 'Unknown'}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Department</small>
                                    <strong>${data.employee.department || 'N/A'}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold mb-0">
                                    <i class="fas fa-calendar-check text-primary me-2"></i>
                                    ${data.date}
                                </h6>
                                <span class="badge bg-${statusBadgeClass} rounded-pill">
                                    ${attendance ? (attendance.status || 'Unknown') : 'Absent'}
                                </span>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Check In</small>
                                    <div class="fw-semibold ${attendance && attendance.late_minutes > 0 ? 'text-warning' : 'text-success'}">
                                        ${attendance ? (attendance.check_in || '-') : 'No Record'}
                                        ${attendance && attendance.late_minutes > 0 ? `<br><small class="text-warning">+${attendance.late_minutes}min late</small>` : ''}
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Check Out</small>
                                    <div class="fw-semibold">
                                        ${attendance ? (attendance.check_out || '-') : 'No Record'}
                                        ${attendance && attendance.early_minutes > 0 ? `<br><small class="text-warning">-${attendance.early_minutes}min early</small>` : ''}
                                    </div>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Total Work Hours</small>
                                    <div class="fw-bold text-primary fs-5">
                                        <i class="fas fa-clock me-2"></i>${workHours}h
                                    </div>
                                </div>
                                ${attendance && attendance.notes ? `
                                <div class="col-12">
                                    <small class="text-muted d-block">Notes</small>
                                    <div class="bg-white p-2 rounded border">
                                        <small>${attendance.notes}</small>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent History Section -->
            <div class="mt-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-history text-info me-2"></i>
                    Recent Attendance History (Last 7 Days)
                </h6>
                <div class="history-container">
                    ${historyHtml}
                </div>
            </div>
        </div>
    `;
    
    $('#employee-details-content').html(detailsHtml);
}

function viewAttendanceHistory(employeeId) {
    // Redirect to employee attendance history page
    window.open(`/hr-central/employees/${employeeId}#attendance`, '_blank');
}

function viewNotes(notes) {
    // Create modern modal for notes
    const notesModal = `
        <div class="modal fade" id="notesModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-sticky-note me-2"></i>
                            Attendance Notes
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded">
                            <p class="mb-0 lh-lg">${notes}</p>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing notes modal if any
    $('#notesModal').remove();
    
    // Append new modal to body
    $('body').append(notesModal);
    
    // Show modal
    $('#notesModal').modal('show');
    
    // Clean up modal after hide
    $('#notesModal').on('hidden.bs.modal', function() {
        $(this).remove();
    });
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
