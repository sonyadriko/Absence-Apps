<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use Carbon\Carbon;

class LeaveController extends ApiController
{
    /**
     * Get leave requests for authenticated employee
     */
    public function getLeaveRequests(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->unauthorizedResponse('Employee not found');
        }

        $query = LeaveRequest::with(['leaveType', 'employee.user'])
            ->where('employee_id', $user->employee->id);

        // Apply filters
        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->whereHas('leaveType', function($q) use ($request) {
                $q->where('code', $request->type);
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $leaves = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform the data for frontend compatibility
        $leaves->getCollection()->transform(function ($leave) {
            return [
                'id' => $leave->id,
                'leave_type' => $leave->leaveType->code,
                'duration_type' => 'full_day', // Default since not in current schema
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'start_time' => null,
                'end_time' => null,
                'total_days' => $leave->total_days,
                'reason' => $leave->reason,
                'status' => $this->mapStatus($leave->status),
                'supporting_document' => $leave->document_path,
                'supporting_document_url' => $leave->document_path ? asset('storage/' . $leave->document_path) : null,
                'emergency_contact' => null, // Not in current schema
                'created_at' => $leave->created_at->toISOString(),
                'approved_by' => $leave->final_approved_by,
                'approved_at' => $leave->final_approved_at ? $leave->final_approved_at->toISOString() : null,
                'rejected_by' => $leave->rejected_by,
                'rejected_at' => $leave->rejected_at ? $leave->rejected_at->toISOString() : null,
                'rejection_reason' => $leave->rejection_reason,
                'approver' => $leave->final_approved_by ? ['name' => 'Manager'] : null,
                'rejector' => $leave->rejected_by ? ['name' => 'Manager'] : null,
            ];
        });

        return $this->successResponse($leaves);
    }

    /**
     * Get leave balance for authenticated employee
     */
    public function getLeaveBalance(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->unauthorizedResponse('Employee not found');
        }

        $year = $request->get('year', date('Y'));

        // Get or create leave balances for current year
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $balances = [];

        foreach ($leaveTypes as $leaveType) {
            $balance = LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $user->employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year
                ],
                [
                    'allocated_days' => $leaveType->max_days_per_year ?? 0,
                    'used_days' => 0,
                    'carry_forward_days' => 0,
                    'remaining_days' => $leaveType->max_days_per_year ?? 0
                ]
            );

            // Calculate used days for this year
            $usedDays = LeaveRequest::where('employee_id', $user->employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->whereYear('start_date', $year)
                ->whereIn('status', ['approved', 'approved_by_hr', 'approved_by_manager'])
                ->sum('total_days');

            // Update balance
            $balance->used_days = $usedDays;
            $balance->remaining_days = $balance->allocated_days + $balance->carry_forward_days - $usedDays;
            $balance->save();

            $balances[$leaveType->code] = $balance->remaining_days;
        }

        // Return balance data for frontend compatibility
        return $this->successResponse([
            'annual' => $balances['annual'] ?? 0,
            'sick' => $balances['sick'] ?? 0,
            'personal' => $balances['personal'] ?? 0,
            'maternity' => $balances['maternity'] ?? 0,
            'paternity' => $balances['paternity'] ?? 0,
            'emergency' => $balances['emergency'] ?? 0,
            'total_used' => array_sum(array_map(fn($b) => max(0, ($leaveTypes->where('code', array_search($b, $balances))->first()->max_days_per_year ?? 0) - $b), $balances)),
            'year' => $year
        ]);
    }

    /**
     * Create new leave request
     */
    public function createLeaveRequest(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->unauthorizedResponse('Employee not found');
        }

        $validator = Validator::make($request->all(), [
            'leave_type' => 'required|exists:leave_types,code',
            'duration_type' => 'required|in:full_day,half_day,hourly',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'emergency_contact' => 'nullable|string|max:255',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120' // 5MB
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Get leave type
            $leaveType = LeaveType::where('code', $request->leave_type)->first();
            
            // Calculate total days
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $totalDays = $startDate->diffInDays($endDate) + 1;

            if ($request->duration_type === 'half_day') {
                $totalDays = $totalDays * 0.5;
            } elseif ($request->duration_type === 'hourly' && $request->start_time && $request->end_time) {
                $startTime = Carbon::parse($request->start_time);
                $endTime = Carbon::parse($request->end_time);
                $hours = $startTime->diffInHours($endTime);
                $totalDays = $hours / 8; // Assuming 8 hours = 1 day
            }

            // Check leave balance
            $balance = LeaveBalance::where('employee_id', $user->employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $startDate->year)
                ->first();

            if ($balance && $balance->remaining_days < $totalDays) {
                return $this->errorResponse('Insufficient leave balance. Available: ' . $balance->remaining_days . ' days');
            }

            // Handle file upload
            $documentPath = null;
            if ($request->hasFile('supporting_document')) {
                $file = $request->file('supporting_document');
                $filename = time() . '_' . $file->getClientOriginalName();
                $documentPath = $file->storeAs('leave_documents', $filename, 'public');
            }

            // Create leave request
            $leaveRequest = LeaveRequest::create([
                'employee_id' => $user->employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'reason' => $request->reason,
                'document_path' => $documentPath,
                'status' => 'pending'
            ]);

            DB::commit();

            return $this->successResponse([
                'id' => $leaveRequest->id,
                'leave_type' => $leaveType->code,
                'duration_type' => $request->duration_type,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $totalDays,
                'reason' => $request->reason,
                'status' => 'pending',
                'created_at' => $leaveRequest->created_at->toISOString()
            ], 'Leave request submitted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to submit leave request: ' . $e->getMessage());
        }
    }

    /**
     * Get specific leave request details
     */
    public function getLeaveRequest($id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->unauthorizedResponse('Employee not found');
        }

        $leave = LeaveRequest::with(['leaveType', 'employee.user'])
            ->where('employee_id', $user->employee->id)
            ->where('id', $id)
            ->first();

        if (!$leave) {
            return $this->notFoundResponse('Leave request not found');
        }

        return $this->successResponse([
            'id' => $leave->id,
            'leave_type' => $leave->leaveType->code,
            'duration_type' => 'full_day',
            'start_date' => $leave->start_date->format('Y-m-d'),
            'end_date' => $leave->end_date->format('Y-m-d'),
            'start_time' => null,
            'end_time' => null,
            'total_days' => $leave->total_days,
            'reason' => $leave->reason,
            'status' => $this->mapStatus($leave->status),
            'supporting_document' => $leave->document_path,
            'supporting_document_url' => $leave->document_path ? asset('storage/' . $leave->document_path) : null,
            'emergency_contact' => null,
            'created_at' => $leave->created_at->toISOString(),
            'approved_by' => $leave->final_approved_by,
            'approved_at' => $leave->final_approved_at ? $leave->final_approved_at->toISOString() : null,
            'rejected_by' => $leave->rejected_by,
            'rejected_at' => $leave->rejected_at ? $leave->rejected_at->toISOString() : null,
            'rejection_reason' => $leave->rejection_reason,
            'approver' => $leave->final_approved_by ? ['name' => 'Manager'] : null,
            'rejector' => $leave->rejected_by ? ['name' => 'Manager'] : null,
        ]);
    }

    /**
     * Cancel leave request
     */
    public function cancelLeaveRequest($id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->unauthorizedResponse('Employee not found');
        }

        $leave = LeaveRequest::where('employee_id', $user->employee->id)
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$leave) {
            return $this->notFoundResponse('Leave request not found or cannot be cancelled');
        }

        try {
            DB::beginTransaction();
            
            $leave->update([
                'status' => 'cancelled',
                'rejection_reason' => 'Cancelled by employee'
            ]);

            DB::commit();

            return $this->successResponse(null, 'Leave request cancelled successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to cancel leave request');
        }
    }

    /**
     * Map database status to frontend status
     */
    private function mapStatus($status)
    {
        $statusMap = [
            'pending' => 'pending',
            'approved_by_pengelola' => 'pending',
            'approved_by_manager' => 'pending',
            'approved_by_hr' => 'pending',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled'
        ];

        return $statusMap[$status] ?? $status;
    }
}
