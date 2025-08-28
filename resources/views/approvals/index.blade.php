@extends('layouts.app')

@section('title', 'Leave Approvals')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-clipboard-check me-2"></i>Leave Approvals
            </h1>
            <p class="text-muted mb-0">Review and approve employee leave requests</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-outline-info" id="refresh-btn">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
            <button class="btn btn-outline-secondary" id="bulk-actions-btn" style="display: none;">
                <i class="fas fa-tasks me-2"></i>Bulk Actions
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-warning" id="pending-count">0</div>
                        <div class="text-muted small">Pending Requests</div>
                    </div>
                    <div class="fs-2 text-warning">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-danger" id="urgent-count">0</div>
                        <div class="text-muted small">Urgent (≤3 days)</div>
                    </div>
                    <div class="fs-2 text-danger">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info" id="today-count">0</div>
                        <div class="text-muted small">Today's Requests</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-success" id="approved-today">0</div>
                        <div class="text-muted small">Approved Today</div>
                    </div>
                    <div class="fs-2 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="approvalFilterForm" class="row g-3">
                <div class="col-md-2">
                    <label for="urgency-filter" class="form-label">Urgency</label>
                    <select class="form-select" id="urgency-filter" name="urgency">
                        <option value="">All Urgency</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
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
                <div class="col-md-3">
                    <label for="branch-filter" class="form-label">Branch</label>
                    <select class="form-select" id="branch-filter" name="branch">
                        <option value="">All Branches</option>
                        <!-- Will be populated dynamically -->
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-approval-filter">
                        <i class="fas fa-undo me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Approval Requests Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Pending Approvals
            </h5>
            <div class="text-muted small">
                Your Approval Level: <span id="user-approval-level" class="badge bg-primary">-</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="approvalTable">
                    <thead>
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="select-all" class="form-check-input">
                            </th>
                            <th>Employee</th>
                            <th>Leave Details</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Urgency</th>
                            <th>Progress</th>
                            <th width="200px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="approvalTableBody">
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
            <nav id="approval-pagination-nav" class="mt-3" style="display: none;">
                <ul class="pagination justify-content-center" id="approval-pagination">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Leave Details Modal -->
<div class="modal fade" id="leaveDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
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
            <div class="modal-footer" id="leave-detail-actions">
                <!-- Action buttons will be added dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2 text-success"></i>Approve Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm">
                <div class="modal-body">
                    <div class="alert alert-success">
                        <strong>Approving request for:</strong> <span id="approve-employee-name"></span>
                    </div>
                    <div class="mb-3">
                        <label for="approve-notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea class="form-control" id="approve-notes" name="notes" rows="3" 
                                  placeholder="Add any notes for this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Approve Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle me-2 text-danger"></i>Reject Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Rejecting request for:</strong> <span id="reject-employee-name"></span>
                    </div>
                    <div class="mb-3">
                        <label for="reject-reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject-reason" name="reason" rows="4" 
                                  placeholder="Please provide a clear reason for rejection..." required></textarea>
                        <div class="form-text">This reason will be visible to the employee.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const approvalCenter = {
    state: {
        filters: {},
        currentPage: 1,
        totalPages: 1,
        requests: [],
        userApprovalLevel: 'none',
        selectedIds: []
    },

    init() {
        this.setupEvents();
        this.loadPendingRequests();
    },

    setupEvents() {
        document.getElementById('refresh-btn').addEventListener('click', () => this.loadPendingRequests());
        document.getElementById('approvalFilterForm').addEventListener('submit', (e) => this.applyFilters(e));
        document.getElementById('reset-approval-filter').addEventListener('click', () => this.resetFilters());
        document.getElementById('approveForm').addEventListener('submit', (e) => this.handleApprove(e));
        document.getElementById('rejectForm').addEventListener('submit', (e) => this.handleReject(e));
        document.getElementById('select-all').addEventListener('change', (e) => this.toggleSelectAll(e.target.checked));
    },

    async loadPendingRequests(page = 1) {
        try {
            const params = new URLSearchParams({
                page: page,
                ...this.state.filters
            });
            
            const response = await API.get(`/approvals/pending?${params}`);
            this.state.requests = response.data.data.data || response.data.data;
            this.state.currentPage = response.data.data.current_page || 1;
            this.state.totalPages = response.data.data.last_page || 1;
            this.state.userApprovalLevel = response.data.user_approval_level;
            
            this.updateApprovalTable();
            this.updatePagination();
            this.updateSummaryCards();
            this.updateUserApprovalLevel();
            
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load pending requests');
            document.getElementById('approvalTableBody').innerHTML = 
                '<tr><td colspan="8" class="text-center text-muted py-4">Failed to load requests</td></tr>';
        }
    },

    updateApprovalTable() {
        const tbody = document.getElementById('approvalTableBody');
        
        if (!this.state.requests || this.state.requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No pending requests found</td></tr>';
            return;
        }
        
        tbody.innerHTML = this.state.requests.map(request => `
            <tr>
                <td>
                    <input type="checkbox" class="form-check-input request-checkbox" value="${request.id}">
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                            ${request.employee.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="fw-medium">${request.employee.name}</div>
                            <small class="text-muted">${request.employee.branch}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge ${this.getLeaveTypeBadge(request.leave_type.code)}">${request.leave_type.name}</span>
                    <br><small class="text-muted">${request.total_days} day${request.total_days > 1 ? 's' : ''}</small>
                </td>
                <td>
                    <div class="fw-medium">${Utils.formatDate(request.start_date)}</div>
                    <small class="text-muted">to ${Utils.formatDate(request.end_date)}</small>
                    <br><small class="text-muted">${request.days_pending} days pending</small>
                </td>
                <td>
                    <span class="badge ${this.getStatusBadge(request.status)}">${this.formatStatus(request.status)}</span>
                </td>
                <td>
                    ${this.renderUrgency(request.urgency)}
                </td>
                <td>
                    ${this.renderProgress(request)}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info" onclick="approvalCenter.viewDetails('${request.id}')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="approvalCenter.openApproveModal('${request.id}', '${request.employee.name}')" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="approvalCenter.openRejectModal('${request.id}', '${request.employee.name}')" title="Reject">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Add event listeners to checkboxes
        document.querySelectorAll('.request-checkbox').forEach(cb => {
            cb.addEventListener('change', () => this.updateSelectedIds());
        });
    },

    async viewDetails(requestId) {
        try {
            const request = this.state.requests.find(r => r.id == requestId);
            if (!request) return;

            const content = document.getElementById('leave-detail-content');
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-8">
                        ${this.renderRequestDetails(request)}
                    </div>
                    <div class="col-md-4">
                        ${this.renderApprovalTimeline(request.approval_timeline)}
                    </div>
                </div>
            `;
            
            // Add action buttons
            const actions = document.getElementById('leave-detail-actions');
            actions.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success me-2" onclick="approvalCenter.openApproveModal('${request.id}', '${request.employee.name}')">
                    <i class="fas fa-check me-2"></i>Approve
                </button>
                <button type="button" class="btn btn-danger" onclick="approvalCenter.openRejectModal('${request.id}', '${request.employee.name}')">
                    <i class="fas fa-times me-2"></i>Reject
                </button>
            `;
            
            new bootstrap.Modal(document.getElementById('leaveDetailModal')).show();
            
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load request details');
        }
    },

    openApproveModal(requestId, employeeName) {
        this.currentRequestId = requestId;
        document.getElementById('approve-employee-name').textContent = employeeName;
        document.getElementById('approveForm').reset();
        
        // Close detail modal if open
        const detailModal = bootstrap.Modal.getInstance(document.getElementById('leaveDetailModal'));
        if (detailModal) detailModal.hide();
        
        new bootstrap.Modal(document.getElementById('approveModal')).show();
    },

    openRejectModal(requestId, employeeName) {
        this.currentRequestId = requestId;
        document.getElementById('reject-employee-name').textContent = employeeName;
        document.getElementById('rejectForm').reset();
        
        // Close detail modal if open
        const detailModal = bootstrap.Modal.getInstance(document.getElementById('leaveDetailModal'));
        if (detailModal) detailModal.hide();
        
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    },

    async handleApprove(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const response = await API.post(`/approvals/${this.currentRequestId}/approve`, {
                notes: formData.get('notes')
            });
            
            Utils.showToast(response.message || 'Request approved successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('approveModal')).hide();
            this.loadPendingRequests(); // Refresh data
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },

    async handleReject(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const response = await API.post(`/approvals/${this.currentRequestId}/reject`, {
                reason: formData.get('reason')
            });
            
            Utils.showToast(response.message || 'Request rejected', 'warning');
            bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
            this.loadPendingRequests(); // Refresh data
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },

    // Helper methods
    renderRequestDetails(request) {
        return `
            <h6><i class="fas fa-user me-2"></i>Employee Information</h6>
            <table class="table table-sm table-borderless mb-4">
                <tr><th>Name:</th><td>${request.employee.name}</td></tr>
                <tr><th>Employee ID:</th><td>${request.employee.employee_id}</td></tr>
                <tr><th>Branch:</th><td>${request.employee.branch}</td></tr>
            </table>

            <h6><i class="fas fa-calendar me-2"></i>Leave Details</h6>
            <table class="table table-sm table-borderless mb-4">
                <tr><th>Type:</th><td><span class="badge ${this.getLeaveTypeBadge(request.leave_type.code)}">${request.leave_type.name}</span></td></tr>
                <tr><th>Start Date:</th><td>${Utils.formatDate(request.start_date)}</td></tr>
                <tr><th>End Date:</th><td>${Utils.formatDate(request.end_date)}</td></tr>
                <tr><th>Total Days:</th><td>${request.total_days} day${request.total_days > 1 ? 's' : ''}</td></tr>
                <tr><th>Submitted:</th><td>${Utils.formatDateTime(request.submitted_at)}</td></tr>
                <tr><th>Days Pending:</th><td>${request.days_pending} days</td></tr>
            </table>

            <h6><i class="fas fa-comment me-2"></i>Reason</h6>
            <div class="bg-light p-3 rounded mb-4">${request.reason}</div>

            ${request.document_url ? `
                <h6><i class="fas fa-paperclip me-2"></i>Supporting Document</h6>
                <a href="${request.document_url}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-download me-2"></i>Download Document
                </a>
            ` : ''}
        `;
    },

    renderApprovalTimeline(timeline) {
        return `
            <h6><i class="fas fa-route me-2"></i>Approval Timeline</h6>
            <div class="timeline">
                ${timeline.map(step => `
                    <div class="timeline-item ${step.is_current ? 'current' : ''} ${step.status === 'approved' ? 'completed' : ''}">
                        <div class="timeline-marker ${step.color}">
                            <i class="${step.icon}"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-1">${step.title}</h6>
                            ${step.approved_by ? `
                                <small class="text-success">✓ Approved by ${step.approved_by}</small><br>
                                <small class="text-muted">${Utils.formatDateTime(step.approved_at)}</small>
                                ${step.notes ? `<br><small class="text-muted">${step.notes}</small>` : ''}
                            ` : step.is_current ? `
                                <small class="text-warning">⏳ Current step</small>
                            ` : `
                                <small class="text-muted">○ Pending</small>
                            `}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },

    renderUrgency(urgency) {
        const urgencyMap = {
            'high': '<span class="badge bg-danger">High</span>',
            'medium': '<span class="badge bg-warning">Medium</span>', 
            'low': '<span class="badge bg-success">Low</span>'
        };
        return urgencyMap[urgency] || '<span class="badge bg-secondary">-</span>';
    },

    renderProgress(request) {
        const progress = request.approval_progress || 0;
        return `
            <div class="progress mb-1" style="height: 8px;">
                <div class="progress-bar ${this.getProgressColor(progress)}" 
                     style="width: ${progress}%"></div>
            </div>
            <small class="text-muted">${progress}%</small>
        `;
    },

    getLeaveTypeBadge(type) {
        const badges = {
            'annual': 'bg-success',
            'sick': 'bg-info', 
            'personal': 'bg-warning',
            'maternity': 'bg-primary',
            'paternity': 'bg-primary',
            'emergency': 'bg-danger'
        };
        return badges[type] || 'bg-secondary';
    },

    getStatusBadge(status) {
        const badges = {
            'pending': 'bg-warning text-dark',
            'approved_by_pengelola': 'bg-info',
            'approved_by_manager': 'bg-primary'
        };
        return badges[status] || 'bg-secondary';
    },

    formatStatus(status) {
        const statusMap = {
            'pending': 'Pending',
            'approved_by_pengelola': 'Approved by Supervisor',
            'approved_by_manager': 'Approved by Manager'
        };
        return statusMap[status] || status;
    },

    getProgressColor(progress) {
        if (progress >= 75) return 'bg-success';
        if (progress >= 50) return 'bg-info';  
        if (progress >= 25) return 'bg-warning';
        return 'bg-secondary';
    },

    updateSummaryCards() {
        const totalPending = this.state.requests.length;
        const urgentCount = this.state.requests.filter(r => r.urgency === 'high').length;
        const todayCount = this.state.requests.filter(r => 
            Utils.formatDate(r.submitted_at) === Utils.formatDate(new Date())
        ).length;

        document.getElementById('pending-count').textContent = totalPending;
        document.getElementById('urgent-count').textContent = urgentCount; 
        document.getElementById('today-count').textContent = todayCount;
        document.getElementById('approved-today').textContent = '0'; // Will implement this later
    },

    updateUserApprovalLevel() {
        const levelMap = {
            'pengelola': 'Store Supervisor',
            'branch_manager': 'Branch Manager',
            'hr_central': 'HR Central'
        };
        document.getElementById('user-approval-level').textContent = 
            levelMap[this.state.userApprovalLevel] || 'No Permission';
    },

    // Filter and pagination methods similar to leave page...
    applyFilters(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        this.state.filters = Object.fromEntries(formData.entries());
        
        Object.keys(this.state.filters).forEach(key => {
            if (!this.state.filters[key]) delete this.state.filters[key];
        });
        
        this.state.currentPage = 1;
        this.loadPendingRequests();
    },

    resetFilters() {
        document.getElementById('approvalFilterForm').reset();
        this.state.filters = {};
        this.state.currentPage = 1;
        this.loadPendingRequests();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    approvalCenter.init();
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

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
    font-weight: bold;
}

.timeline {
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -20px;
    top: 20px;
    bottom: -10px;
    width: 2px;
    background: #dee2e6;
}

.timeline-item:last-child:before {
    display: none;
}

.timeline-marker {
    position: absolute;
    left: -27px;
    top: 0;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #dee2e6;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
}

.timeline-marker.success {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.timeline-marker.warning {
    background: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.timeline-content {
    margin-left: 10px;
}

.timeline-item.current .timeline-marker {
    background: #007bff;
    border-color: #007bff;
    color: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
    70% { box-shadow: 0 0 0 6px rgba(0, 123, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
}
</style>
@endpush
