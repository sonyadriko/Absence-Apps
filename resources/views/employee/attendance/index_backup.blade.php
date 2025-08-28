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
            <form id="filterForm" class="row g-3">
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
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-filter">
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

{{-- Missing Checkout Correction Modal --}}
@include('attendance.missing-checkout-modal')
@endsection

@push('scripts')
<script>
// Global variable to store attendance data
let attendanceData = {
    records: [],
    stats: {},
    currentPage: 1,
    totalPages: 1,
    filters: {},
    
    init() {
        this.setupEventListeners();
        this.initializeDateFilters();
        this.loadAttendanceRecords();
        this.checkMissingCheckouts();
    },
    
    setupEventListeners() {
        document.getElementById('filterForm').addEventListener('submit', (e) => this.applyFilters(e));
        document.getElementById('reset-filter').addEventListener('click', () => this.resetFilters());
        document.getElementById('correction-type').addEventListener('change', (e) => this.toggleTimeFields(e.target.value));
        document.getElementById('correctionForm').addEventListener('submit', (e) => this.submitCorrection(e));
    },
    
    initializeDateFilters() {
        // Set default date range to current month
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        
        document.getElementById('start-date').value = firstDay.toISOString().split('T')[0];
        document.getElementById('end-date').value = lastDay.toISOString().split('T')[0];
    },
    
    async loadAttendanceRecords(page = 1) {
        try {
            const tbody = document.getElementById('attendanceTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
            
            const params = {
                page: page,
                per_page: 20,
                ...this.filters
            };
            
            const response = await API.get('/employee/attendance/history', params);
            
            // Handle response data structure
            if (response.data) {
                this.records = Array.isArray(response.data.data) ? response.data.data : [];
                this.currentPage = response.data.current_page || 1;
                this.totalPages = response.data.last_page || 1;
            } else if (Array.isArray(response)) {
                this.records = response;
                this.currentPage = 1;
                this.totalPages = 1;
            } else {
                this.records = [];
            }
            
            this.updateTable();
            this.updatePagination();
            
            // Load statistics
            await this.loadStatistics();
            
        } catch (error) {
            console.error('Failed to load attendance records:', error);
            Utils.handleApiError(error, 'Failed to load attendance records');
            document.getElementById('attendanceTableBody').innerHTML = 
                '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load records. Please try again.</td></tr>';
        }
    },
    
    async loadStatistics() {
        try {
            const response = await API.get('/employee/attendance/stats', this.filters);
            this.stats = response.data || {};
            this.updateStats();
        } catch (error) {
            console.error('Failed to load statistics:', error);
            // Set default values if stats fail to load
            this.stats = {
                total_days: 0,
                present_days: 0,
                late_days: 0,
                total_hours: 0
            };
            this.updateStats();
        }
    },
    
    updateStats() {
        document.getElementById('totalDays').textContent = this.stats.total_days || 0;
        document.getElementById('presentDays').textContent = this.stats.present_days || 0;
        document.getElementById('lateDays').textContent = this.stats.late_days || 0;
        document.getElementById('totalHours').textContent = this.formatHours(this.stats.total_hours || 0);
    },
    
    updateTable() {
        const tbody = document.getElementById('attendanceTableBody');
        
        if (!this.records || this.records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-calendar-times me-2"></i>No attendance records found for the selected period</td></tr>';
            return;
        }
        
        tbody.innerHTML = this.records.map(record => {
            const recordDate = new Date(record.date + 'T00:00:00');
            const dayName = recordDate.toLocaleDateString('en', {weekday: 'short'});
            
            return `
                <tr>
                    <td>
                        <div class="fw-medium">${Utils.formatDate(record.date)}</div>
                        <small class="text-muted">${dayName}</small>
                    </td>
                    <td>
                        <i class="fas fa-building me-1 text-muted"></i>
                        ${record.branch?.name || 'Main Branch'}
                    </td>
                    <td>
                        ${record.check_in_time ? `
                            <span class="text-success fw-medium">
                                <i class="fas fa-sign-in-alt me-1"></i>${this.formatTimeString(record.check_in_time)}
                            </span>
                            ${record.is_late ? '<br><small class="text-warning"><i class="fas fa-clock me-1"></i>Late</small>' : ''}
                        ` : '<span class="text-muted">--</span>'}
                    </td>
                    <td>
                        ${record.check_out_time ? `
                            <span class="text-danger fw-medium">
                                <i class="fas fa-sign-out-alt me-1"></i>${this.formatTimeString(record.check_out_time)}
                            </span>
                        ` : (record.check_in_time ? '<span class="badge bg-warning text-dark">Pending</span>' : '<span class="text-muted">--</span>')}
                    </td>
                    <td>
                        ${record.work_hours !== undefined && record.work_hours !== null ? `
                            <span class="fw-medium">${this.formatHours(record.work_hours)}</span>
                        ` : '<span class="text-muted">--</span>'}
                    </td>
                    <td>
                        ${this.renderStatusBadge(record)}
                    </td>
                    <td>
                        ${record.check_in_notes || record.check_out_notes ? `
                            <a href="#" class="text-info" data-bs-toggle="tooltip" 
                               title="${this.escapeHtml(record.check_in_notes || record.check_out_notes)}">
                                <i class="fas fa-sticky-note"></i>
                            </a>
                        ` : '<span class="text-muted">--</span>'}
                    </td>
                    <td>
                        ${this.renderActions(record)}
                    </td>
                </tr>
            `;
        }).join('');
        
        // Reinitialize tooltips
        const tooltipTriggerList = [].slice.call(tbody.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },
    
    updatePagination() {
        const paginationNav = document.getElementById('pagination-nav');
        const pagination = document.getElementById('pagination');
        
        if (this.totalPages <= 1) {
            paginationNav.style.display = 'none';
            return;
        }
        
        paginationNav.style.display = 'block';
        
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `
            <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); attendanceData.loadAttendanceRecords(${this.currentPage - 1}); return false;">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            </li>
        `;
        
        // Page numbers
        let startPage = Math.max(1, this.currentPage - 2);
        let endPage = Math.min(this.totalPages, this.currentPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="event.preventDefault(); attendanceData.loadAttendanceRecords(1); return false;">1</a>
                </li>
            `;
            if (startPage > 2) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); attendanceData.loadAttendanceRecords(${i}); return false;">${i}</a>
                </li>
            `;
        }
        
        if (endPage < this.totalPages) {
            if (endPage < this.totalPages - 1) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="event.preventDefault(); attendanceData.loadAttendanceRecords(${this.totalPages}); return false;">${this.totalPages}</a>
                </li>
            `;
        }
        
        // Next button
        paginationHtml += `
            <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); attendanceData.loadAttendanceRecords(${this.currentPage + 1}); return false;">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
        
        pagination.innerHTML = paginationHtml;
    },
    
    applyFilters(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        this.filters = Object.fromEntries(formData.entries());
        
        // Remove empty values
        Object.keys(this.filters).forEach(key => {
            if (!this.filters[key]) delete this.filters[key];
        });
        
        this.currentPage = 1;
        this.loadAttendanceRecords();
    },
    
    resetFilters() {
        document.getElementById('filterForm').reset();
        this.initializeDateFilters();
        this.filters = {};
        this.currentPage = 1;
        this.loadAttendanceRecords();
    },
    
    async viewDetails(recordId) {
        try {
            const modalContent = document.getElementById('attendance-detail-content');
            modalContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            const modal = new bootstrap.Modal(document.getElementById('attendanceDetailModal'));
            modal.show();
            
            const response = await API.get(`/employee/attendance/${recordId}`);
            const record = response.data || {};
            
            modalContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="border-bottom pb-2"><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Date:</th>
                                <td>${Utils.formatDate(record.date)}</td>
                            </tr>
                            <tr>
                                <th>Branch:</th>
                                <td><i class="fas fa-building me-1"></i>${record.branch?.name || 'Main Branch'}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>${this.renderStatusBadge(record)}</td>
                            </tr>
                            <tr>
                                <th>Work Hours:</th>
                                <td>${record.work_hours ? this.formatHours(record.work_hours) : '--'}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="border-bottom pb-2"><i class="fas fa-clock me-2"></i>Time Records</h6>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Check In:</th>
                                <td>${record.check_in_time ? `<span class="text-success"><i class="fas fa-sign-in-alt me-1"></i>${this.formatTimeString(record.check_in_time)}</span>` : '--'}</td>
                            </tr>
                            <tr>
                                <th>Check Out:</th>
                                <td>${record.check_out_time ? `<span class="text-danger"><i class="fas fa-sign-out-alt me-1"></i>${this.formatTimeString(record.check_out_time)}</span>` : '--'}</td>
                            </tr>
                            <tr>
                                <th>Late:</th>
                                <td>${record.is_late ? '<span class="text-warning"><i class="fas fa-exclamation-circle me-1"></i>Yes</span>' : '<span class="text-success"><i class="fas fa-check-circle me-1"></i>No</span>'}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                ${record.check_in_notes || record.check_out_notes ? `
                    <div class="mt-3">
                        <h6 class="border-bottom pb-2"><i class="fas fa-sticky-note me-2"></i>Notes</h6>
                        ${record.check_in_notes ? `<div class="mb-2"><strong>Check In:</strong> ${this.escapeHtml(record.check_in_notes)}</div>` : ''}
                        ${record.check_out_notes ? `<div class="mb-2"><strong>Check Out:</strong> ${this.escapeHtml(record.check_out_notes)}</div>` : ''}
                    </div>
                ` : ''}
                
                ${record.check_in_selfie_url || record.check_out_selfie_url ? `
                    <div class="mt-3">
                        <h6 class="border-bottom pb-2"><i class="fas fa-camera me-2"></i>Photos</h6>
                        <div class="row">
                            ${record.check_in_selfie_url ? `
                                <div class="col-md-6 text-center">
                                    <p class="mb-1"><strong>Check In Photo</strong></p>
                                    <img src="${record.check_in_selfie_url}" class="img-fluid rounded shadow-sm" style="max-height: 200px;" alt="Check-in photo">
                                </div>
                            ` : ''}
                            ${record.check_out_selfie_url ? `
                                <div class="col-md-6 text-center">
                                    <p class="mb-1"><strong>Check Out Photo</strong></p>
                                    <img src="${record.check_out_selfie_url}" class="img-fluid rounded shadow-sm" style="max-height: 200px;" alt="Check-out photo">
                                </div>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
            `;
            
        } catch (error) {
            const modalContent = document.getElementById('attendance-detail-content');
            modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load attendance details. Please try again.</div>';
            Utils.handleApiError(error, 'Failed to load attendance details');
        }
    },
    
    requestCorrection(recordId, date) {
        document.getElementById('correctionForm').reset();
        document.getElementById('correctionForm').setAttribute('data-record-id', recordId);
        document.getElementById('correctionForm').setAttribute('data-date', date);
        
        const modal = new bootstrap.Modal(document.getElementById('correctionModal'));
        modal.show();
    },
    
    toggleTimeFields(type) {
        const timeFields = document.getElementById('time-fields');
        timeFields.style.display = ['missing_checkin', 'missing_checkout', 'wrong_time'].includes(type) ? 'block' : 'none';
    },
    
    async submitCorrection(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const recordId = e.target.getAttribute('data-record-id');
            const date = e.target.getAttribute('data-date');
            
            const data = Object.fromEntries(formData.entries());
            data.attendance_id = recordId;
            data.date = date;
            
            const response = await API.post('/employee/corrections', data);
            
            Utils.showToast(response.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('correctionModal')).hide();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },
    
    canRequestCorrection(record) {
        // Can request correction for records from last 7 days
        const recordDate = new Date(record.date);
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
        return recordDate >= sevenDaysAgo;
    },
    
    formatHours(hours) {
        if (hours === null || hours === undefined || hours === 0) return '0h 0m';
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        return `${h}h ${m > 0 ? m + 'm' : ''}`;
    },
    
    formatTimeString(timeStr) {
        if (!timeStr) return '--';
        // Handle both HH:MM:SS and HH:MM formats
        const parts = timeStr.split(':');
        if (parts.length >= 2) {
            return `${parts[0]}:${parts[1]}`;
        }
        return timeStr;
    },
    
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    },
    
    getStatusBadgeClass(status) {
        const classes = {
            'present': 'bg-success',
            'late': 'bg-warning text-dark',
            'absent': 'bg-danger',
            'partial': 'bg-info',
            'half_day': 'bg-info'
        };
        return classes[status?.toLowerCase()] || 'bg-secondary';
    },
    
    renderStatusBadge(record) {
        const status = record.status || 'unknown';
        const statusText = {
            'present': 'Present',
            'late': 'Late',
            'absent': 'Absent',
            'partial': 'Partial',
            'half_day': 'Half Day'
        };
        const displayText = statusText[status.toLowerCase()] || status;
        const badgeClass = this.getStatusBadgeClass(status);
        return `<span class="badge ${badgeClass}">${displayText}</span>`;
    },

    // Render actions including Complete Checkout when applicable
    renderActions(record) {
        const actions = [];
        
        // View details button
        actions.push(`
            <button class="btn btn-sm btn-outline-primary" 
                    onclick="attendanceData.viewDetails('${record.id}')" 
                    title="View Details" 
                    data-bs-toggle="tooltip">
                <i class="fas fa-eye"></i>
            </button>
        `);
        
        // Request correction button
        if (this.canRequestCorrection(record)) {
            actions.push(`
                <button class="btn btn-sm btn-outline-warning" 
                        onclick="attendanceData.requestCorrection('${record.id}', '${record.date}')" 
                        title="Request Correction" 
                        data-bs-toggle="tooltip">
                    <i class="fas fa-edit"></i>
                </button>
            `);
        }
        
        // Complete checkout button
        if (this.shouldShowCompleteCheckout(record)) {
            actions.push(`
                <button class="btn btn-sm btn-outline-danger" 
                        title="Complete Checkout" 
                        data-bs-toggle="tooltip"
                        onclick="showMissingCheckoutModal({ 
                            id: '${record.id}', 
                            date: '${record.date}', 
                            check_in_time: '${record.check_in_time || ''}' 
                        })">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            `);
        }
        
        return `<div class="btn-group btn-group-sm" role="group">${actions.join('')}</div>`;
    },

    shouldShowCompleteCheckout(record) {
        if (!record.check_in_time || record.check_out_time) return false;
        // Show for previous days only (not today)
        const recDate = new Date(record.date + 'T00:00:00');
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        return recDate < today;
    },

    // Check missing checkouts and show banner
    async checkMissingCheckouts() {
        try {
            const response = await API.get('/employee/attendance/missing-checkouts');
            let missingCheckouts = [];
            
            // Handle different response structures
            if (response && response.data) {
                if (Array.isArray(response.data)) {
                    missingCheckouts = response.data;
                } else if (response.data.data && Array.isArray(response.data.data)) {
                    missingCheckouts = response.data.data;
                }
            }
            
            if (missingCheckouts.length > 0) {
                this.showMissingCheckoutBanner(missingCheckouts[0]);
            }
        } catch (error) {
            // Silent fail - don't show error for missing checkouts check
            console.debug('No missing checkouts or error checking:', error);
        }
    },

    showMissingCheckoutBanner(item) {
        const alertContainer = document.getElementById('missing-checkout-alert');
        if (!alertContainer) return;
        
        const formattedDate = Utils.formatDate(item.date);
        const formattedTime = this.formatTimeString(item.check_in_time);
        
        alertContainer.innerHTML = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="fw-bold">Pending Checkout Detected</div>
                        <div>You checked in on ${formattedDate} at ${formattedTime} but did not check out. Please complete your checkout to ensure accurate attendance records.</div>
                    </div>
                    <div class="ms-3">
                        <button class="btn btn-warning" 
                                onclick='showMissingCheckoutModal(${JSON.stringify({
                                    id: item.id.toString(), 
                                    date: item.date, 
                                    check_in_time: item.check_in_time
                                })})'>
                            <i class="fas fa-sign-out-alt me-1"></i> Complete Checkout
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    },
    
    // Helper method to refresh attendance data
    refresh() {
        this.loadAttendanceRecords(this.currentPage);
    }
};

// Export function
function exportAttendance() {
    const params = new URLSearchParams(attendanceData.filters);
    const url = `${API.baseURL}/employee/reports/export/attendance?${params}`;
    
    // Add authorization header via fetch then download
    fetch(url, {
        headers: {
            'Authorization': `Bearer ${API.token}`,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Export failed');
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `attendance_report_${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(error => {
        Utils.handleApiError(error, 'Failed to export attendance report');
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    attendanceData.init();
});

// Global function for missing checkout modal
window.refreshAttendanceStatus = function() {
    attendanceData.refresh();
};
</script>
@endpush
