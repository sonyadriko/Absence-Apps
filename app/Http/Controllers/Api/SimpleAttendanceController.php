<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceEvent;
use App\Models\Attendance;
use Carbon\Carbon;

class SimpleAttendanceController extends ApiController
{
    /**
     * Get current attendance status
     */
    public function getCurrentStatus()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $employee = $user->employee;
        $today = Carbon::now()->format('Y-m-d');
        
        // Get today's attendance events if table exists
        $todayEvents = [];
        $latestEvent = null;
        
        // Try to get from attendance_events table, fallback to simple status
        try {
            if (class_exists(AttendanceEvent::class)) {
                $todayEvents = AttendanceEvent::where('employee_id', $employee->id)
                    ->whereDate('event_time', $today)
                    ->orderBy('event_time', 'desc')
                    ->get();
                    
                $latestEvent = $todayEvents->first();
            }
        } catch (\Exception $e) {
            // Table might not exist, continue with basic response
        }

        // Determine current status
        $status = 'not_started';
        if ($latestEvent) {
            $status = match($latestEvent->event_type) {
                'check_in' => 'checked_in',
                'check_out' => 'checked_out',
                'break_start' => 'on_break',
                'break_end' => 'back_from_break',
                default => 'unknown'
            };
        }

        // Get branch info for GPS validation
        $branch = $employee->branch ?? $employee->primaryBranch ?? null;
        
        if (!$branch) {
            // Get first available branch
            $branch = \App\Models\Branch::first();
        }

        return $this->successResponse([
            'current_status' => $status,
            'latest_event' => $latestEvent ? [
                'id' => $latestEvent->id,
                'event_type' => $latestEvent->event_type,
                'event_time' => $latestEvent->event_time->toISOString(),
                'is_late' => $latestEvent->is_late ?? false,
                'is_early_departure' => $latestEvent->is_early_departure ?? false,
            ] : null,
            'today_events' => collect($todayEvents)->map(function($event) {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_time' => $event->event_time->toISOString(),
                    'is_late' => $event->is_late ?? false,
                    'is_early_departure' => $event->is_early_departure ?? false,
                    'notes' => $event->notes ?? '',
                ];
            }),
            'current_schedule' => null, // Simplified for now
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name,
                'latitude' => $branch->latitude ?? -6.2088,
                'longitude' => $branch->longitude ?? 106.8456,
                'geofence_radius' => $branch->geofence_radius ?? 100,
            ] : [
                'id' => 1,
                'name' => 'Main Branch',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'geofence_radius' => 100,
            ]
        ]);
    }

    /**
     * Process check-in/check-out (simplified)
     */
    public function processCheckInOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|in:check_in,check_out',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        try {
            DB::beginTransaction();

            $employee = $user->employee;
            $eventTime = Carbon::now();
            
            // Check if attendance record exists for today
            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $eventTime->format('Y-m-d'))
                ->first();

            if ($request->event_type === 'check_in') {
                // Check if already checked in today
                if ($attendance && $attendance->check_in) {
                    return $this->errorResponse('You have already checked in today', 400);
                }
                
                // Create new attendance record for check-in
                $attendanceData = [
                    'employee_id' => $employee->id,
                    'date' => $eventTime->format('Y-m-d'),
                    'status' => 'present',
                    'check_in' => $eventTime,
                    'actual_check_in' => $eventTime,
                    'notes' => $request->notes,
                    'location_data' => json_encode([
                        'check_in' => [
                            'latitude' => $request->latitude,
                            'longitude' => $request->longitude,
                            'time' => $eventTime->toISOString()
                        ]
                    ])
                ];
                
                // Add branch_id if not check-out
                if ($request->has('branch_id')) {
                    $attendanceData['branch_id'] = $request->branch_id;
                }
                
                if ($attendance) {
                    // Update existing record
                    $attendance->update($attendanceData);
                } else {
                    // Create new record
                    Attendance::create($attendanceData);
                }
            } else {
                // Check-out logic
                if (!$attendance || !$attendance->check_in) {
                    return $this->errorResponse('You must check in first before checking out', 400);
                }
                
                if ($attendance->check_out) {
                    return $this->errorResponse('You have already checked out today', 400);
                }
                
                // Calculate work minutes
                $checkInTime = Carbon::parse($attendance->check_in);
                $workMinutes = $checkInTime->diffInMinutes($eventTime);
                
                // Update location data
                $locationData = json_decode($attendance->location_data, true) ?? [];
                $locationData['check_out'] = [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'time' => $eventTime->toISOString()
                ];
                
                // Update with check-out data
                $attendance->update([
                    'check_out' => $eventTime,
                    'actual_check_out' => $eventTime,
                    'total_work_minutes' => $workMinutes,
                    'location_data' => json_encode($locationData)
                ]);
                
                // Add check-out notes if provided
                if ($request->notes) {
                    $attendance->notes = ($attendance->notes ? $attendance->notes . ' | Check-out: ' : 'Check-out: ') . $request->notes;
                    $attendance->save();
                }
            }

            DB::commit();

            return $this->successResponse([
                'event' => [
                    'event_type' => $request->event_type,
                    'event_time' => $eventTime->toISOString(),
                    'is_late' => false,
                    'is_early_departure' => false,
                ],
                'current_status' => $request->event_type === 'check_in' ? 'checked_in' : 'checked_out',
                'message' => $this->getSuccessMessage($request->event_type)
            ], $this->getSuccessMessage($request->event_type));

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to process attendance: ' . $e->getMessage());
        }
    }

    /**
     * Get attendance history
     */
    public function getAttendanceHistory(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $query = Attendance::with('branch')
            ->where('employee_id', $user->employee->id)
            ->orderBy('date', 'desc');

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Default to current month if no filters
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $query->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
        }

        $perPage = $request->get('per_page', 20);
        $attendances = $query->paginate($perPage);

        // Transform data to match frontend expectations
        $transformedData = $attendances->getCollection()->map(function($attendance) {
            // Calculate work hours from minutes if available
            $workHours = 0;
            if ($attendance->total_work_minutes > 0) {
                $workHours = round($attendance->total_work_minutes / 60, 2);
            }
            
            return [
                'id' => $attendance->id,
                'date' => $attendance->date,
                'branch' => ['name' => $attendance->branch ? $attendance->branch->name : 'Main Branch'],
                'check_in_time' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i:s') : null,
                'check_out_time' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i:s') : null,
                'work_hours' => $workHours,
                'status' => $attendance->status,
                'is_late' => $attendance->late_minutes > 0,
                'check_in_notes' => $attendance->notes,
                'check_out_notes' => null,
            ];
        });

        $attendances->setCollection($transformedData);

        return $this->successResponse($attendances);
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        // Default to current month
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $attendances = Attendance::where('employee_id', $user->employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalDays = Carbon::parse($startDate)->diffInWeekdays(Carbon::parse($endDate)) + 1;
        $presentDays = $attendances->count();
        $lateDays = 0; // Simplified
        $totalHours = $attendances->sum('work_hours');

        return $this->successResponse([
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'total_hours' => $totalHours,
        ]);
    }

    /**
     * Get attendance detail
     */
    public function getAttendanceDetail($id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $attendance = Attendance::where('employee_id', $user->employee->id)
            ->where('id', $id)
            ->first();

        if (!$attendance) {
            return $this->notFoundResponse('Attendance record not found');
        }

        return $this->successResponse([
            'id' => $attendance->id,
            'date' => $attendance->date,
            'branch' => ['name' => 'Main Branch'],
            'check_in_time' => $attendance->check_in_time,
            'check_out_time' => $attendance->check_out_time,
            'work_hours' => $attendance->work_hours ?? 0,
            'status' => $attendance->status,
            'is_late' => false,
            'check_in_notes' => $attendance->notes,
            'check_out_notes' => null,
            'check_in_selfie_url' => null,
            'check_out_selfie_url' => null,
        ]);
    }

    /**
     * Get success message based on event type
     */
    private function getSuccessMessage($eventType)
    {
        $messages = [
            'check_in' => 'Successfully checked in!',
            'check_out' => 'Successfully checked out!',
        ];

        return $messages[$eventType] ?? 'Attendance recorded successfully!';
    }
}
