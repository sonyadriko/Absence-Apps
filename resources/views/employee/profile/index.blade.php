@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-user me-2"></i>My Profile
            </h1>
            <p class="text-muted mb-0">Manage your personal information and employment details</p>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-outline-primary" id="change-password-btn">
                <i class="fas fa-key me-2"></i>Change Password
            </button>
            <button class="btn btn-primary" id="edit-profile-btn">
                <i class="fas fa-edit me-2"></i>Edit Profile
            </button>
        </div>
    </div>

    <!-- Profile Overview -->
    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="stats-card text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white; border-left: none;">
                <div class="position-relative d-inline-block mb-3">
                    <img id="profile-photo" src="" alt="Profile Photo" 
                         class="rounded-circle" width="120" height="120" 
                         style="object-fit: cover; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <button class="btn btn-light btn-sm rounded-circle position-absolute" 
                            style="bottom: 0; right: 0; width: 36px; height: 36px; color: var(--primary-color);" 
                            id="upload-photo-btn">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <h5 class="mb-1 text-white" id="profile-name">Loading...</h5>
                <p class="text-white-50" id="profile-role">Role</p>
                <div class="row text-center mt-4">
                    <div class="col-4">
                        <div class="h5 mb-0 text-white" id="profile-experience">0</div>
                        <small class="text-white-50">Years</small>
                    </div>
                    <div class="col-4">
                        <div class="h5 mb-0 text-white" id="profile-branch">-</div>
                        <small class="text-white-50">Branch</small>
                    </div>
                    <div class="col-4">
                        <div class="h5 mb-0 text-white" id="profile-status">-</div>
                        <small class="text-white-50">Status</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="stats-card">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-coffee text-primary me-2 fs-5"></i>
                    <h5 class="mb-0 text-primary">Quick Overview</h5>
                </div>
                <div class="row" id="profile-overview">
                    <!-- Content will be loaded dynamically -->
                    <div class="col-12 text-center py-4">
                        <div class="spinner-border" style="color: var(--primary-color);" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Details Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="stats-card" style="padding: 0; overflow: hidden;">
                <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); border: none; margin: 0;">
                    <ul class="nav nav-tabs card-header-tabs border-0" id="profileTabs" role="tablist" 
                        style="border-bottom: none !important;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-white border-0" id="personal-tab" data-bs-toggle="tab" 
                                    data-bs-target="#personal" type="button" role="tab"
                                    style="background: rgba(255,255,255,0.1); border-radius: 8px 8px 0 0;">
                                <i class="fas fa-user me-2"></i>Personal Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white-50 border-0" id="employment-tab" data-bs-toggle="tab" 
                                    data-bs-target="#employment" type="button" role="tab">
                                <i class="fas fa-coffee me-2"></i>Employment Details
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white-50 border-0" id="contact-tab" data-bs-toggle="tab" 
                                    data-bs-target="#contact" type="button" role="tab">
                                <i class="fas fa-phone me-2"></i>Contact Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white-50 border-0" id="documents-tab" data-bs-toggle="tab" 
                                    data-bs-target="#documents" type="button" role="tab">
                                <i class="fas fa-file-alt me-2"></i>Documents
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body" style="padding: 30px;">
                    <div class="tab-content" id="profileTabContent">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <form id="personalInfoForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required 
                                                   placeholder="Enter your full name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="employee_number" class="form-label">Employee Number</label>
                                            <input type="text" class="form-control" id="employee_number" name="employee_number" readonly
                                                   style="background-color: #f8f9fa;">
                                            <div class="form-text">Auto-generated by system</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select class="form-select" id="gender" name="gender">
                                                <option value="">Select gender</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="id_number" class="form-label">ID Number (KTP)</label>
                                            <input type="text" class="form-control" id="id_number" name="id_number" 
                                                   placeholder="16 digit KTP number" maxlength="16">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="blood_type" class="form-label">Blood Type</label>
                                            <select class="form-select" id="blood_type" name="blood_type">
                                                <option value="">Select blood type</option>
                                                <option value="A+">A+</option>
                                                <option value="A-">A-</option>
                                                <option value="B+">B+</option>
                                                <option value="B-">B-</option>
                                                <option value="AB+">AB+</option>
                                                <option value="AB-">AB-</option>
                                                <option value="O+">O+</option>
                                                <option value="O-">O-</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="bio" class="form-label">About Me</label>
                                            <textarea class="form-control" id="bio" name="bio" rows="3" 
                                                      placeholder="Share something about yourself, your coffee passion, or experience..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Employment Details Tab -->
                        <div class="tab-pane fade" id="employment" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-id-badge me-2"></i>Employee Number</label>
                                        <input type="text" class="form-control" id="employee_id" readonly style="background-color: #f8f9fa;">
                                        <div class="form-text">Auto-generated by system</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-calendar-plus me-2"></i>Hire Date</label>
                                        <input type="text" class="form-control" id="join_date" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-coffee me-2"></i>Position</label>
                                        <input type="text" class="form-control" id="position" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-user-check me-2"></i>Employment Status</label>
                                        <input type="text" class="form-control" id="employment_status" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-clock me-2"></i>Employment Type</label>
                                        <input type="text" class="form-control" id="employment_type" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-dollar-sign me-2"></i>Hourly Rate</label>
                                        <input type="text" class="form-control" id="hourly_rate" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-store me-2"></i>Primary Branch</label>
                                        <input type="text" class="form-control" id="primary_branch" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-building me-2"></i>Department</label>
                                        <input type="text" class="form-control" id="department" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>
                            </div>

                            <!-- Work Schedule -->
                            <div class="mt-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-calendar-week text-primary me-2"></i>
                                    <h6 class="mb-0 text-primary">Work Schedule</h6>
                                </div>
                                <div id="work-schedule" class="stats-card">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" style="color: var(--primary-color);" role="status"></div>
                                        <p class="text-muted mt-2 mb-0">Loading schedule...</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Assigned Branches -->
                            <div class="mt-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                    <h6 class="mb-0 text-primary">Assigned Branches</h6>
                                </div>
                                <div id="assigned-branches">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" style="color: var(--primary-color);" role="status"></div>
                                        <p class="text-muted mt-2 mb-0">Loading branches...</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Roles -->
                            <div class="mt-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-user-shield text-primary me-2"></i>
                                    <h6 class="mb-0 text-primary">Current Roles & Permissions</h6>
                                </div>
                                <div id="current-roles">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" style="color: var(--primary-color);" role="status"></div>
                                        <p class="text-muted mt-2 mb-0">Loading roles...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Tab -->
                        <div class="tab-pane fade" id="contact" role="tabpanel">
                            <form id="contactInfoForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope me-2"></i>Email Address <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" class="form-control" id="email" name="email" required readonly
                                                   style="background-color: #f8f9fa;">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>Email cannot be changed. Contact HR if needed.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">
                                                <i class="fas fa-phone me-2"></i>Phone Number
                                            </label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   placeholder="e.g., +62812-3456-7890">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="emergency_contact_name" class="form-label">
                                                <i class="fas fa-user-plus me-2"></i>Emergency Contact Name
                                            </label>
                                            <input type="text" class="form-control" id="emergency_contact_name" 
                                                   name="emergency_contact_name" placeholder="Contact person name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="emergency_contact_phone" class="form-label">
                                                <i class="fas fa-phone-alt me-2"></i>Emergency Contact Phone
                                            </label>
                                            <input type="tel" class="form-control" id="emergency_contact_phone" 
                                                   name="emergency_contact_phone" placeholder="Emergency contact number">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="emergency_relation" class="form-label">
                                                <i class="fas fa-heart me-2"></i>Relationship
                                            </label>
                                            <select class="form-select" id="emergency_relation" name="emergency_relation">
                                                <option value="">Select relationship</option>
                                                <option value="parent">Parent</option>
                                                <option value="spouse">Spouse</option>
                                                <option value="sibling">Sibling</option>
                                                <option value="child">Child</option>
                                                <option value="friend">Friend</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="transportation" class="form-label">
                                                <i class="fas fa-car me-2"></i>Transportation
                                            </label>
                                            <select class="form-select" id="transportation" name="transportation">
                                                <option value="">Select transportation</option>
                                                <option value="motorcycle">Motorcycle</option>
                                                <option value="car">Car</option>
                                                <option value="public_transport">Public Transport</option>
                                                <option value="bicycle">Bicycle</option>
                                                <option value="walking">Walking</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">
                                                <i class="fas fa-map-marker-alt me-2"></i>Home Address
                                            </label>
                                            <textarea class="form-control" id="address" name="address" rows="3" 
                                                      placeholder="Complete home address (street, city, postal code)"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="stats-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-upload text-primary me-2"></i>
                                            <h6 class="mb-0 text-primary">Upload Documents</h6>
                                        </div>
                                        <form id="documentUploadForm" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="document_type" class="form-label">Document Type</label>
                                                <select class="form-select" id="document_type" name="document_type" required>
                                                    <option value="">Select document type</option>
                                                    <option value="id_card">KTP (ID Card)</option>
                                                    <option value="family_card">Kartu Keluarga (Family Card)</option>
                                                    <option value="cv">CV/Resume</option>
                                                    <option value="diploma">Diploma/Certificate</option>
                                                    <option value="health_certificate">Health Certificate</option>
                                                    <option value="food_safety_cert">Food Safety Certificate</option>
                                                    <option value="barista_cert">Barista Certificate</option>
                                                    <option value="contract">Employment Contract</option>
                                                    <option value="bank_account">Bank Account Info</option>
                                                    <option value="tax_number">NPWP (Tax ID)</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="document_file" class="form-label">Choose File</label>
                                                <input type="file" class="form-control" id="document_file" name="document_file" 
                                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Accepted: PDF, JPG, PNG, DOC, DOCX (max 5MB)
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="document_description" class="form-label">Description (Optional)</label>
                                                <input type="text" class="form-control" id="document_description" 
                                                       name="description" placeholder="Brief description or notes">
                                            </div>
                                            <button type="submit" class="btn" style="background-color: var(--primary-color); color: white;">
                                                <i class="fas fa-upload me-2"></i>Upload Document
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="stats-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-folder text-primary me-2"></i>
                                            <h6 class="mb-0 text-primary">My Documents</h6>
                                        </div>
                                        <div id="documents-list">
                                            <div class="text-center py-3">
                                                <div class="spinner-border spinner-border-sm" style="color: var(--primary-color);" role="status"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Upload Modal -->
<div class="modal fade" id="photoUploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-camera me-2"></i>Update Profile Photo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="photoUploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="photo-preview" src="" alt="Photo preview" 
                             class="rounded-circle" width="120" height="120" 
                             style="object-fit: cover; display: none;">
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Choose Photo</label>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                               accept="image/jpeg,image/jpg,image/png" required>
                        <div class="form-text">Accepted formats: JPG, PNG (max 2MB). Square images work best.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Photo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               minlength="8" required>
                        <div class="form-text">Password must be at least 8 characters long</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="new_password_confirmation" 
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Custom tab styling */
#profileTabs .nav-link {
    transition: all 0.3s ease;
    margin-right: 8px;
    border-radius: 8px 8px 0 0 !important;
}

#profileTabs .nav-link:hover {
    background: rgba(255,255,255,0.15) !important;
    color: white !important;
}

#profileTabs .nav-link.active {
    background: rgba(255,255,255,0.2) !important;
    color: white !important;
}

/* Profile photo hover effect */
#profile-photo {
    transition: all 0.3s ease;
}

.position-relative:hover #profile-photo {
    transform: scale(1.05);
}

/* Form styling improvements */
.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
}

/* Loading spinner coffee color */
.spinner-border {
    color: var(--primary-color) !important;
}

/* Enhanced button styling */
.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--dark-color);
    border-color: var(--dark-color);
}
</style>
@endpush

@push('scripts')
<script>
let profileManager = {
    profileData: null,
    editMode: false,
    
    init() {
        this.loadProfile();
        this.setupEventListeners();
        this.loadEmploymentDetails();
        this.loadDocuments();
    },
    
    setupEventListeners() {
        // Main action buttons
        document.getElementById('edit-profile-btn').addEventListener('click', () => this.toggleEditMode());
        document.getElementById('change-password-btn').addEventListener('click', () => this.openChangePasswordModal());
        document.getElementById('upload-photo-btn').addEventListener('click', () => this.openPhotoUploadModal());
        
        // Form submissions
        document.getElementById('personalInfoForm').addEventListener('submit', (e) => this.savePersonalInfo(e));
        document.getElementById('contactInfoForm').addEventListener('submit', (e) => this.saveContactInfo(e));
        document.getElementById('photoUploadForm').addEventListener('submit', (e) => this.uploadPhoto(e));
        document.getElementById('changePasswordForm').addEventListener('submit', (e) => this.changePassword(e));
        document.getElementById('documentUploadForm').addEventListener('submit', (e) => this.uploadDocument(e));
        
        // Photo preview
        document.getElementById('profile_photo').addEventListener('change', (e) => this.previewPhoto(e));
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', () => this.validatePasswordConfirmation());
        document.getElementById('new_password').addEventListener('input', () => this.validatePasswordConfirmation());
    },
    
    async loadProfile() {
        try {
            const response = await API.get('/auth/me');
            this.profileData = response.data;
            this.updateProfileUI();
        } catch (error) {
            Utils.handleApiError(error, 'Failed to load profile data');
        }
    },
    
    updateProfileUI() {
        const data = this.profileData;
        
        // Profile header
        document.getElementById('profile-name').textContent = data.name || 'No Name';
        document.getElementById('profile-role').textContent = data.employee?.current_role?.display_name || 'No Role Assigned';
        
        // Profile photo
        const photoUrl = data.employee?.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(data.name)}&size=120&background=8B4513&color=fff`;
        document.getElementById('profile-photo').src = photoUrl;
        
        // Quick stats
        const joinDate = data.employee?.hire_date ? new Date(data.employee.hire_date) : null;
        const experience = joinDate ? Math.floor((new Date() - joinDate) / (365.25 * 24 * 60 * 60 * 1000)) : 0;
        document.getElementById('profile-experience').textContent = experience;
        document.getElementById('profile-branch').textContent = data.employee?.primary_branch?.name || 'None';
        document.getElementById('profile-status').textContent = data.employee?.status || 'Active';
        
        // Profile overview
        this.updateProfileOverview();
        
        // Form fields
        this.populateFormFields();
    },
    
    updateProfileOverview() {
        const data = this.profileData;
        const overview = document.getElementById('profile-overview');
        
        overview.innerHTML = `
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="fw-medium">Full Name:</td>
                        <td>${data.name || 'Not set'}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Email:</td>
                        <td>${data.email}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Phone:</td>
                        <td>${data.employee?.phone || 'Not set'}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Employee ID:</td>
                        <td>${data.employee?.employee_id || 'Not assigned'}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="fw-medium">Join Date:</td>
                        <td>${data.employee?.hire_date ? Utils.formatDate(data.employee.hire_date) : 'Not set'}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Position:</td>
                        <td>${data.employee?.position || 'Not assigned'}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Department:</td>
                        <td>${data.employee?.department || 'Not assigned'}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Status:</td>
                        <td><span class="badge ${this.getStatusBadgeClass(data.employee?.status)}">${data.employee?.status || 'Active'}</span></td>
                    </tr>
                </table>
            </div>
        `;
    },
    
    populateFormFields() {
        const data = this.profileData;
        const employee = data.employee || {};
        
        // Personal info
        document.getElementById('full_name').value = employee.full_name || data.name || '';
        document.getElementById('employee_number').value = employee.employee_number || 'Not assigned';
        document.getElementById('date_of_birth').value = employee.date_of_birth || '';
        document.getElementById('gender').value = employee.gender || '';
        document.getElementById('id_number').value = employee.id_number || '';
        document.getElementById('blood_type').value = employee.blood_type || '';
        document.getElementById('bio').value = employee.bio || '';
        
        // Contact info
        document.getElementById('email').value = data.email || '';
        document.getElementById('phone').value = employee.phone || '';
        document.getElementById('emergency_contact_name').value = employee.emergency_contact_name || '';
        document.getElementById('emergency_contact_phone').value = employee.emergency_contact_phone || '';
        document.getElementById('emergency_relation').value = employee.emergency_relation || '';
        document.getElementById('transportation').value = employee.transportation || '';
        document.getElementById('address').value = employee.address || '';
        
        // Employment (readonly)
        document.getElementById('employee_id').value = employee.employee_number || 'Not assigned';
        document.getElementById('join_date').value = employee.hire_date ? Utils.formatDate(employee.hire_date) : 'Not set';
        document.getElementById('position').value = employee.position?.name || 'Not assigned';
        document.getElementById('employment_status').value = employee.status || 'Active';
        document.getElementById('employment_type').value = employee.employment_type || 'Not assigned';
        document.getElementById('hourly_rate').value = employee.hourly_rate ? 'Rp ' + employee.hourly_rate.toLocaleString() : 'Not set';
        document.getElementById('primary_branch').value = employee.primary_branch?.name || 'Not assigned';
        document.getElementById('department').value = employee.department || 'Not assigned';
    },
    
    async loadEmploymentDetails() {
        try {
            // Load assigned branches
            const branchesResponse = await API.get('/branches');
            this.displayAssignedBranches(branchesResponse.data);
            
            // Load current roles
            // Note: This would typically come from the user profile API
            this.displayCurrentRoles();
            
        } catch (error) {
            console.error('Failed to load employment details:', error);
        }
    },
    
    displayAssignedBranches(branches) {
        const container = document.getElementById('assigned-branches');
        
        if (!branches || branches.length === 0) {
            container.innerHTML = '<p class="text-muted">No branches assigned</p>';
            return;
        }
        
        container.innerHTML = branches.map(branch => `
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-building text-primary me-3"></i>
                    <div>
                        <h6 class="mb-0">${branch.name}</h6>
                        <small class="text-muted">${branch.location || 'Location not specified'}</small>
                    </div>
                </div>
                <span class="badge bg-success">Active</span>
            </div>
        `).join('');
    },
    
    displayCurrentRoles() {
        const container = document.getElementById('current-roles');
        const roles = this.profileData?.employee?.roles || [];
        
        if (!roles || roles.length === 0) {
            container.innerHTML = '<p class="text-muted">No roles assigned</p>';
            return;
        }
        
        container.innerHTML = roles.map(userRole => `
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="badge" style="background-color: ${userRole.role.color}; width: 12px; height: 12px; border-radius: 50%;"></div>
                    </div>
                    <div>
                        <h6 class="mb-0">${userRole.role.display_name}</h6>
                        <small class="text-muted">${userRole.role.description}</small>
                    </div>
                </div>
                <div class="text-end">
                    <small class="text-muted">Level ${userRole.role.hierarchy_level}</small><br>
                    <span class="badge ${userRole.is_active ? 'bg-success' : 'bg-secondary'} small">
                        ${userRole.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
            </div>
        `).join('');
    },
    
    async loadDocuments() {
        try {
            const response = await API.get('/employee/documents');
            this.displayDocuments(response.data);
        } catch (error) {
            document.getElementById('documents-list').innerHTML = '<p class="text-muted">No documents uploaded</p>';
        }
    },
    
    displayDocuments(documents) {
        const container = document.getElementById('documents-list');
        
        if (!documents || documents.length === 0) {
            container.innerHTML = '<p class="text-muted">No documents uploaded</p>';
            return;
        }
        
        container.innerHTML = documents.map(doc => `
            <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fas ${this.getDocumentIcon(doc.type)} text-primary me-2"></i>
                    <div>
                        <div class="small fw-medium">${doc.name || doc.type}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">${Utils.formatDate(doc.uploaded_at)}</div>
                    </div>
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="profileManager.downloadDocument('${doc.id}')">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="profileManager.deleteDocument('${doc.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    },
    
    toggleEditMode() {
        this.editMode = !this.editMode;
        const btn = document.getElementById('edit-profile-btn');
        const forms = document.querySelectorAll('#personal input, #personal textarea, #contact input, #contact textarea');
        
        if (this.editMode) {
            btn.innerHTML = '<i class="fas fa-times me-2"></i>Cancel Edit';
            btn.className = 'btn btn-outline-secondary';
            forms.forEach(input => {
                if (input.id !== 'email') input.readOnly = false;
            });
        } else {
            btn.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Profile';
            btn.className = 'btn btn-primary';
            forms.forEach(input => input.readOnly = true);
            this.populateFormFields(); // Reset form values
        }
    },
    
    async savePersonalInfo(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const response = await API.put('/auth/profile', data);
            
            Utils.showToast(response.message, 'success');
            await this.loadProfile();
            this.toggleEditMode();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },
    
    async saveContactInfo(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const response = await API.put('/auth/profile', data);
            
            Utils.showToast(response.message, 'success');
            await this.loadProfile();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },
    
    openPhotoUploadModal() {
        const modal = new bootstrap.Modal(document.getElementById('photoUploadModal'));
        document.getElementById('photoUploadForm').reset();
        document.getElementById('photo-preview').style.display = 'none';
        modal.show();
    },
    
    previewPhoto(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('photo-preview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    },
    
    async uploadPhoto(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const response = await API.postForm('/auth/profile/photo', formData);
            
            Utils.showToast(response.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('photoUploadModal')).hide();
            
            // Update profile photo
            document.getElementById('profile-photo').src = response.data.photo_url;
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },
    
    openChangePasswordModal() {
        const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
        document.getElementById('changePasswordForm').reset();
        modal.show();
    },
    
    validatePasswordConfirmation() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const confirmInput = document.getElementById('confirm_password');
        
        if (confirmPassword && newPassword !== confirmPassword) {
            confirmInput.setCustomValidity('Passwords do not match');
            confirmInput.classList.add('is-invalid');
        } else {
            confirmInput.setCustomValidity('');
            confirmInput.classList.remove('is-invalid');
        }
    },
    
    async changePassword(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const response = await API.put('/auth/password', data);
            
            Utils.showToast(response.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },
    
    async uploadDocument(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        Utils.setButtonLoading(submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            const response = await API.postForm('/employee/documents', formData);
            
            Utils.showToast(response.message, 'success');
            document.getElementById('documentUploadForm').reset();
            await this.loadDocuments();
            
        } catch (error) {
            Utils.handleApiError(error);
        } finally {
            Utils.setButtonLoading(submitBtn, false);
        }
    },
    
    async downloadDocument(documentId) {
        try {
            window.open(`/api/employee/documents/${documentId}/download`, '_blank');
        } catch (error) {
            Utils.handleApiError(error, 'Failed to download document');
        }
    },
    
    async deleteDocument(documentId) {
        if (await Utils.confirm('Are you sure you want to delete this document?', 'Delete Document')) {
            try {
                await API.delete(`/employee/documents/${documentId}`);
                Utils.showToast('Document deleted successfully', 'success');
                await this.loadDocuments();
            } catch (error) {
                Utils.handleApiError(error, 'Failed to delete document');
            }
        }
    },
    
    // Utility functions
    getStatusBadgeClass(status) {
        const classes = {
            'active': 'bg-success',
            'inactive': 'bg-secondary',
            'terminated': 'bg-danger',
            'on_leave': 'bg-warning'
        };
        return classes[status] || 'bg-primary';
    },
    
    getDocumentIcon(type) {
        const icons = {
            'cv': 'fa-file-user',
            'certificate': 'fa-certificate',
            'id_card': 'fa-id-card',
            'contract': 'fa-file-contract',
            'other': 'fa-file-alt'
        };
        return icons[type] || 'fa-file-alt';
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    profileManager.init();
});
</script>
@endpush
