<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class AttendanceCorrectionController extends ApiController
{
    /**
     * Get missing checkouts for current user
     */
    public function getMissingCheckouts()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        // Find attendance records with check-in but no check-out
        // Exclude today to avoid confusion with ongoing attendance
        $missingCheckouts = Attendance::where('employee_id', $user->employee->id)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->where('date', '<', Carbon::today())
            ->orderBy('date', 'desc')
            ->limit(30) // Last 30 days
            ->get();

        $data = $missingCheckouts->map(function($attendance) {
            return [
                'id' => $attendance->id,
                'date' => $attendance->date,
                'check_in_time' => Carbon::parse($attendance->check_in)->format('H:i:s'),
                'branch' => $attendance->branch ? $attendance->branch->name : 'Unknown',
                'notes' => $attendance->notes,
            ];
        });

        return $this->successResponse($data);
    }

    /**
     * Submit correction for missing checkout
     */
    public function submitMissingCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendance_id' => 'required|exists:attendances,id',
            'check_out_time' => 'required|date_format:H:i',
            'reason' => 'required|string|max:500',
            'actual_date' => 'nullable|date', // For overtime past midnight
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

            $attendance = Attendance::where('id', $request->attendance_id)
                ->where('employee_id', $user->employee->id)
                ->first();

            if (!$attendance) {
                return $this->forbiddenResponse('Attendance record not found');
            }

            if ($attendance->check_out) {
                return $this->errorResponse('Checkout already exists for this record', 400);
            }

            // Determine checkout date and time
            $checkInDate = Carbon::parse($attendance->date);
            $checkOutTime = $request->check_out_time;
            
            // Handle overtime past midnight
            if ($request->actual_date) {
                $actualDate = Carbon::parse($request->actual_date);
                if ($actualDate->gt($checkInDate)) {
                    // Checkout happened on next day
                    $checkOutDateTime = $actualDate->setTimeFromTimeString($checkOutTime);
                } else {
                    $checkOutDateTime = $checkInDate->setTimeFromTimeString($checkOutTime);
                }
            } else {
                $checkOutDateTime = $checkInDate->setTimeFromTimeString($checkOutTime);
            }

            // Validate checkout time is after check-in
            $checkInDateTime = Carbon::parse($attendance->check_in);
            if ($checkOutDateTime->lte($checkInDateTime)) {
                return $this->errorResponse('Checkout time must be after check-in time', 400);
            }

            // Calculate work minutes
            $workMinutes = $checkInDateTime->diffInMinutes($checkOutDateTime);

            // Create correction record
            $correction = AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'employee_id' => $user->employee->id,
                'correction_type' => 'missing_checkout',
                'original_value' => null,
                'corrected_value' => $checkOutDateTime->toISOString(),
                'reason' => $request->reason,
                'status' => 'pending',
                'submitted_at' => Carbon::now(),
            ]);

            // Auto-approve for now (can be changed to require manager approval)
            $correction->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => Carbon::now(),
                'approval_notes' => 'Auto-approved - Missing checkout correction'
            ]);

            // Update attendance record
            $attendance->update([
                'check_out' => $checkOutDateTime,
                'actual_check_out' => $checkOutDateTime,
                'total_work_minutes' => $workMinutes,
                'has_corrections' => true,
                'correction_history' => json_encode([
                    'missing_checkout' => [
                        'corrected_at' => Carbon::now()->toISOString(),
                        'reason' => $request->reason,
                        'approved' => true
                    ]
                ])
            ]);

            // Add note about correction
            $attendance->notes = ($attendance->notes ? $attendance->notes . ' | ' : '') . 
                'Checkout correction: ' . $request->reason;
            $attendance->save();

            DB::commit();

            return $this->successResponse([
                'message' => 'Missing checkout corrected successfully',
                'attendance_id' => $attendance->id,
                'check_out_time' => $checkOutDateTime->format('H:i:s'),
                'work_hours' => round($workMinutes / 60, 2)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to process correction: ' . $e->getMessage());
        }
    }

    /**
     * Submit late checkout (for overtime past midnight)
     */
    public function submitLateCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes' => 'required|string|max:500', // Reason for late checkout
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

            // Find yesterday's attendance with no checkout
            $yesterday = Carbon::yesterday();
            $attendance = Attendance::where('employee_id', $user->employee->id)
                ->where('date', $yesterday->format('Y-m-d'))
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->first();

            if (!$attendance) {
                return $this->errorResponse('No pending checkout found from yesterday', 400);
            }

            $eventTime = Carbon::now();
            
            // Calculate work minutes (from yesterday's check-in to now)
            $checkInTime = Carbon::parse($attendance->check_in);
            $workMinutes = $checkInTime->diffInMinutes($eventTime);

            // Update location data
            $locationData = json_decode($attendance->location_data, true) ?? [];
            $locationData['check_out'] = [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'time' => $eventTime->toISOString(),
                'late_checkout' => true
            ];

            // Update attendance
            $attendance->update([
                'check_out' => $eventTime,
                'actual_check_out' => $eventTime,
                'total_work_minutes' => $workMinutes,
                'overtime_minutes' => max(0, $workMinutes - 480), // Assuming 8 hours regular time
                'location_data' => json_encode($locationData),
                'has_corrections' => true,
                'notes' => $attendance->notes . ' | Late checkout (past midnight): ' . $request->notes
            ]);

            DB::commit();

            return $this->successResponse([
                'message' => 'Late checkout processed successfully',
                'attendance_date' => $yesterday->format('Y-m-d'),
                'check_in_time' => Carbon::parse($attendance->check_in)->format('H:i:s'),
                'check_out_time' => $eventTime->format('H:i:s'),
                'total_hours' => round($workMinutes / 60, 2),
                'overtime_hours' => round(max(0, $workMinutes - 480) / 60, 2)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to process late checkout: ' . $e->getMessage());
        }
    }

    /**
     * Get correction history
     */
    public function getCorrectionHistory(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        $corrections = AttendanceCorrection::where('employee_id', $user->employee->id)
            ->with('attendance')
            ->orderBy('submitted_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->successResponse($corrections);
    }
}
