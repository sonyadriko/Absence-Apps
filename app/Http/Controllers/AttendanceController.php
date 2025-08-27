<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEvent;
use App\Models\Employee;
use App\Models\Branch;
use App\Services\PolicyResolver;
use App\Services\ShiftResolver;
use App\Services\RBACService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    protected $policyResolver;
    protected $shiftResolver;
    protected $rbacService;

    public function __construct(
        PolicyResolver $policyResolver,
        ShiftResolver $shiftResolver,
        RBACService $rbacService
    ) {
        $this->policyResolver = $policyResolver;
        $this->shiftResolver = $shiftResolver;
        $this->rbacService = $rbacService;
    }

    /**
     * Show check-in/out form for employees
     */
    public function checkinForm()
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee profile not found.');
        }

        // Get current shift schedule for today
        $today = Carbon::now()->format('Y-m-d');
        $currentSchedule = $this->shiftResolver->getCurrentShift($employee, $today);
        
        // Get latest attendance event for today
        $latestEvent = AttendanceEvent::where('employee_id', $employee->id)
            ->whereDate('event_time', $today)
            ->orderBy('event_time', 'desc')
            ->first();

        // Get employee's branch for GPS validation
        $branch = $employee->branch;

        return view('employee.attendance.checkin', compact(
            'employee', 
            'currentSchedule', 
            'latestEvent',
            'branch'
        ));
    }

    /**
     * Process check-in/out
     */
    public function processCheckInOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|in:check_in,check_out,break_start,break_end',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'selfie' => 'required|image|mimes:jpeg,jpg,png|max:2048',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Auth::user()->employee;
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Validate GPS location
            $gpsValidation = $this->validateGPSLocation(
                $employee->branch,
                $request->latitude,
                $request->longitude
            );

            if (!$gpsValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $gpsValidation['message']
                ], 400);
            }

            // Store selfie
            $selfiePath = $this->storeSelfie($request->file('selfie'), $employee->id);

            // Create attendance event
            $eventTime = Carbon::now();
            $eventHash = $this->generateEventHash($employee->id, $request->event_type, $eventTime);

            // Check for duplicate events (within 1 minute)
            $existingEvent = AttendanceEvent::where('employee_id', $employee->id)
                ->where('event_type', $request->event_type)
                ->where('event_time', '>=', $eventTime->copy()->subMinute())
                ->where('event_time', '<=', $eventTime->copy()->addMinute())
                ->first();

            if ($existingEvent) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate event detected. Please wait before trying again.'
                ], 400);
            }

            // Get current schedule and policy
            $schedule = $this->shiftResolver->getCurrentShift($employee, $eventTime->format('Y-m-d'));
            $policy = $this->policyResolver->resolvePolicy($employee, $eventTime->format('Y-m-d'));

            $attendanceEvent = AttendanceEvent::create([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'event_type' => $request->event_type,
                'event_time' => $eventTime,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy ?? null,
                'selfie_path' => $selfiePath,
                'notes' => $request->notes,
                'event_hash' => $eventHash,
                'shift_schedule_id' => $schedule ? $schedule->id : null,
                'policy_id' => $policy ? $policy->id : null,
                'is_late' => $this->calculateIsLate($request->event_type, $eventTime, $schedule, $policy),
                'is_early_departure' => $this->calculateIsEarlyDeparture($request->event_type, $eventTime, $schedule, $policy),
                'source' => $request->has('kiosk_mode') ? 'kiosk' : 'mobile'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $this->getSuccessMessage($request->event_type),
                'data' => [
                    'event_id' => $attendanceEvent->id,
                    'event_time' => $attendanceEvent->event_time->format('H:i:s'),
                    'event_type' => $attendanceEvent->event_type,
                    'is_late' => $attendanceEvent->is_late,
                    'is_early_departure' => $attendanceEvent->is_early_departure
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete uploaded selfie if exists
            if (isset($selfiePath)) {
                Storage::delete($selfiePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to process attendance. Please try again.'
            ], 500);
        }
    }

    /**
     * Show employee's attendance history
     */
    public function index(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee profile not found.');
        }

        $query = AttendanceEvent::where('employee_id', $employee->id)
            ->with(['branch', 'schedule.workShift'])
            ->orderBy('event_time', 'desc');

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('event_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('event_time', '<=', $request->end_date);
        }

        // Default to current month if no filters
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $query->whereMonth('event_time', Carbon::now()->month)
                  ->whereYear('event_time', Carbon::now()->year);
        }

        $attendanceEvents = $query->paginate(20);

        // Get attendance statistics
        $stats = $this->getAttendanceStats($employee, $request);

        return view('employee.attendance.index', compact('attendanceEvents', 'stats'));
    }

    /**
     * Show attendance overview for managers
     */
    public function managementOverview(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'attendance.view.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'attendance.view.all')) {
            abort(403, 'Unauthorized access');
        }

        $user = Auth::user();
        $userRoles = $this->rbacService->getUserActiveRoles($user);
        $primaryRole = $userRoles->first();

        // Get accessible branches based on role
        $branches = $this->getAccessibleBranches($user);
        
        // Get selected branch (default to first accessible branch)
        $selectedBranchId = $request->get('branch_id', $branches->first()?->id);
        $selectedBranch = $branches->find($selectedBranchId);

        if (!$selectedBranch) {
            return redirect()->back()->with('error', 'No accessible branches found.');
        }

        // Get date range (default to today)
        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        // Get attendance data for selected branch and date range
        $attendanceData = $this->getAttendanceOverviewData($selectedBranch, $startDate, $endDate);

        return view('management.attendance.overview', compact(
            'branches',
            'selectedBranch', 
            'startDate',
            'endDate',
            'attendanceData',
            'primaryRole'
        ));
    }

    /**
     * Validate GPS location against branch coordinates
     */
    private function validateGPSLocation(Branch $branch, $latitude, $longitude)
    {
        if (!$branch->latitude || !$branch->longitude) {
            return [
                'valid' => false,
                'message' => 'Branch GPS coordinates not configured. Please contact administrator.'
            ];
        }

        $distance = $this->calculateDistance(
            $branch->latitude, 
            $branch->longitude,
            $latitude, 
            $longitude
        );

        $allowedRadius = $branch->geofence_radius ?? 100; // Default 100 meters

        if ($distance > $allowedRadius) {
            return [
                'valid' => false,
                'message' => "You are {$distance}m away from the branch. Please get closer to check in/out."
            ];
        }

        return ['valid' => true];
    }

    /**
     * Calculate distance between two GPS points using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return round($distance);
    }

    /**
     * Store selfie image
     */
    private function storeSelfie($file, $employeeId)
    {
        $timestamp = Carbon::now()->format('YmdHis');
        $filename = "selfie_{$employeeId}_{$timestamp}.jpg";
        
        return $file->storeAs('attendance/selfies', $filename, 'private');
    }

    /**
     * Generate unique event hash to prevent duplicates
     */
    private function generateEventHash($employeeId, $eventType, $eventTime)
    {
        return hash('sha256', $employeeId . $eventType . $eventTime->format('Y-m-d H:i'));
    }

    /**
     * Calculate if check-in is late
     */
    private function calculateIsLate($eventType, $eventTime, $schedule, $policy)
    {
        if ($eventType !== 'check_in' || !$schedule || !$policy) {
            return false;
        }

        $scheduledStartTime = Carbon::parse($schedule->start_time);
        $graceMinutes = $policy->late_threshold_minutes ?? 0;
        $lateThreshold = $scheduledStartTime->addMinutes($graceMinutes);

        return $eventTime->gt($lateThreshold);
    }

    /**
     * Calculate if check-out is early departure
     */
    private function calculateIsEarlyDeparture($eventType, $eventTime, $schedule, $policy)
    {
        if ($eventType !== 'check_out' || !$schedule || !$policy) {
            return false;
        }

        $scheduledEndTime = Carbon::parse($schedule->end_time);
        $graceMinutes = $policy->early_departure_threshold_minutes ?? 0;
        $earlyThreshold = $scheduledEndTime->subMinutes($graceMinutes);

        return $eventTime->lt($earlyThreshold);
    }

    /**
     * Get success message based on event type
     */
    private function getSuccessMessage($eventType)
    {
        $messages = [
            'check_in' => 'Successfully checked in!',
            'check_out' => 'Successfully checked out!',
            'break_start' => 'Break started successfully!',
            'break_end' => 'Break ended successfully!'
        ];

        return $messages[$eventType] ?? 'Attendance recorded successfully!';
    }

    /**
     * Get attendance statistics for employee
     */
    private function getAttendanceStats($employee, $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $stats = [];

        // Total working days in period
        $totalWorkingDays = $this->getTotalWorkingDays($employee, $startDate, $endDate);
        $stats['total_working_days'] = $totalWorkingDays;

        // Days with attendance
        $daysWithAttendance = AttendanceEvent::where('employee_id', $employee->id)
            ->whereDate('event_time', '>=', $startDate)
            ->whereDate('event_time', '<=', $endDate)
            ->whereIn('event_type', ['check_in'])
            ->distinct('event_time')
            ->count('event_time');
        
        $stats['days_present'] = $daysWithAttendance;
        $stats['days_absent'] = max(0, $totalWorkingDays - $daysWithAttendance);

        // Late arrivals
        $lateArrivals = AttendanceEvent::where('employee_id', $employee->id)
            ->whereDate('event_time', '>=', $startDate)
            ->whereDate('event_time', '<=', $endDate)
            ->where('event_type', 'check_in')
            ->where('is_late', true)
            ->count();
        
        $stats['late_arrivals'] = $lateArrivals;

        // Early departures
        $earlyDepartures = AttendanceEvent::where('employee_id', $employee->id)
            ->whereDate('event_time', '>=', $startDate)
            ->whereDate('event_time', '<=', $endDate)
            ->where('event_type', 'check_out')
            ->where('is_early_departure', true)
            ->count();
        
        $stats['early_departures'] = $earlyDepartures;

        // On-time percentage
        $stats['ontime_percentage'] = $daysWithAttendance > 0 
            ? round((($daysWithAttendance - $lateArrivals) / $daysWithAttendance) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get total working days for employee in date range
     */
    private function getTotalWorkingDays($employee, $startDate, $endDate)
    {
        // This should check employee's scheduled days
        // For now, return simple weekdays count
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $workingDays = 0;
        while ($start->lte($end)) {
            if (!$start->isWeekend()) {
                $workingDays++;
            }
            $start->addDay();
        }
        
        return $workingDays;
    }

    /**
     * Get accessible branches for user based on role
     */
    private function getAccessibleBranches($user)
    {
        if ($this->rbacService->userHasPermission($user, 'branch.view.all')) {
            // HR Central or System Admin - all branches
            return Branch::all();
        }

        if ($this->rbacService->userHasPermission($user, 'branch.view.assigned')) {
            // Branch Manager or Pengelola - assigned branches only
            $employee = $user->employee;
            if ($employee) {
                // Get branches from manager/pengelola mappings
                return Branch::whereHas('managerBranchMaps', function($query) use ($employee) {
                    $query->where('employee_id', $employee->id);
                })->orWhereHas('pengelolaBranchMaps', function($query) use ($employee) {
                    $query->where('employee_id', $employee->id);
                })->get();
            }
        }

        // Employee - only their own branch
        $employee = $user->employee;
        return $employee ? collect([$employee->branch]) : collect([]);
    }

    /**
     * Get attendance overview data for branch and date range
     */
    private function getAttendanceOverviewData($branch, $startDate, $endDate)
    {
        $data = [];
        
        // Get employees in this branch
        $employees = Employee::where('branch_id', $branch->id)->get();
        
        // Get attendance events for this period
        $events = AttendanceEvent::whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('event_time', '>=', $startDate)
            ->whereDate('event_time', '<=', $endDate)
            ->with(['employee.user'])
            ->orderBy('event_time', 'desc')
            ->get();

        $data['events'] = $events;

        // Calculate summary statistics
        $data['total_employees'] = $employees->count();
        $data['total_events'] = $events->count();
        $data['check_ins_today'] = $events->where('event_type', 'check_in')
            ->filter(function($event) {
                return $event->event_time->isToday();
            })->count();
        $data['late_today'] = $events->where('event_type', 'check_in')
            ->where('is_late', true)
            ->filter(function($event) {
                return $event->event_time->isToday();
            })->count();

        return $data;
    }
}
