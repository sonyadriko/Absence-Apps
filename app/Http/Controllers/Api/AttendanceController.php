<?php

namespace App\Http\Controllers\Api;

use App\Models\AttendanceEvent;
use App\Models\Employee;
use App\Services\PolicyResolver;
use App\Services\ShiftResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends ApiController
{
    protected $policyResolver;
    protected $shiftResolver;

    public function __construct(PolicyResolver $policyResolver, ShiftResolver $shiftResolver)
    {
        $this->policyResolver = $policyResolver;
        $this->shiftResolver = $shiftResolver;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get current attendance status and today's events
     */
    public function getCurrentStatus()
    {
        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $today = Carbon::now()->format('Y-m-d');
        
        // Get current shift schedule for today
        $currentSchedule = $this->shiftResolver->getCurrentShift($employee, $today);
        
        // Get today's attendance events
        $todayEvents = AttendanceEvent::where('employee_id', $employee->id)
            ->whereDate('event_time', $today)
            ->orderBy('event_time', 'desc')
            ->get();

        // Get latest event
        $latestEvent = $todayEvents->first();

        // Determine current status
        $status = $this->determineCurrentStatus($todayEvents);

        // Get branch info for GPS validation
        $branch = $employee->branch;

        return $this->successResponse([
            'current_status' => $status,
            'latest_event' => $latestEvent ? [
                'id' => $latestEvent->id,
                'event_type' => $latestEvent->event_type,
                'event_time' => $latestEvent->event_time->toISOString(),
                'is_late' => $latestEvent->is_late,
                'is_early_departure' => $latestEvent->is_early_departure,
            ] : null,
            'today_events' => $todayEvents->map(function($event) {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_time' => $event->event_time->toISOString(),
                    'is_late' => $event->is_late,
                    'is_early_departure' => $event->is_early_departure,
                    'notes' => $event->notes,
                ];
            }),
            'current_schedule' => $currentSchedule ? [
                'id' => $currentSchedule->id,
                'work_shift' => [
                    'id' => $currentSchedule->workShift->id,
                    'name' => $currentSchedule->workShift->name,
                ],
                'start_time' => $currentSchedule->start_time,
                'end_time' => $currentSchedule->end_time,
                'schedule_date' => $currentSchedule->schedule_date,
            ] : null,
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'geofence_radius' => $branch->geofence_radius,
            ]
        ]);
    }

    /**
     * Process check-in/check-out
     */
    public function processCheckInOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|in:check_in,check_out,break_start,break_end',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'selfie' => 'required|image|mimes:jpeg,jpg,png|max:2048',
            'notes' => 'nullable|string|max:500',
            'is_kiosk_mode' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
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
                return $this->errorResponse($gpsValidation['message']);
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
                Storage::delete($selfiePath);
                return $this->errorResponse('Duplicate event detected. Please wait before trying again.');
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
                'accuracy' => $request->accuracy,
                'selfie_path' => $selfiePath,
                'notes' => $request->notes,
                'event_hash' => $eventHash,
                'shift_schedule_id' => $schedule ? $schedule->id : null,
                'policy_id' => $policy ? $policy->id : null,
                'is_late' => $this->calculateIsLate($request->event_type, $eventTime, $schedule, $policy),
                'is_early_departure' => $this->calculateIsEarlyDeparture($request->event_type, $eventTime, $schedule, $policy),
                'source' => $request->is_kiosk_mode ? 'kiosk' : 'mobile'
            ]);

            // Log the attendance event
            \App\Models\AuditLog::create([
                'user_id' => $this->getAuthenticatedUser()->id,
                'employee_id' => $employee->id,
                'action' => 'attendance_' . $request->event_type,
                'model_type' => 'AttendanceEvent',
                'model_id' => $attendanceEvent->id,
                'old_values' => null,
                'new_values' => json_encode([
                    'event_type' => $attendanceEvent->event_type,
                    'event_time' => $attendanceEvent->event_time->toISOString(),
                    'latitude' => $attendanceEvent->latitude,
                    'longitude' => $attendanceEvent->longitude,
                    'is_late' => $attendanceEvent->is_late,
                    'is_early_departure' => $attendanceEvent->is_early_departure,
                    'source' => $attendanceEvent->source,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            DB::commit();

            return $this->successResponse([
                'event' => [
                    'id' => $attendanceEvent->id,
                    'event_type' => $attendanceEvent->event_type,
                    'event_time' => $attendanceEvent->event_time->toISOString(),
                    'is_late' => $attendanceEvent->is_late,
                    'is_early_departure' => $attendanceEvent->is_early_departure,
                ],
                'current_status' => $this->determineCurrentStatus(
                    AttendanceEvent::where('employee_id', $employee->id)
                        ->whereDate('event_time', $eventTime->format('Y-m-d'))
                        ->orderBy('event_time', 'desc')
                        ->get()
                ),
                'message' => $this->getSuccessMessage($request->event_type)
            ], $this->getSuccessMessage($request->event_type));

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete uploaded selfie if exists
            if (isset($selfiePath)) {
                Storage::delete($selfiePath);
            }

            return $this->serverErrorResponse('Failed to process attendance. Please try again.');
        }
    }

    /**
     * Get attendance history with pagination
     */
    public function getAttendanceHistory(Request $request)
    {
        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'event_type' => 'nullable|in:check_in,check_out,break_start,break_end',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $query = AttendanceEvent::where('employee_id', $employee->id)
            ->with(['branch', 'schedule.workShift'])
            ->orderBy('event_time', 'desc');

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('event_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('event_time', '<=', $request->end_date);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Default to current month if no filters
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $query->whereMonth('event_time', Carbon::now()->month)
                  ->whereYear('event_time', Carbon::now()->year);
        }

        $perPage = $request->get('per_page', 20);
        $attendanceEvents = $query->paginate($perPage);

        // Transform data
        $transformedEvents = $attendanceEvents->getCollection()->map(function($event) {
            return [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'event_time' => $event->event_time->toISOString(),
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
                'accuracy' => $event->accuracy,
                'notes' => $event->notes,
                'is_late' => $event->is_late,
                'is_early_departure' => $event->is_early_departure,
                'source' => $event->source,
                'schedule' => $event->schedule ? [
                    'id' => $event->schedule->id,
                    'work_shift' => [
                        'id' => $event->schedule->workShift->id,
                        'name' => $event->schedule->workShift->name,
                    ],
                    'start_time' => $event->schedule->start_time,
                    'end_time' => $event->schedule->end_time,
                ] : null,
            ];
        });

        $attendanceEvents->setCollection($transformedEvents);

        return $this->paginatedResponse($attendanceEvents);
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats(Request $request)
    {
        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:week,month,quarter,year',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Determine date range based on period or explicit dates
        if ($request->filled('period')) {
            [$startDate, $endDate] = $this->getDateRangeFromPeriod($request->period);
        } else {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        }

        $stats = $this->calculateAttendanceStats($employee, $startDate, $endDate);

        return $this->successResponse($stats);
    }

    /**
     * Get selfie image for attendance event
     */
    public function getSelfie($eventId)
    {
        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $event = AttendanceEvent::where('id', $eventId)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$event) {
            return $this->notFoundResponse('Attendance event not found');
        }

        if (!$event->selfie_path || !Storage::exists($event->selfie_path)) {
            return $this->notFoundResponse('Selfie image not found');
        }

        // Return base64 encoded image
        $imageData = Storage::get($event->selfie_path);
        $base64Image = base64_encode($imageData);
        $mimeType = Storage::mimeType($event->selfie_path);

        return $this->successResponse([
            'image' => "data:{$mimeType};base64,{$base64Image}",
            'event_id' => $event->id,
            'event_time' => $event->event_time->toISOString(),
        ]);
    }

    /**
     * Validate GPS location against branch coordinates
     */
    private function validateGPSLocation($branch, $latitude, $longitude)
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
                'message' => "You are {$distance}m away from the branch. Please get closer to check in/out (allowed: {$allowedRadius}m).",
                'distance' => $distance,
                'allowed_radius' => $allowedRadius
            ];
        }

        return [
            'valid' => true,
            'distance' => $distance,
            'allowed_radius' => $allowedRadius
        ];
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
     * Determine current attendance status based on today's events
     */
    private function determineCurrentStatus($todayEvents)
    {
        if ($todayEvents->isEmpty()) {
            return 'not_started';
        }

        $latestEvent = $todayEvents->first();
        
        return match($latestEvent->event_type) {
            'check_in' => 'checked_in',
            'check_out' => 'checked_out',
            'break_start' => 'on_break',
            'break_end' => 'back_from_break',
            default => 'unknown'
        };
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
     * Get date range from period
     */
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

    /**
     * Calculate attendance statistics
     */
    private function calculateAttendanceStats($employee, $startDate, $endDate)
    {
        // Get total working days
        $totalWorkingDays = $this->getTotalWorkingDays($startDate, $endDate);
        
        // Get check-ins in the period
        $checkIns = AttendanceEvent::where('employee_id', $employee->id)
            ->where('event_type', 'check_in')
            ->whereBetween('event_time', [$startDate, $endDate])
            ->get();

        $daysPresent = $checkIns->count();
        $lateArrivals = $checkIns->where('is_late', true)->count();

        // Get early departures
        $earlyDepartures = AttendanceEvent::where('employee_id', $employee->id)
            ->where('event_type', 'check_out')
            ->where('is_early_departure', true)
            ->whereBetween('event_time', [$startDate, $endDate])
            ->count();

        // Calculate percentages
        $attendanceRate = $totalWorkingDays > 0 ? round(($daysPresent / $totalWorkingDays) * 100, 1) : 0;
        $ontimeRate = $daysPresent > 0 ? round((($daysPresent - $lateArrivals) / $daysPresent) * 100, 1) : 0;

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => [
                'working_days' => $totalWorkingDays,
                'days_present' => $daysPresent,
                'days_absent' => max(0, $totalWorkingDays - $daysPresent),
                'late_arrivals' => $lateArrivals,
                'early_departures' => $earlyDepartures,
            ],
            'rates' => [
                'attendance_rate' => $attendanceRate,
                'ontime_rate' => $ontimeRate,
            ]
        ];
    }

    /**
     * Get total working days (excluding weekends)
     */
    private function getTotalWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        return $start->diffInWeekdays($end) + ($start->isWeekday() ? 1 : 0);
    }
}
