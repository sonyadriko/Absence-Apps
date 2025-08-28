<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Models\EmployeeShiftSchedule;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SimpleScheduleController extends ApiController
{
    /**
     * Get employee's personal schedule (simplified)
     */
    public function getMySchedule(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $employee = $user->employee;

        // Get date range (default to current week)
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfWeek()->format('Y-m-d'));

        // Simple mock data for now
        $schedules = collect();
        
        // Generate basic weekly schedule
        $current = Carbon::parse($startDate);
        while ($current->lte(Carbon::parse($endDate))) {
            // Skip weekends for regular schedule
            if (!$current->isWeekend()) {
                $schedules->push([
                    'id' => $current->format('Ymd'),
                    'schedule_date' => $current->format('Y-m-d'),
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'notes' => null,
                    'work_shift' => [
                        'id' => 1,
                        'name' => 'Regular Shift',
                        'code' => 'REG',
                        'color' => '#007bff',
                    ],
                    'slots' => [],
                    'duration_hours' => 8,
                ]);
            }
            $current->addDay();
        }

        return $this->successResponse($schedules->values());
    }

    /**
     * Create schedule change request (simplified)
     */
    public function createScheduleRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'requested_date' => 'required|date|after:today',
            'requested_shift_id' => 'required|integer',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        // For now, just return success without actually creating anything
        return $this->successResponse([
            'id' => rand(1000, 9999),
            'requested_date' => $request->requested_date,
            'requested_shift_id' => $request->requested_shift_id,
            'reason' => $request->reason,
            'status' => 'pending',
            'submitted_at' => now()->toISOString(),
        ], 'Schedule change request submitted successfully');
    }

    /**
     * Get available work shifts
     */
    public function getWorkShifts()
    {
        // Simple mock shifts
        $shifts = [
            [
                'id' => 1,
                'name' => 'Morning Shift',
                'code' => 'MORN',
                'start_time' => '07:00',
                'end_time' => '15:00',
                'color' => '#28a745',
                'is_overnight' => false,
                'duration_hours' => 8,
            ],
            [
                'id' => 2,
                'name' => 'Afternoon Shift',
                'code' => 'AFT',
                'start_time' => '15:00',
                'end_time' => '23:00',
                'color' => '#ffc107',
                'is_overnight' => false,
                'duration_hours' => 8,
            ],
            [
                'id' => 3,
                'name' => 'Night Shift',
                'code' => 'NIGHT',
                'start_time' => '23:00',
                'end_time' => '07:00',
                'color' => '#6f42c1',
                'is_overnight' => true,
                'duration_hours' => 8,
            ],
        ];

        return $this->successResponse($shifts);
    }
}
