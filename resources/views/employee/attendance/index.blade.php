@extends('layouts.app')

@section('title', 'My Attendance Records')

@push('styles')
<style>
    /* Custom styles for attendance page */
    .stats-card {
        transition: transform 0.2s ease-in-out;
        cursor: pointer;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .table-hover tbody tr {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(139, 69, 19, 0.05);
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffeaa7;
        color: #856404;
    }
    
    .modal-header {
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        color: white;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .badge {
        font-weight: 500;
        padding: 0.375rem 0.75rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .btn-group {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        .btn-group .btn {
            margin-bottom: 0.5rem;
            width: 100%;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .stats-card {
            margin-bottom: 1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-clock me-2"></i>My Attendance Records
            </h1>
            <p class="text-muted mb-0">View your attendance history and statistics</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-outline-primary" onclick="exportAttendance()">
                <i class="fas fa-download me-2"></i>Export
            </button>
            <a href="{{ route('employee.attendance.checkin') }}" class="btn btn-primary">
                <i class="fas fa-fingerprint me-2"></i>Check In/Out
            </a>
        </div>
    </div>

    <!-- Missing Checkout Alert (will be added dynamically) -->
    <div id="missing-checkout-alert"></div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold" id="totalDays">0</div>
                        <div class="text-muted small">Total Days</div>
                    </div>
                    <div class="fs-2 text-primary">
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-success" id="presentDays">0</div>
                        <div class="text-muted small">Present Days</div>
                    </div>
                    <div class="fs-2 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-warning" id="lateDays">0</div>
                        <div class="text-muted small">Late Arrivals</div>
                    </div>
                    <div class="fs-2 text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info" id="totalHours">0h</div>
                        <div class="text-muted small">Total Hours</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3" onsubmit="return false;">
                <div class="col-md-3">
                    <label for="start-date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start-date" name="start_date">
                </div>
                <div class="col-md-3">
                    <label for="end-date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end-date" name="end_date">
                </div>
                <div class="col-md-3">
                    <label for="status-filter" class="form-label">Status</label>
                    <select class="form-select" id="status-filter" name="status">
                        <option value="">All Statuses</option>
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="absent">Absent</option>
                        <option value="partial">Partial</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary me-2" onclick="applyFilters()">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Records Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Attendance Records
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Work Hours</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav id="pagination-nav" class="mt-3" style="display: none;">
                <ul class="pagination justify-content-center" id="pagination">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Attendance Detail Modal -->
<div class="modal fade" id="attendanceDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Attendance Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="attendance-detail-content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Request Correction Modal -->
<div class="modal fade" id="correctionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Request Correction
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="correctionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="correction-type" class="form-label">Correction Type</label>
                        <select class="form-select" id="correction-type" name="type" required>
                            <option value="">Select correction type...</option>
                            <option value="missing_checkin">Missing Check In</option>
                            <option value="missing_checkout">Missing Check Out</option>
                            <option value="wrong_time">Wrong Time</option>
                            <option value="wrong_branch">Wrong Branch</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="correction-reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="correction-reason" name="reason" rows="3" 
                                  placeholder="Please explain why this correction is needed..." required></textarea>
                    </div>
                    
                    <div class="mb-3" id="time-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="correct-checkin" class="form-label">Correct Check In Time</label>
                                <input type="time" class="form-control" id="correct-checkin" name="correct_checkin_time">
                            </div>
                            <div class="col-md-6">
                                <label for="correct-checkout" class="form-label">Correct Check Out Time</label>
                                <input type="time" class="form-control" id="correct-checkout" name="correct_checkout_time">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Global variables
let attendanceRecords = [];
let currentPage = 1;
let totalPages = 1;
let filters = {};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing attendance page...');
    
    // Check if API is available
    if (typeof window.API === 'undefined') {
        console.error('API not loaded, waiting...');
        setTimeout(function() {
            initializePage();
        }, 1000);
    } else {
        initializePage();
    }
});

function initializePage() {
    console.log('Initializing page...');
    setDefaultDates();
    setupEventListeners();
    loadAttendanceRecords();
    checkMissingCheckouts();
}

function setupEventListeners() {
    // Correction type change listener
    const correctionType = document.getElementById('correction-type');
    if (correctionType) {
        correctionType.addEventListener('change', function(e) {
            const timeFields = document.getElementById('time-fields');
            if (['missing_checkin', 'missing_checkout', 'wrong_time'].includes(e.target.value)) {
                timeFields.style.display = 'block';
            } else {
                timeFields.style.display = 'none';
            }
        });
    }
    
    // Correction form submit
    const correctionForm = document.getElementById('correctionForm');
    if (correctionForm) {
        correctionForm.addEventListener('submit', submitCorrection);
    }
}

function setDefaultDates() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    document.getElementById('start-date').value = firstDay.toISOString().split('T')[0];
    document.getElementById('end-date').value = lastDay.toISOString().split('T')[0];
    
    filters.start_date = firstDay.toISOString().split('T')[0];
    filters.end_date = lastDay.toISOString().split('T')[0];
}

async function loadAttendanceRecords(page = 1) {
    console.log('Loading attendance records...');
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    try {
        const params = {
            page: page,
            per_page: 20,
            ...filters
        };
        
        const response = await window.API.get('/employee/attendance/history', params);
        console.log('Attendance response:', response);
        
        if (response.data) {
            attendanceRecords = Array.isArray(response.data.data) ? response.data.data : [];
            currentPage = response.data.current_page || 1;
            totalPages = response.data.last_page || 1;
        } else if (Array.isArray(response)) {
            attendanceRecords = response;
            currentPage = 1;
            totalPages = 1;
        }
        
        updateTable();
        updatePagination();
        loadStatistics();
        
    } catch (error) {
        console.error('Failed to load attendance:', error);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load records. Please try again.</td></tr>';
        if (window.Utils) {
            window.Utils.showToast('Failed to load attendance records', 'error');
        }
    }
}

async function loadStatistics() {
    try {
        const response = await window.API.get('/employee/attendance/stats', filters);
        const stats = response.data || {};
        
        document.getElementById('totalDays').textContent = stats.total_days || 0;
        document.getElementById('presentDays').textContent = stats.present_days || 0;
        document.getElementById('lateDays').textContent = stats.late_days || 0;
        document.getElementById('totalHours').textContent = formatHours(stats.total_hours || 0);
        
    } catch (error) {
        console.error('Failed to load statistics:', error);
    }
}

function updateTable() {
    const tbody = document.getElementById('attendanceTableBody');
    
    if (!attendanceRecords || attendanceRecords.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-calendar-times me-2"></i>No attendance records found for the selected period</td></tr>';
        return;
    }
    
    tbody.innerHTML = attendanceRecords.map(record => {
        const recordDate = new Date(record.date + 'T00:00:00');
        const dayName = recordDate.toLocaleDateString('en', {weekday: 'short'});
        
        return `
            <tr>
                <td>
                    <div class="fw-medium">${formatDate(record.date)}</div>
                    <small class="text-muted">${dayName}</small>
                </td>
                <td>
                    <i class="fas fa-building me-1 text-muted"></i>
                    ${record.branch?.name || 'Main Branch'}
                </td>
                <td>
                    ${record.check_in_time ? `
                        <span class="text-success fw-medium">
                            <i class="fas fa-sign-in-alt me-1"></i>${formatTime(record.check_in_time)}
                        </span>
                        ${record.is_late ? '<br><small class="text-warning"><i class="fas fa-clock me-1"></i>Late</small>' : ''}
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    ${record.check_out_time ? `
                        <span class="text-danger fw-medium">
                            <i class="fas fa-sign-out-alt me-1"></i>${formatTime(record.check_out_time)}
                        </span>
                    ` : (record.check_in_time ? '<span class="badge bg-warning text-dark">Pending</span>' : '<span class="text-muted">--</span>')}
                </td>
                <td>
                    ${record.work_hours !== undefined && record.work_hours !== null ? `
                        <span class="fw-medium">${formatHours(record.work_hours)}</span>
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    ${renderStatusBadge(record.status)}
                </td>
                <td>
                    ${record.check_in_notes || record.check_out_notes ? `
                        <i class="fas fa-sticky-note text-info" 
                           data-bs-toggle="tooltip" 
                           title="${escapeHtml(record.check_in_notes || record.check_out_notes)}"></i>
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    ${renderActions(record)}
                </td>
            </tr>
        `;
    }).join('');
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function updatePagination() {
    const paginationNav = document.getElementById('pagination-nav');
    const pagination = document.getElementById('pagination');
    
    if (totalPages <= 1) {
        paginationNav.style.display = 'none';
        return;
    }
    
    paginationNav.style.display = 'block';
    
    let paginationHtml = '';
    
    // Previous button
    paginationHtml += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        </li>
    `;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Next button
    paginationHtml += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    pagination.innerHTML = paginationHtml;
}

function changePage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    loadAttendanceRecords(page);
}

function applyFilters() {
    filters = {};
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const status = document.getElementById('status-filter').value;
    
    if (startDate) filters.start_date = startDate;
    if (endDate) filters.end_date = endDate;
    if (status) filters.status = status;
    
    currentPage = 1;
    loadAttendanceRecords();
}

function resetFilters() {
    document.getElementById('filterForm').reset();
    setDefaultDates();
    currentPage = 1;
    loadAttendanceRecords();
}

async function viewDetails(recordId) {
    console.log('Viewing details for record:', recordId);
    const modalContent = document.getElementById('attendance-detail-content');
    modalContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    const modal = new bootstrap.Modal(document.getElementById('attendanceDetailModal'));
    modal.show();
    
    try {
        const response = await window.API.get(`/employee/attendance/${recordId}`);
        const record = response.data || {};
        
        modalContent.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="border-bottom pb-2"><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Date:</th>
                            <td>${formatDate(record.date)}</td>
                        </tr>
                        <tr>
                            <th>Branch:</th>
                            <td><i class="fas fa-building me-1"></i>${record.branch?.name || 'Main Branch'}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>${renderStatusBadge(record.status)}</td>
                        </tr>
                        <tr>
                            <th>Work Hours:</th>
                            <td>${record.work_hours ? formatHours(record.work_hours) : '--'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="border-bottom pb-2"><i class="fas fa-clock me-2"></i>Time Records</h6>
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Check In:</th>
                            <td>${record.check_in_time ? `<span class="text-success"><i class="fas fa-sign-in-alt me-1"></i>${formatTime(record.check_in_time)}</span>` : '--'}</td>
                        </tr>
                        <tr>
                            <th>Check Out:</th>
                            <td>${record.check_out_time ? `<span class="text-danger"><i class="fas fa-sign-out-alt me-1"></i>${formatTime(record.check_out_time)}</span>` : '--'}</td>
                        </tr>
                        <tr>
                            <th>Late:</th>
                            <td>${record.is_late ? '<span class="text-warning"><i class="fas fa-exclamation-circle me-1"></i>Yes</span>' : '<span class="text-success"><i class="fas fa-check-circle me-1"></i>No</span>'}</td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        
    } catch (error) {
        console.error('Failed to load details:', error);
        modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load attendance details.</div>';
    }
}

function requestCorrection(recordId, date) {
    console.log('Request correction for:', recordId, date);
    document.getElementById('correctionForm').reset();
    document.getElementById('correctionForm').setAttribute('data-record-id', recordId);
    document.getElementById('correctionForm').setAttribute('data-date', date);
    
    const modal = new bootstrap.Modal(document.getElementById('correctionModal'));
    modal.show();
}

async function submitCorrection(e) {
    e.preventDefault();
    console.log('Submitting correction...');
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    if (window.Utils) {
        window.Utils.setButtonLoading(submitBtn, true);
    }
    
    try {
        const formData = new FormData(e.target);
        const recordId = e.target.getAttribute('data-record-id');
        const date = e.target.getAttribute('data-date');
        
        const data = Object.fromEntries(formData.entries());
        data.attendance_id = recordId;
        data.date = date;
        
        const response = await window.API.post('/employee/corrections', data);
        
        if (window.Utils) {
            window.Utils.showToast(response.message || 'Correction submitted successfully', 'success');
        }
        
        bootstrap.Modal.getInstance(document.getElementById('correctionModal')).hide();
        
    } catch (error) {
        console.error('Failed to submit correction:', error);
        if (window.Utils) {
            window.Utils.showToast('Failed to submit correction', 'error');
        }
    } finally {
        if (window.Utils) {
            window.Utils.setButtonLoading(submitBtn, false);
        }
    }
}

async function checkMissingCheckouts() {
    try {
        const response = await window.API.get('/employee/attendance/missing-checkouts');
        const missingCheckouts = response.data || [];
        
        if (missingCheckouts.length > 0) {
            showMissingCheckoutBanner(missingCheckouts[0]);
        }
    } catch (error) {
        console.debug('No missing checkouts');
    }
}

function showMissingCheckoutBanner(item) {
    const alertContainer = document.getElementById('missing-checkout-alert');
    if (!alertContainer) return;
    
    alertContainer.innerHTML = `
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fw-bold">Pending Checkout Detected</div>
                    <div>You checked in on ${formatDate(item.date)} at ${formatTime(item.check_in_time)} but did not check out.</div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    if (!timeString) return '';
    const parts = timeString.split(':');
    if (parts.length >= 2) {
        return `${parts[0]}:${parts[1]}`;
    }
    return timeString;
}

function formatHours(hours) {
    if (hours === null || hours === undefined || hours === 0) return '0h 0m';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    return `${h}h ${m > 0 ? m + 'm' : ''}`;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function renderStatusBadge(status) {
    const statusMap = {
        'present': { text: 'Present', class: 'bg-success' },
        'late': { text: 'Late', class: 'bg-warning text-dark' },
        'absent': { text: 'Absent', class: 'bg-danger' },
        'partial': { text: 'Partial', class: 'bg-info' },
        'half_day': { text: 'Half Day', class: 'bg-info' }
    };
    
    const config = statusMap[status?.toLowerCase()] || { text: status || 'Unknown', class: 'bg-secondary' };
    return `<span class="badge ${config.class}">${config.text}</span>`;
}

function renderActions(record) {
    const actions = [];
    
    // View details button
    actions.push(`
        <button class="btn btn-sm btn-outline-primary" 
                onclick="viewDetails('${record.id}')" 
                title="View Details" 
                data-bs-toggle="tooltip">
            <i class="fas fa-eye"></i>
        </button>
    `);
    
    // Request correction button (only for last 7 days)
    const recordDate = new Date(record.date);
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    
    if (recordDate >= sevenDaysAgo) {
        actions.push(`
            <button class="btn btn-sm btn-outline-warning" 
                    onclick="requestCorrection('${record.id}', '${record.date}')" 
                    title="Request Correction" 
                    data-bs-toggle="tooltip">
                <i class="fas fa-edit"></i>
            </button>
        `);
    }
    
    return `<div class="btn-group btn-group-sm" role="group">${actions.join('')}</div>`;
}

function exportAttendance() {
    const params = new URLSearchParams(filters);
    const url = `${window.API.baseURL}/employee/reports/export/attendance?${params}`;
    window.open(url, '_blank');
}

// Make functions globally available
window.viewDetails = viewDetails;
window.requestCorrection = requestCorrection;
window.changePage = changePage;
window.applyFilters = applyFilters;
window.resetFilters = resetFilters;
window.exportAttendance = exportAttendance;

</script>
@endpush
