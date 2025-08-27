<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEvent;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\AuditLog;
use App\Services\RBACService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CorrectionsController extends Controller
{
    protected $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
    }

    /**
     * Show corrections index for employees (their own requests)
     */
    public function employeeIndex(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee profile not found.');
        }

        // For now, return placeholder view since we don't have corrections table yet
        // In full implementation, this would show employee's correction requests
        return view('employee.corrections.index', [
            'employee' => $employee,
            'corrections' => collect([]), // Placeholder empty collection
            'stats' => [
                'total_requests' => 0,
                'pending_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0
            ]
        ]);
    }

    /**
     * Show form to create attendance correction request
     */
    public function create(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee profile not found.');
        }

        // Get attendance event to correct (if specified)
        $eventId = $request->get('event_id');
        $attendanceEvent = null;
        
        if ($eventId) {
            $attendanceEvent = AttendanceEvent::where('id', $eventId)
                ->where('employee_id', $employee->id)
                ->first();
                
            if (!$attendanceEvent) {
                return redirect()->back()->with('error', 'Attendance event not found or unauthorized.');
            }
        }

        // Get recent attendance events for reference
        $recentEvents = AttendanceEvent::where('employee_id', $employee->id)
            ->whereDate('event_time', '>=', Carbon::now()->subDays(30))
            ->with('schedule.workShift')
            ->orderBy('event_time', 'desc')
            ->limit(20)
            ->get();

        return view('employee.corrections.create', compact(
            'employee',
            'attendanceEvent', 
            'recentEvents'
        ));
    }

    /**
     * Store correction request (placeholder implementation)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correction_type' => 'required|in:missing_checkin,missing_checkout,wrong_time,system_error',
            'event_date' => 'required|date|before_or_equal:today|after:' . Carbon::now()->subDays(30)->format('Y-m-d'),
            'event_time' => 'required|date_format:H:i',
            'event_type' => 'required|in:check_in,check_out,break_start,break_end',
            'reason' => 'required|string|min:10|max:500',
            'supporting_documents' => 'nullable|array',
            'supporting_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee profile not found.');
        }

        try {
            DB::beginTransaction();

            // Store supporting documents if any
            $documentPaths = [];
            if ($request->hasFile('supporting_documents')) {
                foreach ($request->file('supporting_documents') as $file) {
                    $path = $file->store('corrections/documents', 'private');
                    $documentPaths[] = $path;
                }
            }

            // Log the correction request creation
            AuditLog::create([
                'user_id' => Auth::id(),
                'employee_id' => $employee->id,
                'action' => 'correction_request_created',
                'model_type' => 'AttendanceCorrection',
                'model_id' => null, // Would be correction ID in full implementation
                'old_values' => null,
                'new_values' => json_encode([
                    'correction_type' => $request->correction_type,
                    'event_date' => $request->event_date,
                    'event_time' => $request->event_time,
                    'event_type' => $request->event_type,
                    'reason' => $request->reason,
                    'supporting_documents' => $documentPaths
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            DB::commit();

            return redirect()->route('employee.corrections.index')
                ->with('success', 'Correction request submitted successfully! It will be reviewed by your supervisor.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded files if any
            foreach ($documentPaths as $path) {
                \Storage::delete($path);
            }

            return redirect()->back()
                ->with('error', 'Failed to submit correction request. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show corrections management for supervisors/managers
     */
    public function managementIndex(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.all')) {
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

        // Get filter parameters
        $status = $request->get('status', 'pending');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Placeholder data - in full implementation, this would query corrections table
        $corrections = collect([]); // Empty collection for now
        $stats = [
            'total_pending' => 0,
            'total_approved' => 0,
            'total_rejected' => 0,
            'avg_approval_time_hours' => 0
        ];

        return view('management.corrections.index', compact(
            'branches',
            'selectedBranch',
            'corrections',
            'stats',
            'status',
            'startDate',
            'endDate',
            'primaryRole'
        ));
    }

    /**
     * Show correction request details for approval
     */
    public function show($id)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.all')) {
            abort(403, 'Unauthorized access');
        }

        // Placeholder - in full implementation, find correction by ID
        return view('management.corrections.show', [
            'correction' => null, // Would be actual correction model
            'timeline' => [], // Approval timeline
            'relatedEvents' => [] // Related attendance events
        ]);
    }

    /**
     * Approve or reject correction request
     */
    public function updateStatus(Request $request, $id)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.all')) {
            abort(403, 'Unauthorized access');
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject,request_info',
            'comments' => 'required|string|min:5|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Log the approval/rejection action
            AuditLog::create([
                'user_id' => Auth::id(),
                'employee_id' => null, // Would get from correction model
                'action' => 'correction_' . $request->action,
                'model_type' => 'AttendanceCorrection',
                'model_id' => $id,
                'old_values' => json_encode(['status' => 'pending']),
                'new_values' => json_encode([
                    'status' => $request->action === 'approve' ? 'approved' : 
                              ($request->action === 'reject' ? 'rejected' : 'info_requested'),
                    'comments' => $request->comments,
                    'processed_by' => Auth::id(),
                    'processed_at' => Carbon::now()
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // If approved, apply the correction to actual attendance data
            if ($request->action === 'approve') {
                $this->applyCorrection($id);
            }

            DB::commit();

            $message = match($request->action) {
                'approve' => 'Correction request approved successfully!',
                'reject' => 'Correction request rejected.',
                'request_info' => 'Additional information requested from employee.'
            };

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process correction request. Please try again.'
            ], 500);
        }
    }

    /**
     * Bulk approve/reject corrections
     */
    public function bulkUpdateStatus(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.all')) {
            abort(403, 'Unauthorized access');
        }

        $validator = Validator::make($request->all(), [
            'correction_ids' => 'required|array|min:1',
            'correction_ids.*' => 'integer',
            'action' => 'required|in:approve,reject',
            'comments' => 'required|string|min:5|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $processedCount = 0;
            
            foreach ($request->correction_ids as $correctionId) {
                // Log bulk action
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'employee_id' => null,
                    'action' => 'correction_bulk_' . $request->action,
                    'model_type' => 'AttendanceCorrection',
                    'model_id' => $correctionId,
                    'old_values' => json_encode(['status' => 'pending']),
                    'new_values' => json_encode([
                        'status' => $request->action === 'approve' ? 'approved' : 'rejected',
                        'comments' => $request->comments,
                        'processed_by' => Auth::id(),
                        'processed_at' => Carbon::now()
                    ]),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                if ($request->action === 'approve') {
                    $this->applyCorrection($correctionId);
                }

                $processedCount++;
            }

            DB::commit();

            $action = $request->action === 'approve' ? 'approved' : 'rejected';
            return response()->json([
                'success' => true,
                'message' => "{$processedCount} correction requests {$action} successfully!"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process correction requests. Please try again.'
            ], 500);
        }
    }

    /**
     * Get correction statistics for dashboard
     */
    public function getStats(Request $request)
    {
        // Check permissions
        if (!$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.branch') &&
            !$this->rbacService->userHasPermission(Auth::user(), 'correction.approve.all')) {
            abort(403, 'Unauthorized access');
        }

        $user = Auth::user();
        $branches = $this->getAccessibleBranches($user);
        $selectedBranchId = $request->get('branch_id', $branches->first()?->id);

        // Placeholder stats - in full implementation, would calculate from corrections table
        $stats = [
            'pending_corrections' => 0,
            'corrections_this_week' => 0,
            'avg_approval_time_hours' => 0,
            'most_common_correction_type' => 'missing_checkout',
            'corrections_by_type' => [
                'missing_checkin' => 0,
                'missing_checkout' => 0,
                'wrong_time' => 0,
                'system_error' => 0
            ],
            'corrections_by_status' => [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'info_requested' => 0
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Apply approved correction to attendance data
     */
    private function applyCorrection($correctionId)
    {
        // Placeholder implementation
        // In full version, this would:
        // 1. Get correction details from corrections table
        // 2. Create/update/delete attendance events based on correction type
        // 3. Recalculate daily attendance status
        // 4. Update audit logs
        
        // For now, just log that correction would be applied
        AuditLog::create([
            'user_id' => Auth::id(),
            'employee_id' => null,
            'action' => 'correction_applied',
            'model_type' => 'AttendanceEvent',
            'model_id' => null,
            'old_values' => null,
            'new_values' => json_encode(['correction_id' => $correctionId]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
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
}
