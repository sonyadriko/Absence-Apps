<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\Branch;
use App\Services\RBACService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    protected $rbac;

    public function __construct(RBACService $rbac)
    {
        $this->middleware('auth');
        $this->rbac = $rbac;
    }

    /**
     * Display reports dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Check permissions
        $canViewOwnReports = $this->rbac->userHasPermission($user, 'report.view.own');
        $canViewBranchReports = $this->rbac->userHasPermission($user, 'report.view.branch');
        $canViewAllReports = $this->rbac->userHasPermission($user, 'report.view.all');

        if (!$canViewOwnReports && !$canViewBranchReports && !$canViewAllReports) {
            abort(403, 'You do not have permission to view reports.');
        }

        return view('reports.index');
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$this->hasReportPermission($user)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $stats = $this->calculateDashboardStats($user);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'user_permissions' => [
                    'can_view_own' => $this->rbac->userHasPermission($user, 'report.view.own'),
                    'can_view_branch' => $this->rbac->userHasPermission($user, 'report.view.branch'),
                    'can_view_all' => $this->rbac->userHasPermission($user, 'report.view.all')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics'
            ], 500);
        }
    }

    /**
     * Get attendance report data
     */
    public function getAttendanceReport(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$this->hasReportPermission($user)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $filters = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'branch_id' => 'nullable|exists:branches,id',
                'employee_id' => 'nullable|exists:users,id',
                'status' => 'nullable|in:present,late,absent',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $attendanceData = $this->getAttendanceData($user, $filters);

            return response()->json([
                'success' => true,
                'data' => $attendanceData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance report'
            ], 500);
        }
    }

    /**
     * Get leave report data
     */
    public function getLeaveReport(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$this->hasReportPermission($user)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $filters = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'branch_id' => 'nullable|exists:branches,id',
                'employee_id' => 'nullable|exists:users,id',
                'status' => 'nullable|in:pending,approved,rejected,approved_by_pengelola,approved_by_manager',
                'leave_type' => 'nullable|string',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $leaveData = $this->getLeaveData($user, $filters);

            return response()->json([
                'success' => true,
                'data' => $leaveData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leave report'
            ], 500);
        }
    }

    /**
     * Get performance report data
     */
    public function getPerformanceReport(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Performance reports require at least branch-level permissions
            if (!$this->rbac->userHasPermission($user, 'report.view.branch') && 
                !$this->rbac->userHasPermission($user, 'report.view.all')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $filters = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'branch_id' => 'nullable|exists:branches,id',
                'employee_id' => 'nullable|exists:users,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $performanceData = $this->getPerformanceData($user, $filters);

            return response()->json([
                'success' => true,
                'data' => $performanceData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch performance report'
            ], 500);
        }
    }

    /**
     * Get filter options
     */
    public function getFilterOptions(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$this->hasReportPermission($user)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $branches = $this->getUserAccessibleBranches($user);
            $employees = $this->getUserAccessibleEmployees($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'branches' => $branches->map(function($branch) {
                        return [
                            'id' => $branch->id,
                            'name' => $branch->name,
                            'code' => $branch->code
                        ];
                    }),
                    'employees' => $employees->map(function($employee) {
                        return [
                            'id' => $employee->id,
                            'name' => $employee->name,
                            'employee_id' => $employee->employee_id,
                            'branch' => $employee->branch ? $employee->branch->name : null
                        ];
                    }),
                    'leave_types' => [
                        ['code' => 'annual', 'name' => 'Annual Leave'],
                        ['code' => 'sick', 'name' => 'Sick Leave'],
                        ['code' => 'personal', 'name' => 'Personal Leave'],
                        ['code' => 'maternity', 'name' => 'Maternity Leave'],
                        ['code' => 'paternity', 'name' => 'Paternity Leave'],
                        ['code' => 'emergency', 'name' => 'Emergency Leave']
                    ],
                    'attendance_statuses' => [
                        ['value' => 'present', 'label' => 'Present'],
                        ['value' => 'late', 'label' => 'Late'],
                        ['value' => 'absent', 'label' => 'Absent']
                    ],
                    'leave_statuses' => [
                        ['value' => 'pending', 'label' => 'Pending'],
                        ['value' => 'approved_by_pengelola', 'label' => 'Approved by Supervisor'],
                        ['value' => 'approved_by_manager', 'label' => 'Approved by Manager'],
                        ['value' => 'approved', 'label' => 'Approved'],
                        ['value' => 'rejected', 'label' => 'Rejected']
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch filter options'
            ], 500);
        }
    }

    /**
     * Export reports
     */
    public function export(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:dashboard,attendance,leave,performance',
                'format' => 'required|in:pdf,excel',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            $user = auth()->user();
            
            if (!$this->hasReportPermission($user)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $type = $request->input('type');
            $format = $request->input('format');

            // For now, return a placeholder response
            // This can be implemented later with actual export libraries
            return response()->json([
                'success' => false,
                'message' => ucfirst($format) . ' export for ' . ucfirst($type) . ' report will be available soon',
                'export_info' => [
                    'type' => $type,
                    'format' => $format,
                    'generated_at' => now()->toISOString(),
                    'requested_by' => $user->name
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed'
            ], 500);
        }
    }

    /**
     * Check if user has any report permission
     */
    private function hasReportPermission($user)
    {
        return $this->rbac->userHasPermission($user, 'report.view.own') ||
               $this->rbac->userHasPermission($user, 'report.view.branch') ||
               $this->rbac->userHasPermission($user, 'report.view.all');
    }

    /**
     * Calculate dashboard statistics
     */
    private function calculateDashboardStats($user)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        if ($this->rbac->userHasPermission($user, 'report.view.all')) {
            return $this->getAllDashboardStats($today, $thisMonth);
        } elseif ($this->rbac->userHasPermission($user, 'report.view.branch')) {
            return $this->getBranchDashboardStats($user, $today, $thisMonth);
        } else {
            return $this->getOwnDashboardStats($user, $today, $thisMonth);
        }
    }

    private function getAllDashboardStats($today, $thisMonth)
    {
        $totalEmployees = User::where('is_active', true)->count();
        $presentToday = Attendance::whereDate('date', $today)
            ->whereNotNull('check_in')
            ->distinct('employee_id')
            ->count('employee_id');

        return [
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'absent_today' => $totalEmployees - $presentToday,
            'late_today' => Attendance::whereDate('date', $today)
                ->where('status', 'late')
                ->count(),
            'pending_leaves' => LeaveRequest::whereIn('status', ['pending', 'approved_by_pengelola', 'approved_by_manager'])->count(),
            'approved_leaves_month' => LeaveRequest::where('status', 'approved')
                ->whereMonth('created_at', $thisMonth->month)
                ->whereYear('created_at', $thisMonth->year)
                ->count(),
            'attendance_rate_month' => $this->calculateAttendanceRate(),
            'leave_utilization' => $this->calculateLeaveUtilization(),
            'charts' => $this->getDashboardCharts()
        ];
    }

    private function getBranchDashboardStats($user, $today, $thisMonth)
    {
        $userBranches = $this->getUserAccessibleBranches($user);
        $branchIds = $userBranches->pluck('id');

        $totalEmployees = User::whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->count();
            
        $presentToday = Attendance::whereDate('date', $today)
            ->where(function($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds);
            })
            ->whereNotNull('check_in')
            ->distinct('employee_id')
            ->count('employee_id');

        return [
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'absent_today' => $totalEmployees - $presentToday,
            'late_today' => Attendance::whereDate('date', $today)
                ->where(function($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                })
                ->where('status', 'late')
                ->count(),
            'pending_leaves' => LeaveRequest::whereHas('employee', function($q) use ($branchIds) {
                $q->whereHas('user', function($q2) use ($branchIds) {
                    $q2->whereIn('branch_id', $branchIds);
                });
            })
                ->whereIn('status', ['pending', 'approved_by_pengelola', 'approved_by_manager'])
                ->count(),
            'approved_leaves_month' => LeaveRequest::whereHas('employee', function($q) use ($branchIds) {
                $q->whereHas('user', function($q2) use ($branchIds) {
                    $q2->whereIn('branch_id', $branchIds);
                });
            })
                ->where('status', 'approved')
                ->whereMonth('created_at', $thisMonth->month)
                ->whereYear('created_at', $thisMonth->year)
                ->count(),
            'attendance_rate_month' => $this->calculateAttendanceRate($branchIds),
            'leave_utilization' => $this->calculateLeaveUtilization($branchIds),
            'charts' => $this->getDashboardCharts($branchIds)
        ];
    }

    private function getOwnDashboardStats($user, $today, $thisMonth)
    {
        $presentToday = Attendance::whereHas('employee', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereDate('date', $today)
            ->whereNotNull('check_in')
            ->exists() ? 1 : 0;

        return [
            'total_employees' => 1,
            'present_today' => $presentToday,
            'absent_today' => 1 - $presentToday,
            'late_today' => Attendance::whereHas('employee', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->whereDate('date', $today)
                ->where('status', 'late')
                ->count(),
            'pending_leaves' => LeaveRequest::whereHas('employee', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->whereIn('status', ['pending', 'approved_by_pengelola', 'approved_by_manager'])
                ->count(),
            'approved_leaves_month' => LeaveRequest::whereHas('employee', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->where('status', 'approved')
                ->whereMonth('created_at', $thisMonth->month)
                ->whereYear('created_at', $thisMonth->year)
                ->count(),
            'attendance_rate_month' => $this->calculateUserAttendanceRate($user->id),
            'leave_utilization' => $this->calculateUserLeaveUtilization($user->id),
            'charts' => $this->getUserDashboardCharts($user->id)
        ];
    }

    /**
     * Get dashboard charts data
     */
    private function getDashboardCharts($branchIds = null)
    {
        // Attendance trend (last 7 days)
        $attendanceTrend = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $query = Attendance::whereDate('date', $date)->whereNotNull('check_in');
            
            if ($branchIds) {
                $query->where(function($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                });
            }
            
            $attendanceTrend->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'present' => $query->count()
            ]);
        }

        // Leave requests by status
        $leaveStatusQuery = LeaveRequest::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status');
            
        if ($branchIds) {
            $leaveStatusQuery->whereHas('employee.user', function($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds);
            });
        }
        
        $leaveByStatus = $leaveStatusQuery->get();

        // Monthly attendance vs leaves
        $monthlyData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            
            $attendanceQuery = Attendance::whereMonth('date', $month->month)
                ->whereYear('date', $month->year)
                ->whereNotNull('check_in');
                
            $leaveQuery = LeaveRequest::whereMonth('start_date', $month->month)
                ->whereYear('start_date', $month->year)
                ->where('status', 'approved');
            
            if ($branchIds) {
                $attendanceQuery->where(function($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                });
                $leaveQuery->whereHas('employee.user', function($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                });
            }
            
            $monthlyData->push([
                'month' => $month->format('M Y'),
                'attendance' => $attendanceQuery->count(),
                'leaves' => $leaveQuery->sum('total_days')
            ]);
        }

        return [
            'attendance_trend' => $attendanceTrend,
            'leave_by_status' => $leaveByStatus,
            'monthly_comparison' => $monthlyData
        ];
    }

    /**
     * Get user-specific dashboard charts
     */
    private function getUserDashboardCharts($userId)
    {
        // User attendance trend (last 30 days)
        $attendanceTrend = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $attendance = Attendance::whereHas('employee', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->whereDate('date', $date)
                ->first();
            
            $status = 'absent';
            if ($attendance) {
                if ($attendance->check_in) {
                    $status = $attendance->status === 'late' ? 'late' : 'present';
                }
            }
            
            $attendanceTrend->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'status' => $status
            ]);
        }

        // User leave history (last 6 months)
        $leaveHistory = LeaveRequest::whereHas('employee', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->where('start_date', '>=', Carbon::now()->subMonths(6))
            ->with('leaveType')
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function($leave) {
                return [
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'days' => $leave->total_days,
                    'type' => $leave->leaveType->name ?? 'Unknown',
                    'status' => $leave->status
                ];
            });

        return [
            'attendance_trend' => $attendanceTrend,
            'leave_history' => $leaveHistory
        ];
    }

    /**
     * Get attendance report data
     */
    private function getAttendanceData($user, $filters)
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');
        $perPage = $filters['per_page'] ?? 25;
        
        $query = Attendance::with(['user', 'user.branch'])
            ->whereBetween('date', [$dateFrom, $dateTo]);

        // Apply permission-based filtering
        $this->applyPermissionFilter($query, $user);

        // Apply additional filters
        $this->applyAttendanceFilters($query, $filters);

        $records = $query->orderBy('date', 'desc')
            ->orderBy('check_in', 'desc')
            ->paginate($perPage);

        // Calculate summary
        $summary = $this->calculateAttendanceSummary($query->clone(), $dateFrom, $dateTo);

        return [
            'records' => $records,
            'summary' => $summary,
            'filters' => array_merge($filters, ['date_from' => $dateFrom, 'date_to' => $dateTo])
        ];
    }

    /**
     * Get leave report data
     */
    private function getLeaveData($user, $filters)
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');
        $perPage = $filters['per_page'] ?? 25;
        
        $query = LeaveRequest::with(['employee.user', 'employee.user.branch', 'leaveType'])
            ->whereBetween('start_date', [$dateFrom, $dateTo]);

        // Apply permission-based filtering
        $this->applyLeavePermissionFilter($query, $user);

        // Apply additional filters
        $this->applyLeaveFilters($query, $filters);

        $records = $query->orderBy('start_date', 'desc')
            ->paginate($perPage);

        // Calculate summary and analytics
        $summary = $this->calculateLeaveSummary($query->clone());
        $analytics = $this->calculateLeaveAnalytics($query->clone());

        return [
            'records' => $records,
            'summary' => $summary,
            'analytics' => $analytics,
            'filters' => array_merge($filters, ['date_from' => $dateFrom, 'date_to' => $dateTo])
        ];
    }

    /**
     * Get performance report data
     */
    private function getPerformanceData($user, $filters)
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');
        $perPage = $filters['per_page'] ?? 25;

        $query = User::with(['branch'])
            ->where('is_active', true);

        // Apply permission-based filtering
        if ($this->rbac->userHasPermission($user, 'report.view.all')) {
            // HR Central can see all
        } else {
            // Branch-level users see their branch only
            $userBranches = $this->getUserAccessibleBranches($user);
            $query->whereIn('branch_id', $userBranches->pluck('id'));
        }

        // Apply additional filters
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('id', $filters['employee_id']);
        }

        $employees = $query->paginate($perPage);

        // Calculate performance metrics for each employee
        $performanceData = $employees->getCollection()->map(function($employee) use ($dateFrom, $dateTo) {
            return $this->calculateEmployeePerformance($employee, $dateFrom, $dateTo);
        });

        $employees->setCollection($performanceData);

        // Calculate summary
        $summary = $this->calculatePerformanceSummary($performanceData);

        return [
            'employees' => $employees,
            'summary' => $summary,
            'filters' => array_merge($filters, ['date_from' => $dateFrom, 'date_to' => $dateTo])
        ];
    }

    /**
     * Apply permission-based filtering for attendance
     */
    private function applyPermissionFilter($query, $user)
    {
        if ($this->rbac->userHasPermission($user, 'report.view.all')) {
            // HR Central can see all
            return;
        } elseif ($this->rbac->userHasPermission($user, 'report.view.branch')) {
            $userBranches = $this->getUserAccessibleBranches($user);
            $query->whereHas('user', function($q) use ($userBranches) {
                $q->whereIn('branch_id', $userBranches->pluck('id'));
            });
        } else {
            // Employee can only see own
            $query->where('user_id', $user->id);
        }
    }

    /**
     * Apply permission-based filtering for leave
     */
    private function applyLeavePermissionFilter($query, $user)
    {
        if ($this->rbac->userHasPermission($user, 'report.view.all')) {
            // HR Central can see all
            return;
        } elseif ($this->rbac->userHasPermission($user, 'report.view.branch')) {
            $userBranches = $this->getUserAccessibleBranches($user);
            $query->whereHas('employee.user', function($q) use ($userBranches) {
                $q->whereIn('branch_id', $userBranches->pluck('id'));
            });
        } else {
            // Employee can only see own
            $query->whereHas('employee', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
    }

    /**
     * Apply attendance filters
     */
    private function applyAttendanceFilters($query, $filters)
    {
        if (!empty($filters['branch_id'])) {
            $query->whereHas('employee.user', function($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['employee_id'])) {
            $query->whereHas('employee', function($q) use ($filters) {
                $q->where('user_id', $filters['employee_id']);
            });
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'present') {
                $query->whereNotNull('check_in')->where('status', '!=', 'late');
            } elseif ($filters['status'] === 'absent') {
                $query->whereNull('check_in');
            } else {
                $query->where('status', $filters['status']);
            }
        }
    }

    /**
     * Apply leave filters
     */
    private function applyLeaveFilters($query, $filters)
    {
        if (!empty($filters['branch_id'])) {
            $query->whereHas('user', function($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['employee_id'])) {
            $query->where('user_id', $filters['employee_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['leave_type'])) {
            $query->whereHas('leaveType', function($q) use ($filters) {
                $q->where('code', $filters['leave_type']);
            });
        }
    }

    /**
     * Calculate attendance summary
     */
    private function calculateAttendanceSummary($query, $dateFrom, $dateTo)
    {
        $totalRecords = $query->count();
        $presentCount = $query->clone()->whereNotNull('check_in')->count();
        $lateCount = $query->clone()->where('status', 'late')->count();
        $absentCount = $totalRecords - $presentCount;

        return [
            'total_records' => $totalRecords,
            'present_count' => $presentCount,
            'late_count' => $lateCount,
            'absent_count' => $absentCount,
            'attendance_rate' => $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0,
            'punctuality_rate' => $presentCount > 0 ? round((($presentCount - $lateCount) / $presentCount) * 100, 1) : 0,
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo]
        ];
    }

    /**
     * Calculate leave summary
     */
    private function calculateLeaveSummary($query)
    {
        $totalRequests = $query->count();
        $approvedCount = $query->clone()->where('status', 'approved')->count();
        $pendingCount = $query->clone()->whereIn('status', ['pending', 'approved_by_pengelola', 'approved_by_manager'])->count();
        $rejectedCount = $query->clone()->where('status', 'rejected')->count();
        $totalDays = $query->clone()->sum('total_days');

        return [
            'total_requests' => $totalRequests,
            'approved_count' => $approvedCount,
            'pending_count' => $pendingCount,
            'rejected_count' => $rejectedCount,
            'total_days' => $totalDays,
            'approval_rate' => $totalRequests > 0 ? round(($approvedCount / $totalRequests) * 100, 1) : 0,
            'average_duration' => $totalRequests > 0 ? round($totalDays / $totalRequests, 1) : 0
        ];
    }

    /**
     * Calculate leave analytics
     */
    private function calculateLeaveAnalytics($query)
    {
        // Leave type breakdown
        $leaveTypeBreakdown = $query->clone()
            ->select('leave_type_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_days) as total_days'))
            ->with('leaveType')
            ->groupBy('leave_type_id')
            ->get()
            ->map(function($item) {
                return [
                    'type' => $item->leaveType->name ?? 'Unknown',
                    'count' => $item->count,
                    'total_days' => $item->total_days
                ];
            });

        // Monthly trend
        $monthlyTrend = $query->clone()
            ->select(
                DB::raw('DATE_FORMAT(start_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(total_days) as days')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'leave_type_breakdown' => $leaveTypeBreakdown,
            'monthly_trend' => $monthlyTrend
        ];
    }

    /**
     * Calculate employee performance
     */
    private function calculateEmployeePerformance($employee, $dateFrom, $dateTo)
    {
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        $workingDays = $startDate->diffInWeekdays($endDate) + 1;
        
        // Attendance metrics
        $attendanceCount = Attendance::whereHas('employee', function($q) use ($employee) {
                $q->where('user_id', $employee->id);
            })
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereNotNull('check_in')
            ->count();
        
        $lateCount = Attendance::whereHas('employee', function($q) use ($employee) {
                $q->where('user_id', $employee->id);
            })
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', 'late')
            ->count();

        $attendanceRate = $workingDays > 0 ? ($attendanceCount / $workingDays) * 100 : 0;
        $punctualityRate = $attendanceCount > 0 ? (($attendanceCount - $lateCount) / $attendanceCount) * 100 : 0;

        // Leave metrics
        $leaveRequests = LeaveRequest::whereHas('employee', function($q) use ($employee) {
                $q->where('user_id', $employee->id);
            })
            ->whereBetween('start_date', [$dateFrom, $dateTo])
            ->count();

        $leaveDays = LeaveRequest::whereHas('employee', function($q) use ($employee) {
                $q->where('user_id', $employee->id);
            })
            ->whereBetween('start_date', [$dateFrom, $dateTo])
            ->where('status', 'approved')
            ->sum('total_days');

        // Performance score calculation
        $performanceScore = $this->calculatePerformanceScore($attendanceRate, $punctualityRate, $leaveDays, $workingDays);

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'employee_id' => $employee->employee_id,
            'branch' => $employee->branch ? $employee->branch->name : null,
            'attendance_rate' => round($attendanceRate, 1),
            'punctuality_rate' => round($punctualityRate, 1),
            'working_days' => $workingDays,
            'present_days' => $attendanceCount,
            'late_days' => $lateCount,
            'leave_requests' => $leaveRequests,
            'leave_days' => $leaveDays,
            'performance_score' => $performanceScore,
            'performance_grade' => $this->getPerformanceGrade($performanceScore)
        ];
    }

    /**
     * Calculate performance score
     */
    private function calculatePerformanceScore($attendanceRate, $punctualityRate, $leaveDays, $workingDays)
    {
        $attendanceWeight = 0.6;
        $punctualityWeight = 0.3;
        $leaveWeight = 0.1;

        // Leave utilization impact
        $leaveUtilization = $workingDays > 0 ? ($leaveDays / $workingDays) * 100 : 0;
        $leaveScore = max(0, 100 - ($leaveUtilization * 2));

        $score = ($attendanceRate * $attendanceWeight) + 
                 ($punctualityRate * $punctualityWeight) + 
                 ($leaveScore * $leaveWeight);

        return round(min(100, max(0, $score)), 1);
    }

    /**
     * Get performance grade
     */
    private function getPerformanceGrade($score)
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Calculate performance summary
     */
    private function calculatePerformanceSummary($performanceData)
    {
        if ($performanceData->isEmpty()) {
            return [
                'total_employees' => 0,
                'average_attendance_rate' => 0,
                'average_punctuality_rate' => 0,
                'average_performance_score' => 0,
                'grade_distribution' => []
            ];
        }

        $totalEmployees = $performanceData->count();
        $avgAttendance = $performanceData->avg('attendance_rate');
        $avgPunctuality = $performanceData->avg('punctuality_rate');
        $avgScore = $performanceData->avg('performance_score');

        $gradeDistribution = $performanceData->countBy('performance_grade');

        return [
            'total_employees' => $totalEmployees,
            'average_attendance_rate' => round($avgAttendance, 1),
            'average_punctuality_rate' => round($avgPunctuality, 1),
            'average_performance_score' => round($avgScore, 1),
            'grade_distribution' => $gradeDistribution
        ];
    }

    /**
     * Get user accessible branches
     */
    private function getUserAccessibleBranches($user)
    {
        if ($this->rbac->userHasPermission($user, 'branch.view.all')) {
            return Branch::where('status', 'active')->get();
        }
        
        return Branch::where('id', $user->branch_id)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get user accessible employees
     */
    private function getUserAccessibleEmployees($user)
    {
        $query = User::with('branch')->where('is_active', true);

        if ($this->rbac->userHasPermission($user, 'report.view.all')) {
            // HR Central can see all employees
        } elseif ($this->rbac->userHasPermission($user, 'report.view.branch')) {
            $userBranches = $this->getUserAccessibleBranches($user);
            $query->whereIn('branch_id', $userBranches->pluck('id'));
        } else {
            // Employee can only see self
            $query->where('id', $user->id);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Calculate overall attendance rate
     */
    private function calculateAttendanceRate($branchIds = null)
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $workingDays = $thisMonth->diffInWeekdays(Carbon::now()) + 1;

        $employeeQuery = User::where('is_active', true);
        if ($branchIds) {
            $employeeQuery->whereIn('branch_id', $branchIds);
        }
        $totalEmployees = $employeeQuery->count();

        if ($totalEmployees === 0 || $workingDays === 0) return 0;

        $attendanceQuery = Attendance::whereMonth('date', $thisMonth->month)
            ->whereYear('date', $thisMonth->year)
            ->whereNotNull('check_in');

        if ($branchIds) {
            $attendanceQuery->where(function($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds);
            });
        }

        $totalAttendances = $attendanceQuery->count();
        $expectedAttendances = $totalEmployees * $workingDays;

        return $expectedAttendances > 0 ? round(($totalAttendances / $expectedAttendances) * 100, 1) : 0;
    }

    /**
     * Calculate leave utilization
     */
    private function calculateLeaveUtilization($branchIds = null)
    {
        $thisYear = Carbon::now()->startOfYear();
        
        $employeeQuery = User::where('is_active', true);
        if ($branchIds) {
            $employeeQuery->whereIn('branch_id', $branchIds);
        }
        
        $totalEmployees = $employeeQuery->count();
        if ($totalEmployees === 0) return 0;

        $leaveQuery = LeaveRequest::whereYear('start_date', $thisYear->year)
            ->where('status', 'approved');

        if ($branchIds) {
            $leaveQuery->whereHas('employee.user', function($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds);
            });
        }

        $totalLeaveDays = $leaveQuery->sum('total_days');
        $annualLeaveEntitlement = $totalEmployees * 12; // Assuming 12 days per year

        return $annualLeaveEntitlement > 0 ? round(($totalLeaveDays / $annualLeaveEntitlement) * 100, 1) : 0;
    }

    /**
     * Calculate user attendance rate
     */
    private function calculateUserAttendanceRate($userId)
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $workingDays = $thisMonth->diffInWeekdays(Carbon::now()) + 1;

        if ($workingDays === 0) return 0;

        $attendanceCount = Attendance::whereHas('employee', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereMonth('date', $thisMonth->month)
            ->whereYear('date', $thisMonth->year)
            ->whereNotNull('check_in')
            ->count();

        return round(($attendanceCount / $workingDays) * 100, 1);
    }

    /**
     * Calculate user leave utilization
     */
    private function calculateUserLeaveUtilization($userId)
    {
        $thisYear = Carbon::now()->startOfYear();
        
        $leaveDaysUsed = LeaveRequest::whereHas('employee', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereYear('start_date', $thisYear->year)
            ->where('status', 'approved')
            ->sum('total_days');

        $annualEntitlement = 12; // Assuming 12 days per year
        
        return $annualEntitlement > 0 ? round(($leaveDaysUsed / $annualEntitlement) * 100, 1) : 0;
    }
}
