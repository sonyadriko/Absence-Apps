# 🚀 Role Management System - HR Central Coffee Shop

## 📋 Overview

Sistem Role Management ini adalah sistem RBAC (Role-Based Access Control) yang sangat fleksibel dan dinamis, khusus dirancang untuk coffee shop chain. Sistem ini memungkinkan Anda untuk:

- ✅ Membuat role custom tanpa coding
- ✅ Mengatur permissions granular
- ✅ Assignment role ke user dengan scope restrictions
- ✅ Sistem approval berlevel (hierarchy)
- ✅ Dashboard custom per role
- ✅ Role templates untuk setup cepat

## 🎯 Pre-defined Roles

### System Roles (Tidak dapat diubah)

1. **HR Central** (Level 100)
   - Akses penuh ke semua fitur
   - Dapat mengelola semua roles dan permissions
   - Dashboard executive dengan global summary

2. **System Admin** (Level 90)
   - Akses teknis ke sistem
   - Manage system settings dan audit logs

3. **Branch Manager** (Level 80)
   - Mengelola multiple branches
   - Approval level 2 untuk leave requests
   - Akses ke reports multi-branch

4. **Pengelola** (Level 60)
   - Mengelola hingga 3 outlets
   - Create schedules untuk branch
   - Approval level 1 untuk leave requests

5. **Employee** (Level 10)
   - Akses basic untuk attendance dan schedule pribadi
   - Bisa apply leave requests

### Customizable Roles

6. **Supervisor** (Level 50)
7. **Shift Leader** (Level 40)
8. **Senior Barista** (Level 30)

## 🛠 Cara Menggunakan

### 1. Akses Admin Panel

```
URL: http://your-app.com/admin/roles
Akses: HR Central atau System Admin
```

### 2. Membuat Role Baru

#### Via Interface Admin:
1. Klik "Create Custom Role"
2. Isi form:
   - **Role Name**: nama_role (lowercase, underscore)
   - **Display Name**: Nama Role (untuk display)
   - **Hierarchy Level**: 1-99 (semakin tinggi = lebih berkuasa)
   - **Color**: Warna untuk badge role
   - **Description**: Deskripsi role
   - **Permissions**: Pilih permissions yang diinginkan

#### Via API:
```php
POST /api/admin/roles
{
    "name": "area_manager",
    "display_name": "Area Manager",
    "description": "Manages multiple regions of coffee shops",
    "color": "#8e44ad",
    "hierarchy_level": 75,
    "permissions": [
        "branch.view.assigned",
        "employee.view.branch",
        "attendance.view.branch",
        "report.view.branch",
        "leave.approve.level2"
    ]
}
```

### 3. Menggunakan Role Templates

#### Available Templates:
- **coffee_manager**: Complete coffee shop management
- **drive_thru_specialist**: Special role for drive-thru operations
- **night_shift_manager**: Manager for night shift
- **quality_inspector**: Quality control across locations

```php
POST /api/admin/roles/template
{
    "template": "coffee_manager",
    "name": "jakarta_manager",
    "display_name": "Jakarta Store Manager"
}
```

### 4. Assign Role ke User

```php
// Via RBAC Service
$rbacService->assignRole($user, $role, [
    'branches' => [1, 2, 3], // Hanya bisa akses branch ini
    'date_range' => [
        'from' => '2024-01-01',
        'until' => '2024-12-31'
    ]
]);

// Via API
POST /api/admin/user-roles/assign
{
    "user_id": 1,
    "role_id": 2,
    "scope_data": {
        "branches": [1, 2, 3]
    }
}
```

## 🔐 Permission System

### Permission Format
```
{resource}.{action}.{scope}
```

### Contoh Permissions:

#### Attendance
- `attendance.view.all` - Lihat semua attendance
- `attendance.view.branch` - Lihat attendance branch saja
- `attendance.view.own` - Lihat attendance sendiri
- `attendance.edit.branch` - Edit attendance di branch
- `attendance.approve.corrections` - Approve koreksi attendance

#### Schedule
- `schedule.create.branch` - Buat schedule untuk branch
- `schedule.view.own` - Lihat schedule sendiri
- `schedule.manage.roster` - Kelola roster harian

#### Employee Management
- `employee.view.branch` - Lihat employee di branch
- `employee.create.all` - Buat employee baru
- `employee.edit.branch` - Edit employee di branch

#### Leave Management
- `leave.approve.level1` - Approve leave level 1
- `leave.approve.level2` - Approve leave level 2
- `leave.approve.final` - Final approval

#### System
- `system.role.manage` - Kelola roles (admin only)
- `system.audit.view` - Lihat audit logs

## 📊 Scope Restrictions

### Branch Restrictions
```php
'scope_data' => [
    'branches' => [1, 2, 5] // Hanya bisa akses branch ID 1, 2, 5
]
```

### Date Restrictions
```php
'scope_data' => [
    'date_range' => [
        'from' => '2024-01-01',
        'until' => '2024-12-31'
    ]
]
```

### Employee Restrictions
```php
'scope_data' => [
    'employees' => [10, 20, 30] // Hanya bisa manage employee ini
]
```

## 🎨 Custom Dashboard Configuration

```php
'dashboard_config' => [
    'layout' => 'executive', // executive, manager, employee
    'widgets' => [
        'global_summary',
        'branch_performance', 
        'pending_approvals',
        'system_alerts'
    ],
    'theme' => 'dark' // light, dark
]
```

## 🔄 Approval Chain (Hierarchy)

Role hierarchy menentukan approval chain:

```
HR Central (100) → System Admin (90) → Branch Manager (80) 
→ Pengelola (60) → Supervisor (50) → Shift Leader (40) 
→ Senior Barista (30) → Employee (10)
```

### Contoh Usage:
```php
// Cek apakah user bisa approve
$canApprove = $rbacService->canUserApprove($approver, $requester);

// Get approval chain untuk role
$chain = $rbacService->getApprovalChain($role);
```

## 🧪 Testing Permissions

### Via Tinker:
```bash
php artisan tinker
```

```php
// Test user permissions
$user = User::find(1);
$rbac = app(App\Services\RBACService::class);

// Cek permission
$hasPermission = $rbac->userHasPermission($user, 'attendance.view.all');
echo $hasPermission ? 'YES' : 'NO';

// Get all user permissions
$permissions = $rbac->getUserPermissions($user);
$permissions->keys()->each(fn($p) => print($p . "\n"));

// Get user roles
$roles = $rbac->getUserActiveRoles($user);
$roles->each(fn($ur) => print($ur->role->display_name . "\n"));
```

### Via Web Interface:
```
URL: /admin/roles
- View all roles
- See permissions per role
- Assign/remove roles from users
```

## 🌟 Advanced Features

### 1. Role Templates
Buat template untuk role yang sering digunakan:

```php
// Di RoleManagementController
private function getRoleTemplates()
{
    return [
        'franchise_owner' => [
            'display_name' => 'Franchise Owner',
            'description' => 'Owner of franchise location',
            'color' => '#gold',
            'hierarchy_level' => 85,
            'permissions' => [...]
        ]
    ];
}
```

### 2. Menu Customization
```php
'menu_config' => [
    'attendance' => [
        'title' => 'Attendance Management',
        'items' => [
            ['route' => 'attendance.index', 'title' => 'View Attendance'],
            ['route' => 'attendance.reports', 'title' => 'Reports']
        ]
    ]
]
```

### 3. Real-time Permission Changes
Permission changes berlaku langsung tanpa logout:

```php
// Clear cache setelah role assignment
$rbacService->clearUserCache($user);
```

## 🚨 Important Notes

### Security:
1. **System roles** tidak bisa diubah atau dihapus
2. Permission di-cache untuk performance
3. Audit trail untuk semua perubahan
4. Hierarchy level mencegah privilege escalation

### Performance:
1. Permissions di-cache selama 1 jam
2. Gunakan `clearUserCache()` setelah perubahan role
3. Session storage untuk quick access

### Best Practices:
1. Gunakan hierarchy level dengan bijak
2. Beri nama role yang descriptive
3. Gunakan scope restrictions untuk security
4. Regular audit permissions

## 📞 Contoh Real-World Usage

### Skenario 1: Buka Cabang Baru
```php
// 1. Buat role khusus untuk cabang Bandung
$role = $rbacService->createRole(
    'bandung_manager',
    'Bandung Store Manager',
    [
        'hierarchy_level' => 65,
        'color' => '#e74c3c'
    ]
);

// 2. Assign permissions
$role->syncPermissions([
    'branch.view.assigned',
    'employee.view.branch',
    'attendance.view.branch',
    'schedule.create.branch'
]);

// 3. Assign ke user dengan scope restriction
$rbacService->assignRole($manager, $role, [
    'branches' => [5] // Hanya branch Bandung
]);
```

### Skenario 2: Seasonal Manager
```php
// Manager temporary untuk high season
$rbacService->assignRole($user, $role, [], 
    '2024-12-01', // dari
    '2024-01-31'  // sampai
);
```

### Skenario 3: Quality Inspector Multi-Region
```php
$rbacService->assignRole($inspector, $qualityRole, [
    'branches' => [1, 3, 5, 7, 9], // Multiple branches
    'employees' => [10, 15, 20] // Specific employees
]);
```

## 🎉 Benefits

✅ **No Code Changes** - Tambah role baru tanpa coding
✅ **Franchise Ready** - Perfect untuk multi-location business
✅ **Granular Control** - Permission level sangat detail
✅ **Easy Management** - Admin panel yang user-friendly
✅ **Quick Setup** - Role templates untuk setup cepat
✅ **Audit Trail** - Tracking semua perubahan
✅ **Scalable** - Bisa handle ribuan users dan roles

---

## 📚 API Documentation

### GET /api/admin/roles
List all roles dengan permissions

### POST /api/admin/roles
Create new custom role

### PUT /api/admin/roles/{id}
Update existing role

### DELETE /api/admin/roles/{id}
Delete custom role (jika tidak ada users)

### POST /api/admin/roles/template
Create role from template

### GET /api/admin/roles/export
Export role configuration

### POST /api/admin/user-roles/assign
Assign role to user

### POST /api/admin/user-roles/remove  
Remove role from user

### GET /api/admin/user-roles/users/{id}/permissions
Get user's all permissions

---

**🎯 System is now 100% flexible and scalable! 🎯**

Sistem role management ini sekarang bisa menangani segala kebutuhan coffee shop chain Anda, dari cabang tunggal hingga franchise besar dengan ratusan lokasi.
