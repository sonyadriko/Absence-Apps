# 🏖️ Leave Management System

## Overview

The Leave Management System provides a comprehensive solution for managing employee leave requests in a coffee shop chain environment. It features a multi-level approval workflow, automated balance tracking, and role-based permissions.

## 📋 Table of Contents

1. [Features](#features)
2. [Leave Types](#leave-types)
3. [Approval Workflow](#approval-workflow)
4. [User Roles & Permissions](#user-roles--permissions)
5. [Database Structure](#database-structure)
6. [API Endpoints](#api-endpoints)
7. [Usage Guide](#usage-guide)
8. [Troubleshooting](#troubleshooting)

---

## ✨ Features

- **Multi-level Approval Workflow** - 3-tier approval system
- **Leave Balance Tracking** - Automatic calculation of remaining days
- **Multiple Leave Types** - Annual, Sick, Personal, Maternity, etc.
- **Document Upload** - Support for medical certificates and documents
- **Real-time Status Updates** - Track approval progress
- **Role-based Access Control** - Different permissions for different roles
- **Responsive UI** - Works on desktop and mobile devices
- **Audit Trail** - Complete history of all changes and approvals

---

## 🏷️ Leave Types

| Type | Code | Max Days/Year | Requires Document | Carry Forward |
|------|------|---------------|-------------------|---------------|
| **Annual Leave** | `annual` | 12 days | No | Yes (max 5 days) |
| **Sick Leave** | `sick` | 12 days | Yes (3+ days) | No |
| **Personal Leave** | `personal` | 5 days | No | No |
| **Maternity Leave** | `maternity` | 90 days | Yes | No |
| **Paternity Leave** | `paternity` | 3 days | Yes | No |
| **Emergency Leave** | `emergency` | Unlimited | No | No |

### Leave Type Settings

```json
{
  "annual": {
    "carry_forward_allowed": true,
    "max_carry_forward_days": 5,
    "advance_notice_days": 14
  },
  "sick": {
    "carry_forward_allowed": false,
    "advance_notice_days": 0,
    "requires_medical_certificate": true
  },
  "personal": {
    "carry_forward_allowed": false,
    "advance_notice_days": 7
  }
}
```

---

## 🔄 Approval Workflow

### Flow Diagram

```
Employee Submit Request
        ↓
   [PENDING Status]
        ↓
    Pengelola Review
        ↓
[APPROVED_BY_PENGELOLA]
        ↓
  Branch Manager Review
        ↓
[APPROVED_BY_MANAGER]
        ↓
    HR Central Review
        ↓
  [APPROVED_BY_HR]
        ↓
   [FINAL APPROVED] ✅
```

### Approval Levels

#### **Level 1: Pengelola (Store Supervisor)**
- **Role**: `pengelola` (Hierarchy Level 60)
- **Permission**: `leave.approve.level1`
- **Responsibility**: First-line approval for daily operations
- **Scope**: Up to 3 outlets
- **Status Change**: `pending` → `approved_by_pengelola`

#### **Level 2: Branch Manager**
- **Role**: `branch_manager` (Hierarchy Level 80)
- **Permission**: `leave.approve.level2`
- **Responsibility**: Multi-branch operational oversight
- **Scope**: Multiple branches
- **Status Change**: `approved_by_pengelola` → `approved_by_manager`

#### **Level 3: HR Central (Final Authority)**
- **Role**: `hr_central` (Hierarchy Level 100)
- **Permission**: `leave.approve.final`
- **Responsibility**: Global HR policies and final decisions
- **Scope**: All branches, company-wide
- **Status Change**: `approved_by_manager` → `approved_by_hr` → `approved`

### Status Definitions

| Status | Description | Next Action |
|--------|-------------|-------------|
| `pending` | Newly submitted request | Pengelola review |
| `approved_by_pengelola` | First level approved | Manager review |
| `approved_by_manager` | Second level approved | HR review |
| `approved_by_hr` | HR approved | System final approval |
| `approved` | **FULLY APPROVED** ✅ | No further action |
| `rejected` | Rejected at any level ❌ | Process ends |

---

## 👥 User Roles & Permissions

### Role Hierarchy

```
Level 100: 🏢 HR Central (Global Authority)
Level 90:  ⚙️ System Admin (Technical)
Level 80:  🏪 Branch Manager (Multi-branch)
Level 60:  📊 Pengelola (Up to 3 outlets)
Level 50:  👨‍💼 Supervisor (Customizable)
Level 40:  👨‍🏫 Shift Leader (Customizable)
Level 30:  ☕ Senior Barista (Customizable)
Level 10:  👨‍💼 Employee (Basic staff)
```

### Leave-Related Permissions

| Permission | Description | Assigned To |
|------------|-------------|-------------|
| `leave.create.own` | Create own leave requests | All employees |
| `leave.view.own` | View own leave requests | All employees |
| `leave.view.branch` | View branch leave requests | Supervisors+ |
| `leave.view.all` | View all leave requests | HR Central |
| `leave.approve.level1` | First level approval | Pengelola |
| `leave.approve.level2` | Second level approval | Branch Manager |
| `leave.approve.final` | Final approval | HR Central |

---

## 🗄️ Database Structure

### Core Tables

#### `leave_requests`
```sql
id                    - Primary key
employee_id           - Foreign key to employees
leave_type_id         - Foreign key to leave_types
start_date            - Leave start date
end_date              - Leave end date
total_days            - Working days requested
reason                - Leave reason
document_path         - Supporting document path
status                - Current approval status

-- Approval Chain Tracking
pengelola_approved_by    - User who approved at level 1
pengelola_approved_at    - Timestamp of level 1 approval
pengelola_notes          - Level 1 approval notes

manager_approved_by      - User who approved at level 2
manager_approved_at      - Timestamp of level 2 approval
manager_notes            - Level 2 approval notes

hr_approved_by           - User who approved at level 3
hr_approved_at           - Timestamp of level 3 approval
hr_notes                 - Level 3 approval notes

final_approved_by        - Final approver
final_approved_at        - Final approval timestamp

-- Rejection Tracking
rejected_by              - User who rejected
rejected_at              - Rejection timestamp
rejection_reason         - Reason for rejection

created_at, updated_at, deleted_at
```

#### `leave_types`
```sql
id                    - Primary key
name                  - Display name
code                  - Unique code (annual, sick, etc.)
description           - Detailed description
requires_approval     - Boolean flag
requires_document     - Boolean flag
max_days_per_year     - Maximum days allowed
is_paid               - Is this paid leave?
is_active             - Is this type active?
settings              - JSON configuration
```

#### `leave_balances`
```sql
id                    - Primary key
employee_id           - Foreign key to employees
leave_type_id         - Foreign key to leave_types
year                  - Balance year
allocated_days        - Days allocated for the year
used_days             - Days used so far
carry_forward_days    - Days carried from previous year
remaining_days        - Calculated remaining days
```

---

## 🔌 API Endpoints

### Employee Leave Management

#### Get Leave Requests
```http
GET /api/employee/leave?year=2025&status=pending&type=annual
```

**Parameters:**
- `year` (optional) - Filter by year
- `status` (optional) - Filter by status
- `type` (optional) - Filter by leave type
- `page` (optional) - Page number for pagination

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "leave_type": "annual",
        "duration_type": "full_day",
        "start_date": "2025-09-01",
        "end_date": "2025-09-05",
        "total_days": 5,
        "reason": "Family vacation",
        "status": "pending",
        "created_at": "2025-08-28T10:00:00Z",
        "supporting_document_url": null
      }
    ],
    "last_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

#### Get Leave Balance
```http
GET /api/employee/leave/balance?year=2025
```

**Response:**
```json
{
  "success": true,
  "data": {
    "annual": 7,
    "sick": 12,
    "personal": 3,
    "total_used": 5,
    "year": 2025
  }
}
```

#### Create Leave Request
```http
POST /api/employee/leave
Content-Type: multipart/form-data
```

**Form Data:**
- `leave_type` - Leave type code
- `duration_type` - full_day, half_day, or hourly
- `start_date` - Start date (YYYY-MM-DD)
- `end_date` - End date (YYYY-MM-DD)
- `start_time` - Start time (HH:MM) for hourly leaves
- `end_time` - End time (HH:MM) for hourly leaves
- `reason` - Reason for leave
- `emergency_contact` - Emergency contact info
- `supporting_document` - File upload (optional)

#### Get Leave Request Details
```http
GET /api/employee/leave/{id}
```

#### Cancel Leave Request
```http
PUT /api/employee/leave/{id}/cancel
```

---

## 📖 Usage Guide

### For Employees

#### 1. Submitting a Leave Request

1. Navigate to **Leave Requests** page
2. Click **"+ New Request"** button
3. Fill in the form:
   - Select leave type
   - Choose duration type
   - Set start and end dates
   - Provide reason
   - Upload supporting documents (if required)
4. Click **"Submit Request"**
5. Request status will be **"Pending"**

#### 2. Tracking Request Status

- View all requests in the main table
- Use filters to find specific requests
- Click **"View Details"** for complete information
- Check approval progress and notes

#### 3. Canceling Requests

- Only **pending** requests can be canceled
- Click the **"Cancel"** button
- Confirm cancellation in the dialog

### For Approvers (Pengelola/Manager/HR)

#### 1. Reviewing Requests

1. Navigate to Leave Management dashboard
2. View pending requests assigned to your level
3. Click on request to see details
4. Review:
   - Employee information
   - Leave dates and duration
   - Reason provided
   - Supporting documents
   - Leave balance impact

#### 2. Approving Requests

1. Open request details
2. Add approval notes (optional)
3. Click **"Approve"** button
4. Request moves to next approval level

#### 3. Rejecting Requests

1. Open request details
2. Provide rejection reason
3. Click **"Reject"** button
4. Employee will be notified

---

## 🎨 Frontend Features

### Dashboard Cards
- **Annual Leave Balance** - Shows remaining annual leave days
- **Sick Leave Balance** - Shows remaining sick leave days
- **Personal Leave Balance** - Shows remaining personal leave days
- **Total Used** - Shows total days used this year

### Filtering & Search
- Filter by year (current year ±2)
- Filter by status (All, Pending, Approved, Rejected)
- Filter by leave type
- Real-time filtering without page reload

### Request Management
- **Create New Request** - Modal form with validation
- **View Details** - Comprehensive request information
- **Edit Requests** - Only for pending requests
- **Cancel Requests** - Only for pending requests
- **Download Documents** - Supporting document downloads

### Responsive Design
- Mobile-friendly interface
- Touch-optimized buttons
- Collapsible sidebar on mobile
- Adaptive tables and forms

---

## ⚙️ Configuration

### Leave Policy Configuration

Edit `leave_types` table settings column:

```json
{
  "carry_forward_allowed": true,
  "max_carry_forward_days": 5,
  "advance_notice_days": 14,
  "requires_medical_certificate": false,
  "gender_specific": null,
  "consecutive_days_limit": 10,
  "annual_reset_date": "01-01",
  "prorate_for_new_employees": true
}
```

### Role Permissions

To modify approval permissions:

1. Go to **Admin > Role Management**
2. Select the role to modify
3. Adjust leave-related permissions:
   - `leave.approve.level1` for Pengelola
   - `leave.approve.level2` for Branch Manager
   - `leave.approve.final` for HR Central

---

## 🔧 Troubleshooting

### Common Issues

#### 1. "Invalid Date" Error
**Problem**: JavaScript date formatting issues
**Solution**: 
- Check date format in API responses
- Ensure UTC/timezone handling is correct
- Verify Utils.formatDate() function

#### 2. Filter Not Working
**Problem**: API authentication or route issues
**Solution**:
- Verify user is logged in
- Check API middleware configuration
- Confirm CSRF token handling

#### 3. File Upload Issues
**Problem**: Document upload failing
**Solution**:
- Check file size limits (max 5MB)
- Verify allowed file types
- Ensure storage permissions

#### 4. Approval Notifications Not Sent
**Problem**: Email/notification system issues
**Solution**:
- Check mail configuration
- Verify notification queues
- Review approval event listeners

### Debug Steps

1. **Check Browser Console**: Look for JavaScript errors
2. **Network Tab**: Verify API calls and responses
3. **Laravel Logs**: Check `storage/logs/laravel.log`
4. **Database**: Verify data integrity and relationships
5. **Permissions**: Confirm user has required permissions

### Performance Optimization

1. **Database Indexing**:
   ```sql
   CREATE INDEX idx_leave_requests_employee_dates ON leave_requests(employee_id, start_date, end_date);
   CREATE INDEX idx_leave_requests_status_created ON leave_requests(status, created_at);
   ```

2. **Caching**:
   - Cache leave balances for current year
   - Cache role permissions
   - Use Redis for session management

3. **Query Optimization**:
   - Use eager loading for relationships
   - Paginate large result sets
   - Optimize API response payloads

---

## 📧 Support

### System Requirements
- **PHP**: 8.1+
- **Laravel**: 10.x
- **MySQL**: 8.0+
- **Node.js**: 16+ (for asset compilation)

### File Locations
- **Controllers**: `app/Http/Controllers/Api/LeaveController.php`
- **Models**: `app/Models/LeaveRequest.php`, `app/Models/LeaveType.php`
- **Migrations**: `database/migrations/*leave*.php`
- **Views**: `resources/views/employee/leave/index.blade.php`
- **API Routes**: `routes/api.php`

### Key Dependencies
- **Laravel Sanctum**: API authentication
- **Bootstrap 5**: UI framework
- **Font Awesome**: Icons
- **jQuery**: DOM manipulation
- **DataTables**: Table functionality

---

## 📄 License

This Leave Management System is part of the Coffee Shop Attendance Management Application.

---

*Last updated: August 28, 2025*
*Version: 1.0.0*
