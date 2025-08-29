@extends('layouts.app')

@section('title', 'HR Central Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-primary-custom fw-bold">
                <i class="fas fa-tachometer-alt me-2"></i>HR Central Dashboard
            </h1>
            <p class="text-muted mb-0">Company-wide overview and analytics</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Print Report
            </button>
            <button class="btn btn-primary" id="refreshDashboard">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Employees</h6>
                        <h3 class="mb-0 text-primary-custom" id="total-employees">--</h3>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>5.2% from last month
                        </small>
                    </div>
                    <div class="text-primary-custom opacity-75">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Active Branches</h6>
                        <h3 class="mb-0 text-info" id="active-branches">--</h3>
                        <small class="text-muted">
                            <i class="fas fa-store me-1"></i>Across regions
                        </small>
                    </div>
                    <div class="text-info opacity-75">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Present Today</h6>
                        <h3 class="mb-0 text-success" id="present-today">--</h3>
                        <small class="text-muted" id="attendance-rate">
                            <i class="fas fa-chart-line me-1"></i>-- attendance rate
                        </small>
                    </div>
                    <div class="text-success opacity-75">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Pending Approvals</h6>
                        <h3 class="mb-0 text-warning" id="pending-approvals">--</h3>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>Requires action
                        </small>
                    </div>
                    <div class="text-warning opacity-75">
                        <i class="fas fa-clipboard-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Row -->
    <div class="row mb-4">
        <!-- Attendance Trends Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Attendance Trends
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary active" data-period="week">Week</button>
                        <button class="btn btn-outline-primary" data-period="month">Month</button>
                        <button class="btn btn-outline-primary" data-period="quarter">Quarter</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Department Breakdown -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Employee Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" width="300" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row">
        <!-- Recent Leave Requests -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Recent Leave Requests
                    </h5>
                    <a href="{{ route('leaves.index') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="recent-leave-requests">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>Top Performers (Attendance)
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            This Month
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-period="week">This Week</a></li>
                            <li><a class="dropdown-item" href="#" data-period="month">This Month</a></li>
                            <li><a class="dropdown-item" href="#" data-period="quarter">This Quarter</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Employee</th>
                                    <th>Branch</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody id="top-performers">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-central.employees.index') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-users fa-2x d-block mb-2"></i>
                                Manage Employees
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-central.branches.index') }}" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-building fa-2x d-block mb-2"></i>
                                Manage Branches
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('approvals.index') }}" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-clipboard-check fa-2x d-block mb-2"></i>
                                Pending Approvals
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('reports.index') }}" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                                Generate Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const hrDashboard = {
    charts: {
        attendance: null,
        department: null
    },

    init() {
        this.loadDashboardData();
        this.initCharts();
        this.setupEvents();
    },

    async loadDashboardData() {
        try {
            // Load main statistics
            await Promise.all([
                this.loadStats(),
                this.loadRecentLeaveRequests(),
                this.loadTopPerformers()
            ]);
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            Utils.showToast('Failed to load dashboard data', 'error');
        }
    },

    async loadStats() {
        try {
            const response = await API.get('/hr-central/dashboard/stats');
            const stats = response.data;

            document.getElementById('total-employees').textContent = stats.totalEmployees;
            document.getElementById('active-branches').textContent = stats.activeBranches;
            document.getElementById('present-today').textContent = stats.presentToday;
            document.getElementById('attendance-rate').innerHTML = `<i class="fas fa-chart-line me-1"></i>${stats.attendanceRate}% attendance rate`;
            document.getElementById('pending-approvals').textContent = stats.pendingApprovals;
            
            // Update growth indicator
            const growthElement = document.querySelector('.text-success');
            if (stats.employeeGrowth !== undefined) {
                const growthClass = stats.employeeGrowth >= 0 ? 'text-success' : 'text-danger';
                const growthIcon = stats.employeeGrowth >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                growthElement.className = growthClass;
                growthElement.innerHTML = `<i class="fas ${growthIcon} me-1"></i>${Math.abs(stats.employeeGrowth)}% from last month`;
            }

        } catch (error) {
            console.error('Error loading stats:', error);
            Utils.handleApiError(error, 'Failed to load dashboard statistics');
        }
    },

    async loadRecentLeaveRequests() {
        try {
            const response = await API.get('/hr-central/dashboard/recent-leave-requests');
            const requests = response.data;

            const tbody = document.getElementById('recent-leave-requests');
            
            if (!requests || requests.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            <i class="fas fa-inbox me-2"></i>No recent leave requests
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = requests.map(req => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                ${req.employee.split(' ').map(n => n[0]).join('')}
                            </div>
                            <div>
                                <div class="fw-medium">${req.employee}</div>
                                <small class="text-muted">${req.branch}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-info">${req.type}</span></td>
                    <td>${req.duration}</td>
                    <td><span class="badge bg-${this.getStatusColor(req.status)}">${req.status}</span></td>
                </tr>
            `).join('');

        } catch (error) {
            console.error('Error loading leave requests:', error);
            Utils.handleApiError(error, 'Failed to load recent leave requests');
            
            const tbody = document.getElementById('recent-leave-requests');
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger py-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>Failed to load data
                    </td>
                </tr>
            `;
        }
    },

    async loadTopPerformers() {
        try {
            // Mock data for now
            const performers = [
                { rank: 1, name: 'Alice Brown', branch: 'Jakarta Pusat', rate: 98.5 },
                { rank: 2, name: 'Bob White', branch: 'Surabaya', rate: 96.2 },
                { rank: 3, name: 'Carol Green', branch: 'Bandung', rate: 94.8 },
                { rank: 4, name: 'David Black', branch: 'Medan', rate: 92.1 },
                { rank: 5, name: 'Eva Blue', branch: 'Jakarta Selatan', rate: 91.7 }
            ];

            const tbody = document.getElementById('top-performers');
            tbody.innerHTML = performers.map(performer => `
                <tr>
                    <td>
                        <span class="badge bg-${performer.rank <= 3 ? 'warning' : 'secondary'} rounded-pill">
                            #${performer.rank}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                ${performer.name.split(' ').map(n => n[0]).join('')}
                            </div>
                            ${performer.name}
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark">${performer.branch}</span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: ${performer.rate}%"></div>
                            </div>
                            <small class="text-success fw-bold">${performer.rate}%</small>
                        </div>
                    </td>
                </tr>
            `).join('');

        } catch (error) {
            console.error('Error loading top performers:', error);
        }
    },

    initCharts() {
        this.initAttendanceChart();
        this.initDepartmentChart();
    },

    initAttendanceChart() {
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        this.charts.attendance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: [85, 89, 78, 92, 88, 76, 82],
                    borderColor: '#8B4513',
                    backgroundColor: 'rgba(139, 69, 19, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
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
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    },

    initDepartmentChart() {
        const ctx = document.getElementById('departmentChart').getContext('2d');
        this.charts.department = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Barista', 'Kitchen', 'Management', 'Admin'],
                datasets: [{
                    data: [45, 25, 20, 10],
                    backgroundColor: [
                        '#8B4513',
                        '#D2B48C',
                        '#F4A460',
                        '#DEB887'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },

    setupEvents() {
        // Refresh button
        document.getElementById('refreshDashboard').addEventListener('click', () => {
            Utils.showToast('Refreshing dashboard...', 'info');
            this.loadDashboardData();
        });

        // Period buttons for attendance chart
        document.querySelectorAll('[data-period]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Update active state
                e.target.closest('.btn-group').querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                // Reload chart data (implement based on period)
                this.loadAttendanceData(e.target.dataset.period);
            });
        });
    },

    async loadAttendanceData(period) {
        // Implement based on period selection
        Utils.showToast(`Loading ${period} data...`, 'info');
    },

    getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'approved': 'success',
            'rejected': 'danger',
            'cancelled': 'secondary'
        };
        return colors[status] || 'secondary';
    }
};

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    hrDashboard.init();
});
</script>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 12px;
}

.stats-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.progress {
    border-radius: 10px;
}

.btn-group .btn {
    font-size: 0.85rem;
}

/* Print styles */
@media print {
    .btn, .dropdown {
        display: none !important;
    }
    
    .card {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>
@endpush
