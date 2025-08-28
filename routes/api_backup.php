<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CorrectionsController;
use App\Http\Controllers\Api\ManagementController;
use App\Http\Controllers\Api\LeaveController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (no authentication required)
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']); // If registration is enabled

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('tokens', [AuthController::class, 'getTokens']);
        Route::delete('tokens/{tokenId}', [AuthController::class, 'revokeToken']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('profile/photo', [AuthController::class, 'uploadPhoto']);
        Route::put('password', [AuthController::class, 'changePassword']);
    });
        
        // ========================================
        // EMPLOYEE ROUTES (Personal use)
        // ========================================
        
        Route::prefix('employee')->group(function () {
            
            // Attendance routes
            Route::prefix('attendance')->group(function () {
                Route::get('status', [AttendanceController::class, 'getCurrentStatus']);
                Route::post('checkin', [AttendanceController::class, 'processCheckInOut']);
                Route::get('history', [AttendanceController::class, 'getAttendanceHistory']);
                Route::get('stats', [AttendanceController::class, 'getAttendanceStats']);
                Route::get('selfie/{eventId}', [AttendanceController::class, 'getSelfie']);
            });
            
            // Schedule routes
            Route::prefix('schedule')->group(function () {
                Route::get('/', [ScheduleController::class, 'getMySchedule']);
                Route::get('upcoming', [ScheduleController::class, 'getUpcomingSchedule']);
                Route::get('conflicts', [ScheduleController::class, 'checkScheduleConflicts']);
            });
            
            // Personal reports
            Route::prefix('reports')->group(function () {
                Route::get('personal', [ReportController::class, 'getPersonalReport']);
                Route::get('export/{type}', [ReportController::class, 'exportPersonalReport']);
            });
            
            // Correction requests
            Route::prefix('corrections')->group(function () {
                Route::get('/', [CorrectionsController::class, 'getMyCorrections']);
                Route::post('/', [CorrectionsController::class, 'submitCorrection']);
                Route::get('{id}', [CorrectionsController::class, 'getCorrectionDetails']);
                Route::put('{id}', [CorrectionsController::class, 'updateCorrection']);
                Route::delete('{id}', [CorrectionsController::class, 'cancelCorrection']);
            });
            
            // Leave management routes
            Route::prefix('leave')->group(function () {
                Route::get('/', [LeaveController::class, 'getLeaveRequests']);
                Route::post('/', [LeaveController::class, 'createLeaveRequest']);
                Route::get('{id}', [LeaveController::class, 'getLeaveRequest']);
                Route::put('{id}', [LeaveController::class, 'updateLeaveRequest']);
                Route::put('{id}/cancel', [LeaveController::class, 'cancelLeaveRequest']);
                Route::get('balance', [LeaveController::class, 'getLeaveBalance']);
            });
            
            // Document management routes
            Route::prefix('documents')->group(function () {
                Route::get('/', [AuthController::class, 'getDocuments']);
                Route::post('/', [AuthController::class, 'uploadDocument']);
                Route::get('{id}/download', [AuthController::class, 'downloadDocument']);
                Route::delete('{id}', [AuthController::class, 'deleteDocument']);
            });
            
            // Profile and preferences
            Route::get('profile', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
        });
        
        // ========================================
        // MANAGEMENT ROUTES (Supervisors/Managers)
        // ========================================
        
        Route::prefix('management')->group(function () {
            
            // Dashboard and overview
            Route::get('dashboard', [ManagementController::class, 'getDashboardData']);
            Route::get('branches', [ManagementController::class, 'getAccessibleBranches']);
            
            // Employee management
            Route::prefix('employees')->group(function () {
                Route::get('/', [ManagementController::class, 'getEmployees']);
                Route::get('{id}', [ManagementController::class, 'getEmployee']);
                Route::get('{id}/attendance', [ManagementController::class, 'getEmployeeAttendance']);
                Route::get('{id}/schedule', [ManagementController::class, 'getEmployeeSchedule']);
                Route::get('{id}/stats', [ManagementController::class, 'getEmployeeStats']);
            });
            
            // Attendance management
            Route::prefix('attendance')->group(function () {
                Route::get('overview', [AttendanceController::class, 'getAttendanceOverview']);
                Route::get('daily', [AttendanceController::class, 'getDailyAttendance']);
                Route::get('events', [AttendanceController::class, 'getAttendanceEvents']);
                Route::get('alerts', [AttendanceController::class, 'getAttendanceAlerts']);
                Route::post('manual-entry', [AttendanceController::class, 'createManualEntry']);
                Route::put('events/{id}', [AttendanceController::class, 'updateAttendanceEvent']);
            });
            
            // Schedule management
            Route::prefix('schedules')->group(function () {
                Route::get('/', [ScheduleController::class, 'getSchedules']);
                Route::post('/', [ScheduleController::class, 'createSchedule']);
                Route::get('{id}', [ScheduleController::class, 'getSchedule']);
                Route::put('{id}', [ScheduleController::class, 'updateSchedule']);
                Route::delete('{id}', [ScheduleController::class, 'deleteSchedule']);
                Route::post('bulk', [ScheduleController::class, 'bulkCreateSchedules']);
                Route::get('templates', [ScheduleController::class, 'getScheduleTemplates']);
                Route::get('conflicts', [ScheduleController::class, 'checkScheduleConflicts']);
            });
            
            // Reports management
            Route::prefix('reports')->group(function () {
                Route::get('types', [ReportController::class, 'getAvailableReports']);
                Route::get('daily-summary', [ReportController::class, 'getDailySummaryReport']);
                Route::get('monthly-recap', [ReportController::class, 'getMonthlyRecapReport']);
                Route::get('peak-hours', [ReportController::class, 'getPeakHoursReport']);
                Route::get('attendance-trends', [ReportController::class, 'getAttendanceTrendsReport']);
                Route::get('export/{type}', [ReportController::class, 'exportReport']);
            });
            
            // Corrections management
            Route::prefix('corrections')->group(function () {
                Route::get('/', [CorrectionsController::class, 'getCorrections']);
                Route::get('{id}', [CorrectionsController::class, 'getCorrectionDetails']);
                Route::put('{id}/status', [CorrectionsController::class, 'updateCorrectionStatus']);
                Route::post('bulk-approve', [CorrectionsController::class, 'bulkApproveCorrections']);
                Route::post('bulk-reject', [CorrectionsController::class, 'bulkRejectCorrections']);
                Route::get('stats', [CorrectionsController::class, 'getCorrectionStats']);
            });
            
            // Branch management (for higher-level roles)
            Route::prefix('branches')->group(function () {
                Route::get('/', [ManagementController::class, 'getBranches']);
                Route::get('{id}', [ManagementController::class, 'getBranch']);
                Route::get('{id}/stats', [ManagementController::class, 'getBranchStats']);
                Route::get('{id}/employees', [ManagementController::class, 'getBranchEmployees']);
                Route::put('{id}', [ManagementController::class, 'updateBranch']);
            });
        });
        
        // ========================================
        // SHARED UTILITY ROUTES
        // ========================================
        
        // Branch information
        Route::get('branches', [ManagementController::class, 'getAccessibleBranches']);
        Route::get('branches/{id}', [ManagementController::class, 'getBranch']);
        
        // Work shifts and policies
        Route::get('work-shifts', [ScheduleController::class, 'getWorkShifts']);
        Route::get('attendance-policies', [AttendanceController::class, 'getAttendancePolicies']);
        
        // System information
        Route::get('system/time', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'server_time' => now()->toISOString(),
                    'timezone' => config('app.timezone'),
                    'timestamp' => now()->timestamp,
                ]
            ]);
        });
        
        // Health check
        Route::get('health', function () {
            return response()->json([
                'success' => true,
                'message' => 'API is healthy',
                'data' => [
                    'version' => '1.0.0',
                    'environment' => app()->environment(),
                    'timestamp' => now()->toISOString(),
                ]
            ]);
        });
    });
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error' => 'The requested API endpoint does not exist'
    ], 404);
});

// Legacy Sanctum route (keeping for backward compatibility if needed)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
