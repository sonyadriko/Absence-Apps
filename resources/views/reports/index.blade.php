@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
            </h1>
            <p class="text-muted mb-0">Comprehensive reporting and analytics dashboard</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-outline-info" id="refresh-stats">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-2"></i>Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportReport('dashboard', 'pdf')">
                        <i class="fas fa-file-pdf me-2"></i>Export Dashboard (PDF)
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportReport('dashboard', 'excel')">
                        <i class="fas fa-file-excel me-2"></i>Export Dashboard (Excel)
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Dashboard Statistics -->
    <div class="row mb-4" id="dashboard-stats">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-primary" id="total-employees">-</div>
                        <div class="text-muted small">Total Employees</div>
                    </div>
                    <div class="fs-2 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-success" id="present-today">-</div>
                        <div class="text-muted small">Present Today</div>
                    </div>
                    <div class="fs-2 text-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-danger" id="absent-today">-</div>
                        <div class="text-muted small">Absent Today</div>
                    </div>
                    <div class="fs-2 text-danger">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-warning" id="late-today">-</div>
                        <div class="text-muted small">Late Today</div>
                    </div>
                    <div class="fs-2 text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info" id="pending-leaves">-</div>
                        <div class="text-muted small">Pending Leaves</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-success" id="approved-leaves">-</div>
                        <div class="text-muted small">Approved This Month</div>
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
                        <div class="h4 mb-0 fw-bold text-primary" id="attendance-rate">-%</div>
                        <div class="text-muted small">Attendance Rate</div>
                    </div>
                    <div class="fs-2 text-primary">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold text-info" id="leave-utilization">-%</div>
                        <div class="text-muted small">Leave Utilization</div>
                    </div>
                    <div class="fs-2 text-info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Attendance Trend (Last 7 Days)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="attendanceTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Leave Requests by Status
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="leaveStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Comparison Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Monthly Attendance vs Leave Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyComparisonChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Categories -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-folder me-2"></i>Detailed Reports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="report-category-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="report-icon bg-success text-white me-3">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Attendance Report</h6>
                                        <small class="text-muted">Detailed attendance tracking and analysis</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Features:</small>
                                    <small class="text-muted">• Daily attendance records</small><br>
                                    <small class="text-muted">• Late arrivals tracking</small><br>
                                    <small class="text-muted">• Absence patterns analysis</small>
                                </div>
                                <button class="btn btn-success btn-sm w-100" onclick="openAttendanceReport()">
                                    <i class="fas fa-chart-bar me-2"></i>View Attendance Report
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="report-category-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="report-icon bg-info text-white me-3">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Leave Report</h6>
                                        <small class="text-muted">Leave requests analysis and trends</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Features:</small>
                                    <small class="text-muted">• Leave request statistics</small><br>
                                    <small class="text-muted">• Approval workflow analysis</small><br>
                                    <small class="text-muted">• Leave type breakdown</small>
                                </div>
                                <button class="btn btn-info btn-sm w-100" onclick="openLeaveReport()">
                                    <i class="fas fa-chart-line me-2"></i>View Leave Report
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3" id="performance-report-card" style="display: none;">
                            <div class="report-category-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="report-icon bg-warning text-white me-3">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Performance Report</h6>
                                        <small class="text-muted">Employee performance metrics</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Features:</small>
                                    <small class="text-muted">• Performance scoring</small><br>
                                    <small class="text-muted">• Attendance vs productivity</small><br>
                                    <small class="text-muted">• Employee rankings</small>
                                </div>
                                <button class="btn btn-warning btn-sm w-100" onclick="openPerformanceReport()">
                                    <i class="fas fa-medal me-2"></i>View Performance Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Modals -->
<div class="modal fade" id="attendanceReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-check me-2"></i>Attendance Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="attendance-report-content">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="exportReport('attendance', 'excel')">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </button>
                <button type="button" class="btn btn-danger" onclick="exportReport('attendance', 'pdf')">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-alt me-2"></i>Leave Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="leave-report-content">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info" onclick="exportReport('leave', 'excel')">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </button>
                <button type="button" class="btn btn-danger" onclick="exportReport('leave', 'pdf')">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="performanceReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-trophy me-2"></i>Performance Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="performance-report-content">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="exportReport('performance', 'excel')">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </button>
                <button type="button" class="btn btn-danger" onclick="exportReport('performance', 'pdf')">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ReportsManager = {
    charts: {
        attendanceTrend: null,
        leaveStatus: null,
        monthlyComparison: null
    },
    
    userPermissions: {
        can_view_own: false,
        can_view_branch: false,
        can_view_all: false
    },

    init() {
        this.loadDashboardStats();
        this.setupEventListeners();
    },

    setupEventListeners() {
        document.getElementById('refresh-stats').addEventListener('click', () => {
            this.loadDashboardStats();
        });
    },

    async loadDashboardStats() {
        try {
            Utils.showLoading('dashboard-stats');
            
            const response = await API.get('/reports/dashboard-stats');
            
            // Check if response exists
            if (!response) {
                throw new Error('No response received from API');
            }
            
            // Handle both direct response and nested response.data structures
            let data, user_permissions;
            if (response.data) {
                // Response has nested data structure: { success: true, data: {...}, user_permissions: {...} }
                data = response.data;
                user_permissions = response.user_permissions;
            } else {
                // Response is the data directly: { total_employees: 8, ... }
                data = response;
                user_permissions = null;
            }
            
            // Check if data exists
            if (!data) {
                throw new Error('No data received from API');
            }
            
            this.userPermissions = user_permissions || {
                can_view_own: false,
                can_view_branch: false,
                can_view_all: false
            };
            
            this.updateDashboardStats(data);
            if (data.charts) {
                this.updateCharts(data.charts);
            }
            this.updateReportAccess();
            
        } catch (error) {
            console.error('Dashboard stats error:', error);
            Utils.handleApiError(error, 'Failed to load dashboard statistics');
        } finally {
            Utils.hideLoading('dashboard-stats');
        }
    },

    updateDashboardStats(data) {
        document.getElementById('total-employees').textContent = data.total_employees;
        document.getElementById('present-today').textContent = data.present_today;
        document.getElementById('absent-today').textContent = data.absent_today;
        document.getElementById('late-today').textContent = data.late_today;
        document.getElementById('pending-leaves').textContent = data.pending_leaves;
        document.getElementById('approved-leaves').textContent = data.approved_leaves_month;
        document.getElementById('attendance-rate').textContent = data.attendance_rate_month + '%';
        document.getElementById('leave-utilization').textContent = data.leave_utilization + '%';
    },

    updateReportAccess() {
        // Show performance report only for branch/all level users
        if (this.userPermissions.can_view_branch || this.userPermissions.can_view_all) {
            document.getElementById('performance-report-card').style.display = 'block';
        }
    },

    updateCharts(chartsData) {
        this.renderAttendanceTrendChart(chartsData.attendance_trend);
        this.renderLeaveStatusChart(chartsData.leave_by_status);
        this.renderMonthlyComparisonChart(chartsData.monthly_comparison);
    },

    renderAttendanceTrendChart(data) {
        const ctx = document.getElementById('attendanceTrendChart').getContext('2d');
        
        if (this.charts.attendanceTrend) {
            this.charts.attendanceTrend.destroy();
        }

        this.charts.attendanceTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.day),
                datasets: [{
                    label: 'Present Employees',
                    data: data.map(d => d.present),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    },

    renderLeaveStatusChart(data) {
        const ctx = document.getElementById('leaveStatusChart').getContext('2d');
        
        if (this.charts.leaveStatus) {
            this.charts.leaveStatus.destroy();
        }

        const colors = {
            'pending': '#ffc107',
            'approved_by_pengelola': '#17a2b8',
            'approved_by_manager': '#007bff',
            'approved': '#28a745',
            'rejected': '#dc3545'
        };

        this.charts.leaveStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => this.formatStatusLabel(d.status)),
                datasets: [{
                    data: data.map(d => d.count),
                    backgroundColor: data.map(d => colors[d.status] || '#6c757d'),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },

    renderMonthlyComparisonChart(data) {
        const ctx = document.getElementById('monthlyComparisonChart').getContext('2d');
        
        if (this.charts.monthlyComparison) {
            this.charts.monthlyComparison.destroy();
        }

        this.charts.monthlyComparison = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.month),
                datasets: [
                    {
                        label: 'Attendance',
                        data: data.map(d => d.attendance),
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Leave Days',
                        data: data.map(d => d.leaves),
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        borderColor: '#ffc107',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        display: true,
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Attendance Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Leave Days'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                },
            },
        });
    },

    formatStatusLabel(status) {
        const statusLabels = {
            'pending': 'Pending',
            'approved_by_pengelola': 'Approved by Supervisor',
            'approved_by_manager': 'Approved by Manager',
            'approved': 'Approved',
            'rejected': 'Rejected'
        };
        return statusLabels[status] || status;
    }
};

// Report Modal Functions
async function openAttendanceReport() {
    try {
        Utils.showLoading('attendance-report-content');
        
        const modal = new bootstrap.Modal(document.getElementById('attendanceReportModal'));
        modal.show();
        
        const response = await API.get('/reports/attendance');
        const { data } = response.data;
        
        document.getElementById('attendance-report-content').innerHTML = renderAttendanceReportContent(data);
        
    } catch (error) {
        Utils.handleApiError(error, 'Failed to load attendance report');
    } finally {
        Utils.hideLoading('attendance-report-content');
    }
}

async function openLeaveReport() {
    try {
        Utils.showLoading('leave-report-content');
        
        const modal = new bootstrap.Modal(document.getElementById('leaveReportModal'));
        modal.show();
        
        const response = await API.get('/reports/leave');
        const { data } = response.data;
        
        document.getElementById('leave-report-content').innerHTML = renderLeaveReportContent(data);
        
    } catch (error) {
        Utils.handleApiError(error, 'Failed to load leave report');
    } finally {
        Utils.hideLoading('leave-report-content');
    }
}

async function openPerformanceReport() {
    try {
        Utils.showLoading('performance-report-content');
        
        const modal = new bootstrap.Modal(document.getElementById('performanceReportModal'));
        modal.show();
        
        const response = await API.get('/reports/performance');
        const { data } = response.data;
        
        document.getElementById('performance-report-content').innerHTML = renderPerformanceReportContent(data);
        
    } catch (error) {
        Utils.handleApiError(error, 'Failed to load performance report');
    } finally {
        Utils.hideLoading('performance-report-content');
    }
}

function renderAttendanceReportContent(data) {
    let html = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card-small">
                    <h6 class="text-success">Present</h6>
                    <div class="h4">${data.summary.present_count}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card-small">
                    <h6 class="text-warning">Late</h6>
                    <div class="h4">${data.summary.late_count}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card-small">
                    <h6 class="text-danger">Absent</h6>
                    <div class="h4">${data.summary.absent_count}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card-small">
                    <h6 class="text-info">Rate</h6>
                    <div class="h4">${data.summary.attendance_rate}%</div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Branch</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    if (data.records.data && data.records.data.length > 0) {
        data.records.data.forEach(record => {
            const status = record.check_in ? (record.status === 'late' ? 'Late' : 'Present') : 'Absent';
            const statusClass = record.check_in ? (record.status === 'late' ? 'text-warning' : 'text-success') : 'text-danger';
            
            html += `
                <tr>
                    <td>${Utils.formatDate(record.date)}</td>
                    <td>
                        <strong>${record.user.name}</strong><br>
                        <small class="text-muted">${record.user.employee_id}</small>
                    </td>
                    <td>${record.user.branch ? record.user.branch.name : 'N/A'}</td>
                    <td>${record.check_in ? Utils.formatTime(record.check_in) : '-'}</td>
                    <td>${record.check_out ? Utils.formatTime(record.check_out) : '-'}</td>
                    <td><span class="${statusClass}">${status}</span></td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="6" class="text-center text-muted">No attendance records found</td></tr>';
    }
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    return html;
}

function renderLeaveReportContent(data) {
    let html = `
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stats-card-small">
                    <h6 class="text-primary">Total</h6>
                    <div class="h4">${data.summary.total_requests}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card-small">
                    <h6 class="text-success">Approved</h6>
                    <div class="h4">${data.summary.approved_count}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card-small">
                    <h6 class="text-warning">Pending</h6>
                    <div class="h4">${data.summary.pending_count}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card-small">
                    <h6 class="text-danger">Rejected</h6>
                    <div class="h4">${data.summary.rejected_count}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card-small">
                    <h6 class="text-info">Total Days</h6>
                    <div class="h4">${data.summary.total_days}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card-small">
                    <h6 class="text-secondary">Approval Rate</h6>
                    <div class="h4">${data.summary.approval_rate}%</div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    if (data.records.data && data.records.data.length > 0) {
        data.records.data.forEach(record => {
            const statusClass = {
                'approved': 'success',
                'pending': 'warning',
                'rejected': 'danger',
                'approved_by_pengelola': 'info',
                'approved_by_manager': 'primary'
            }[record.status] || 'secondary';
            
            html += `
                <tr>
                    <td>
                        <strong>${record.user.name}</strong><br>
                        <small class="text-muted">${record.user.branch ? record.user.branch.name : 'N/A'}</small>
                    </td>
                    <td>
                        <span class="badge bg-info">${record.leave_type ? record.leave_type.name : 'Unknown'}</span>
                    </td>
                    <td>${Utils.formatDate(record.start_date)}</td>
                    <td>${Utils.formatDate(record.end_date)}</td>
                    <td>${record.total_days}</td>
                    <td><span class="badge bg-${statusClass}">${record.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></td>
                    <td>${Utils.formatDateTime(record.created_at)}</td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="7" class="text-center text-muted">No leave records found</td></tr>';
    }
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    return html;
}

function renderPerformanceReportContent(data) {
    let html = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card-small">
                    <h6 class="text-primary">Total Employees</h6>
                    <div class="h4">${data.summary.total_employees}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card-small">
                    <h6 class="text-success">Avg Attendance</h6>
                    <div class="h4">${data.summary.average_attendance_rate}%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card-small">
                    <h6 class="text-info">Avg Punctuality</h6>
                    <div class="h4">${data.summary.average_punctuality_rate}%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card-small">
                    <h6 class="text-warning">Avg Performance</h6>
                    <div class="h4">${data.summary.average_performance_score}</div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Branch</th>
                        <th>Attendance Rate</th>
                        <th>Punctuality Rate</th>
                        <th>Leave Days</th>
                        <th>Performance Score</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    if (data.employees.data && data.employees.data.length > 0) {
        data.employees.data.forEach(employee => {
            const gradeClass = {
                'A': 'success',
                'B': 'primary',
                'C': 'info',
                'D': 'warning',
                'F': 'danger'
            }[employee.performance_grade] || 'secondary';
            
            html += `
                <tr>
                    <td>
                        <strong>${employee.name}</strong><br>
                        <small class="text-muted">${employee.employee_id}</small>
                    </td>
                    <td>${employee.branch || 'N/A'}</td>
                    <td>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-success" style="width: ${employee.attendance_rate}%"></div>
                        </div>
                        ${employee.attendance_rate}%
                    </td>
                    <td>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-info" style="width: ${employee.punctuality_rate}%"></div>
                        </div>
                        ${employee.punctuality_rate}%
                    </td>
                    <td>${employee.leave_days}</td>
                    <td>${employee.performance_score}</td>
                    <td><span class="badge bg-${gradeClass}">${employee.performance_grade}</span></td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="7" class="text-center text-muted">No performance data found</td></tr>';
    }
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    return html;
}

// Export function
async function exportReport(type, format) {
    try {
        Utils.showToast('Preparing export...', 'info');
        
        const response = await API.post('/reports/export', {
            type: type,
            format: format
        });
        
        if (response.data.success) {
            // Handle download
            Utils.showToast('Export completed successfully', 'success');
        } else {
            Utils.showToast(response.data.message || 'Export functionality coming soon', 'warning');
        }
        
    } catch (error) {
        Utils.handleApiError(error, 'Export failed');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    ReportsManager.init();
});
</script>

<style>
.stats-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.stats-card-small {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.report-category-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 20px;
    height: 100%;
    transition: all 0.3s ease;
}

.report-category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.report-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.progress-sm {
    height: 8px;
    margin-bottom: 5px;
}

.chart-container {
    position: relative;
    height: 300px;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #ccc;
    border-radius: 50%;
    border-top-color: #333;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endpush
