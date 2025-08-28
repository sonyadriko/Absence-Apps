<?php

namespace App\Http\Controllers\HRCentral;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Display attendance overview for HR Central
     */
    public function index(Request $request)
    {
        // Get all branches for filter dropdown
        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get selected filters
        $selectedBranchId = $request->get('branch_id');
        $selectedDate = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedEmployee = $request->get('employee_id');

        // Get attendance data based on filters
        $attendanceData = $this->getFilteredAttendanceData($selectedBranchId, $selectedDate, $selectedEmployee);

        return view('hr-central.attendance.index', compact(
            'branches',
            'selectedBranchId',
            'selectedDate', 
            'selectedEmployee',
            'attendanceData'
        ));
    }

    /**
     * Get daily attendance summary
     */
    public function dailySummary(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $branchId = $request->get('branch_id');

        $query = Attendance::with(['employee.user', 'branch'])
            ->whereDate('date', $date);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $attendances,
            'summary' => $this->calculateDailySummary($attendances)
        ]);
    }

    /**
     * Get attendance statistics
     */
    public function getStats(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->get('branch_id');

        $stats = $this->calculateAttendanceStats($startDate, $endDate, $branchId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get employees by branch
     */
    public function getEmployeesByBranch(Request $request)
    {
        $branchId = $request->get('branch_id');
        
        if (!$branchId) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $employees = Employee::with('user')
            ->where('primary_branch_id', $branchId)
            ->where('status', 'active')
            ->orderBy('employee_number')
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_number,
                    'name' => $employee->user->name ?? 'Unknown',
                    'position' => $employee->department ?? 'N/A'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Export attendance report
     */
    public function export(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->get('branch_id');

        // Get attendance data
        $query = Attendance::with(['employee.user', 'branch'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $attendances = $query->orderBy('employee_id')
            ->orderBy('date')
            ->get();

        // Generate CSV content
        $filename = "attendance_report_{$startDate}_to_{$endDate}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Employee Number',
                'Employee Name',
                'Branch',
                'Date',
                'Check In',
                'Check Out',
                'Status',
                'Late Minutes',
                'Work Hours',
                'Notes'
            ]);

            // CSV data
            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->employee->employee_number ?? 'N/A',
                    $attendance->employee->user->name ?? 'Unknown',
                    $attendance->branch->name ?? 'Unknown',
                    $attendance->date->format('Y-m-d'),
                    $attendance->check_in ? $attendance->check_in->format('H:i:s') : 'No Check In',
                    $attendance->check_out ? $attendance->check_out->format('H:i:s') : 'No Check Out',
                    $attendance->status ?? 'Unknown',
                    $attendance->late_minutes ?? 0,
                    $attendance->total_work_minutes ? round($attendance->total_work_minutes / 60, 2) : 0,
                    $attendance->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get employee attendance details for specific date
     */
    public function getEmployeeAttendanceDetails(Request $request, $employeeId)
    {
        // Debug info untuk troubleshooting
        $debug = [
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'employee_id' => $employeeId,
            'request_date' => $request->get('date')
        ];
        
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        
        $employee = Employee::with(['user', 'primaryBranch'])->find($employeeId);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
                'debug' => $debug
            ], 404);
        }

        // Get attendance for specific date
        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        // Get recent attendance history (last 7 days)
        $recentAttendances = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', '>=', Carbon::parse($date)->subDays(7))
            ->whereDate('date', '<=', $date)
            ->orderBy('date', 'desc')
            ->get();

        $data = [
            'employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_number,
                'name' => $employee->user->name ?? 'Unknown',
                'branch' => $employee->primaryBranch->name ?? 'Unknown',
                'department' => $employee->department ?? 'N/A',
                'position' => $employee->department ?? 'N/A'
            ],
            'date' => $date,
            'attendance' => $attendance ? [
                'check_in' => $attendance->check_in ? $attendance->check_in->format('H:i:s') : null,
                'check_out' => $attendance->check_out ? $attendance->check_out->format('H:i:s') : null,
                'status' => $attendance->status,
                'late_minutes' => $attendance->late_minutes ?? 0,
                'early_minutes' => $attendance->early_minutes ?? 0,
                'work_hours' => $attendance->total_work_minutes ? round($attendance->total_work_minutes / 60, 2) : 0,
                'notes' => $attendance->notes
            ] : null,
            'recent_history' => $recentAttendances->map(function($att) {
                return [
                    'date' => $att->date->format('Y-m-d'),
                    'check_in' => $att->check_in ? $att->check_in->format('H:i:s') : null,
                    'check_out' => $att->check_out ? $att->check_out->format('H:i:s') : null,
                    'status' => $att->status,
                    'work_hours' => $att->total_work_minutes ? round($att->total_work_minutes / 60, 2) : 0,
                    'late_minutes' => $att->late_minutes ?? 0
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get filtered attendance data
     */
    private function getFilteredAttendanceData($branchId, $date, $employeeId)
    {
        $query = Attendance::with(['employee.user', 'branch'])
            ->whereDate('date', $date);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        return [
            'events' => $attendances,
            'summary' => $this->calculateDailySummary($attendances)
        ];
    }

    /**
     * Calculate daily summary statistics from attendance records
     */
    private function calculateDailySummary($attendances)
    {
        $totalEmployees = $attendances->count();
        $present = $attendances->whereNotNull('check_in')->count();
        $late = $attendances->where('late_minutes', '>', 0)->count();
        $earlyDeparture = $attendances->where('early_minutes', '>', 0)->count();

        return [
            'total_employees' => $totalEmployees,
            'present' => $present,
            'absent' => $totalEmployees - $present,
            'late' => $late,
            'early_departure' => $earlyDeparture,
            'attendance_rate' => $totalEmployees > 0 ? round(($present / $totalEmployees) * 100, 1) : 0,
            'punctuality_rate' => $present > 0 ? round((($present - $late) / $present) * 100, 1) : 0
        ];
    }

    /**
     * Calculate attendance statistics for period
     */
    private function calculateAttendanceStats($startDate, $endDate, $branchId = null)
    {
        $query = Attendance::with(['employee', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $attendances = $query->whereBetween('date', [$startDate, $endDate])->get();

        // Group by branch and calculate stats
        $branchStats = [];
        
        foreach ($attendances as $attendance) {
            $branchName = $attendance->branch->name ?? 'Unknown';
            
            if (!isset($branchStats[$branchName])) {
                $branchStats[$branchName] = [
                    'total_records' => 0,
                    'present_days' => 0,
                    'late_arrivals' => 0,
                    'early_departures' => 0,
                    'total_work_hours' => 0
                ];
            }

            $branchStats[$branchName]['total_records']++;
            
            if ($attendance->check_in) {
                $branchStats[$branchName]['present_days']++;
            }
            
            if ($attendance->late_minutes > 0) {
                $branchStats[$branchName]['late_arrivals']++;
            }
            
            if ($attendance->early_minutes > 0) {
                $branchStats[$branchName]['early_departures']++;
            }
            
            $branchStats[$branchName]['total_work_hours'] += ($attendance->total_work_minutes ?? 0) / 60;
        }

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'branch_stats' => $branchStats,
            'total_records' => $attendances->count(),
            'total_present_days' => $attendances->whereNotNull('check_in')->count(),
            'total_late_arrivals' => $attendances->where('late_minutes', '>', 0)->count(),
            'total_early_departures' => $attendances->where('early_minutes', '>', 0)->count()
        ];
    }
}
