# Coffee Shop Attendance Management System - Complete Documentation

## 📋 Overview

Sistem absensi (attendance) yang dirancang khusus untuk mengelola kedai kopi dengan multiple cabang. Aplikasi ini menggunakan Laravel 9 dengan fitur GPS tracking, role-based access control (RBAC), dan manajemen shift yang fleksibel.

## 🏗️ Arsitektur & Teknologi

### Tech Stack
- **Backend**: Laravel 9 (PHP 8.0.2+)
- **Database**: MySQL 
- **Authentication**: Laravel Sanctum (API tokens)
- **Frontend**: Bootstrap 5.3, jQuery, FontAwesome
- **Additional**: 
  - GPS location tracking
  - Real-time clock
  - Photo verification (camera capture)
  - XAMPP environment

### Struktur Database Utama

#### 1. **Users Table**
- Menyimpan data user authentication
- Fields: id, name, email, password, role, is_active, last_login_at, preferences
- Roles: admin, employee, hr, hr_central, branch_manager, pengelola

#### 2. **Employees Table**  
- Profil lengkap karyawan
- Fields: user_id, employee_number, full_name, email, phone, position_id, hire_date, status, department, address, avatar
- Relasi: belongsTo User, Position

#### 3. **Attendances Table**
- Record kehadiran harian
- Fields utama:
  - employee_id, branch_id, date
  - check_in, check_out (timestamp)
  - scheduled_start, scheduled_end (time)
  - status (present, late, absent, half_day)
  - late_minutes, early_minutes, total_work_minutes
  - location_data (JSON - GPS coordinates)
  - has_corrections, correction_history
- Unique constraint: satu record per karyawan per hari

#### 4. **Branches Table**
- Data cabang/outlet
- Fields: code, name, address, latitude, longitude, radius (geofence), timezone, operating_hours, settings
- Fitur: Geofencing untuk validasi lokasi check-in

#### 5. **Work Shifts & Schedules**
- work_shifts: Template shift (Morning, Evening, Night)
- shift_slots: Detail waktu per shift
- employee_shift_schedules: Jadwal shift karyawan
- employee_shift_schedule_slots: Detail slot jadwal karyawan

#### 6. **Leave Management**
- leave_types: Jenis cuti (Annual, Sick, etc)
- leave_requests: Pengajuan cuti
- leave_balances: Saldo cuti karyawan

#### 7. **RBAC Tables**
- roles: Daftar role sistem
- permissions: Daftar permission
- user_roles: Mapping user ke role dengan scope
- user_permissions: Direct permission assignment

## 🔐 Authentication & Authorization

### Sistem Authentication
1. **Login API**: `/api/auth/login`
   - Input: email, password, device_name
   - Output: Bearer token (Sanctum)
   - Token abilities berdasarkan user permissions

2. **Logout**: Single device atau all devices

### Role-Based Access Control (RBAC)

#### Role Hierarchy:
1. **Admin** - Full system access
2. **HR Central** - Akses semua cabang, manage policies
3. **Branch Manager** - Manage assigned branches
4. **Pengelola** - Manage up to 3 branches
5. **Shift Leader** - Limited branch management
6. **Supervisor** - Team oversight
7. **Employee** - Self-service only

#### Key Features:
- Dynamic permission assignment
- Scoped permissions (branch/department level)
- Role inheritance
- Audit trail untuk semua aksi

## 📱 Fitur Utama

### 1. Attendance Check-in/Check-out

**Endpoint**: `POST /api/employee/attendance/checkin`

**Features**:
- GPS location validation (geofencing)
- Photo capture requirement
- Real-time clock display
- Automatic work hours calculation
- Late/early detection
- Overtime tracking

**Process Flow**:
```
1. User clicks Check-in
2. System captures GPS location
3. Validates against branch geofence
4. User takes selfie
5. Submit attendance record
6. Calculate late minutes if applicable
```

### 2. Attendance History & Reporting

**Features**:
- Monthly/daily attendance views
- Export to Excel/PDF
- Statistics dashboard
- Missing checkout alerts
- Correction request system

### 3. Schedule Management

**Features**:
- Flexible shift patterns
- Multiple shifts per day support
- Schedule templates
- Bulk schedule assignment
- Schedule change requests

### 4. Leave Management

**Features**:
- Multiple leave types
- Balance tracking
- Approval workflow
- Calendar integration
- Auto-deduction

### 5. Branch Management

**For Managers**:
- Real-time attendance monitoring
- Branch statistics
- Employee management
- Schedule oversight
- Report generation

## 🗂️ Project Structure

```
absence-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── SimpleAttendanceController.php
│   │   │   │   ├── AttendanceCorrectionController.php
│   │   │   │   ├── ScheduleController.php
│   │   │   │   └── ManagementController.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Employee.php
│   │   ├── Attendance.php
│   │   ├── Branch.php
│   │   └── [other models]
│   ├── Services/
│   │   ├── RBACService.php
│   │   ├── PolicyResolver.php
│   │   └── AttendanceNotificationService.php
│   └── Helpers/
│       └── AttendanceHelper.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php      # API endpoints
│   └── web.php      # Web routes
├── resources/
│   └── views/
│       ├── auth/
│       ├── employee/
│       └── layouts/
└── config/
```

## 🚀 Key API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get user profile

### Attendance
- `GET /api/employee/attendance/status` - Current status
- `POST /api/employee/attendance/checkin` - Check in/out
- `GET /api/employee/attendance/history` - History
- `GET /api/employee/attendance/stats` - Statistics

### Schedule
- `GET /api/employee/schedule` - My schedule
- `POST /api/employee/schedule/requests` - Request change

### Management
- `GET /api/management/dashboard` - Manager dashboard
- `GET /api/management/employees` - List employees
- `GET /api/management/attendance/overview` - Attendance overview

## 🔧 Configuration

### Environment Variables (.env)
```env
APP_NAME="Coffee Shop Attendance"
APP_URL=http://localhost/absence-app/public

DB_CONNECTION=mysql
DB_DATABASE=absence_app
DB_USERNAME=root
DB_PASSWORD=

# Timezone untuk Indonesia
APP_TIMEZONE=Asia/Jakarta

# Geofence default radius (meters)
GEOFENCE_RADIUS=100
```

### Key Configuration Files
- `config/app.php` - Application settings
- `config/auth.php` - Authentication guards
- `config/sanctum.php` - API token settings

## 🎨 UI/UX Features

### Login Page
- Role-based quick demo login
- Coffee shop themed design
- Mobile responsive
- Feature highlights

### Dashboard Features
- Real-time clock
- Today's status card
- Monthly statistics
- Quick actions
- Recent attendance table

### Check-in/out Interface
- GPS status indicator
- Camera preview
- Branch selection (if multiple access)
- Photo capture with retake option
- Loading states

## 🔒 Security Features

1. **API Token Authentication** - Bearer tokens via Sanctum
2. **GPS Validation** - Geofence checking
3. **Photo Verification** - Selfie requirement
4. **Audit Trail** - All actions logged
5. **Permission Checks** - Granular access control
6. **Session Management** - Token revocation

## 📊 Business Logic

### Attendance Rules
1. One check-in per day per employee
2. Check-out requires prior check-in
3. Overtime handling for late checkouts
4. Automatic absent marking
5. Late/early calculations based on schedule

### Leave Rules
1. Balance validation
2. Overlap prevention
3. Advance notice requirements
4. Approval hierarchy
5. Auto-deduction on approval

## 🚦 Development Guidelines

### Coding Standards
- PSR-12 compliance
- Repository pattern for data access
- Service layer for business logic
- API resource transformers
- Request validation classes

### Testing
- Unit tests for services
- Feature tests for APIs
- Browser tests for UI
- Performance benchmarks

## 📱 Mobile Compatibility

- Responsive design
- Touch-optimized UI
- Camera API support
- GPS permission handling
- Offline capability (planned)

## 🔄 Future Enhancements

1. **Facial Recognition** - Replace selfie with face matching
2. **Offline Mode** - Queue attendance when offline
3. **Push Notifications** - Reminders and alerts
4. **Advanced Analytics** - ML-based insights
5. **Integration APIs** - Payroll, HRIS systems
6. **Multi-language Support** - Bahasa Indonesia, English
7. **Biometric Integration** - Fingerprint scanners
8. **QR Code Check-in** - Alternative method

## 📞 Support & Maintenance

### Common Issues
1. GPS not working - Check browser permissions
2. Camera access denied - Enable camera permissions
3. Token expired - Re-login required
4. Branch not showing - Check user assignments

### Database Maintenance
- Daily backup recommended
- Archive old attendance records
- Cleanup expired tokens
- Optimize photo storage

### Performance Tips
- Enable query caching
- Use eager loading for relationships
- Implement pagination
- Compress uploaded photos
- Regular database indexing

## 📄 License & Credits

- Framework: Laravel (MIT License)
- Icons: FontAwesome
- UI: Bootstrap
- Development: Coffee Shop IT Team
