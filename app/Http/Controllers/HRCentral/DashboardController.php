<?php

namespace App\Http\Controllers\HRCentral;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Branch;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        return view('hr-central.dashboard');
    }

    public function getStats()
    {
        try {
            $today = Carbon::today();
            
            // Total Employees (active only)
            $totalEmployees = User::where('is_active', true)->count();
            
            // Active Branches  
            $activeBranches = Branch::where('is_active', true)->count();
            
            // Present Today - count users who have checked in today
            $presentToday = Attendance::whereDate('date', $today)
                ->whereNotNull('check_in')
                ->distinct('employee_id')
                ->count();
            
            // Calculate attendance rate
            $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100, 1) : 0;
            
            // Pending Approvals
            $pendingApprovals = LeaveRequest::where('status', 'pending')->count();
            
            // Growth calculation (compare with last month)
            $lastMonth = Carbon::now()->subMonth();
            $totalEmployeesLastMonth = User::where('is_active', true)
                ->where('created_at', '<', $lastMonth)
                ->count();
            
            $employeeGrowth = 0;
            if ($totalEmployeesLastMonth > 0) {
                $employeeGrowth = round((($totalEmployees - $totalEmployeesLastMonth) / $totalEmployeesLastMonth) * 100, 1);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'totalEmployees' => $totalEmployees,
                    'employeeGrowth' => $employeeGrowth,
                    'activeBranches' => $activeBranches,
                    'presentToday' => $presentToday,
                    'attendanceRate' => $attendanceRate,
                    'pendingApprovals' => $pendingApprovals
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRecentLeaveRequests()
    {
        try {
            $requests = LeaveRequest::with(['employee', 'employee.primaryBranch'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $formattedRequests = $requests->map(function ($request) {
                $duration = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1;
                
                return [
                    'id' => $request->id,
                    'employee' => $request->employee->full_name ?? 'N/A',
                    'employee_id' => $request->employee_id ?? 'N/A',
                    'type' => ucfirst($request->leave_type ?? 'Unknown'),
                    'duration' => $duration . ' day' . ($duration > 1 ? 's' : ''),
                    'status' => $request->status,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'branch' => $request->employee->primaryBranch->name ?? 'N/A'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedRequests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load recent leave requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTopPerformers(Request $request)
    {
        try {
            $period = $request->get('period', 'month'); // week, month, quarter
            
            $startDate = match($period) {
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                'quarter' => Carbon::now()->startOfQuarter(),
                default => Carbon::now()->startOfMonth()
            };

            $endDate = Carbon::now();

            // Calculate attendance rate for each user
            $performers = User::with('branch')
                ->where('is_active', true)
                ->get()
                ->map(function ($user) use ($startDate, $endDate) {
                    // Count total working days in the period (excluding weekends)
                    $totalWorkingDays = 0;
                    $current = $startDate->copy();
                    while ($current <= $endDate) {
                        if ($current->isWeekday()) { // Monday to Friday
                            $totalWorkingDays++;
                        }
                        $current->addDay();
                    }

                    // Count days user was present - need to map user to employee_id
                    // For now, assuming user_id = employee_id or use user->employee_id if exists
                    $presentDays = Attendance::where('employee_id', $user->employee_id ?? $user->id)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->whereNotNull('check_in')
                        ->count();

                    $attendanceRate = $totalWorkingDays > 0 ? round(($presentDays / $totalWorkingDays) * 100, 1) : 0;

                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'branch' => $user->branch->name ?? 'N/A',
                        'rate' => $attendanceRate,
                        'present_days' => $presentDays,
                        'total_days' => $totalWorkingDays
                    ];
                })
                ->sortByDesc('rate')
                ->take(5)
                ->values()
                ->map(function ($performer, $index) {
                    $performer['rank'] = $index + 1;
                    return $performer;
                });

            return response()->json([
                'success' => true,
                'data' => $performers,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load top performers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAttendanceTrends(Request $request)
    {
        try {
            $period = $request->get('period', 'week'); // week, month, quarter
            
            $data = match($period) {
                'week' => $this->getWeeklyTrends(),
                'month' => $this->getMonthlyTrends(),
                'quarter' => $this->getQuarterlyTrends(),
                default => $this->getWeeklyTrends()
            };

            return response()->json([
                'success' => true,
                'data' => $data,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance trends',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getWeeklyTrends()
    {
        $trends = [];
        $totalEmployees = User::where('is_active', true)->count();
        
        // Get last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $presentCount = Attendance::whereDate('date', $date)
                ->whereNotNull('check_in')
                ->distinct('employee_id')
                ->count();
            
            $rate = $totalEmployees > 0 ? round(($presentCount / $totalEmployees) * 100, 1) : 0;
            
            $trends[] = [
                'label' => $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'rate' => $rate,
                'present' => $presentCount,
                'total' => $totalEmployees
            ];
        }

        return $trends;
    }

    private function getMonthlyTrends()
    {
        $trends = [];
        $totalEmployees = User::where('is_active', true)->count();
        
        // Get last 30 days, group by week
        for ($i = 4; $i >= 0; $i--) {
            $startWeek = Carbon::now()->subWeeks($i)->startOfWeek();
            $endWeek = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $presentCount = Attendance::whereBetween('date', [$startWeek, $endWeek])
                ->whereNotNull('check_in')
                ->distinct('employee_id')
                ->count();
            
            // Average over working days (5 days per week)
            $avgPresent = $presentCount / 5;
            $rate = $totalEmployees > 0 ? round(($avgPresent / $totalEmployees) * 100, 1) : 0;
            
            $trends[] = [
                'label' => 'Week ' . $startWeek->weekOfMonth,
                'date' => $startWeek->format('Y-m-d'),
                'rate' => $rate,
                'present' => round($avgPresent),
                'total' => $totalEmployees
            ];
        }

        return $trends;
    }

    private function getQuarterlyTrends()
    {
        $trends = [];
        $totalEmployees = User::where('is_active', true)->count();
        
        // Get last 3 months
        for ($i = 2; $i >= 0; $i--) {
            $startMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endMonth = Carbon::now()->subMonths($i)->endOfMonth();
            
            $presentCount = Attendance::whereBetween('date', [$startMonth, $endMonth])
                ->whereNotNull('check_in')
                ->distinct('employee_id')
                ->count();
            
            // Average over working days in month (approximately 22 working days)
            $workingDays = $startMonth->diffInWeekdays($endMonth);
            $avgPresent = $workingDays > 0 ? $presentCount / $workingDays : 0;
            $rate = $totalEmployees > 0 ? round(($avgPresent / $totalEmployees) * 100, 1) : 0;
            
            $trends[] = [
                'label' => $startMonth->format('M'),
                'date' => $startMonth->format('Y-m-d'),
                'rate' => $rate,
                'present' => round($avgPresent * $workingDays),
                'total' => $totalEmployees
            ];
        }

        return $trends;
    }

    public function getDepartmentDistribution()
    {
        try {
            // Get user role distribution
            $distribution = DB::table('users')
                ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('users.is_active = 1')
                ->where('user_roles.is_active', true)
                ->select('roles.display_name as role_name', DB::raw('count(*) as count'))
                ->groupBy('roles.id', 'roles.display_name')
                ->orderBy('count', 'desc')
                ->get();

            // Map role names to more user-friendly department names
            $departmentMap = [
                'Employee' => 'Barista',
                'Senior Barista' => 'Barista',
                'Shift Leader' => 'Management',
                'Supervisor' => 'Management',
                'Branch Manager' => 'Management',
                'Pengelola' => 'Management',
                'HR Central' => 'Admin',
                'System Admin' => 'Admin'
            ];

            $departments = [];
            foreach ($distribution as $item) {
                $deptName = $departmentMap[$item->role_name] ?? 'Other';
                if (isset($departments[$deptName])) {
                    $departments[$deptName] += $item->count;
                } else {
                    $departments[$deptName] = $item->count;
                }
            }

            // Format for Chart.js
            $chartData = [
                'labels' => array_keys($departments),
                'data' => array_values($departments)
            ];

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load department distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
