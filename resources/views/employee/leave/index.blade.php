@extends('layouts.app')

@section('title', 'Leave Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-times me-2"></i>Leave Management
            </h1>
            <p class="text-muted mb-0">Manage your leave requests and view balances</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-outline-primary" id="leave-policy-btn">
                <i class="fas fa-info-circle me-2"></i>Leave Policy
            </button>
            <button class="btn btn-primary" id="new-leave-btn">
                <i class="fas fa-plus me-2"></i>New Request
            </button>
        </div>
    </div>

    <!-- Leave Balance Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-success" id="annual-balance">0</div>
                        <div class="text-muted small">Annual Leave</div>
                    </div>
                    <div class="fs-2 text-success">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info" id="sick-balance">0</div>
                        <div class="text-muted small">Sick Leave</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-warning" id="personal-balance">0</div>
                        <div class="text-muted small">Personal Leave</div>
                    </div>
                    <div class="fs-2 text-warning">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-primary" id="total-used">0</div>
                        <div class="text-muted small">Used This Year</div>
                    </div>
                    <div class="fs-2 text-primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="leaveFilterForm" class="row g-3">
                <div class="col-md-2">
                    <label for="year-filter" class="form-label">Year</label>
                    <select class="form-select" id="year-filter" name="year">
                        <!-- Will be populated dynamically -->
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status-filter" class="form-label">Status</label>
                    <select class="form-select" id="status-filter" name="status">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type-filter" class="form-label">Leave Type</label>
                    <select class="form-select" id="type-filter" name="type">
                        <option value="">All Types</option>
                        <option value="annual">Annual Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="personal">Personal Leave</option>
                        <option value="maternity">Maternity Leave</option>
                        <option value="paternity">Paternity Leave</option>
                        <option value="emergency">Emergency Leave</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-leave-filter">
                        <i class="fas fa-undo me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Leave Requests Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Leave Requests
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="leaveTable">
                    <thead>
                        <tr>
                            <th>Request Date</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leaveTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav id="leave-pagination-nav" class="mt-3" style="display: none;">
                <ul class="pagination justify-content-center" id="leave-pagination">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- New Leave Request Modal -->
<div class="modal fade" id="newLeaveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>New Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newLeaveForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="leave_type" class="form-label">Leave Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="leave_type" name="leave_type" required>
                                    <option value="">Select leave type</option>
                                    <option value="annual">Annual Leave</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="personal">Personal Leave</option>
                                    <option value="maternity">Maternity Leave</option>
                                    <option value="paternity">Paternity Leave</option>
                                    <option value="emergency">Emergency Leave</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duration_type" class="form-label">Duration Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="duration_type" name="duration_type" required>
                                    <option value="full_day">Full Day</option>
                                    <option value="half_day">Half Day</option>
                                    <option value="hourly">Hourly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div id="time-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" 
                                  placeholder="Please provide a reason for your leave request" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="supporting_document" class="form-label">Supporting Document</label>
                        <input type="file" class="form-control" id="supporting_document" name="supporting_document" 
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div class="form-text">Optional: Upload medical certificate, invitation letter, etc. (Max 5MB)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="emergency_contact" class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" 
                               placeholder="Name and phone number">
                        <div class="form-text">Required for extended leaves</div>
                    </div>
                    
                    <!-- Leave Balance Info -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Current Leave Balance</h6>
                        <div class="row">
                            <div class="col-4">Annual: <span id="balance-annual">0</span> days</div>
                            <div class="col-4">Sick: <span id="balance-sick">0</span> days</div>
                            <div class="col-4">Personal: <span id="balance-personal">0</span> days</div>
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

<!-- Leave Details Modal -->
<div class="modal fade" id="leaveDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Leave Request Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="leave-detail-content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Leave Policy Modal -->
<div class="modal fade" id="leavePolicyModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-gavel me-2"></i>Leave Policy
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Annual Leave</h6>
                        <ul class="list-unstyled ps-3">
                            <li><i class="fas fa-check text-success me-2"></i>12 days per year for new employees</li>
                            <li><i class="fas fa-check text-success me-2"></i>15 days after 2 years of service</li>
                            <li><i class="fas fa-check text-success me-2"></i>18 days after 5 years of service</li>
                            <li><i class="fas fa-check text-success me-2"></i>Must be requested 2 weeks in advance</li>
                            <li><i class="fas fa-check text-success me-2"></i>Maximum 5 consecutive days without approval</li>
                        </ul>
                        
                        <h6 class="mt-4">Sick Leave</h6>
                        <ul class="list-unstyled ps-3">
                            <li><i class="fas fa-check text-info me-2"></i>12 days per year</li>
                            <li><i class="fas fa-check text-info me-2"></i>Medical certificate required for 3+ days</li>
                            <li><i class="fas fa-check text-info me-2"></i>Must notify supervisor within 2 hours</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Personal Leave</h6>
                        <ul class="list-unstyled ps-3">
                            <li><i class="fas fa-check text-warning me-2"></i>5 days per year</li>
                            <li><i class="fas fa-check text-warning me-2"></i>For personal matters, family events</li>
                            <li><i class="fas fa-check text-warning me-2"></i>1 week advance notice preferred</li>
                        </ul>
                        
                        <h6 class="mt-4">Special Leave</h6>
                        <ul class="list-unstyled ps-3">
                            <li><i class="fas fa-check text-primary me-2"></i>Maternity: 90 days</li>
                            <li><i class="fas fa-check text-primary me-2"></i>Paternity: 3 days</li>
                            <li><i class="fas fa-check text-primary me-2"></i>Emergency: As needed (unpaid)</li>
                            <li><i class="fas fa-check text-primary me-2"></i>Bereavement: 3-5 days</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const leavePage = {
    state: {
        filters: {},
        currentPage: 1,
        totalPages: 1,
        leaves: [],
        balances: {}
    },

    init() {
        this.setupEvents();
        this.initYearFilter();
        this.loadLeaveBalances();
        this.loadLeaveRequests();
    },

    setupEvents() {
        document.getElementById('new-leave-btn').addEventListener('click', () => this.openNewLeaveModal());
        document.getElementById('leave-policy-btn').addEventListener('click', () => this.openPolicyModal());
        document.getElementById('leaveFilterForm').addEventListener('submit', (e) => this.applyFilters(e));
        document.getElementById('reset-leave-filter').addEventListener('click', () => this.resetFilters());
        document.getElementById('newLeaveForm').addEventListener('submit', (e) => this.submitLeaveRequest(e));
        document.getElementById('duration_type').addEventListener('change', (e) => this.toggleTimeFields(e.target.value));
        document.getElementById('start_date').addEventListener('change', () => this.updateEndDateMin());
        document.getElementById('end_date').addEventListener('change', () => this.validateDateRange());
    },

    initYearFilter() {
        const yearSelect = document.getElementById('year-filter');
        const currentYear = new Date().getFullYear();
        for (let i = currentYear - 2; i <= currentYear + 1; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            option.selected = i === currentYear;
            yearSelect.appendChild(option);
        }
    },

    async loadLeaveBalances() {
        try {
            const year = document.getElementById('year-filter').value || new Date().getFullYear();
            const response = await API.get(`/employee/leave/balance?year=${year}`);
            this.state.balances = response.data;
            this.updateBalanceDisplay();
        } catch (error) {
            console.error('Failed to load leave balances:', error);
        }
    },

    updateBalanceDisplay() {
        const balances = this.state.balances;
        document.getElementById('annual-balance').textContent = balances.annual || 0;
        document.getElementById('sick-balance').textContent = balances.sick || 0;
        document.getElementById('personal-balance').textContent = balances.personal || 0;
        document.getElementById('total-used').textContent = balances.total_used || 0;
        
        // Update modal balance info
        document.getElementById('balance-annual').textContent = balances.annual || 0;
        document.getElementById('balance-sick').textContent = balances.sick || 0;
        document.getElementById('balance-personal').textContent = balances.personal || 0;
    },

    async loadLeaveRequests(page = 1) {
        try {
            const params = new URLSearchParams({
                page: page,
                ...this.state.filters
            });
            
            const response = await API.get(`/employee/leave?${params}`);
            this.state.leaves = response.data.data || response.data;
            this.state.currentPage = response.data.current_page || 1;
            this.state.totalPages = response.data.last_page || 1;
            
            this.updateLeaveTable();
            this.updatePagination();
            
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load leave requests');
            document.getElementById('leaveTableBody').innerHTML = 
                '<tr><td colspan="7" class="text-center text-muted py-4">Failed to load requests</td></tr>';
        }
    },

    updateLeaveTable() {
        const tbody = document.getElementById('leaveTableBody');
        
        if (!this.state.leaves || this.state.leaves.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No leave requests found</td></tr>';
            return;
        }
        
        tbody.innerHTML = this.state.leaves.map(leave => `
            <tr>
                <td>
                    <div class="fw-medium">${Utils.formatDate(leave.created_at)}</div>
                    <small class="text-muted">${Utils.formatTime(leave.created_at)}</small>
                </td>
                <td>
                    <span class="badge ${this.getLeaveTypeBadge(leave.leave_type)}">${this.formatLeaveType(leave.leave_type)}</span>
                    <br><small class="text-muted">${leave.duration_type.replace('_', ' ')}</small>
                </td>
                <td>
                    <div class="fw-medium">${Utils.formatDate(leave.start_date)}</div>
                    ${leave.start_time ? `<small class="text-muted">${Utils.formatTime(leave.start_time)}</small>` : ''}
                </td>
                <td>
                    <div class="fw-medium">${Utils.formatDate(leave.end_date)}</div>
                    ${leave.end_time ? `<small class="text-muted">${Utils.formatTime(leave.end_time)}</small>` : ''}
                </td>
                <td>
                    <span class="fw-medium">${leave.total_days}</span>
                    <small class="text-muted">day${leave.total_days > 1 ? 's' : ''}</small>
                </td>
                <td>
                    <span class="badge ${this.getStatusBadge(leave.status)}">${leave.status}</span>
                    ${leave.approved_by ? `<br><small class="text-muted">by ${leave.approver?.name}</small>` : ''}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="leavePage.viewDetails('${leave.id}')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${leave.status === 'pending' ? `
                            <button class="btn btn-outline-warning" onclick="leavePage.editRequest('${leave.id}')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="leavePage.cancelRequest('${leave.id}')" title="Cancel">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    },

    updatePagination() {
        const paginationNav = document.getElementById('leave-pagination-nav');
        const pagination = document.getElementById('leave-pagination');
        
        if (this.state.totalPages <= 1) {
            paginationNav.style.display = 'none';
            return;
        }
        
        paginationNav.style.display = 'block';
        
        let html = '';
        
        // Previous button
        html += `
            <li class="page-item ${this.state.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="leavePage.loadLeaveRequests(${this.state.currentPage - 1})">Previous</a>
            </li>
        `;
        
        // Page numbers
        for (let i = 1; i <= this.state.totalPages; i++) {
            if (i === this.state.currentPage || i === 1 || i === this.state.totalPages || 
                (i >= this.state.currentPage - 2 && i <= this.state.currentPage + 2)) {
                html += `
                    <li class="page-item ${i === this.state.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="leavePage.loadLeaveRequests(${i})">${i}</a>
                    </li>
                `;
            } else if (i === this.state.currentPage - 3 || i === this.state.currentPage + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Next button
        html += `
            <li class="page-item ${this.state.currentPage === this.state.totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="leavePage.loadLeaveRequests(${this.state.currentPage + 1})">Next</a>
            </li>
        `;
        
        pagination.innerHTML = html;
    },

    openNewLeaveModal() {
        document.getElementById('newLeaveForm').reset();
        document.getElementById('time-fields').style.display = 'none';
        this.setMinimumDates();
        new bootstrap.Modal(document.getElementById('newLeaveModal')).show();
    },

    openPolicyModal() {
        new bootstrap.Modal(document.getElementById('leavePolicyModal')).show();
    },

    setMinimumDates() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').min = today;
        document.getElementById('end_date').min = today;
    },

    updateEndDateMin() {
        const startDate = document.getElementById('start_date').value;
        if (startDate) {
            document.getElementById('end_date').min = startDate;
            document.getElementById('end_date').value = startDate; // Default to same day
        }
    },

    validateDateRange() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
            document.getElementById('end_date').value = startDate;
        }
    },

    toggleTimeFields(durationType) {
        const timeFields = document.getElementById('time-fields');
        timeFields.style.display = durationType === 'hourly' ? 'block' : 'none';
        
        if (durationType === 'hourly') {
            document.getElementById('start_time').required = true;
            document.getElementById('end_time').required = true;
        } else {
            document.getElementById('start_time').required = false;
            document.getElementById('end_time').required = false;
        }
    },

    async submitLeaveRequest(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const response = await API.postForm('/employee/leave', formData);
            
            Utils.showToast(response.message || 'Leave request submitted successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('newLeaveModal')).hide();
            this.loadLeaveRequests();
            this.loadLeaveBalances();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },

    async viewDetails(leaveId) {
        try {
            const response = await API.get(`/employee/leave/${leaveId}`);
            const leave = response.data;
            
            const content = document.getElementById('leave-detail-content');
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Request Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th>Leave Type:</th><td><span class="badge ${this.getLeaveTypeBadge(leave.leave_type)}">${this.formatLeaveType(leave.leave_type)}</span></td></tr>
                            <tr><th>Duration:</th><td>${leave.duration_type.replace('_', ' ')}</td></tr>
                            <tr><th>Start Date:</th><td>${Utils.formatDate(leave.start_date)} ${leave.start_time ? Utils.formatTime(leave.start_time) : ''}</td></tr>
                            <tr><th>End Date:</th><td>${Utils.formatDate(leave.end_date)} ${leave.end_time ? Utils.formatTime(leave.end_time) : ''}</td></tr>
                            <tr><th>Total Days:</th><td>${leave.total_days} day${leave.total_days > 1 ? 's' : ''}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Status & Approval</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th>Status:</th><td><span class="badge ${this.getStatusBadge(leave.status)}">${leave.status}</span></td></tr>
                            <tr><th>Submitted:</th><td>${Utils.formatDateTime(leave.created_at)}</td></tr>
                            ${leave.approved_by ? `
                                <tr><th>Approved By:</th><td>${leave.approver?.name}</td></tr>
                                <tr><th>Approved At:</th><td>${Utils.formatDateTime(leave.approved_at)}</td></tr>
                            ` : ''}
                            ${leave.rejected_by ? `
                                <tr><th>Rejected By:</th><td>${leave.rejector?.name}</td></tr>
                                <tr><th>Rejected At:</th><td>${Utils.formatDateTime(leave.rejected_at)}</td></tr>
                            ` : ''}
                        </table>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6>Reason</h6>
                    <p class="bg-light p-3 rounded">${leave.reason}</p>
                </div>
                
                ${leave.rejection_reason ? `
                    <div class="mt-3">
                        <h6>Rejection Reason</h6>
                        <p class="bg-danger bg-opacity-10 p-3 rounded text-danger">${leave.rejection_reason}</p>
                    </div>
                ` : ''}
                
                ${leave.emergency_contact ? `
                    <div class="mt-3">
                        <h6>Emergency Contact</h6>
                        <p>${leave.emergency_contact}</p>
                    </div>
                ` : ''}
                
                ${leave.supporting_document ? `
                    <div class="mt-3">
                        <h6>Supporting Document</h6>
                        <a href="${leave.supporting_document_url}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-2"></i>Download Document
                        </a>
                    </div>
                ` : ''}
            `;
            
            new bootstrap.Modal(document.getElementById('leaveDetailModal')).show();
            
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load leave details');
        }
    },

    async cancelRequest(leaveId) {
        if (await Utils.confirm('Are you sure you want to cancel this leave request?', 'Cancel Leave Request')) {
            try {
                await API.put(`/employee/leave/${leaveId}/cancel`);
                Utils.showToast('Leave request cancelled successfully', 'success');
                this.loadLeaveRequests();
                this.loadLeaveBalances();
            } catch (error) {
                Utils.handleApiError(error, 'Failed to cancel leave request');
            }
        }
    },

    applyFilters(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        this.state.filters = Object.fromEntries(formData.entries());
        
        // Remove empty values
        Object.keys(this.state.filters).forEach(key => {
            if (!this.state.filters[key]) delete this.state.filters[key];
        });
        
        this.state.currentPage = 1;
        this.loadLeaveRequests();
        this.loadLeaveBalances();
    },

    resetFilters() {
        document.getElementById('leaveFilterForm').reset();
        this.initYearFilter();
        this.state.filters = {};
        this.state.currentPage = 1;
        this.loadLeaveRequests();
        this.loadLeaveBalances();
    },

    // Utility functions
    getLeaveTypeBadge(type) {
        const badges = {
            'annual': 'bg-success',
            'sick': 'bg-info',
            'personal': 'bg-warning text-dark',
            'maternity': 'bg-primary',
            'paternity': 'bg-primary',
            'emergency': 'bg-danger'
        };
        return badges[type] || 'bg-secondary';
    },

    formatLeaveType(type) {
        const types = {
            'annual': 'Annual Leave',
            'sick': 'Sick Leave',
            'personal': 'Personal Leave',
            'maternity': 'Maternity Leave',
            'paternity': 'Paternity Leave',
            'emergency': 'Emergency Leave'
        };
        return types[type] || type;
    },

    getStatusBadge(status) {
        const badges = {
            'pending': 'bg-warning text-dark',
            'approved': 'bg-success',
            'rejected': 'bg-danger',
            'cancelled': 'bg-secondary'
        };
        return badges[status] || 'bg-secondary';
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    leavePage.init();
});
</script>

<style>
.stats-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 1rem;
}
</style>
@endpush
