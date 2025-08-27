<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\WorkShift;
use App\Models\EmployeeShiftSchedule;
use App\Models\EmployeeShiftScheduleSlot;
use App\Services\ShiftResolver;
use App\Services\RBACService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    protected $shiftResolver;
    protected $rbacService;

    public function __construct(ShiftResolver $shiftResolver, RBACService $rbacService)
    {
        $this->shiftResolver = $shiftResolver;
        $this->rbacService = $rbacService;
    }

    /**
     * Show employee's personal schedule
     */
    public function employeeSchedule(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee profile not found.');
        }

        // Get date range (default to current week)
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        // Get employee's schedules for the date range
        $schedules = EmployeeShiftSchedule::where('employee_id', $employee->id)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->with(['workShift', 'slots.shiftSlot'])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get();

        // Group schedules by date for calendar view
        $schedulesByDate = $schedules->groupBy('schedule_date');

        // Get work shifts for reference
        $workShifts = WorkShift::where('branch_id', $employee->branch_id)
            ->orderBy('name')
            ->get();

        return view('employee.schedule.index', compact(
            'employee',
            'schedulesByDate',
            'workShifts',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Show schedule management for managers
     */
    public function managementIndex(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.all')) {
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

        // Get date range (default to current week)
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        // Get employees in the selected branch
        $employees = Employee::where('branch_id', $selectedBranch->id)
            ->with('user')
            ->orderBy('employee_code')
            ->get();

        // Get work shifts for the branch
        $workShifts = WorkShift::where('branch_id', $selectedBranch->id)
            ->with('slots')
            ->orderBy('name')
            ->get();

        // Get existing schedules for the date range
        $schedules = EmployeeShiftSchedule::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->with(['employee.user', 'workShift', 'slots.shiftSlot'])
            ->get();

        // Group schedules by employee and date for roster grid
        $scheduleGrid = $this->buildScheduleGrid($employees, $startDate, $endDate, $schedules);

        return view('management.schedules.index', compact(
            'branches',
            'selectedBranch',
            'employees',
            'workShifts',
            'scheduleGrid',
            'startDate',
            'endDate',
            'primaryRole'
        ));
    }

    /**
     * Show form to create/edit employee schedule
     */
    public function create(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.all')) {
            abort(403, 'Unauthorized access');
        }

        $employeeId = $request->get('employee_id');
        $scheduleDate = $request->get('schedule_date');

        if (!$employeeId || !$scheduleDate) {
            return redirect()->back()->with('error', 'Employee ID and schedule date are required.');
        }

        $employee = Employee::findOrFail($employeeId);
        
        // Check if user has access to this employee's branch
        $accessibleBranches = $this->getAccessibleBranches(Auth::user());
        if (!$accessibleBranches->contains('id', $employee->branch_id)) {
            abort(403, 'Unauthorized access to this employee.');
        }

        // Get work shifts for the branch
        $workShifts = WorkShift::where('branch_id', $employee->branch_id)
            ->with('slots')
            ->orderBy('name')
            ->get();

        // Check if schedule already exists
        $existingSchedule = EmployeeShiftSchedule::where('employee_id', $employeeId)
            ->where('schedule_date', $scheduleDate)
            ->with(['workShift', 'slots.shiftSlot'])
            ->first();

        return view('management.schedules.create', compact(
            'employee',
            'scheduleDate',
            'workShifts',
            'existingSchedule'
        ));
    }

    /**
     * Store employee schedule
     */
    public function store(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.all')) {
            abort(403, 'Unauthorized access');
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'schedule_date' => 'required|date|after_or_equal:today',
            'work_shift_id' => 'required|exists:work_shifts,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'selected_slots' => 'nullable|array',
            'selected_slots.*' => 'exists:shift_slots,id',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $employee = Employee::findOrFail($request->employee_id);
        
        // Check if user has access to this employee's branch
        $accessibleBranches = $this->getAccessibleBranches(Auth::user());
        if (!$accessibleBranches->contains('id', $employee->branch_id)) {
            abort(403, 'Unauthorized access to this employee.');
        }

        try {
            DB::beginTransaction();

            // Check for existing schedule on the same date
            $existingSchedule = EmployeeShiftSchedule::where('employee_id', $request->employee_id)
                ->where('schedule_date', $request->schedule_date)
                ->first();

            if ($existingSchedule) {
                // Update existing schedule
                $existingSchedule->update([
                    'work_shift_id' => $request->work_shift_id,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'notes' => $request->notes,
                    'updated_by' => Auth::id()
                ]);

                // Delete existing slot assignments
                EmployeeShiftScheduleSlot::where('employee_shift_schedule_id', $existingSchedule->id)->delete();

                $schedule = $existingSchedule;
            } else {
                // Create new schedule
                $schedule = EmployeeShiftSchedule::create([
                    'employee_id' => $request->employee_id,
                    'work_shift_id' => $request->work_shift_id,
                    'schedule_date' => $request->schedule_date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'notes' => $request->notes,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Create slot assignments if any
            if ($request->filled('selected_slots')) {
                foreach ($request->selected_slots as $slotId) {
                    EmployeeShiftScheduleSlot::create([
                        'employee_shift_schedule_id' => $schedule->id,
                        'shift_slot_id' => $slotId
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('schedules.management.index', [
                'branch_id' => $employee->branch_id,
                'start_date' => Carbon::parse($request->schedule_date)->startOfWeek()->format('Y-m-d'),
                'end_date' => Carbon::parse($request->schedule_date)->endOfWeek()->format('Y-m-d')
            ])->with('success', 'Schedule saved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to save schedule. Please try again.')
                ->withInput();
        }
    }

    /**
     * Delete employee schedule
     */
    public function destroy(Request $request, $scheduleId)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.all')) {
            abort(403, 'Unauthorized access');
        }

        $schedule = EmployeeShiftSchedule::findOrFail($scheduleId);
        $employee = $schedule->employee;

        // Check if user has access to this employee's branch
        $accessibleBranches = $this->getAccessibleBranches(Auth::user());
        if (!$accessibleBranches->contains('id', $employee->branch_id)) {
            abort(403, 'Unauthorized access to this employee.');
        }

        try {
            DB::beginTransaction();

            // Delete slot assignments first
            EmployeeShiftScheduleSlot::where('employee_shift_schedule_id', $schedule->id)->delete();

            // Delete the schedule
            $schedule->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule. Please try again.'
            ], 500);
        }
    }

    /**
     * Bulk assign schedules using templates
     */
    public function bulkAssign(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'schedule.manage.all')) {
            abort(403, 'Unauthorized access');
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'work_shift_id' => 'required|exists:work_shifts,id',
            'selected_days' => 'required|array|min:1',
            'selected_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user has access to the branch
        $accessibleBranches = $this->getAccessibleBranches(Auth::user());
        if (!$accessibleBranches->contains('id', $request->branch_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this branch'
            ], 403);
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

            // Loop through each date in the range
            while ($start->lte($end)) {
                $dayName = strtolower($start->format('l'));
                
                // Only create schedules for selected days
                if (in_array($dayName, $selectedDays)) {
                    foreach ($employeeIds as $employeeId) {
                        // Check if schedule already exists
                        $existingSchedule = EmployeeShiftSchedule::where('employee_id', $employeeId)
                            ->where('schedule_date', $start->format('Y-m-d'))
                            ->first();

                        if ($existingSchedule) {
                            // Update existing schedule
                            $existingSchedule->update([
                                'work_shift_id' => $workShift->id,
                                'start_time' => $workShift->start_time,
                                'end_time' => $workShift->end_time,
                                'updated_by' => Auth::id()
                            ]);
                            $schedulesUpdated++;
                        } else {
                            // Create new schedule
                            EmployeeShiftSchedule::create([
                                'employee_id' => $employeeId,
                                'work_shift_id' => $workShift->id,
                                'schedule_date' => $start->format('Y-m-d'),
                                'start_time' => $workShift->start_time,
                                'end_time' => $workShift->end_time,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id()
                            ]);
                            $schedulesCreated++;
                        }
                    }
                }
                
                $start->addDay();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk assignment completed! Created: {$schedulesCreated}, Updated: {$schedulesUpdated}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign schedules. Please try again.'
            ], 500);
        }
    }

    /**
     * Get schedule conflicts for validation
     */
    public function checkConflicts(Request $request)
    {
        $employeeId = $request->get('employee_id');
        $scheduleDate = $request->get('schedule_date');
        $startTime = $request->get('start_time');
        $endTime = $request->get('end_time');
        $excludeScheduleId = $request->get('exclude_schedule_id');

        if (!$employeeId || !$scheduleDate || !$startTime || !$endTime) {
            return response()->json(['conflicts' => []]);
        }

        $query = EmployeeShiftSchedule::where('employee_id', $employeeId)
            ->where('schedule_date', $scheduleDate)
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        $conflicts = $query->with('workShift')->get();

        return response()->json([
            'conflicts' => $conflicts->map(function($conflict) {
                return [
                    'id' => $conflict->id,
                    'work_shift_name' => $conflict->workShift->name,
                    'start_time' => $conflict->start_time,
                    'end_time' => $conflict->end_time
                ];
            })
        ]);
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
     * Build schedule grid for roster view
     */
    private function buildScheduleGrid($employees, $startDate, $endDate, $schedules)
    {
        $grid = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Group schedules by employee_id and date
        $schedulesByEmployeeAndDate = $schedules->groupBy(function($schedule) {
            return $schedule->employee_id . '_' . $schedule->schedule_date;
        });

        foreach ($employees as $employee) {
            $employeeSchedules = [];
            $current = $start->copy();
            
            while ($current->lte($end)) {
                $dateKey = $employee->id . '_' . $current->format('Y-m-d');
                $daySchedules = $schedulesByEmployeeAndDate->get($dateKey, collect());
                
                $employeeSchedules[$current->format('Y-m-d')] = [
                    'date' => $current->format('Y-m-d'),
                    'day_name' => $current->format('l'),
                    'schedules' => $daySchedules
                ];
                
                $current->addDay();
            }
            
            $grid[$employee->id] = [
                'employee' => $employee,
                'schedules' => $employeeSchedules
            ];
        }

        return $grid;
    }
}
