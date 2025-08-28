@extends('layouts.app')

@section('title', 'Attendance Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Attendance Dashboard</h1>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary" id="monthlyView">Monthly</button>
            <button type="button" class="btn btn-outline-primary" id="weeklyView">Weekly</button>
            <button type="button" class="btn btn-outline-primary" id="dailyView">Daily</button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Present Days</h6>
                            <h3 class="mb-0" id="presentDays">0</h3>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> On track</small>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Late Check-ins</h6>
                            <h3 class="mb-0" id="lateDays">0</h3>
                            <small class="text-warning"><i class="fas fa-exclamation-circle"></i> Needs improvement</small>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-clock text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Hours</h6>
                            <h3 class="mb-0" id="totalHours">0</h3>
                            <small class="text-info"><i class="fas fa-info-circle"></i> This month</small>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="fas fa-business-time text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Productivity Score</h6>
                            <h3 class="mb-0"><span id="productivityScore">0</span>%</h3>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-primary" id="scoreProgress" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-chart-line text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Attendance Pattern Chart -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">Attendance Pattern</h5>
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Working Hours Distribution -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">Weekly Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed View -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Attendance Details</h5>
                <div class="input-group" style="width: 300px;">
                    <input type="date" class="form-control" id="startDate">
                    <span class="input-group-text">to</span>
                    <input type="date" class="form-control" id="endDate">
                    <button class="btn btn-primary" id="filterBtn">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Work Hours</th>
                            <th>Status</th>
                            <th>Overtime</th>
                            <th>Score</th>
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
        </div>
    </div>
</div>

<!-- Attendance Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.stats-card {
    transition: transform 0.2s;
}
.stats-card:hover {
    transform: translateY(-5px);
}
.table tbody tr {
    cursor: pointer;
}
.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Dashboard Manager
const dashboardManager = {
    charts: {},
    currentView: 'monthly',
    
    async init() {
        this.setupEventListeners();
        this.initCharts();
        await this.loadDashboardData();
    },
    
    setupEventListeners() {
        document.getElementById('monthlyView').addEventListener('click', () => this.changeView('monthly'));
        document.getElementById('weeklyView').addEventListener('click', () => this.changeView('weekly'));
        document.getElementById('dailyView').addEventListener('click', () => this.changeView('daily'));
        document.getElementById('filterBtn').addEventListener('click', () => this.applyFilter());
        
        // Set default date range
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        document.getElementById('startDate').valueAsDate = firstDay;
        document.getElementById('endDate').valueAsDate = today;
    },
    
    changeView(view) {
        this.currentView = view;
        
        // Update button states
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        document.getElementById(view + 'View').classList.remove('btn-outline-primary');
        document.getElementById(view + 'View').classList.add('btn-primary');
        
        this.loadDashboardData();
    },
    
    async loadDashboardData() {
        try {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            // Load statistics
            const statsResponse = await API.get('/employee/attendance/stats', {
                params: { start_date: startDate, end_date: endDate, view: this.currentView }
            });
            this.updateStats(statsResponse.data);
            
            // Load attendance records
            const historyResponse = await API.get('/employee/attendance/history', {
                params: { start_date: startDate, end_date: endDate }
            });
            this.updateTable(historyResponse.data.data);
            this.updateCharts(historyResponse.data.data);
            
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            Utils.showToast('Failed to load dashboard data', 'error');
        }
    },
    
    updateStats(stats) {
        document.getElementById('presentDays').textContent = stats.present_days || 0;
        document.getElementById('lateDays').textContent = stats.late_days || 0;
        document.getElementById('totalHours').textContent = stats.total_hours || 0;
        
        const score = this.calculateProductivityScore(stats);
        document.getElementById('productivityScore').textContent = score;
        document.getElementById('scoreProgress').style.width = score + '%';
    },
    
    calculateProductivityScore(stats) {
        if (!stats.present_days || !stats.total_days) return 0;
        
        const attendanceRate = (stats.present_days / stats.total_days) * 100;
        const punctualityRate = ((stats.present_days - stats.late_days) / stats.present_days) * 100;
        
        return Math.round((attendanceRate * 0.7 + punctualityRate * 0.3));
    },
    
    updateTable(records) {
        const tbody = document.getElementById('attendanceTableBody');
        
        if (!records || records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No attendance records found</td></tr>';
            return;
        }
        
        tbody.innerHTML = records.map(record => {
            const date = new Date(record.date);
            const dayName = date.toLocaleDateString('en', { weekday: 'short' });
            const status = this.getStatusBadge(record);
            const overtime = record.overtime_hours ? `+${record.overtime_hours}h` : '-';
            const score = this.getRecordScore(record);
            
            return `
                <tr onclick="dashboardManager.showDetail('${record.id}')" role="button">
                    <td>${Utils.formatDate(record.date)}</td>
                    <td>${dayName}</td>
                    <td>${record.check_in_time || '<span class="text-muted">--</span>'}</td>
                    <td>${record.check_out_time || '<span class="text-muted">--</span>'}</td>
                    <td>${record.work_hours ? record.work_hours + 'h' : '<span class="text-muted">--</span>'}</td>
                    <td>${status}</td>
                    <td>${overtime}</td>
                    <td><span class="badge bg-${this.getScoreColor(score)}">${score}%</span></td>
                </tr>
            `;
        }).join('');
    },
    
    getStatusBadge(record) {
        if (!record.check_in) {
            return '<span class="badge bg-danger">Absent</span>';
        }
        if (!record.check_out) {
            return '<span class="badge bg-warning">Incomplete</span>';
        }
        if (record.is_late) {
            return '<span class="badge bg-warning">Late</span>';
        }
        return '<span class="badge bg-success">Present</span>';
    },
    
    getRecordScore(record) {
        if (!record.check_in) return 0;
        if (!record.check_out) return 50;
        
        let score = 100;
        if (record.is_late) score -= 20;
        if (record.is_early_departure) score -= 20;
        
        return Math.max(0, score);
    },
    
    getScoreColor(score) {
        if (score >= 80) return 'success';
        if (score >= 60) return 'warning';
        return 'danger';
    },
    
    initCharts() {
        // Attendance Pattern Chart
        const ctx1 = document.getElementById('attendanceChart').getContext('2d');
        this.charts.attendance = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Check-in Time',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Check-out Time',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'time',
                        time: {
                            parser: 'HH:mm',
                            unit: 'hour',
                            displayFormats: {
                                hour: 'HH:mm'
                            }
                        }
                    }
                }
            }
        });
        
        // Distribution Chart
        const ctx2 = document.getElementById('distributionChart').getContext('2d');
        this.charts.distribution = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#FF6384'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    },
    
    updateCharts(records) {
        // Update attendance pattern chart
        const dates = records.map(r => Utils.formatDate(r.date));
        const checkIns = records.map(r => r.check_in_time || null);
        const checkOuts = records.map(r => r.check_out_time || null);
        
        this.charts.attendance.data.labels = dates;
        this.charts.attendance.data.datasets[0].data = checkIns;
        this.charts.attendance.data.datasets[1].data = checkOuts;
        this.charts.attendance.update();
        
        // Update distribution chart
        const weekdayHours = [0, 0, 0, 0, 0, 0, 0];
        records.forEach(record => {
            if (record.work_hours) {
                const day = new Date(record.date).getDay();
                weekdayHours[day] += parseFloat(record.work_hours);
            }
        });
        
        this.charts.distribution.data.datasets[0].data = weekdayHours;
        this.charts.distribution.update();
    },
    
    async showDetail(id) {
        try {
            const response = await API.get(`/employee/attendance/${id}`);
            const record = response.data;
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Date & Time</h6>
                        <p><strong>Date:</strong> ${Utils.formatDate(record.date)}</p>
                        <p><strong>Check-in:</strong> ${record.check_in_time || 'N/A'}</p>
                        <p><strong>Check-out:</strong> ${record.check_out_time || 'N/A'}</p>
                        <p><strong>Work Hours:</strong> ${record.work_hours || 0} hours</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Additional Info</h6>
                        <p><strong>Branch:</strong> ${record.branch?.name || 'N/A'}</p>
                        <p><strong>Status:</strong> ${record.status}</p>
                        <p><strong>Notes:</strong> ${record.check_in_notes || 'None'}</p>
                    </div>
                </div>
                ${record.check_in_selfie_url ? `
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Check-in Photo</h6>
                            <img src="${record.check_in_selfie_url}" class="img-fluid rounded" alt="Check-in photo">
                        </div>
                        ${record.check_out_selfie_url ? `
                            <div class="col-md-6">
                                <h6>Check-out Photo</h6>
                                <img src="${record.check_out_selfie_url}" class="img-fluid rounded" alt="Check-out photo">
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
            `;
            
            document.getElementById('detailContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
            
        } catch (error) {
            Utils.showToast('Failed to load attendance details', 'error');
        }
    },
    
    applyFilter() {
        this.loadDashboardData();
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    dashboardManager.init();
});
</script>
@endpush
