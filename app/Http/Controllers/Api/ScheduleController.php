<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Models\EmployeeShiftSchedule;
use App\Models\EmployeeShiftScheduleSlot;
use App\Models\WorkShift;
use App\Services\ShiftResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends ApiController
{
    protected $shiftResolver;

    public function __construct(ShiftResolver $shiftResolver)
    {
        $this->shiftResolver = $shiftResolver;
        $this->middleware('auth:api');
    }

    /**
     * Get employee's personal schedule
     */
    public function getMySchedule(Request $request)
    {
        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Get date range (default to current week)
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        $query = EmployeeShiftSchedule::where('employee_id', $employee->id)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->with(['workShift', 'slots.shiftSlot'])
            ->orderBy('schedule_date')
            ->orderBy('start_time');

        $perPage = $request->get('per_page', 50);
        $schedules = $query->paginate($perPage);

        // Transform data
        $transformedSchedules = $schedules->getCollection()->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'schedule_date' => $schedule->schedule_date,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'notes' => $schedule->notes,
                'work_shift' => [
                    'id' => $schedule->workShift->id,
                    'name' => $schedule->workShift->name,
                    'code' => $schedule->workShift->code,
                    'color' => $schedule->workShift->color,
                ],
                'slots' => $schedule->slots->map(function($slot) {
                    return [
                        'id' => $slot->shiftSlot->id,
                        'name' => $slot->shiftSlot->name,
                        'start_time' => $slot->shiftSlot->start_time,
                        'end_time' => $slot->shiftSlot->end_time,
                    ];
                }),
                'duration_hours' => $this->calculateDurationHours($schedule->start_time, $schedule->end_time),
            ];
        });

        $schedules->setCollection($transformedSchedules);

        return $this->paginatedResponse($schedules, 'Schedules retrieved successfully');
    }

    /**
     * Get upcoming schedules (next 7 days)
     */
    public function getUpcomingSchedule()
    {
        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $startDate = Carbon::now()->format('Y-m-d');
        $endDate = Carbon::now()->addDays(7)->format('Y-m-d');

        $schedules = EmployeeShiftSchedule::where('employee_id', $employee->id)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->with(['workShift', 'slots.shiftSlot'])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get();

        $upcomingSchedules = $schedules->map(function($schedule) {
            $scheduleDate = Carbon::parse($schedule->schedule_date);
            
            return [
                'id' => $schedule->id,
                'schedule_date' => $schedule->schedule_date,
                'day_name' => $scheduleDate->format('l'),
                'is_today' => $scheduleDate->isToday(),
                'is_tomorrow' => $scheduleDate->isTomorrow(),
                'days_from_now' => $scheduleDate->diffInDays(Carbon::now()),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'duration_hours' => $this->calculateDurationHours($schedule->start_time, $schedule->end_time),
                'work_shift' => [
                    'id' => $schedule->workShift->id,
                    'name' => $schedule->workShift->name,
                    'color' => $schedule->workShift->color,
                ],
                'notes' => $schedule->notes,
            ];
        });

        return $this->successResponse($upcomingSchedules, 'Upcoming schedules retrieved successfully');
    }

    /**
     * Check for schedule conflicts
     */
    public function checkScheduleConflicts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'exclude_schedule_id' => 'nullable|integer|exists:employee_shift_schedules,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $query = EmployeeShiftSchedule::where('employee_id', $employee->id)
            ->where('schedule_date', $request->schedule_date)
            ->where(function($q) use ($request) {
                $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                  ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                  ->orWhere(function($q2) use ($request) {
                      $q2->where('start_time', '<=', $request->start_time)
                         ->where('end_time', '>=', $request->end_time);
                  });
            });

        if ($request->filled('exclude_schedule_id')) {
            $query->where('id', '!=', $request->exclude_schedule_id);
        }

        $conflicts = $query->with('workShift')->get();

        $conflictData = $conflicts->map(function($conflict) {
            return [
                'id' => $conflict->id,
                'schedule_date' => $conflict->schedule_date,
                'start_time' => $conflict->start_time,
                'end_time' => $conflict->end_time,
                'work_shift' => [
                    'id' => $conflict->workShift->id,
                    'name' => $conflict->workShift->name,
                ],
            ];
        });

        return $this->successResponse([
            'has_conflicts' => $conflicts->isNotEmpty(),
            'conflict_count' => $conflicts->count(),
            'conflicts' => $conflictData,
        ], 'Schedule conflicts checked successfully');
    }

    /**
     * Get work shifts for the employee's branch
     */
    public function getWorkShifts()
    {
        $employee = $this->getAuthenticatedEmployee();
        if (!$employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $workShifts = WorkShift::where('branch_id', $employee->branch_id)
            ->with('slots')
            ->orderBy('name')
            ->get();

        $transformedShifts = $workShifts->map(function($shift) {
            return [
                'id' => $shift->id,
                'name' => $shift->name,
                'code' => $shift->code,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'color' => $shift->color,
                'is_overnight' => $shift->is_overnight,
                'duration_hours' => $this->calculateDurationHours($shift->start_time, $shift->end_time),
                'slots' => $shift->slots->map(function($slot) {
                    return [
                        'id' => $slot->id,
                        'name' => $slot->name,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'duration_hours' => $this->calculateDurationHours($slot->start_time, $slot->end_time),
                    ];
                }),
            ];
        });

        return $this->successResponse($transformedShifts, 'Work shifts retrieved successfully');
    }

    // ========================================
    // MANAGEMENT ENDPOINTS (for supervisors/managers)
    // ========================================

    /**
     * Get schedules for management (branch level)
     */
    public function getSchedules(Request $request)
    {
        if (!$this->hasPermission('schedule.view.branch') && !$this->hasPermission('schedule.view.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|integer|exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Get accessible branches
        $accessibleBranches = $this->getAccessibleBranches();
        
        // Validate branch access
        if ($request->filled('branch_id') && !$this->validateBranchAccess($request->branch_id)) {
            return $this->forbiddenResponse('Access denied to this branch');
        }

        // Default to first accessible branch if not specified
        $branchId = $request->get('branch_id', $accessibleBranches->first()?->id);
        
        if (!$branchId) {
            return $this->errorResponse('No accessible branches found');
        }

        // Get date range (default to current week)
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        // Build query
        $query = EmployeeShiftSchedule::whereHas('employee', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->with(['employee.user', 'workShift', 'slots.shiftSlot']);

        // Filter by employee if specified
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $query->orderBy('schedule_date')
              ->orderBy('start_time');

        $perPage = $request->get('per_page', 50);
        $schedules = $query->paginate($perPage);

        // Transform data
        $transformedSchedules = $schedules->getCollection()->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'schedule_date' => $schedule->schedule_date,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'notes' => $schedule->notes,
                'duration_hours' => $this->calculateDurationHours($schedule->start_time, $schedule->end_time),
                'employee' => [
                    'id' => $schedule->employee->id,
                    'employee_code' => $schedule->employee->employee_code,
                    'name' => $schedule->employee->user->name,
                ],
                'work_shift' => [
                    'id' => $schedule->workShift->id,
                    'name' => $schedule->workShift->name,
                    'code' => $schedule->workShift->code,
                    'color' => $schedule->workShift->color,
                ],
                'slots' => $schedule->slots->map(function($slot) {
                    return [
                        'id' => $slot->shiftSlot->id,
                        'name' => $slot->shiftSlot->name,
                        'start_time' => $slot->shiftSlot->start_time,
                        'end_time' => $slot->shiftSlot->end_time,
                    ];
                }),
            ];
        });

        $schedules->setCollection($transformedSchedules);

        return $this->paginatedResponse($schedules, 'Schedules retrieved successfully');
    }

    /**
     * Create a new schedule
     */
    public function createSchedule(Request $request)
    {
        if (!$this->hasPermission('schedule.manage.branch') && !$this->hasPermission('schedule.manage.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'work_shift_id' => 'required|integer|exists:work_shifts,id',
            'schedule_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:500',
            'slot_ids' => 'nullable|array',
            'slot_ids.*' => 'integer|exists:shift_slots,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Validate employee access
        $employee = Employee::findOrFail($request->employee_id);
        if (!$this->validateBranchAccess($employee->branch_id)) {
            return $this->forbiddenResponse('Access denied to employee branch');
        }

        try {
            DB::beginTransaction();

            // Check for existing schedule conflicts
            $existingSchedule = EmployeeShiftSchedule::where('employee_id', $request->employee_id)
                ->where('schedule_date', $request->schedule_date)
                ->where(function($q) use ($request) {
                    $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function($q2) use ($request) {
                          $q2->where('start_time', '<=', $request->start_time)
                             ->where('end_time', '>=', $request->end_time);
                      });
                })
                ->exists();

            if ($existingSchedule) {
                return $this->errorResponse('Schedule conflict detected for this employee on the specified date and time');
            }

            // Create schedule
            $schedule = EmployeeShiftSchedule::create([
                'employee_id' => $request->employee_id,
                'work_shift_id' => $request->work_shift_id,
                'schedule_date' => $request->schedule_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'notes' => $request->notes,
                'created_by' => $this->getAuthenticatedUser()->id,
                'updated_by' => $this->getAuthenticatedUser()->id,
            ]);

            // Add slots if specified
            if ($request->filled('slot_ids')) {
                foreach ($request->slot_ids as $slotId) {
                    EmployeeShiftScheduleSlot::create([
                        'employee_shift_schedule_id' => $schedule->id,
                        'shift_slot_id' => $slotId,
                    ]);
                }
            }

            // Log the creation
            \App\Models\AuditLog::create([
                'user_id' => $this->getAuthenticatedUser()->id,
                'employee_id' => $employee->id,
                'action' => 'schedule_created',
                'model_type' => 'EmployeeShiftSchedule',
                'model_id' => $schedule->id,
                'old_values' => null,
                'new_values' => json_encode($schedule->toArray()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            // Load relations for response
            $schedule->load(['employee.user', 'workShift', 'slots.shiftSlot']);

            return $this->successResponse([
                'id' => $schedule->id,
                'schedule_date' => $schedule->schedule_date,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'notes' => $schedule->notes,
                'employee' => [
                    'id' => $schedule->employee->id,
                    'employee_code' => $schedule->employee->employee_code,
                    'name' => $schedule->employee->user->name,
                ],
                'work_shift' => [
                    'id' => $schedule->workShift->id,
                    'name' => $schedule->workShift->name,
                ],
            ], 'Schedule created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to create schedule');
        }
    }

    /**
     * Update schedule
     */
    public function updateSchedule(Request $request, $id)
    {
        if (!$this->hasPermission('schedule.manage.branch') && !$this->hasPermission('schedule.manage.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $schedule = EmployeeShiftSchedule::findOrFail($id);

        // Validate employee access
        if (!$this->validateBranchAccess($schedule->employee->branch_id)) {
            return $this->forbiddenResponse('Access denied to employee branch');
        }

        $validator = Validator::make($request->all(), [
            'work_shift_id' => 'required|integer|exists:work_shifts,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:500',
            'slot_ids' => 'nullable|array',
            'slot_ids.*' => 'integer|exists:shift_slots,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $oldValues = $schedule->toArray();

            // Update schedule
            $schedule->update([
                'work_shift_id' => $request->work_shift_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'notes' => $request->notes,
                'updated_by' => $this->getAuthenticatedUser()->id,
            ]);

            // Update slots
            EmployeeShiftScheduleSlot::where('employee_shift_schedule_id', $schedule->id)->delete();
            
            if ($request->filled('slot_ids')) {
                foreach ($request->slot_ids as $slotId) {
                    EmployeeShiftScheduleSlot::create([
                        'employee_shift_schedule_id' => $schedule->id,
                        'shift_slot_id' => $slotId,
                    ]);
                }
            }

            // Log the update
            \App\Models\AuditLog::create([
                'user_id' => $this->getAuthenticatedUser()->id,
                'employee_id' => $schedule->employee_id,
                'action' => 'schedule_updated',
                'model_type' => 'EmployeeShiftSchedule',
                'model_id' => $schedule->id,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($schedule->fresh()->toArray()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return $this->successResponse(null, 'Schedule updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to update schedule');
        }
    }

    /**
     * Delete schedule
     */
    public function deleteSchedule($id)
    {
        if (!$this->hasPermission('schedule.manage.branch') && !$this->hasPermission('schedule.manage.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $schedule = EmployeeShiftSchedule::findOrFail($id);

        // Validate employee access
        if (!$this->validateBranchAccess($schedule->employee->branch_id)) {
            return $this->forbiddenResponse('Access denied to employee branch');
        }

        try {
            DB::beginTransaction();

            // Delete slots first
            EmployeeShiftScheduleSlot::where('employee_shift_schedule_id', $schedule->id)->delete();

            // Log the deletion
            \App\Models\AuditLog::create([
                'user_id' => $this->getAuthenticatedUser()->id,
                'employee_id' => $schedule->employee_id,
                'action' => 'schedule_deleted',
                'model_type' => 'EmployeeShiftSchedule',
                'model_id' => $schedule->id,
                'old_values' => json_encode($schedule->toArray()),
                'new_values' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Delete schedule
            $schedule->delete();

            DB::commit();

            return $this->successResponse(null, 'Schedule deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete schedule');
        }
    }

    /**
     * Bulk create schedules
     */
    public function bulkCreateSchedules(Request $request)
    {
        if (!$this->hasPermission('schedule.manage.branch') && !$this->hasPermission('schedule.manage.all')) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'work_shift_id' => 'required|integer|exists:work_shifts,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'selected_days' => 'required|array|min:1',
            'selected_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $workShift = WorkShift::findOrFail($request->work_shift_id);
            $employeeIds = $request->employee_ids;
            $selectedDays = $request->selected_days;

            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);

            $schedulesCreated = 0;
            $schedulesUpdated = 0;
            $errors = [];

            // Validate all employees belong to accessible branches
            $employees = Employee::whereIn('id', $employeeIds)->get();
            foreach ($employees as $employee) {
                if (!$this->validateBranchAccess($employee->branch_id)) {
                    $errors[] = "Access denied to employee {$employee->employee_code}";
                }
            }

            if (!empty($errors)) {
                return $this->errorResponse('Access validation failed', 403, $errors);
            }

            // Create schedules
            while ($start->lte($end)) {
                $dayName = strtolower($start->format('l'));
                
                if (in_array($dayName, $selectedDays)) {
                    foreach ($employeeIds as $employeeId) {
                        // Check if schedule already exists
                        $existingSchedule = EmployeeShiftSchedule::where('employee_id', $employeeId)
                            ->where('schedule_date', $start->format('Y-m-d'))
                            ->first();

                        if ($existingSchedule) {
                            // Update existing
                            $existingSchedule->update([
                                'work_shift_id' => $workShift->id,
                                'start_time' => $workShift->start_time,
                                'end_time' => $workShift->end_time,
                                'updated_by' => $this->getAuthenticatedUser()->id,
                            ]);
                            $schedulesUpdated++;
                        } else {
                            // Create new
                            EmployeeShiftSchedule::create([
                                'employee_id' => $employeeId,
                                'work_shift_id' => $workShift->id,
                                'schedule_date' => $start->format('Y-m-d'),
                                'start_time' => $workShift->start_time,
                                'end_time' => $workShift->end_time,
                                'created_by' => $this->getAuthenticatedUser()->id,
                                'updated_by' => $this->getAuthenticatedUser()->id,
                            ]);
                            $schedulesCreated++;
                        }
                    }
                }
                
                $start->addDay();
            }

            DB::commit();

            return $this->successResponse([
                'schedules_created' => $schedulesCreated,
                'schedules_updated' => $schedulesUpdated,
                'total_processed' => $schedulesCreated + $schedulesUpdated,
            ], "Bulk schedule assignment completed! Created: {$schedulesCreated}, Updated: {$schedulesUpdated}");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to create bulk schedules');
        }
    }

    /**
     * Calculate duration between two times
     */
    private function calculateDurationHours($startTime, $endTime)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        // Handle overnight shifts
        if ($end->lt($start)) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }
}
