<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SimpleAttendanceController;
use App\Http\Controllers\Api\AttendanceCorrectionController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SimpleScheduleController;
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
Route::post('auth/register', [AuthController::class, 'register']);

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
    
    // Employee routes (Personal use)
    Route::prefix('employee')->group(function () {
        
        // Attendance routes
        Route::prefix('attendance')->group(function () {
            Route::get('status', [SimpleAttendanceController::class, 'getCurrentStatus']);
            Route::post('checkin', [SimpleAttendanceController::class, 'processCheckInOut']);
            Route::get('history', [SimpleAttendanceController::class, 'getAttendanceHistory']);
            Route::get('stats', [SimpleAttendanceController::class, 'getAttendanceStats']);
            Route::get('missing-checkouts', [AttendanceCorrectionController::class, 'getMissingCheckouts']);
            Route::post('submit-missing-checkout', [AttendanceCorrectionController::class, 'submitMissingCheckout']);
            Route::post('late-checkout', [AttendanceCorrectionController::class, 'submitLateCheckout']);
            Route::get('correction-history', [AttendanceCorrectionController::class, 'getCorrectionHistory']);
            Route::get('{id}', [SimpleAttendanceController::class, 'getAttendanceDetail']);
        });
        
        // Schedule routes
        Route::prefix('schedule')->group(function () {
            Route::get('/', [SimpleScheduleController::class, 'getMySchedule']);
            Route::post('requests', [SimpleScheduleController::class, 'createScheduleRequest']);
            Route::get('shifts', [SimpleScheduleController::class, 'getWorkShifts']);
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
        
        // Correction requests
        Route::prefix('corrections')->group(function () {
            Route::get('/', [CorrectionsController::class, 'getMyCorrections']);
            Route::post('/', [CorrectionsController::class, 'submitCorrection']);
            Route::get('{id}', [CorrectionsController::class, 'getCorrectionDetails']);
            Route::put('{id}', [CorrectionsController::class, 'updateCorrection']);
            Route::delete('{id}', [CorrectionsController::class, 'cancelCorrection']);
        });
        
        // Document management routes
        Route::prefix('documents')->group(function () {
            Route::get('/', [AuthController::class, 'getDocuments']);
            Route::post('/', [AuthController::class, 'uploadDocument']);
            Route::get('{id}/download', [AuthController::class, 'downloadDocument']);
            Route::delete('{id}', [AuthController::class, 'deleteDocument']);
        });
        
        // Personal reports
        Route::prefix('reports')->group(function () {
            Route::get('export/attendance', [ReportController::class, 'exportAttendanceReport']);
        });
    });
    
    // Management routes (Supervisors/Managers)
    Route::prefix('management')->group(function () {
        
        // Dashboard and overview
        Route::get('dashboard', [ManagementController::class, 'getDashboardData']);
        
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
            Route::get('overview', [SimpleAttendanceController::class, 'getAttendanceHistory']);
            Route::get('daily', [SimpleAttendanceController::class, 'getAttendanceHistory']);
            Route::get('events', [SimpleAttendanceController::class, 'getAttendanceHistory']);
            Route::post('manual-entry', [SimpleAttendanceController::class, 'processCheckInOut']);
            Route::put('events/{id}', [SimpleAttendanceController::class, 'getAttendanceDetail']);
        });
        
        // Branch management
        Route::prefix('branches')->group(function () {
            Route::get('/', [ManagementController::class, 'getBranches']);
            Route::get('{id}', [ManagementController::class, 'getBranch']);
            Route::get('{id}/stats', [ManagementController::class, 'getBranchStats']);
            Route::get('{id}/employees', [ManagementController::class, 'getBranchEmployees']);
            Route::put('{id}', [ManagementController::class, 'updateBranch']);
        });
    });
    
    // Shared utility routes
    Route::get('branches', [ManagementController::class, 'getAccessibleBranches']);
    Route::get('branches/{id}', [ManagementController::class, 'getBranch']);
    
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
    
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error' => 'The requested API endpoint does not exist'
    ], 404);
});

// Legacy Sanctum route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
