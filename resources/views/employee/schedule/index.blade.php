@extends('layouts.app')

@section('title', 'My Schedule')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-alt me-2"></i>My Schedule
            </h1>
            <p class="text-muted mb-0">View your assigned shifts and request changes</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-outline-primary" id="print-schedule-btn">
                <i class="fas fa-print me-2"></i>Print
            </button>
            <button class="btn btn-primary" id="request-shift-btn">
                <i class="fas fa-exchange-alt me-2"></i>Request Change
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="scheduleFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="view-mode" class="form-label">View</label>
                    <select class="form-select" id="view-mode" name="view">
                        <option value="week">Weekly</option>
                        <option value="month">Monthly</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start-week" class="form-label">Week Starting</label>
                    <input type="date" class="form-control" id="start-week" name="start_date">
                </div>
                <div class="col-md-3">
                    <label for="branch-filter" class="form-label">Branch</label>
                    <select class="form-select" id="branch-filter" name="branch_id">
                        <option value="">All Branches</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Apply
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="reset-schedule-filter">
                        <i class="fas fa-undo me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule View -->
    <div class="card">
        <div class="card-body" id="schedule-container">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-muted mt-2">Loading schedule...</div>
            </div>
        </div>
    </div>
</div>

<!-- Request Shift Change Modal -->
<div class="modal fade" id="shiftRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i>Request Schedule Change
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="shiftRequestForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="date" id="request-date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requested Shift <span class="text-danger">*</span></label>
                        <select class="form-select" name="requested_shift" id="requested-shift" required>
                            <option value="">Select shift</option>
                            <option value="morning">Morning (08:00 - 16:00)</option>
                            <option value="evening">Evening (16:00 - 00:00)</option>
                            <option value="night">Night (00:00 - 08:00)</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div id="custom-shift-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Start Time</label>
                                <input type="time" class="form-control" name="start_time" id="start-time">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Time</label>
                                <input type="time" class="form-control" name="end_time" id="end-time">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Explain why you request this change" required></textarea>
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
const schedulePage = {
    state: {
        view: 'week',
        start_date: null,
        branch_id: '',
        branches: [],
        schedule: [],
    },

    init() {
        this.setupEvents();
        this.initDefaults();
        this.loadBranches();
        this.loadSchedule();
    },

    setupEvents() {
        document.getElementById('scheduleFilterForm').addEventListener('submit', (e) => this.applyFilters(e));
        document.getElementById('reset-schedule-filter').addEventListener('click', () => this.resetFilters());
        document.getElementById('request-shift-btn').addEventListener('click', () => this.openRequestModal());
        document.getElementById('requested-shift').addEventListener('change', (e) => this.toggleCustomShift(e.target.value));
        document.getElementById('print-schedule-btn').addEventListener('click', () => window.print());
    },

    initDefaults() {
        // Default to current week Monday
        const now = new Date();
        const day = now.getDay();
        const diff = now.getDate() - day + (day === 0 ? -6 : 1); // adjust when day is Sunday
        const monday = new Date(now.setDate(diff));
        const mondayStr = monday.toISOString().split('T')[0];
        document.getElementById('start-week').value = mondayStr;
        this.state.start_date = mondayStr;
    },

    async loadBranches() {
        try {
            const res = await API.get('/branches');
            this.state.branches = res.data || [];
            const select = document.getElementById('branch-filter');
            select.innerHTML = '<option value="">All Branches</option>' + this.state.branches.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
        } catch (err) {
            console.error('Failed to load branches', err);
        }
    },

    async loadSchedule() {
        try {
            const params = new URLSearchParams({
                view: this.state.view,
                start_date: this.state.start_date,
                branch_id: this.state.branch_id || undefined,
            });
            const res = await API.get(`/employee/schedule?${params}`);
            // Support both array and paginated formats
            this.state.schedule = res.data.data || res.data || [];
            this.renderSchedule();
        } catch (err) {
            Utils.handleApiError(err, 'Failed to load schedule');
            document.getElementById('schedule-container').innerHTML = '<div class="text-center text-muted py-5">Failed to load schedule</div>';
        }
    },

    renderSchedule() {
        const container = document.getElementById('schedule-container');
        const view = this.state.view;
        const items = this.state.schedule;

        if (!items || items.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-5">No schedule available</div>';
            return;
        }

        if (view === 'week') {
            container.innerHTML = this.renderWeekly(items);
        } else {
            container.innerHTML = this.renderMonthly(items);
        }
    },

    renderWeekly(items) {
        // Group by date
        const byDate = items.reduce((acc, s) => {
            const d = s.date;
            acc[d] = acc[d] || [];
            acc[d].push(s);
            return acc;
        }, {});

        const dates = Object.keys(byDate).sort();
        let html = '<div class="row g-3">';
        for (const d of dates) {
            const dayLabel = new Date(d).toLocaleDateString('en', { weekday: 'long', month: 'short', day: 'numeric' });
            html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="fw-medium">${dayLabel}</div>
                            <span class="badge bg-secondary">${byDate[d].length} shift(s)</span>
                        </div>
                        <div class="card-body">
                            ${byDate[d].map(s => this.renderShiftItem(s)).join('')}
                        </div>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    },

    renderMonthly(items) {
        // Expect items containing date and shift times; build a simple calendar grid
        const start = new Date(this.state.start_date);
        const year = start.getFullYear();
        const month = start.getMonth();
        const firstOfMonth = new Date(year, month, 1);
        const lastOfMonth = new Date(year, month + 1, 0);

        // Build day map
        const map = items.reduce((acc, s) => {
            const key = s.date;
            (acc[key] = acc[key] || []).push(s);
            return acc;
        }, {});

        // Calendar header
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="h5 mb-0">${firstOfMonth.toLocaleString('en', { month: 'long', year: 'numeric' })}</div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="schedulePage.prevMonth()"><i class="fas fa-chevron-left"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="schedulePage.nextMonth()"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table calendar-table">
                    <thead>
                        <tr>
                            ${['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].map(d => `<th class="text-center">${d}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Fill cells
        const firstWeekday = (firstOfMonth.getDay() + 6) % 7; // Monday=0
        const totalDays = lastOfMonth.getDate();
        let day = 1 - firstWeekday;
        for (let r = 0; r < 6; r++) {
            html += '<tr>';
            for (let c = 0; c < 7; c++) {
                const current = new Date(year, month, day);
                const inMonth = current.getMonth() === month;
                const key = current.toISOString().split('T')[0];
                const dayItems = map[key] || [];
                html += `
                    <td class="align-top ${inMonth ? '' : 'bg-light'}" style="height: 140px; min-width: 160px;">
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">${current.getDate()}</span>
                            ${dayItems.length ? `<span class="badge bg-secondary">${dayItems.length}</span>` : ''}
                        </div>
                        <div class="mt-1">
                            ${dayItems.map(s => `
                                <div class="p-2 rounded border mb-1 small">
                                    <div class="fw-medium"><i class="fas fa-store me-1 text-muted"></i>${s.branch?.name || 'Branch'}</div>
                                    <div><i class="fas fa-clock me-1 text-muted"></i>${Utils.formatTime(s.start_time)} - ${Utils.formatTime(s.end_time)}</div>
                                </div>
                            `).join('')}
                        </div>
                    </td>
                `;
                day++;
            }
            html += '</tr>';
            if (day > totalDays && ((day - totalDays - 1 + 7) % 7) === 0) break; // stop when full weeks done
        }

        html += '</tbody></table></div>';
        return html;
    },

    renderShiftItem(s) {
        const duration = this.diffHours(s.start_time, s.end_time);
        return `
            <div class="p-3 border rounded mb-2">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-medium"><i class="fas fa-store me-1 text-muted"></i>${s.branch?.name || 'Branch'}</div>
                        <div class="small text-muted">${Utils.formatTime(s.start_time)} - ${Utils.formatTime(s.end_time)} (${duration})</div>
                    </div>
                    <div>
                        <span class="badge ${this.shiftTypeBadge(s.shift_type)}">${(s.shift_type || 'shift').replace('_',' ')}</span>
                    </div>
                </div>
                ${s.notes ? `<div class="small text-muted mt-1"><i class="fas fa-sticky-note me-1"></i>${s.notes}</div>` : ''}
            </div>
        `;
    },

    shiftTypeBadge(type) {
        const map = { morning: 'bg-success', evening: 'bg-warning text-dark', night: 'bg-primary', custom: 'bg-info text-dark' };
        return map[type] || 'bg-secondary';
    },

    diffHours(start, end) {
        if (!start || !end) return '--';
        const s = new Date(`1970-01-01T${start}`);
        const e = new Date(`1970-01-01T${end}`);
        let diff = (e - s) / (1000 * 60 * 60);
        if (diff < 0) diff += 24; // handle overnight
        const h = Math.floor(diff);
        const m = Math.round((diff - h) * 60);
        return `${h}h ${m}m`;
    },

    applyFilters(e) {
        e.preventDefault();
        this.state.view = document.getElementById('view-mode').value;
        this.state.start_date = document.getElementById('start-week').value;
        this.state.branch_id = document.getElementById('branch-filter').value;
        this.loadSchedule();
    },

    resetFilters() {
        document.getElementById('scheduleFilterForm').reset();
        this.initDefaults();
        this.state.branch_id = '';
        this.state.view = 'week';
        this.loadSchedule();
    },

    openRequestModal(date = null) {
        document.getElementById('shiftRequestForm').reset();
        document.getElementById('custom-shift-fields').style.display = 'none';
        if (date) document.getElementById('request-date').value = date;
        new bootstrap.Modal(document.getElementById('shiftRequestModal')).show();
    },

    toggleCustomShift(value) {
        document.getElementById('custom-shift-fields').style.display = value === 'custom' ? 'block' : 'none';
    }
};

// Submit request handler
async function submitShiftRequest(e) {
    e.preventDefault();
}

document.addEventListener('DOMContentLoaded', () => {
    schedulePage.init();

    // Attach submit handler for shift request
    document.getElementById('shiftRequestForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(btn, true);
        try {
            const form = new FormData(e.target);
            const data = Object.fromEntries(form.entries());
            const res = await API.post('/employee/schedule/requests', data);
            Utils.showToast(res.message || 'Request submitted', 'success');
            bootstrap.Modal.getInstance(document.getElementById('shiftRequestModal')).hide();
        } catch (err) {
            Utils.handleApiError(err, 'Failed to submit request');
        } finally {
            Utils.setButtonLoading(btn, false);
        }
    });
});
</script>

<style>
.stats-card { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 16px; }
.table.calendar-table th, .table.calendar-table td { vertical-align: top; }
</style>
@endpush
