<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Employee\DashboardController;

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
    
    Route::get('/hr-central/branches', function() {
        return view('employee.dashboard');
    })->name('hr-central.branches.index');
    
    Route::get('/hr-central/employees', function() {
        return view('employee.dashboard');
    })->name('hr-central.employees.index');
    
    Route::get('/hr-central/attendance', function() {
        return view('employee.dashboard');
    })->name('hr-central.attendance.index');
    
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
    
    Route::get('/reports', function() {
        return view('employee.dashboard');
    })->name('reports.index');
});

// API routes for AJAX calls
Route::prefix('api')->middleware(['auth'])->group(function () {
    Route::apiResource('admin/roles', RoleManagementController::class);
    Route::post('admin/roles/template', [RoleManagementController::class, 'createFromTemplate']);
    Route::get('admin/roles/export', [RoleManagementController::class, 'export']);
});
