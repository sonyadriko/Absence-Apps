<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\HRCentral\BranchController;
use App\Http\Controllers\HRCentral\EmployeeController;
use App\Http\Controllers\HRCentral\AttendanceController as HRCentralAttendanceController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ReportsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/api/demo-users', [AuthController::class, 'getDemoUsers'])->name('demo.users');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    
    // Role-based dashboards
    Route::get('/hr-central/dashboard', function() {
        return view('employee.dashboard'); // Will create specific ones later
    })->name('hr-central.dashboard');
    
    Route::get('/branch-manager/dashboard', function() {
        return view('employee.dashboard');
    })->name('branch-manager.dashboard');
    
    Route::get('/pengelola/dashboard', function() {
        return view('employee.dashboard');
    })->name('pengelola.dashboard');
    
    Route::get('/employee/dashboard', function() {
        return view('employee.dashboard');
    })->name('employee.dashboard');
    
    Route::get('/admin/dashboard', function() {
        return view('employee.dashboard');
    })->name('admin.dashboard');
    
    Route::get('/shift-leader/dashboard', function() {
        return view('employee.dashboard');
    })->name('shift-leader.dashboard');
    
    Route::get('/supervisor/dashboard', function() {
        return view('employee.dashboard');
    })->name('supervisor.dashboard');
    
    // Role Management (Admin)
    Route::prefix('admin/roles')->name('admin.roles.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'index'])->name('index');
        Route::post('/', [RoleManagementController::class, 'store'])->name('store');
        Route::get('/{role}', [RoleManagementController::class, 'show'])->name('show');
        Route::put('/{role}', [RoleManagementController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleManagementController::class, 'destroy'])->name('destroy');
        Route::post('/template', [RoleManagementController::class, 'createFromTemplate'])->name('template');
    });
    
    // Placeholder routes (will be implemented later)
    Route::get('/employee/profile', function() {
        return view('employee.profile.index');
    })->name('employee.profile.index');
    
    // HR Central Branch Management (View only)
    Route::get('/hr-central/branches', [BranchController::class, 'index'])->name('hr-central.branches.index');
    Route::get('/hr-central/branches/{branch}', [BranchController::class, 'show'])->name('hr-central.branches.show');
    Route::get('/hr-central/branches/{branch}/edit', [BranchController::class, 'edit'])->name('hr-central.branches.edit');
    
    // HR Central Employee Management
    Route::get('/hr-central/employees', [EmployeeController::class, 'index'])->name('hr-central.employees.index');
    Route::get('/hr-central/employees/{employee}', [EmployeeController::class, 'show'])->name('hr-central.employees.show');
    Route::get('/hr-central/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('hr-central.employees.edit');
    
    // HR Central Attendance Management
    Route::get('/hr-central/attendance', [HRCentralAttendanceController::class, 'index'])->name('hr-central.attendance.index');
    Route::get('/hr-central/attendance/export', [HRCentralAttendanceController::class, 'export'])->name('hr-central.attendance.export');
    Route::get('/hr-central/attendance/employees-by-branch', [HRCentralAttendanceController::class, 'getEmployeesByBranch'])->name('hr-central.attendance.employees-by-branch');
    
    Route::get('/branch-manager/branches', function() {
        return view('employee.dashboard');
    })->name('branch-manager.branches.index');
    
    Route::get('/branch-manager/employees', function() {
        return view('employee.dashboard');
    })->name('branch-manager.employees.index');
    
    Route::get('/branch-manager/attendance', function() {
        return view('employee.dashboard');
    })->name('branch-manager.attendance.index');
    
    Route::get('/branch-manager/schedules', function() {
        return view('employee.dashboard');
    })->name('branch-manager.schedules.index');
    
    Route::get('/employee/attendance/checkin', function() {
        return view('employee.attendance.checkin');
    })->name('employee.attendance.checkin');
    
    Route::get('/employee/attendance', function() {
        return view('employee.attendance.index');
    })->name('employee.attendance.index');
    
    Route::get('/employee/schedule', function() {
        return view('employee.schedule.index');
    })->name('employee.schedule.index');
    
    Route::get('/leaves', function() {
        return view('employee.leave.index');
    })->name('leaves.index');
    
    // Approval Center
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
});

// API routes for AJAX calls
Route::prefix('api')->middleware(['auth'])->group(function () {
    // Role Management
    Route::apiResource('admin/roles', RoleManagementController::class);
    Route::post('admin/roles/template', [RoleManagementController::class, 'createFromTemplate']);
    Route::get('admin/roles/export', [RoleManagementController::class, 'export']);
    
    // Branch Management
    Route::apiResource('hr-central/branches', BranchController::class);
    Route::post('hr-central/branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus']);
    Route::get('hr-central/branches/{branch}/employees', [BranchController::class, 'employees']);
    Route::get('hr-central/branches/export', [BranchController::class, 'export']);
    
    // Employee Management  
    Route::get('hr-central/employees/export', [EmployeeController::class, 'export']);
    Route::apiResource('hr-central/employees', EmployeeController::class);
    Route::post('hr-central/employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus']);
    
    // HR Central Attendance AJAX APIs
    Route::prefix('hr-central')->group(function () {
        Route::get('employees/{employee}/attendance', [HRCentralAttendanceController::class, 'getEmployeeAttendanceDetails']);
        Route::get('attendance/daily-summary', [HRCentralAttendanceController::class, 'dailySummary']);
        Route::get('attendance/stats', [HRCentralAttendanceController::class, 'getStats']);
    });
    
    // Approval APIs
    Route::prefix('approvals')->group(function () {
        Route::get('/pending', [ApprovalController::class, 'getPendingRequests']);
        Route::get('/{leave_request}', [ApprovalController::class, 'getLeaveRequestDetails']);
        Route::post('/{leave_request}/approve', [ApprovalController::class, 'approve']);
        Route::post('/{leave_request}/reject', [ApprovalController::class, 'reject']);
    });
    
    // Reports APIs
    Route::prefix('reports')->group(function () {
        Route::get('/dashboard-stats', [ReportsController::class, 'getDashboardStats']);
        Route::get('/attendance', [ReportsController::class, 'getAttendanceReport']);
        Route::get('/leave', [ReportsController::class, 'getLeaveReport']);
        Route::get('/performance', [ReportsController::class, 'getPerformanceReport']);
        Route::get('/filter-options', [ReportsController::class, 'getFilterOptions']);
        Route::post('/export', [ReportsController::class, 'export']);
    });
});
