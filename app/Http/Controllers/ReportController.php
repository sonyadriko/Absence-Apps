<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEvent;
use App\Models\Employee;
use App\Models\Branch;
use App\Services\RBACService;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected $rbacService;
    protected $rollupService;

    public function __construct(RBACService $rbacService, RollupService $rollupService)
    {
        $this->rbacService = $rbacService;
        $this->rollupService = $rollupService;
    }

    /**
     * Show reports index with available report types
     */
    public function index()
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'report.view.own') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'report.view.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'report.view.all')) {
            abort(403, 'Unauthorized access');
        }

        $user = Auth::user();
        $userRoles = $this->rbacService->getUserActiveRoles($user);
        $primaryRole = $userRoles->first();

        // Get accessible branches
        $branches = $this->getAccessibleBranches($user);

        // Define available reports based on permissions
        $availableReports = $this->getAvailableReports($user);

        return view('reports.index', compact('branches', 'primaryRole', 'availableReports'));
    }

    /**
     * Daily attendance summary report
     */
    public function dailyAttendanceSummary(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'report.view.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'report.view.all')) {
            abort(403, 'Unauthorized access');
        }

        $user = Auth::user();
        $branches = $this->getAccessibleBranches($user);
        
        // Get parameters
        $selectedBranchId = $request->get('branch_id', $branches->first()?->id);
        $selectedBranch = $branches->find($selectedBranchId);
        $reportDate = $request->get('report_date', Carbon::today()->format('Y-m-d'));

        if (!$selectedBranch) {
            return redirect()->back()->with('error', 'No accessible branches found.');
        }

        // Get employees in the branch
        $employees = Employee::where('branch_id', $selectedBranch->id)
            ->with('user')
            ->orderBy('employee_code')
            ->get();

        // Get attendance events for the date
        $attendanceEvents = AttendanceEvent::whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('event_time', $reportDate)
            ->with(['employee.user', 'schedule.workShift'])
            ->orderBy('event_time')
            ->get();

        // Group events by employee
        $employeeAttendance = $attendanceEvents->groupBy('employee_id');

        // Build daily summary data
        $summaryData = [];
        foreach ($employees as $employee) {
            $events = $employeeAttendance->get($employee->id, collect());
            $checkIn = $events->where('event_type', 'check_in')->first();
            $checkOut = $events->where('event_type', 'check_out')->first();

            $summaryData[] = [
                'employee' => $employee,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $this->getDailyAttendanceStatus($checkIn, $checkOut),
                'working_hours' => $this->calculateWorkingHours($checkIn, $checkOut),
                'is_late' => $checkIn ? $checkIn->is_late : false,
                'is_early_departure' => $checkOut ? $checkOut->is_early_departure : false
            ];
        }

        // Calculate summary statistics
        $stats = [
            'total_employees' => $employees->count(),
            'present' => collect($summaryData)->where('status', 'present')->count(),
            'absent' => collect($summaryData)->where('status', 'absent')->count(),
            'late' => collect($summaryData)->where('is_late', true)->count(),
            'early_departure' => collect($summaryData)->where('is_early_departure', true)->count(),
        ];

        if ($request->expectsJson() || $request->get('format') === 'json') {
            return response()->json([
                'branch' => $selectedBranch,
                'report_date' => $reportDate,
                'summary_data' => $summaryData,
                'stats' => $stats
            ]);
        }

        return view('reports.daily-attendance-summary', compact(
            'branches',
            'selectedBranch',
            'reportDate',
            'summaryData',
            'stats'
        ));
    }

    /**
     * Monthly employee recap report
     */
    public function monthlyEmployeeRecap(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'report.view.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'report.view.all')) {
            abort(403, 'Unauthorized access');
        }

        $user = Auth::user();
        $branches = $this->getAccessibleBranches($user);
        
        // Get parameters
        $selectedBranchId = $request->get('branch_id', $branches->first()?->id);
        $selectedBranch = $branches->find($selectedBranchId);
        $reportMonth = $request->get('report_month', Carbon::now()->format('Y-m'));

        if (!$selectedBranch) {
            return redirect()->back()->with('error', 'No accessible branches found.');
        }

        $startDate = Carbon::parse($reportMonth . '-01');
        $endDate = $startDate->copy()->endOfMonth();

        // Get employees in the branch
        $employees = Employee::where('branch_id', $selectedBranch->id)
            ->with('user')
            ->orderBy('employee_code')
            ->get();

        // Build monthly recap data
        $recapData = [];
        foreach ($employees as $employee) {
            $stats = $this->getEmployeeMonthlyStats($employee, $startDate, $endDate);
            $recapData[] = [
                'employee' => $employee,
                'stats' => $stats
            ];
        }

        // Calculate branch-level summary
        $branchStats = [
            'total_employees' => $employees->count(),
            'avg_attendance_rate' => collect($recapData)->avg('stats.attendance_rate'),
            'avg_ontime_rate' => collect($recapData)->avg('stats.ontime_rate'),
            'total_working_days' => $startDate->diffInWeekdays($endDate) + 1,
        ];

        if ($request->expectsJson() || $request->get('format') === 'json') {
            return response()->json([
                'branch' => $selectedBranch,
                'report_month' => $reportMonth,
                'recap_data' => $recapData,
                'branch_stats' => $branchStats
            ]);
        }

        return view('reports.monthly-employee-recap', compact(
            'branches',
            'selectedBranch',
            'reportMonth',
            'recapData',
            'branchStats'
        ));
    }

    /**
     * Peak hours coverage analysis report
     */
    public function peakHoursCoverage(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'report.view.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'report.view.all')) {
            abort(403, 'Unauthorized access');
        }

        $user = Auth::user();
        $branches = $this->getAccessibleBranches($user);
        
        // Get parameters
        $selectedBranchId = $request->get('branch_id', $branches->first()?->id);
        $selectedBranch = $branches->find($selectedBranchId);
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        if (!$selectedBranch) {
            return redirect()->back()->with('error', 'No accessible branches found.');
        }

        // Define peak hours (based on requirements)
        $peakHours = [
            'morning' => ['start' => '07:00', 'end' => '10:00', 'label' => 'Morning Peak (07:00-10:00)'],
            'lunch' => ['start' => '11:00', 'end' => '14:00', 'label' => 'Lunch Peak (11:00-14:00)'],
            'evening' => ['start' => '17:00', 'end' => '22:00', 'label' => 'Evening Peak (17:00-22:00)']
        ];

        // Get employees in the branch
        $employees = Employee::where('branch_id', $selectedBranch->id)->get();

        // Analyze coverage for each peak period
        $coverageData = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($start->lte($end)) {
            $dailyCoverage = [
                'date' => $start->format('Y-m-d'),
                'day_name' => $start->format('l'),
                'peak_periods' => []
            ];

            foreach ($peakHours as $periodKey => $period) {
                $coverage = $this->calculatePeakHourCoverage(
                    $employees, 
                    $start->format('Y-m-d'),
                    $period['start'],
                    $period['end']
                );

                $dailyCoverage['peak_periods'][$periodKey] = [
                    'label' => $period['label'],
                    'required_staff' => $coverage['required_staff'],
                    'present_staff' => $coverage['present_staff'],
                    'coverage_percentage' => $coverage['coverage_percentage'],
                    'staff_shortage' => $coverage['staff_shortage']
                ];
            }

            $coverageData[] = $dailyCoverage;
            $start->addDay();
        }

        // Calculate summary statistics
        $summaryStats = $this->calculatePeakHoursSummary($coverageData);

        if ($request->expectsJson() || $request->get('format') === 'json') {
            return response()->json([
                'branch' => $selectedBranch,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'coverage_data' => $coverageData,
                'summary_stats' => $summaryStats
            ]);
        }

        return view('reports.peak-hours-coverage', compact(
            'branches',
            'selectedBranch',
            'startDate',
            'endDate',
            'coverageData',
            'summaryStats'
        ));
    }

    /**
     * Attendance corrections statistics report
     */
    public function attendanceCorrections(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'report.view.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'report.view.all')) {
            abort(403, 'Unauthorized access');
        }

        $user = Auth::user();
        $branches = $this->getAccessibleBranches($user);
        
        // Get parameters
        $selectedBranchId = $request->get('branch_id', $branches->first()?->id);
        $selectedBranch = $branches->find($selectedBranchId);
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        if (!$selectedBranch) {
            return redirect()->back()->with('error', 'No accessible branches found.');
        }

        // This would typically query attendance corrections/adjustments table
        // For now, we'll simulate this data structure
        $correctionsData = [
            'total_corrections' => 0,
            'corrections_by_type' => [],
            'corrections_by_status' => [],
            'corrections_by_employee' => [],
            'approval_timeline' => []
        ];

        // TODO: Implement actual corrections queries when corrections system is built

        if ($request->expectsJson() || $request->get('format') === 'json') {
            return response()->json([
                'branch' => $selectedBranch,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'corrections_data' => $correctionsData
            ]);
        }

        return view('reports.attendance-corrections', compact(
            'branches',
            'selectedBranch',
            'startDate',
            'endDate',
            'correctionsData'
        ));
    }

    /**
     * Personal attendance report for employees
     */
    public function personalAttendance(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'report.view.own')) {
            abort(403, 'Unauthorized access');
        }

        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee profile not found.');
        }

        // Get parameters
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Get employee's monthly stats
        $stats = $this->getEmployeeMonthlyStats($employee, Carbon::parse($startDate), Carbon::parse($endDate));

        // Get daily attendance breakdown
        $dailyBreakdown = $this->getEmployeeDailyBreakdown($employee, $startDate, $endDate);

        if ($request->expectsJson() || $request->get('format') === 'json') {
            return response()->json([
                'employee' => $employee,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'stats' => $stats,
                'daily_breakdown' => $dailyBreakdown
            ]);
        }

        return view('reports.personal-attendance', compact(
            'employee',
            'startDate',
            'endDate',
            'stats',
            'dailyBreakdown'
        ));
    }

    /**
     * Export report to CSV
     */
    public function exportCsv(Request $request, $reportType)
    {
        // Check permissions based on report type
        $this->validateReportAccess($reportType);

        $filename = $reportType . '_report_' . Carbon::now()->format('Y-m-d_His') . '.csv';

        switch ($reportType) {
            case 'daily-attendance-summary':
                return $this->exportDailyAttendanceCsv($request, $filename);
            case 'monthly-employee-recap':
                return $this->exportMonthlyRecapCsv($request, $filename);
            case 'peak-hours-coverage':
                return $this->exportPeakHoursCsv($request, $filename);
            case 'personal-attendance':
                return $this->exportPersonalAttendanceCsv($request, $filename);
            default:
                abort(404, 'Report type not found');
        }
    }

    /**
     * Get accessible branches for user based on role
     */
    private function getAccessibleBranches($user)
    {
        if ($this->rbacService->userHasPermission($user, 'branch.view.all')) {
            return Branch::all();
        }

        if ($this->rbacService->userHasPermission($user, 'branch.view.assigned')) {
            $employee = $user->employee;
            if ($employee) {
                return Branch::whereHas('managerBranchMaps', function($query) use ($employee) {
                    $query->where('employee_id', $employee->id);
                })->orWhereHas('pengelolaBranchMaps', function($query) use ($employee) {
                    $query->where('employee_id', $employee->id);
                })->get();
            }
        }

        $employee = $user->employee;
        return $employee ? collect([$employee->branch]) : collect([]);
    }

    /**
     * Get available reports based on user permissions
     */
    private function getAvailableReports($user)
    {
        $reports = [];

        if ($this->rbacService->userHasPermission($user, 'report.view.own')) {
            $reports[] = [
                'key' => 'personal-attendance',
                'name' => 'Personal Attendance Report',
                'description' => 'View your own attendance statistics and history',
                'icon' => 'fas fa-user-clock'
            ];
        }

        if ($this->rbacService->userHasPermission($user, 'report.view.branch') ||
            $this->rbacService->userHasPermission($user, 'report.view.all')) {
            
            $reports = array_merge($reports, [
                [
                    'key' => 'daily-attendance-summary',
                    'name' => 'Daily Attendance Summary',
                    'description' => 'Daily overview of employee attendance by branch',
                    'icon' => 'fas fa-calendar-day'
                ],
                [
                    'key' => 'monthly-employee-recap',
                    'name' => 'Monthly Employee Recap',
                    'description' => 'Comprehensive monthly attendance statistics for employees',
                    'icon' => 'fas fa-chart-bar'
                ],
                [
                    'key' => 'peak-hours-coverage',
                    'name' => 'Peak Hours Coverage Analysis',
                    'description' => 'Analyze staffing levels during peak business hours',
                    'icon' => 'fas fa-clock'
                ],
                [
                    'key' => 'attendance-corrections',
                    'name' => 'Attendance Corrections Report',
                    'description' => 'Track attendance correction requests and approvals',
                    'icon' => 'fas fa-edit'
                ]
            ]);
        }

        return $reports;
    }

    /**
     * Get daily attendance status
     */
    private function getDailyAttendanceStatus($checkIn, $checkOut)
    {
        if (!$checkIn) {
            return 'absent';
        }

        if ($checkIn && !$checkOut) {
            return 'incomplete';
        }

        return 'present';
    }

    /**
     * Calculate working hours between check-in and check-out
     */
    private function calculateWorkingHours($checkIn, $checkOut)
    {
        if (!$checkIn || !$checkOut) {
            return null;
        }

        $diff = $checkOut->event_time->diffInMinutes($checkIn->event_time);
        return round($diff / 60, 2);
    }

    /**
     * Get employee monthly statistics
     */
    private function getEmployeeMonthlyStats($employee, $startDate, $endDate)
    {
        $totalWorkingDays = $this->getTotalWorkingDays($startDate, $endDate);
        
        $checkIns = AttendanceEvent::where('employee_id', $employee->id)
            ->where('event_type', 'check_in')
            ->whereBetween('event_time', [$startDate, $endDate])
            ->get();

        $daysPresent = $checkIns->count();
        $lateArrivals = $checkIns->where('is_late', true)->count();

        $attendanceRate = $totalWorkingDays > 0 ? round(($daysPresent / $totalWorkingDays) * 100, 1) : 0;
        $ontimeRate = $daysPresent > 0 ? round((($daysPresent - $lateArrivals) / $daysPresent) * 100, 1) : 0;

        return [
            'total_working_days' => $totalWorkingDays,
            'days_present' => $daysPresent,
            'days_absent' => max(0, $totalWorkingDays - $daysPresent),
            'late_arrivals' => $lateArrivals,
            'attendance_rate' => $attendanceRate,
            'ontime_rate' => $ontimeRate
        ];
    }

    /**
     * Get employee daily breakdown
     */
    private function getEmployeeDailyBreakdown($employee, $startDate, $endDate)
    {
        $events = AttendanceEvent::where('employee_id', $employee->id)
            ->whereBetween('event_time', [$startDate, $endDate])
            ->with('schedule.workShift')
            ->orderBy('event_time')
            ->get();

        return $events->groupBy(function($event) {
            return $event->event_time->format('Y-m-d');
        })->map(function($dayEvents, $date) {
            $checkIn = $dayEvents->where('event_type', 'check_in')->first();
            $checkOut = $dayEvents->where('event_type', 'check_out')->first();

            return [
                'date' => $date,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'working_hours' => $this->calculateWorkingHours($checkIn, $checkOut),
                'status' => $this->getDailyAttendanceStatus($checkIn, $checkOut)
            ];
        });
    }

    /**
     * Calculate peak hour coverage
     */
    private function calculatePeakHourCoverage($employees, $date, $startTime, $endTime)
    {
        // This is a simplified implementation
        // In practice, you'd need to check actual schedules and attendance
        
        $requiredStaff = ceil($employees->count() * 0.7); // Assume 70% staffing requirement
        
        $presentStaff = AttendanceEvent::whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('event_time', $date)
            ->where('event_type', 'check_in')
            ->whereTime('event_time', '<=', $startTime)
            ->count();

        $coveragePercentage = $requiredStaff > 0 ? round(($presentStaff / $requiredStaff) * 100, 1) : 0;
        $staffShortage = max(0, $requiredStaff - $presentStaff);

        return [
            'required_staff' => $requiredStaff,
            'present_staff' => $presentStaff,
            'coverage_percentage' => $coveragePercentage,
            'staff_shortage' => $staffShortage
        ];
    }

    /**
     * Calculate peak hours summary statistics
     */
    private function calculatePeakHoursSummary($coverageData)
    {
        $allPeriods = collect($coverageData)->pluck('peak_periods')->flatten(1);
        
        return [
            'avg_coverage_percentage' => $allPeriods->avg('coverage_percentage'),
            'total_staff_shortage_days' => $allPeriods->where('staff_shortage', '>', 0)->count(),
            'best_covered_period' => $allPeriods->sortByDesc('coverage_percentage')->first(),
            'worst_covered_period' => $allPeriods->sortBy('coverage_percentage')->first()
        ];
    }

    /**
     * Get total working days in date range (excluding weekends)
     */
    private function getTotalWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        return $start->diffInWeekdays($end) + ($start->isWeekday() ? 1 : 0);
    }

    /**
     * Validate user access to specific report type
     */
    private function validateReportAccess($reportType)
    {
        $user = Auth::user();
        
        switch ($reportType) {
            case 'personal-attendance':
                if (!$this->rbacService->userHasPermission($user, 'report.view.own')) {
                    abort(403, 'Unauthorized access');
                }
                break;
            default:
                if (!$this->rbacService->userHasPermission($user, 'report.view.branch') &&
                    !$this->rbacService->userHasPermission($user, 'report.view.all')) {
                    abort(403, 'Unauthorized access');
                }
                break;
        }
    }

    /**
     * Export methods for different report types
     */
    private function exportDailyAttendanceCsv($request, $filename)
    {
        // Implementation for CSV export
        // This would generate and return CSV file
        return response()->json(['message' => 'CSV export functionality to be implemented']);
    }

    private function exportMonthlyRecapCsv($request, $filename)
    {
        return response()->json(['message' => 'CSV export functionality to be implemented']);
    }

    private function exportPeakHoursCsv($request, $filename)
    {
        return response()->json(['message' => 'CSV export functionality to be implemented']);
    }

    private function exportPersonalAttendanceCsv($request, $filename)
    {
        return response()->json(['message' => 'CSV export functionality to be implemented']);
    }
}
