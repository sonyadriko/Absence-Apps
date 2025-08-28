@extends('layouts.app')

@section('title', 'Check In/Out')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-fingerprint me-2"></i>Check In/Out
            </h1>
            <p class="text-muted mb-0">Record your daily attendance with location and photo verification</p>
        </div>
        
        <div class="text-end">
            <div class="small text-muted">
                <i class="fas fa-clock me-1"></i>
                <span id="current-time">{{ now()->format('H:i:s') }}</span>
            </div>
            <div class="small text-muted">
                <i class="fas fa-calendar me-1"></i>{{ now()->format('l, F j, Y') }}
            </div>
        </div>
    </div>

    <!-- Current Status Card -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Today's Status
                    </h5>
                </div>
                <div class="card-body">
                    <div id="status-container">
                        <div class="d-flex justify-content-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading status...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h4 mb-0 fw-bold" id="monthlyAttendance">0</div>
                        <div class="text-muted small">This Month</div>
                    </div>
                    <div class="fs-2 text-success">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-4" id="action-section">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-sign-in-alt text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Check In</h5>
                    <p class="card-text text-muted">Start your work day</p>
                    <button class="btn btn-success btn-lg" id="check-in-btn" disabled>
                        <i class="fas fa-play me-2"></i>Check In
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-sign-out-alt text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Check Out</h5>
                    <p class="card-text text-muted">End your work day</p>
                    <button class="btn btn-danger btn-lg" id="check-out-btn" disabled>
                        <i class="fas fa-stop me-2"></i>Check Out
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Attendance
                </h5>
                <a href="{{ route('employee.attendance.index') }}" class="btn btn-sm btn-outline-primary">
                    View All Records
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="recentTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4">
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

<!-- Check In/Out Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-fingerprint me-2"></i>
                    <span id="modal-title">Attendance Check</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="attendanceForm">
                <div class="modal-body">
                    <!-- Branch Selection (for check-in only) -->
                    <div class="mb-3" id="branch-selection" style="display: none;">
                        <label for="branch-select" class="form-label">Select Branch <span class="text-danger">*</span></label>
                        <select class="form-select" id="branch-select" name="branch_id" required>
                            <option value="">Choose your work location...</option>
                        </select>
                    </div>
                    
                    <!-- Location Status -->
                    <div class="mb-3">
                        <label class="form-label">Location Verification</label>
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center" id="location-status">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                <span>Getting your location...</span>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Camera Section -->
                    <div class="mb-3">
                        <label class="form-label">Photo Verification <span class="text-danger">*</span></label>
                        <div class="border rounded p-3">
                            <!-- Camera Placeholder -->
                            <div id="camera-placeholder" class="text-center py-4">
                                <i class="fas fa-camera text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">Camera will activate when you're ready</p>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="activate-camera-btn">
                                    <i class="fas fa-video me-1"></i>Activate Camera
                                </button>
                            </div>
                            
                            <!-- Camera Preview -->
                            <div id="camera-section" style="display: none;">
                                <video id="video" autoplay playsinline class="w-100 rounded mb-2" style="max-height: 300px; object-fit: cover;"></video>
                                <div class="text-center">
                                    <button type="button" class="btn btn-primary" id="capture-btn">
                                        <i class="fas fa-camera me-1"></i>Capture Photo
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Photo Preview -->
                            <div id="photo-preview" style="display: none;">
                                <img id="captured-image" class="w-100 rounded mb-2" style="max-height: 300px; object-fit: cover;">
                                <div class="text-center">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="retake-btn">
                                        <i class="fas fa-redo me-1"></i>Retake Photo
                                    </button>
                                </div>
                            </div>
                            
                            <canvas id="canvas" style="display: none;"></canvas>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Add any notes about your attendance..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                        <i class="fas fa-check me-2"></i>Confirm Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let attendanceManager = {
    currentStatus: null,
    currentLocation: null,
    mediaStream: null,
    capturedPhoto: null,
    selectedAction: null,
    
    init() {
        this.loadCurrentStatus();
        this.loadRecentAttendance();
        this.loadBranches();
        this.setupEventListeners();
        this.startClock();
    },
    
    setupEventListeners() {
        document.getElementById('check-in-btn').addEventListener('click', () => this.openModal('checkin'));
        document.getElementById('check-out-btn').addEventListener('click', () => this.openModal('checkout'));
        document.getElementById('activate-camera-btn').addEventListener('click', () => this.activateCamera());
        document.getElementById('capture-btn').addEventListener('click', () => this.capturePhoto());
        document.getElementById('retake-btn').addEventListener('click', () => this.retakePhoto());
        document.getElementById('attendanceForm').addEventListener('submit', (e) => this.submitAttendance(e));
    },
    
    async loadCurrentStatus() {
        try {
            const response = await API.get('/employee/attendance/status');
            this.currentStatus = response.data;
            this.updateStatusUI();
            this.updateActionButtons();
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load attendance status');
        }
    },
    
    async loadRecentAttendance() {
        try {
            const response = await API.get('/employee/attendance/history?limit=7');
            this.updateRecentTable(response.data.data || response.data);
        } catch (error) {
            document.getElementById('recentTableBody').innerHTML = 
                '<tr><td colspan="6" class="text-center text-muted">Failed to load recent attendance</td></tr>';
        }
    },
    
    async loadBranches() {
        try {
            const response = await API.get('/branches');
            const select = document.getElementById('branch-select');
            
            select.innerHTML = '<option value="">Choose your work location...</option>';
            response.data.forEach(branch => {
                select.innerHTML += `<option value="${branch.id}">${branch.name} - ${branch.location || 'Main Location'}</option>`;
            });
        } catch (error) {
            console.error('Failed to load branches:', error);
        }
    },
    
    updateStatusUI() {
        const container = document.getElementById('status-container');
        const status = this.currentStatus;
        
        let statusHtml = '';
        let badgeClass = '';
        let statusIcon = '';
        
        if (status.checked_out) {
            badgeClass = 'bg-success';
            statusIcon = 'fa-check-circle';
            statusHtml = `
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="border-end">
                            <h4 class="text-success mb-1">${status.check_in_time}</h4>
                            <small class="text-muted">Check In</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border-end">
                            <h4 class="text-danger mb-1">${status.check_out_time}</h4>
                            <small class="text-muted">Check Out</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h4 class="text-primary mb-1">${this.formatHours(status.work_hours || 0)}</h4>
                        <small class="text-muted">Work Hours</small>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <span class="badge ${badgeClass} fs-6"><i class="fas ${statusIcon} me-1"></i>Work Day Completed</span>
                </div>
            `;
        } else if (status.checked_in) {
            badgeClass = 'bg-info';
            statusIcon = 'fa-clock';
            const workingHours = this.calculateWorkingHours(status.check_in_time);
            statusHtml = `
                <div class="row text-center">
                    <div class="col-md-6">
                        <div class="border-end">
                            <h4 class="text-success mb-1">${status.check_in_time}</h4>
                            <small class="text-muted">Checked In</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4 class="text-primary mb-1" id="working-hours">${workingHours}</h4>
                        <small class="text-muted">Working Time</small>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <span class="badge ${badgeClass} fs-6"><i class="fas ${statusIcon} me-1"></i>Currently Working</span>
                </div>
            `;
            
            // Update working hours every minute
            setInterval(() => {
                const workingElement = document.getElementById('working-hours');
                if (workingElement) {
                    workingElement.textContent = this.calculateWorkingHours(status.check_in_time);
                }
            }, 60000);
        } else {
            badgeClass = 'bg-warning';
            statusIcon = 'fa-moon';
            statusHtml = `
                <div class="text-center">
                    <i class="fas fa-sun text-warning mb-3" style="font-size: 3rem;"></i>
                    <h5>Ready to Start Your Day?</h5>
                    <p class="text-muted mb-3">Click "Check In" when you're ready to begin work</p>
                    <span class="badge ${badgeClass} fs-6"><i class="fas ${statusIcon} me-1"></i>Not Started</span>
                </div>
            `;
        }
        
        if (status.branch) {
            statusHtml += `
                <div class="text-center mt-2">
                    <small class="text-muted">
                        <i class="fas fa-building me-1"></i>${status.branch}
                    </small>
                </div>
            `;
        }
        
        container.innerHTML = statusHtml;
        
        // Update monthly stats
        document.getElementById('monthlyAttendance').textContent = status.monthly_count || 0;
    },
    
    updateActionButtons() {
        const checkInBtn = document.getElementById('check-in-btn');
        const checkOutBtn = document.getElementById('check-out-btn');
        
        if (this.currentStatus.checked_out) {
            checkInBtn.disabled = true;
            checkOutBtn.disabled = true;
            checkInBtn.innerHTML = '<i class="fas fa-check me-2"></i>Completed';
            checkOutBtn.innerHTML = '<i class="fas fa-check me-2"></i>Completed';
        } else if (this.currentStatus.checked_in) {
            checkInBtn.disabled = true;
            checkOutBtn.disabled = false;
            checkInBtn.innerHTML = '<i class="fas fa-check me-2"></i>Checked In';
        } else {
            checkInBtn.disabled = false;
            checkOutBtn.disabled = true;
        }
    },
    
    updateRecentTable(data) {
        const tbody = document.getElementById('recentTableBody');
        
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No recent attendance records</td></tr>';
            return;
        }
        
        tbody.innerHTML = data.map(record => `
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
                        <span class="text-success">${Utils.formatTime(record.check_in_time)}</span>
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    ${record.check_out_time ? `
                        <span class="text-danger">${Utils.formatTime(record.check_out_time)}</span>
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    ${record.work_hours ? `
                        <span class="fw-medium">${this.formatHours(record.work_hours)}</span>
                    ` : '<span class="text-muted">--</span>'}
                </td>
                <td>
                    <span class="badge ${this.getStatusBadgeClass(record.status)}">${record.status}</span>
                    ${record.is_late ? '<i class="fas fa-clock text-warning ms-1" title="Late arrival"></i>' : ''}
                </td>
            </tr>
        `).join('');
    },
    
    openModal(action) {
        this.selectedAction = action;
        const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
        const title = document.getElementById('modal-title');
        const branchSelection = document.getElementById('branch-selection');
        
        title.textContent = action === 'checkin' ? 'Check In' : 'Check Out';
        branchSelection.style.display = action === 'checkin' ? 'block' : 'none';
        
        // Reset form
        document.getElementById('attendanceForm').reset();
        this.resetCamera();
        this.getCurrentLocation();
        
        modal.show();
    },
    
    getCurrentLocation() {
        const statusEl = document.getElementById('location-status');
        const progressBar = document.querySelector('.progress-bar');
        
        if (!navigator.geolocation) {
            statusEl.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i>Geolocation not supported';
            return;
        }
        
        statusEl.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Getting location...';
        progressBar.style.width = '30%';
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.currentLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                };
                statusEl.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Location verified';
                progressBar.style.width = '100%';
                this.checkSubmitReady();
            },
            (error) => {
                statusEl.innerHTML = '<i class="fas fa-exclamation-circle text-warning me-2"></i>Location access required';
                progressBar.style.width = '0%';
                console.error('Geolocation error:', error);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
        );
    },
    
    async activateCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'user', width: 640, height: 480 } 
            });
            
            this.mediaStream = stream;
            document.getElementById('camera-placeholder').style.display = 'none';
            document.getElementById('camera-section').style.display = 'block';
            document.getElementById('video').srcObject = stream;
        } catch (error) {
            Utils.showToast('Camera access is required for attendance verification', 'error');
            console.error('Camera error:', error);
        }
    },
    
    capturePhoto() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0);
        
        canvas.toBlob(blob => {
            this.capturedPhoto = blob;
            const url = URL.createObjectURL(blob);
            document.getElementById('captured-image').src = url;
            document.getElementById('camera-section').style.display = 'none';
            document.getElementById('photo-preview').style.display = 'block';
            this.checkSubmitReady();
        }, 'image/jpeg', 0.8);
    },
    
    retakePhoto() {
        this.capturedPhoto = null;
        document.getElementById('photo-preview').style.display = 'none';
        document.getElementById('camera-section').style.display = 'block';
        this.checkSubmitReady();
    },
    
    resetCamera() {
        if (this.mediaStream) {
            this.mediaStream.getTracks().forEach(track => track.stop());
            this.mediaStream = null;
        }
        
        this.capturedPhoto = null;
        document.getElementById('camera-placeholder').style.display = 'block';
        document.getElementById('camera-section').style.display = 'none';
        document.getElementById('photo-preview').style.display = 'none';
        this.checkSubmitReady();
    },
    
    checkSubmitReady() {
        const submitBtn = document.getElementById('submit-btn');
        const hasLocation = !!this.currentLocation;
        const hasPhoto = !!this.capturedPhoto;
        const hasBranch = this.selectedAction === 'checkout' || document.getElementById('branch-select').value;
        
        submitBtn.disabled = !(hasLocation && hasPhoto && hasBranch);
    },
    
    async submitAttendance(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submit-btn');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            // Prepare JSON data instead of FormData for now
            const data = {
                event_type: this.selectedAction === 'checkin' ? 'check_in' : 'check_out',
                latitude: this.currentLocation.latitude,
                longitude: this.currentLocation.longitude
            };
            
            if (this.selectedAction === 'checkin') {
                data.branch_id = document.getElementById('branch-select').value;
            }
            
            const notes = document.getElementById('notes').value;
            if (notes) data.notes = notes;
            
            // TODO: Handle selfie photo upload separately
            // For now, use JSON request
            const response = await API.post('/employee/attendance/checkin', data);
            
            Utils.showToast(response.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('attendanceModal')).hide();
            
            await this.loadCurrentStatus();
            await this.loadRecentAttendance();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },
    
    // Utility functions
    formatHours(hours) {
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        return `${h}h ${m}m`;
    },
    
    calculateWorkingHours(checkInTime) {
        const now = new Date();
        const checkIn = new Date(`${now.toDateString()} ${checkInTime}`);
        const diff = (now - checkIn) / (1000 * 60 * 60); // hours
        return this.formatHours(Math.max(0, diff));
    },
    
    getStatusBadgeClass(status) {
        const classes = {
            'present': 'bg-success',
            'late': 'bg-warning', 
            'absent': 'bg-danger',
            'partial': 'bg-info'
        };
        return classes[status] || 'bg-secondary';
    },
    
    startClock() {
        setInterval(() => {
            document.getElementById('current-time').textContent = new Date().toLocaleTimeString();
        }, 1000);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    attendanceManager.init();
    
    // Update submit button state when branch is selected
    document.getElementById('branch-select')?.addEventListener('change', () => {
        attendanceManager.checkSubmitReady();
    });
});
</script>
@endpush
