<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CorrectionsController extends ApiController
{
    /**
     * Get employee's correction requests
     */
    public function getMyCorrections(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        // Mock data for now
        $corrections = [
            [
                'id' => 1,
                'date' => '2024-01-15',
                'correction_type' => 'check_in_time',
                'original_value' => '09:15',
                'corrected_value' => '09:00',
                'reason' => 'Clock in system was delayed',
                'status' => 'pending',
                'submitted_at' => '2024-01-16T10:30:00Z',
                'reviewed_at' => null,
                'reviewer_notes' => null
            ],
            [
                'id' => 2,
                'date' => '2024-01-10',
                'correction_type' => 'forgot_checkout',
                'original_value' => null,
                'corrected_value' => '17:00',
                'reason' => 'Forgot to check out, left office at 5 PM',
                'status' => 'approved',
                'submitted_at' => '2024-01-11T08:00:00Z',
                'reviewed_at' => '2024-01-11T09:15:00Z',
                'reviewer_notes' => 'Approved based on work schedule'
            ]
        ];

        // Apply filters
        if ($request->filled('status')) {
            $corrections = array_filter($corrections, function($correction) use ($request) {
                return $correction['status'] === $request->status;
            });
        }

        return $this->successResponse(array_values($corrections));
    }

    /**
     * Submit new correction request
     */
    public function submitCorrection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|before_or_equal:today',
            'correction_type' => 'required|in:check_in_time,check_out_time,forgot_checkin,forgot_checkout,wrong_location',
            'corrected_value' => 'nullable|string|max:255',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        // For now, just return success
        return $this->successResponse([
            'id' => rand(1000, 9999),
            'date' => $request->date,
            'correction_type' => $request->correction_type,
            'corrected_value' => $request->corrected_value,
            'reason' => $request->reason,
            'status' => 'pending',
            'submitted_at' => now()->toISOString(),
        ], 'Correction request submitted successfully');
    }

    /**
     * Get correction request details
     */
    public function getCorrectionDetails($id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        // Mock detail data
        $correction = [
            'id' => $id,
            'date' => '2024-01-15',
            'correction_type' => 'check_in_time',
            'original_value' => '09:15',
            'corrected_value' => '09:00',
            'reason' => 'Clock in system was delayed',
            'status' => 'pending',
            'submitted_at' => '2024-01-16T10:30:00Z',
            'reviewed_at' => null,
            'reviewer_notes' => null,
            'supporting_documents' => []
        ];

        return $this->successResponse($correction);
    }

    /**
     * Update correction request
     */
    public function updateCorrection(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'corrected_value' => 'nullable|string|max:255',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        return $this->successResponse(null, 'Correction request updated successfully');
    }

    /**
     * Cancel correction request
     */
    public function cancelCorrection($id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user->employee) {
            return $this->forbiddenResponse('Employee profile not found');
        }

        return $this->successResponse(null, 'Correction request cancelled successfully');
    }
}
