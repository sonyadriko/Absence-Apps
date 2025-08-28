<?php

namespace App\Http\Controllers\Api;

use App\Models\AttendanceEvent;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManagementController extends ApiController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get dashboard data for management
     */
    public function getDashboardData(Request $request)
    {
        if (!$this->hasPermission('attendance.view.branch') && !$this->hasPermission('attendance.view.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|integer|exists:branches,id',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $accessibleBranches = $this->getAccessibleBranches();
        $selectedBranchId = $request->get('branch_id', $accessibleBranches->first()?->id);
        
        if (!$selectedBranchId || !$this->validateBranchAccess($selectedBranchId)) {
            return $this->forbiddenResponse('Access denied to this branch');
        }

        $selectedBranch = $accessibleBranches->find($selectedBranchId);
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        // Get dashboard statistics
        $dashboardData = $this->calculateDashboardStats($selectedBranch, $date);

        return $this->successResponse([
            'branch' => [
                'id' => $selectedBranch->id,
                'name' => $selectedBranch->name,
                'code' => $selectedBranch->code,
            ],
            'date' => $date,
            'stats' => $dashboardData['stats'],
            'recent_activities' => $dashboardData['recent_activities'],
            'attendance_overview' => $dashboardData['attendance_overview'],
            'alerts' => $dashboardData['alerts'],
        ]);
    }

    /**
     * Get accessible branches for the user
     */
    public function getAccessibleBranches()
    {
        // Get all active branches
        // TODO: Implement proper branch access control based on user permissions
        $branches = Branch::where('is_active', true)->get();

        $transformedBranches = $branches->map(function($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'email' => $branch->email,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'geofence_radius' => $branch->geofence_radius,
                'is_active' => $branch->is_active,
                'employee_count' => $branch->employees()->count(),
                'manager_name' => null, // TODO: Implement manager lookup when role system is ready
            ];
        });

        return $this->successResponse($transformedBranches, 'Accessible branches retrieved successfully');
    }

    /**
     * Get employees for management
     */
    public function getEmployees(Request $request)
    {
        if (!$this->hasPermission('employee.view.branch') && !$this->hasPermission('employee.view.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|integer|exists:branches,id',
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $accessibleBranches = $this->getAccessibleBranches();
        $branchId = $request->get('branch_id', $accessibleBranches->first()?->id);

        if (!$branchId || !$this->validateBranchAccess($branchId)) {
            return $this->forbiddenResponse('Access denied to this branch');
        }

        // Build query
        $query = Employee::where('branch_id', $branchId)
            ->with(['user', 'branch', 'userRoles.role']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('userRoles.role', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->whereHas('user', function($q) use ($isActive) {
                if ($isActive) {
                    $q->whereNull('deleted_at');
                } else {
                    $q->whereNotNull('deleted_at');
                }
            });
        }

        $perPage = $request->get('per_page', 20);
        $employees = $query->orderBy('employee_code')->paginate($perPage);

        // Transform data
        $transformedEmployees = $employees->getCollection()->map(function($employee) {
            $primaryRole = $employee->userRoles->where('is_primary', true)->first();
            
            return [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->user->name,
                'email' => $employee->user->email,
                'phone' => $employee->phone,
                'hire_date' => $employee->hire_date,
                'is_active' => !$employee->user->deleted_at,
                'branch' => [
                    'id' => $employee->branch->id,
                    'name' => $employee->branch->name,
                    'code' => $employee->branch->code,
                ],
                'primary_role' => $primaryRole ? [
                    'id' => $primaryRole->role->id,
                    'name' => $primaryRole->role->name,
                    'display_name' => $primaryRole->role->display_name,
                    'color' => $primaryRole->role->color,
                ] : null,
                'last_attendance' => $this->getLastAttendance($employee->id),
            ];
        });

        $employees->setCollection($transformedEmployees);

        return $this->paginatedResponse($employees, 'Employees retrieved successfully');
    }

    /**
     * Get specific employee details
     */
    public function getEmployee($id)
    {
        if (!$this->hasPermission('employee.view.branch') && !$this->hasPermission('employee.view.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $employee = Employee::with(['user', 'branch', 'userRoles.role'])->findOrFail($id);

        // Validate branch access
        if (!$this->validateBranchAccess($employee->branch_id)) {
            return $this->forbiddenResponse('Access denied to this employee');
        }

        $primaryRole = $employee->userRoles->where('is_primary', true)->first();
        $allRoles = $employee->userRoles->map(function($userRole) {
            return [
                'id' => $userRole->role->id,
                'name' => $userRole->role->name,
                'display_name' => $userRole->role->display_name,
                'color' => $userRole->role->color,
                'is_primary' => $userRole->is_primary,
            ];
        });

        // Get recent stats
        $recentStats = $this->getEmployeeRecentStats($employee->id);

        return $this->successResponse([
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'name' => $employee->user->name,
            'email' => $employee->user->email,
            'phone' => $employee->phone,
            'hire_date' => $employee->hire_date,
            'is_active' => !$employee->user->deleted_at,
            'last_login' => $employee->user->last_login_at,
            'branch' => [
                'id' => $employee->branch->id,
                'name' => $employee->branch->name,
                'code' => $employee->branch->code,
                'address' => $employee->branch->address,
            ],
            'primary_role' => $primaryRole ? [
                'id' => $primaryRole->role->id,
                'name' => $primaryRole->role->name,
                'display_name' => $primaryRole->role->display_name,
                'color' => $primaryRole->role->color,
            ] : null,
            'all_roles' => $allRoles,
            'recent_stats' => $recentStats,
        ], 'Employee details retrieved successfully');
    }

    /**
     * Get employee attendance data
     */
    public function getEmployeeAttendance(Request $request, $id)
    {
        if (!$this->hasPermission('attendance.view.branch') && !$this->hasPermission('attendance.view.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $employee = Employee::findOrFail($id);

        // Validate branch access
        if (!$this->validateBranchAccess($employee->branch_id)) {
            return $this->forbiddenResponse('Access denied to this employee');
        }

        // Get date range (default to current month)
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = AttendanceEvent::where('employee_id', $id)
            ->whereBetween('event_time', [$startDate, $endDate])
            ->with(['schedule.workShift'])
            ->orderBy('event_time', 'desc');

        $perPage = $request->get('per_page', 20);
        $attendanceEvents = $query->paginate($perPage);

        // Transform data
        $transformedEvents = $attendanceEvents->getCollection()->map(function($event) {
            return [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'event_time' => $event->event_time->toISOString(),
                'is_late' => $event->is_late,
                'is_early_departure' => $event->is_early_departure,
                'notes' => $event->notes,
                'source' => $event->source,
                'schedule' => $event->schedule ? [
                    'work_shift' => [
                        'name' => $event->schedule->workShift->name,
                        'start_time' => $event->schedule->start_time,
                        'end_time' => $event->schedule->end_time,
                    ]
                ] : null,
            ];
        });

        $attendanceEvents->setCollection($transformedEvents);

        // Get period statistics
        $stats = $this->calculateEmployeePeriodStats($id, $startDate, $endDate);

        return $this->successResponse([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'employee_code' => $employee->employee_code,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'stats' => $stats,
            'attendance_events' => $attendanceEvents->items(),
            'pagination' => [
                'current_page' => $attendanceEvents->currentPage(),
                'last_page' => $attendanceEvents->lastPage(),
                'per_page' => $attendanceEvents->perPage(),
                'total' => $attendanceEvents->total(),
            ],
        ]);
    }

    /**
     * Get employee schedule data
     */
    public function getEmployeeSchedule(Request $request, $id)
    {
        if (!$this->hasPermission('schedule.view.branch') && !$this->hasPermission('schedule.view.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $employee = Employee::findOrFail($id);

        // Validate branch access
        if (!$this->validateBranchAccess($employee->branch_id)) {
            return $this->forbiddenResponse('Access denied to this employee');
        }

        // Get date range (default to current week)
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        $schedules = \App\Models\EmployeeShiftSchedule::where('employee_id', $id)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->with(['workShift', 'slots.shiftSlot'])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get();

        // Transform data
        $transformedSchedules = $schedules->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'schedule_date' => $schedule->schedule_date,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'notes' => $schedule->notes,
                'work_shift' => [
                    'id' => $schedule->workShift->id,
                    'name' => $schedule->workShift->name,
                    'color' => $schedule->workShift->color,
                ],
                'duration_hours' => $this->calculateDurationHours($schedule->start_time, $schedule->end_time),
            ];
        });

        return $this->successResponse([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'employee_code' => $employee->employee_code,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'schedules' => $transformedSchedules,
            'total_scheduled_hours' => $transformedSchedules->sum('duration_hours'),
        ]);
    }

    /**
     * Get employee statistics
     */
    public function getEmployeeStats(Request $request, $id)
    {
        if (!$this->hasPermission('employee.view.branch') && !$this->hasPermission('employee.view.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:week,month,quarter,year',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $employee = Employee::findOrFail($id);

        // Validate branch access
        if (!$this->validateBranchAccess($employee->branch_id)) {
            return $this->forbiddenResponse('Access denied to this employee');
        }

        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRangeFromPeriod($period);

        $stats = $this->calculateEmployeePeriodStats($id, $startDate, $endDate);

        return $this->successResponse([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'employee_code' => $employee->employee_code,
            ],
            'period' => [
                'type' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Get branch statistics
     */
    public function getBranchStats(Request $request, $id)
    {
        if (!$this->hasPermission('branch.view.all') && !$this->hasPermission('branch.view.assigned')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        if (!$this->validateBranchAccess($id)) {
            return $this->forbiddenResponse('Access denied to this branch');
        }

        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $branch = Branch::findOrFail($id);
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        $stats = $this->calculateBranchDailyStats($branch, $date);

        return $this->successResponse([
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ],
            'date' => $date,
            'stats' => $stats,
        ]);
    }

    /**
     * Calculate dashboard statistics
     */
    private function calculateDashboardStats($branch, $date)
    {
        $employees = Employee::where('branch_id', $branch->id)->get();
        $employeeIds = $employees->pluck('id');

        // Today's attendance events
        $todayEvents = AttendanceEvent::whereIn('employee_id', $employeeIds)
            ->whereDate('event_time', $date)
            ->get();

        $checkIns = $todayEvents->where('event_type', 'check_in');
        $lateArrivals = $checkIns->where('is_late', true);
        $earlyDepartures = $todayEvents->where('event_type', 'check_out')
            ->where('is_early_departure', true);

        // Recent activities (last 10 events)
        $recentActivities = AttendanceEvent::whereIn('employee_id', $employeeIds)
            ->with(['employee.user'])
            ->orderBy('event_time', 'desc')
            ->limit(10)
            ->get()
            ->map(function($event) {
                return [
                    'id' => $event->id,
                    'employee_name' => $event->employee->user->name,
                    'event_type' => $event->event_type,
                    'event_time' => $event->event_time->toISOString(),
                    'is_late' => $event->is_late,
                    'is_early_departure' => $event->is_early_departure,
                ];
            });

        // Attendance overview
        $attendanceOverview = $employees->map(function($employee) use ($date) {
            $todayEvents = AttendanceEvent::where('employee_id', $employee->id)
                ->whereDate('event_time', $date)
                ->orderBy('event_time', 'desc')
                ->get();

            $checkIn = $todayEvents->where('event_type', 'check_in')->first();
            $checkOut = $todayEvents->where('event_type', 'check_out')->first();

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->user->name,
                'employee_code' => $employee->employee_code,
                'status' => $this->determineAttendanceStatus($checkIn, $checkOut),
                'check_in_time' => $checkIn?->event_time->format('H:i'),
                'check_out_time' => $checkOut?->event_time->format('H:i'),
                'is_late' => $checkIn?->is_late ?? false,
                'is_early_departure' => $checkOut?->is_early_departure ?? false,
            ];
        });

        // Alerts
        $alerts = [];
        if ($lateArrivals->count() > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => $lateArrivals->count() . ' employees arrived late today',
                'count' => $lateArrivals->count(),
            ];
        }
        if ($earlyDepartures->count() > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => $earlyDepartures->count() . ' employees left early today',
                'count' => $earlyDepartures->count(),
            ];
        }

        return [
            'stats' => [
                'total_employees' => $employees->count(),
                'present_today' => $checkIns->count(),
                'absent_today' => max(0, $employees->count() - $checkIns->count()),
                'late_arrivals' => $lateArrivals->count(),
                'early_departures' => $earlyDepartures->count(),
                'on_time_rate' => $checkIns->count() > 0 
                    ? round((($checkIns->count() - $lateArrivals->count()) / $checkIns->count()) * 100, 1)
                    : 0,
            ],
            'recent_activities' => $recentActivities,
            'attendance_overview' => $attendanceOverview,
            'alerts' => $alerts,
        ];
    }

    /**
     * Get employee recent stats (last 30 days)
     */
    private function getEmployeeRecentStats($employeeId)
    {
        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        return $this->calculateEmployeePeriodStats($employeeId, $startDate, $endDate);
    }

    /**
     * Calculate employee period statistics
     */
    private function calculateEmployeePeriodStats($employeeId, $startDate, $endDate)
    {
        $totalWorkingDays = $this->getTotalWorkingDays($startDate, $endDate);
        
        $checkIns = AttendanceEvent::where('employee_id', $employeeId)
            ->where('event_type', 'check_in')
            ->whereBetween('event_time', [$startDate, $endDate])
            ->get();

        $daysPresent = $checkIns->count();
        $lateArrivals = $checkIns->where('is_late', true)->count();

        $earlyDepartures = AttendanceEvent::where('employee_id', $employeeId)
            ->where('event_type', 'check_out')
            ->where('is_early_departure', true)
            ->whereBetween('event_time', [$startDate, $endDate])
            ->count();

        $attendanceRate = $totalWorkingDays > 0 ? round(($daysPresent / $totalWorkingDays) * 100, 1) : 0;
        $ontimeRate = $daysPresent > 0 ? round((($daysPresent - $lateArrivals) / $daysPresent) * 100, 1) : 0;

        return [
            'total_working_days' => $totalWorkingDays,
            'days_present' => $daysPresent,
            'days_absent' => max(0, $totalWorkingDays - $daysPresent),
            'late_arrivals' => $lateArrivals,
            'early_departures' => $earlyDepartures,
            'attendance_rate' => $attendanceRate,
            'ontime_rate' => $ontimeRate,
        ];
    }

    /**
     * Calculate branch daily statistics
     */
    private function calculateBranchDailyStats($branch, $date)
    {
        $employees = Employee::where('branch_id', $branch->id)->get();
        $employeeIds = $employees->pluck('id');

        $todayEvents = AttendanceEvent::whereIn('employee_id', $employeeIds)
            ->whereDate('event_time', $date)
            ->get();

        $checkIns = $todayEvents->where('event_type', 'check_in');
        $checkOuts = $todayEvents->where('event_type', 'check_out');
        $lateArrivals = $checkIns->where('is_late', true);
        $earlyDepartures = $checkOuts->where('is_early_departure', true);

        return [
            'total_employees' => $employees->count(),
            'check_ins' => $checkIns->count(),
            'check_outs' => $checkOuts->count(),
            'late_arrivals' => $lateArrivals->count(),
            'early_departures' => $earlyDepartures->count(),
            'present_rate' => $employees->count() > 0 
                ? round(($checkIns->count() / $employees->count()) * 100, 1)
                : 0,
            'ontime_rate' => $checkIns->count() > 0 
                ? round((($checkIns->count() - $lateArrivals->count()) / $checkIns->count()) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get last attendance event for employee
     */
    private function getLastAttendance($employeeId)
    {
        $lastEvent = AttendanceEvent::where('employee_id', $employeeId)
            ->orderBy('event_time', 'desc')
            ->first();

        return $lastEvent ? [
            'event_type' => $lastEvent->event_type,
            'event_time' => $lastEvent->event_time->toISOString(),
            'days_ago' => $lastEvent->event_time->diffInDays(Carbon::now()),
        ] : null;
    }

    /**
     * Helper methods
     */
    private function determineAttendanceStatus($checkIn, $checkOut)
    {
        if (!$checkIn) return 'absent';
        if ($checkIn && !$checkOut) return 'checked_in';
        return 'completed';
    }

    private function calculateDurationHours($startTime, $endTime)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        if ($end->lt($start)) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    private function getTotalWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        return $start->diffInWeekdays($end) + ($start->isWeekday() ? 1 : 0);
    }

    private function getDateRangeFromPeriod($period)
    {
        $now = Carbon::now();
        
        return match($period) {
            'week' => [$now->startOfWeek()->format('Y-m-d'), $now->endOfWeek()->format('Y-m-d')],
            'month' => [$now->startOfMonth()->format('Y-m-d'), $now->endOfMonth()->format('Y-m-d')],
            'quarter' => [$now->startOfQuarter()->format('Y-m-d'), $now->endOfQuarter()->format('Y-m-d')],
            'year' => [$now->startOfYear()->format('Y-m-d'), $now->endOfYear()->format('Y-m-d')],
            default => [$now->startOfMonth()->format('Y-m-d'), $now->endOfMonth()->format('Y-m-d')]
        };
    }
}
