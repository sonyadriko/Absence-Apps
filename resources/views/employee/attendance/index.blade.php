@extends('layouts.app')

@section('title', 'My Attendance Records')

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

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
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
        <div class="col-md-3">
            <div class="stats-card">
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
        <div class="col-md-3">
            <div class="stats-card">
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
        <div class="col-md-3">
            <div class="stats-card">
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
let attendanceData = {
    records: [],
    stats: {},
    currentPage: 1,
    totalPages: 1,
    filters: {},
    
    init() {
        this.loadAttendanceRecords();
        this.setupEventListeners();
        this.initializeDateFilters();
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
            const params = new URLSearchParams({
                page: page,
                ...this.filters
            });
            
            const response = await API.get(`/employee/attendance/history?${params}`);
            this.records = response.data.data || response.data;
            this.currentPage = response.data.current_page || 1;
            this.totalPages = response.data.last_page || 1;
            
            this.updateTable();
            this.updatePagination();
            
            // Load statistics
            await this.loadStatistics();
            
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load attendance records');
            document.getElementById('attendanceTableBody').innerHTML = 
                '<tr><td colspan="8" class="text-center text-muted py-4">Failed to load records</td></tr>';
        }
    },
    
    async loadStatistics() {
        try {
            const params = new URLSearchParams(this.filters);
            const response = await API.get(`/employee/attendance/stats?${params}`);
            this.stats = response.data;
            this.updateStats();
        } catch (error) {
            console.error('Failed to load statistics:', error);
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
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No attendance records found</td></tr>';
            return;
        }
        
        tbody.innerHTML = this.records.map(record => `
            <tr>
                <td>
                    <div class="fw-medium">${Utils.formatDate(record.date)}</div>
                    <small class="text-muted">${new Date(record.date).toLocaleDateString('en', {weekday: 'short'})}</small>
                </td>
                <td>
                    <i class="fas fa-building me-1 text-muted"></i>
                    ${record.branch?.name || 'Unknown'}
                </td>
                <td>
                    ${record.check_in_time ? `
                        <span class="text-success fw-medium">${Utils.formatTime(record.check_in_time)}</span>
                        ${record.is_late ? '<br><small class="text-warning"><i class="fas fa-clock me-1"></i>Late</small>' : ''}
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    ${record.check_out_time ? `
                        <span class="text-danger fw-medium">${Utils.formatTime(record.check_out_time)}</span>
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    ${record.work_hours ? `
                        <span class="fw-medium">${this.formatHours(record.work_hours)}</span>
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    <span class="badge ${this.getStatusBadgeClass(record.status)}">${record.status}</span>
                </td>
                <td>
                    ${record.check_in_notes || record.check_out_notes ? `
                        <i class="fas fa-sticky-note text-info" title="${record.check_in_notes || record.check_out_notes}"></i>
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    ${this.renderActions(record)}
                </td>
            </tr>
        `).join('');
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
                <a class="page-link" href="#" onclick="attendanceData.loadAttendanceRecords(${this.currentPage - 1})">Previous</a>
            </li>
        `;
        
        // Page numbers
        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage || i === 1 || i === this.totalPages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
                paginationHtml += `
                    <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="attendanceData.loadAttendanceRecords(${i})">${i}</a>
                    </li>
                `;
            } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Next button
        paginationHtml += `
            <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="attendanceData.loadAttendanceRecords(${this.currentPage + 1})">Next</a>
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
            const response = await API.get(`/employee/attendance/${recordId}`);
            const record = response.data;
            
            const modalContent = document.getElementById('attendance-detail-content');
            modalContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Basic Information</h6>
                        <table class="table table-sm">
                            <tr><th>Date:</th><td>${Utils.formatDate(record.date)}</td></tr>
                            <tr><th>Branch:</th><td>${record.branch?.name || 'Unknown'}</td></tr>
                            <tr><th>Status:</th><td><span class="badge ${this.getStatusBadgeClass(record.status)}">${record.status}</span></td></tr>
                            <tr><th>Work Hours:</th><td>${record.work_hours ? this.formatHours(record.work_hours) : '--'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Time Records</h6>
                        <table class="table table-sm">
                            <tr><th>Check In:</th><td>${record.check_in_time ? Utils.formatTime(record.check_in_time) : '--'}</td></tr>
                            <tr><th>Check Out:</th><td>${record.check_out_time ? Utils.formatTime(record.check_out_time) : '--'}</td></tr>
                            <tr><th>Late:</th><td>${record.is_late ? '<span class="text-warning">Yes</span>' : 'No'}</td></tr>
                        </table>
                    </div>
                </div>
                
                ${record.check_in_notes || record.check_out_notes ? `
                    <div class="mt-3">
                        <h6>Notes</h6>
                        ${record.check_in_notes ? `<p><strong>Check In:</strong> ${record.check_in_notes}</p>` : ''}
                        ${record.check_out_notes ? `<p><strong>Check Out:</strong> ${record.check_out_notes}</p>` : ''}
                    </div>
                ` : ''}
                
                ${record.check_in_selfie || record.check_out_selfie ? `
                    <div class="mt-3">
                        <h6>Photos</h6>
                        <div class="row">
                            ${record.check_in_selfie ? `
                                <div class="col-md-6">
                                    <p><strong>Check In Photo:</strong></p>
                                    <img src="${record.check_in_selfie_url}" class="img-fluid rounded" style="max-height: 200px;">
                                </div>
                            ` : ''}
                            ${record.check_out_selfie ? `
                                <div class="col-md-6">
                                    <p><strong>Check Out Photo:</strong></p>
                                    <img src="${record.check_out_selfie_url}" class="img-fluid rounded" style="max-height: 200px;">
                                </div>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('attendanceDetailModal'));
            modal.show();
            
        } catch (error) {
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
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        return `${h}h ${m}m`;
    },
    
    getStatusBadgeClass(status) {
        const classes = {
            'present': 'bg-success',
            'late': 'bg-warning text-dark',
            'absent': 'bg-danger',
            'partial': 'bg-info'
        };
        return classes[status] || 'bg-secondary';
    },

    // New: Render actions including Complete Checkout when applicable
    renderActions(record) {
        const actions = [];
        actions.push(`
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" onclick="attendanceData.viewDetails('${record.id}')" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                ${this.canRequestCorrection(record) ? `
                    <button class="btn btn-outline-warning" onclick="attendanceData.requestCorrection('${record.id}', '${record.date}')" title="Request Correction">
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ''}
                ${this.shouldShowCompleteCheckout(record) ? `
                    <button class="btn btn-outline-danger" title="Complete Checkout" onclick="showMissingCheckoutModal({ id: '${record.id}', date: '${record.date}', check_in_time: '${record.check_in_time || ''}' })">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                ` : ''}
            </div>
        `);
        return actions.join('');
    },

    shouldShowCompleteCheckout(record) {
        if (!record.check_in_time || record.check_out_time) return false;
        // Show for previous days only (not today)
        const recDate = new Date(record.date + 'T00:00:00');
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        return recDate < today;
    },

    // New: Check missing checkouts and show banner
    async checkMissingCheckouts() {
        try {
            const response = await API.get('/employee/attendance/missing-checkouts');
            const list = (response && response.data && Array.isArray(response.data)) ? response.data : (response.data && response.data.data ? response.data.data : []);
            if (list && list.length > 0) {
                this.showMissingCheckoutBanner(list[0]);
            }
        } catch (e) {
            // silent fail
        }
    },

    showMissingCheckoutBanner(item) {
        const container = document.querySelector('.container-fluid');
        const banner = document.createElement('div');
        banner.className = 'alert alert-warning d-flex align-items-center';
        banner.innerHTML = `
            <i class="fas fa-exclamation-triangle me-3"></i>
            <div class="flex-grow-1">
                <div class="fw-bold">Pending Checkout Detected</div>
                <div class="small">You checked in on ${item.date} at ${item.check_in_time} but did not check out. Please complete it now.</div>
            </div>
            <button class="btn btn-sm btn-warning ms-3" onclick='showMissingCheckoutModal(${JSON.stringify({id: '${item.id}', date: item.date, check_in_time: item.check_in_time})})'>
                <i class="fas fa-sign-out-alt me-1"></i> Complete Now
            </button>
        `;
        container.insertBefore(banner, container.firstChild);
    }
};

// Export function
function exportAttendance() {
    const params = new URLSearchParams(attendanceData.filters);
    window.open(`/api/employee/reports/export/attendance?${params}`, '_blank');
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    attendanceData.init();
});
</script>
@endpush
