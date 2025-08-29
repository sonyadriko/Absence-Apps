@extends('layouts.app')

@section('title', 'Leave Management - HR Central')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-times me-2"></i>Leave Management
            </h1>
            <p class="text-muted mb-0">Manage all employee leave requests across all branches</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-outline-info" id="refresh-leaves">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
            <button class="btn btn-outline-primary" id="export-leaves">
                <i class="fas fa-download me-2"></i>Export
            </button>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-primary" id="total-requests">-</div>
                        <div class="text-muted small">Total Requests</div>
                    </div>
                    <div class="fs-2 text-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-warning" id="pending-requests">-</div>
                        <div class="text-muted small">Pending</div>
                    </div>
                    <div class="fs-2 text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-success" id="approved-requests">-</div>
                        <div class="text-muted small">Approved</div>
                    </div>
                    <div class="fs-2 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-danger" id="rejected-requests">-</div>
                        <div class="text-muted small">Rejected</div>
                    </div>
                    <div class="fs-2 text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info" id="total-days">-</div>
                        <div class="text-muted small">Total Days</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-secondary" id="approval-rate">-%</div>
                        <div class="text-muted small">Approval Rate</div>
                    </div>
                    <div class="fs-2 text-secondary">
                        <i class="fas fa-percentage"></i>
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
                    <label for="branch-filter" class="form-label">Branch</label>
                    <select class="form-select" id="branch-filter" name="branch_id">
                        <option value="">All Branches</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status-filter" class="form-label">Status</label>
                    <select class="form-select" id="status-filter" name="status">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="type-filter" class="form-label">Leave Type</label>
                    <select class="form-select" id="type-filter" name="leave_type">
                        <option value="">All Types</option>
                        <option value="annual">Annual Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="personal">Personal Leave</option>
                        <option value="maternity">Maternity Leave</option>
                        <option value="paternity">Paternity Leave</option>
                        <option value="emergency">Emergency Leave</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start-date-filter" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start-date-filter" name="start_date">
                </div>
                <div class="col-md-2">
                    <label for="end-date-filter" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end-date-filter" name="end_date">
                </div>
                <div class="col-md-2 d-flex align-items-end">
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

    <!-- Leave Requests Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>All Leave Requests
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="leaveTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Branch</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leaveTableBody">
                        <tr>
                            <td colspan="10" class="text-center py-4">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <div id="approval-actions">
                    <!-- Approval buttons will be added dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check me-2"></i>Approve Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approval-notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea class="form-control" id="approval-notes" name="notes" rows="3" 
                                  placeholder="Add any notes or comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times me-2"></i>Reject Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection-reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection-reason" name="reason" rows="3" 
                                  placeholder="Please provide reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const hrLeaveManager = {
    state: {
        filters: {},
        currentPage: 1,
        totalPages: 1,
        leaves: [],
        summary: {},
        currentLeaveId: null
    },

    init() {
        this.setupEvents();
        this.loadBranches();
        this.loadLeaveRequests();
        this.loadSummaryStats();
    },

    setupEvents() {
        document.getElementById('refresh-leaves').addEventListener('click', () => {
            this.loadLeaveRequests();
            this.loadSummaryStats();
        });
        document.getElementById('export-leaves').addEventListener('click', () => this.exportLeaves());
        document.getElementById('leaveFilterForm').addEventListener('submit', (e) => this.applyFilters(e));
        document.getElementById('reset-filter').addEventListener('click', () => this.resetFilters());
        document.getElementById('approvalForm').addEventListener('submit', (e) => this.submitApproval(e));
        document.getElementById('rejectionForm').addEventListener('submit', (e) => this.submitRejection(e));
    },

    async loadBranches() {
        try {
            const response = await API.get('/hr-central/branches');
            const branches = response.data;
            
            const branchSelect = document.getElementById('branch-filter');
            branchSelect.innerHTML = '<option value="">All Branches</option>';
            
            branches.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.id;
                option.textContent = branch.name;
                branchSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load branches:', error);
        }
    },

    async loadSummaryStats() {
        try {
            // Using existing reports API
            const response = await API.get('/reports/leave');
            const data = response.data;
            
            if (data && data.summary) {
                this.updateSummaryStats(data.summary);
            }
        } catch (error) {
            console.error('Failed to load summary stats:', error);
        }
    },

    updateSummaryStats(summary) {
        document.getElementById('total-requests').textContent = summary.total_requests || 0;
        document.getElementById('pending-requests').textContent = summary.pending_count || 0;
        document.getElementById('approved-requests').textContent = summary.approved_count || 0;
        document.getElementById('rejected-requests').textContent = summary.rejected_count || 0;
        document.getElementById('total-days').textContent = summary.total_days || 0;
        document.getElementById('approval-rate').textContent = (summary.approval_rate || 0) + '%';
    },

    async loadLeaveRequests(page = 1) {
        try {
            Utils.showLoading('leaveTableBody');
            
            const params = new URLSearchParams({
                page: page,
                per_page: 20,
                ...this.state.filters
            });
            
            // For HR Central, we'll use a different endpoint that shows all employees' requests
            const response = await API.get(`/reports/leave?${params}`);
            const data = response.data;
            
            this.state.leaves = data.records.data || [];
            this.state.currentPage = data.records.current_page || 1;
            this.state.totalPages = data.records.last_page || 1;
            
            this.updateLeaveTable();
            this.updatePagination();
            
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load leave requests');
            document.getElementById('leaveTableBody').innerHTML = 
                '<tr><td colspan="10" class="text-center text-muted py-4">Failed to load requests</td></tr>';
        } finally {
            Utils.hideLoading('leaveTableBody');
        }
    },

    updateLeaveTable() {
        const tbody = document.getElementById('leaveTableBody');
        
        if (!this.state.leaves || this.state.leaves.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4">No leave requests found</td></tr>';
            return;
        }
        
        tbody.innerHTML = this.state.leaves.map(leave => `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div>
                            <div class="fw-medium">${leave.user.name}</div>
                            <small class="text-muted">${leave.user.employee_id || 'N/A'}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-info">${leave.user.branch ? leave.user.branch.name : 'N/A'}</span>
                </td>
                <td>
                    <span class="badge ${this.getLeaveTypeBadge(leave.leave_type)}">
                        ${this.formatLeaveType(leave.leave_type)}
                    </span>
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
                    <span class="badge ${this.getStatusBadge(leave.status)}">${this.formatStatus(leave.status)}</span>
                </td>
                <td>
                    ${this.renderProgress(leave)}
                </td>
                <td>
                    <div class="fw-medium">${Utils.formatDate(leave.created_at)}</div>
                    <small class="text-muted">${Utils.formatTime(leave.created_at)}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="hrLeaveManager.viewDetails('${leave.id}')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${this.renderActionButtons(leave)}
                    </div>
                </td>
            </tr>
        `).join('');
    },

    renderActionButtons(leave) {
        const canApprove = leave.status === 'pending';
        if (!canApprove) return '';
        
        return `
            <button class="btn btn-outline-success" onclick="hrLeaveManager.approveRequest('${leave.id}')" title="Approve">
                <i class="fas fa-check"></i>
            </button>
            <button class="btn btn-outline-danger" onclick="hrLeaveManager.rejectRequest('${leave.id}')" title="Reject">
                <i class="fas fa-times"></i>
            </button>
        `;
    },

    renderProgress(leave) {
        const progress = leave.approval_progress || 0;
        const currentStep = leave.current_step || 'Unknown status';
        
        return `
            <div class="progress-container">
                <div class="progress mb-1" style="height: 8px;">
                    <div class="progress-bar ${this.getProgressColor(progress)}" 
                         role="progressbar" 
                         style="width: ${progress}%" 
                         aria-valuenow="${progress}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
                <small class="text-muted" title="${currentStep}">${progress}% Complete</small>
            </div>
        `;
    },

    async viewDetails(leaveId) {
        try {
            const response = await API.get(`/employee/leave/${leaveId}`);
            const leave = response.data;
            
            this.state.currentLeaveId = leaveId;
            
            const content = document.getElementById('leave-detail-content');
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Employee Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th>Name:</th><td>${leave.user?.name || 'N/A'}</td></tr>
                            <tr><th>Employee ID:</th><td>${leave.user?.employee_id || 'N/A'}</td></tr>
                            <tr><th>Branch:</th><td>${leave.user?.branch?.name || 'N/A'}</td></tr>
                            <tr><th>Email:</th><td>${leave.user?.email || 'N/A'}</td></tr>
                        </table>
                        
                        <h6 class="mt-4">Request Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th>Leave Type:</th><td><span class="badge ${this.getLeaveTypeBadge(leave.leave_type)}">${this.formatLeaveType(leave.leave_type)}</span></td></tr>
                            <tr><th>Duration:</th><td>${leave.duration_type?.replace('_', ' ')}</td></tr>
                            <tr><th>Start Date:</th><td>${Utils.formatDate(leave.start_date)} ${leave.start_time ? Utils.formatTime(leave.start_time) : ''}</td></tr>
                            <tr><th>End Date:</th><td>${Utils.formatDate(leave.end_date)} ${leave.end_time ? Utils.formatTime(leave.end_time) : ''}</td></tr>
                            <tr><th>Total Days:</th><td>${leave.total_days} day${leave.total_days > 1 ? 's' : ''}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Status & Approval</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th>Status:</th><td><span class="badge ${this.getStatusBadge(leave.status)}">${this.formatStatus(leave.status)}</span></td></tr>
                            <tr><th>Progress:</th><td>${leave.approval_progress || 0}%</td></tr>
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
                
                ${leave.approval_timeline ? `
                    <div class="mt-4">
                        <h6>Approval Timeline</h6>
                        <div class="timeline">
                            ${leave.approval_timeline.map(step => `
                                <div class="timeline-item ${step.is_current ? 'active' : ''} ${step.status}">
                                    <div class="timeline-marker">
                                        <i class="fas ${step.icon}"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="fw-medium">${step.title}</div>
                                        <small class="text-muted">Status: ${step.status}</small>
                                        ${step.approved_at ? `<br><small>Approved: ${Utils.formatDateTime(step.approved_at)}</small>` : ''}
                                        ${step.notes ? `<br><small>Notes: ${step.notes}</small>` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            `;
            
            // Update approval actions
            const approvalActions = document.getElementById('approval-actions');
            if (leave.status === 'pending') {
                approvalActions.innerHTML = `
                    <button type="button" class="btn btn-success" onclick="hrLeaveManager.approveRequest('${leave.id}')">
                        <i class="fas fa-check me-2"></i>Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="hrLeaveManager.rejectRequest('${leave.id}')">
                        <i class="fas fa-times me-2"></i>Reject
                    </button>
                `;
            } else {
                approvalActions.innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('leaveDetailModal')).show();
            
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load leave details');
        }
    },

    async approveRequest(leaveId) {
        this.state.currentLeaveId = leaveId;
        new bootstrap.Modal(document.getElementById('approvalModal')).show();
    },

    async rejectRequest(leaveId) {
        this.state.currentLeaveId = leaveId;
        new bootstrap.Modal(document.getElementById('rejectionModal')).show();
    },

    async submitApproval(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const response = await API.post(`/approvals/${this.state.currentLeaveId}/approve`, {
                notes: formData.get('notes')
            });
            
            Utils.showToast(response.message || 'Leave request approved successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('approvalModal')).hide();
            bootstrap.Modal.getInstance(document.getElementById('leaveDetailModal')).hide();
            
            this.loadLeaveRequests();
            this.loadSummaryStats();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },

    async submitRejection(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const response = await API.post(`/approvals/${this.state.currentLeaveId}/reject`, {
                reason: formData.get('reason')
            });
            
            Utils.showToast(response.message || 'Leave request rejected', 'success');
            bootstrap.Modal.getInstance(document.getElementById('rejectionModal')).hide();
            bootstrap.Modal.getInstance(document.getElementById('leaveDetailModal')).hide();
            
            this.loadLeaveRequests();
            this.loadSummaryStats();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
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
                <a class="page-link" href="#" onclick="hrLeaveManager.loadLeaveRequests(${this.state.currentPage - 1})">Previous</a>
            </li>
        `;
        
        // Page numbers
        for (let i = 1; i <= this.state.totalPages; i++) {
            if (i === this.state.currentPage || i === 1 || i === this.state.totalPages || 
                (i >= this.state.currentPage - 2 && i <= this.state.currentPage + 2)) {
                html += `
                    <li class="page-item ${i === this.state.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="hrLeaveManager.loadLeaveRequests(${i})">${i}</a>
                    </li>
                `;
            } else if (i === this.state.currentPage - 3 || i === this.state.currentPage + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Next button
        html += `
            <li class="page-item ${this.state.currentPage === this.state.totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="hrLeaveManager.loadLeaveRequests(${this.state.currentPage + 1})">Next</a>
            </li>
        `;
        
        pagination.innerHTML = html;
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
        this.loadSummaryStats();
    },

    resetFilters() {
        document.getElementById('leaveFilterForm').reset();
        this.state.filters = {};
        this.state.currentPage = 1;
        this.loadLeaveRequests();
        this.loadSummaryStats();
    },

    async exportLeaves() {
        try {
            Utils.showToast('Preparing export...', 'info');
            
            const response = await API.post('/reports/export', {
                type: 'leave',
                format: 'excel',
                filters: this.state.filters
            });
            
            Utils.showToast('Export completed successfully', 'success');
            
        } catch (error) {
            Utils.handleApiError(error, 'Export failed');
        }
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
    },

    formatStatus(status) {
        return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
    },

    getProgressColor(progress) {
        if (progress >= 100) return 'bg-success';
        if (progress >= 75) return 'bg-info';
        if (progress >= 50) return 'bg-warning';
        return 'bg-secondary';
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    hrLeaveManager.init();
});
</script>

<style>
.stats-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-left: 2px solid #dee2e6;
}

.timeline-item.active {
    border-left-color: #ffc107;
}

.timeline-item.approved {
    border-left-color: #28a745;
}

.timeline-item.rejected {
    border-left-color: #dc3545;
}

.timeline-marker {
    position: absolute;
    left: -9px;
    top: 0;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
}

.timeline-item.active .timeline-marker {
    background: #ffc107;
}

.timeline-item.approved .timeline-marker {
    background: #28a745;
}

.timeline-item.rejected .timeline-marker {
    background: #dc3545;
}

.timeline-content {
    margin-left: 20px;
}

.progress-container {
    min-width: 120px;
}
</style>
@endpush
