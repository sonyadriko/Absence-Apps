<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\RBACService;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    protected $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->middleware('auth');
        $this->rbacService = $rbacService;
    }

    /**
     * Show approval center dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Check if user has any approval permissions
        $hasApprovalPermission = $this->rbacService->userHasPermission($user, 'leave.approve.level1') ||
                                $this->rbacService->userHasPermission($user, 'leave.approve.level2') ||
                                $this->rbacService->userHasPermission($user, 'leave.approve.final');
        
        if (!$hasApprovalPermission) {
            abort(403, 'You do not have permission to approve leave requests.');
        }

        return view('approvals.index');
    }

    /**
     * Get pending leave requests that need approval from current user
     */
    public function getPendingRequests(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found'
            ]);
        }

        // Determine what requests this user can approve based on their role and branch access
        $query = LeaveRequest::with(['employee.user', 'leaveType', 'employee.branch'])
            ->whereIn('status', ['pending', 'approved_by_pengelola', 'approved_by_manager']);

        // Filter based on user's approval level and branch access
        if ($this->rbacService->userHasPermission($user, 'leave.approve.final')) {
            // HR Central - can approve all requests at HR level
            $query->where('status', 'approved_by_manager');
        } elseif ($this->rbacService->userHasPermission($user, 'leave.approve.level2')) {
            // Branch Manager - can approve requests from their branches at manager level
            $branchIds = $this->getUserManagedBranches($employee->id);
            $query->where('status', 'approved_by_pengelola')
                  ->whereHas('employee', function($q) use ($branchIds) {
                      $q->whereIn('branch_id', $branchIds);
                  });
        } elseif ($this->rbacService->userHasPermission($user, 'leave.approve.level1')) {
            // Pengelola - can approve requests from their branches at pengelola level
            $branchIds = $this->getUserPengelolaBranches($employee->id);
            $query->where('status', 'pending')
                  ->whereHas('employee', function($q) use ($branchIds) {
                      $q->whereIn('branch_id', $branchIds);
                  });
        }

        // Apply filters
        if ($request->filled('type')) {
            $query->whereHas('leaveType', function($q) use ($request) {
                $q->where('code', $request->type);
            });
        }

        if ($request->filled('branch')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('branch_id', $request->branch);
            });
        }

        $requests = $query->orderBy('created_at', 'asc')
                          ->paginate($request->get('per_page', 15));

        // Transform the data
        $requests->getCollection()->transform(function ($request) {
            return [
                'id' => $request->id,
                'employee' => [
                    'id' => $request->employee->id,
                    'name' => $request->employee->user->name,
                    'employee_id' => $request->employee->employee_id ?? 'N/A',
                    'branch' => $request->employee->branch->name ?? 'Unknown Branch'
                ],
                'leave_type' => [
                    'name' => $request->leaveType->name,
                    'code' => $request->leaveType->code
                ],
                'start_date' => $request->start_date->format('Y-m-d'),
                'end_date' => $request->end_date->format('Y-m-d'),
                'total_days' => $request->total_days,
                'reason' => $request->reason,
                'document_path' => $request->document_path,
                'document_url' => $request->document_path ? asset('storage/' . $request->document_path) : null,
                'status' => $request->status,
                'submitted_at' => $request->created_at->format('Y-m-d H:i:s'),
                'approval_timeline' => $request->getApprovalTimeline(),
                'next_approver' => $request->getNextApprover(),
                'approval_progress' => $request->getApprovalProgress(),
                'current_step' => $this->getCurrentStepDescription($request->status),
                'days_pending' => $request->created_at->diffInDays(now()),
                'urgency' => $this->calculateUrgency($request)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $requests,
            'user_approval_level' => $this->getUserApprovalLevel($user)
        ]);
    }

    /**
     * Approve a leave request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        $user = auth()->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found'
            ]);
        }

        $leaveRequest = LeaveRequest::with(['employee.branch'])->find($id);
        
        if (!$leaveRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found'
            ]);
        }

        // Check if user can approve this request
        if (!$this->canApproveRequest($user, $leaveRequest)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve this request'
            ]);
        }

        try {
            DB::beginTransaction();

            $notes = $request->input('notes');
            $now = now();

            // Update based on current status and user permission
            if ($leaveRequest->status === 'pending' && $this->rbacService->userHasPermission($user, 'leave.approve.level1')) {
                // Pengelola approval
                $leaveRequest->update([
                    'status' => 'approved_by_pengelola',
                    'pengelola_approved_by' => $user->id,
                    'pengelola_approved_at' => $now,
                    'pengelola_notes' => $notes
                ]);
                $nextStep = 'Branch Manager approval';
            } elseif ($leaveRequest->status === 'approved_by_pengelola' && $this->rbacService->userHasPermission($user, 'leave.approve.level2')) {
                // Manager approval
                $leaveRequest->update([
                    'status' => 'approved_by_manager',
                    'manager_approved_by' => $user->id,
                    'manager_approved_at' => $now,
                    'manager_notes' => $notes
                ]);
                $nextStep = 'HR Central approval';
            } elseif ($leaveRequest->status === 'approved_by_manager' && $this->rbacService->userHasPermission($user, 'leave.approve.final')) {
                // HR final approval
                $leaveRequest->update([
                    'status' => 'approved',
                    'hr_approved_by' => $user->id,
                    'hr_approved_at' => $now,
                    'hr_notes' => $notes,
                    'final_approved_by' => $user->id,
                    'final_approved_at' => $now
                ]);
                $nextStep = 'Fully approved';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid approval state or insufficient permissions'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully',
                'data' => [
                    'next_step' => $nextStep,
                    'approved_by' => $user->name,
                    'approved_at' => $now->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve leave request: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reject a leave request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $user = auth()->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found'
            ]);
        }

        $leaveRequest = LeaveRequest::with(['employee.branch'])->find($id);
        
        if (!$leaveRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found'
            ]);
        }

        // Check if user can reject this request
        if (!$this->canApproveRequest($user, $leaveRequest)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reject this request'
            ]);
        }

        try {
            DB::beginTransaction();

            $leaveRequest->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $request->input('reason')
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected',
                'data' => [
                    'rejected_by' => $user->name,
                    'rejected_at' => now()->format('Y-m-d H:i:s'),
                    'reason' => $request->input('reason')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject leave request: ' . $e->getMessage()
            ]);
        }
    }

    // Helper methods
    private function canApproveRequest($user, $leaveRequest)
    {
        $employee = $user->employee;
        if (!$employee) return false;

        // HR Central can approve manager-approved requests
        if ($leaveRequest->status === 'approved_by_manager' && 
            $this->rbacService->userHasPermission($user, 'leave.approve.final')) {
            return true;
        }

        // Branch Manager can approve pengelola-approved requests from their branches
        if ($leaveRequest->status === 'approved_by_pengelola' && 
            $this->rbacService->userHasPermission($user, 'leave.approve.level2')) {
            $managedBranches = $this->getUserManagedBranches($employee->id);
            return in_array($leaveRequest->employee->branch_id, $managedBranches);
        }

        // Pengelola can approve pending requests from their branches
        if ($leaveRequest->status === 'pending' && 
            $this->rbacService->userHasPermission($user, 'leave.approve.level1')) {
            $pengelolaBranches = $this->getUserPengelolaBranches($employee->id);
            return in_array($leaveRequest->employee->branch_id, $pengelolaBranches);
        }

        return false;
    }

    private function getUserManagedBranches($employeeId)
    {
        return DB::table('manager_branch_maps')
            ->where('employee_id', $employeeId)
            ->pluck('branch_id')
            ->toArray();
    }

    private function getUserPengelolaBranches($employeeId)
    {
        return DB::table('pengelola_branch_maps')
            ->where('employee_id', $employeeId)
            ->pluck('branch_id')
            ->toArray();
    }

    private function getUserApprovalLevel($user)
    {
        if ($this->rbacService->userHasPermission($user, 'leave.approve.final')) {
            return 'hr_central';
        } elseif ($this->rbacService->userHasPermission($user, 'leave.approve.level2')) {
            return 'branch_manager';
        } elseif ($this->rbacService->userHasPermission($user, 'leave.approve.level1')) {
            return 'pengelola';
        }
        return 'none';
    }

    private function getCurrentStepDescription($status)
    {
        $stepMap = [
            'pending' => 'Waiting for Store Supervisor approval',
            'approved_by_pengelola' => 'Waiting for Branch Manager approval',
            'approved_by_manager' => 'Waiting for HR Central approval',
            'approved_by_hr' => 'Finalizing approval',
            'approved' => 'Fully approved',
            'rejected' => 'Request rejected',
            'cancelled' => 'Request cancelled'
        ];

        return $stepMap[$status] ?? 'Unknown status';
    }

    private function calculateUrgency($request)
    {
        $daysPending = $request->created_at->diffInDays(now());
        $daysToLeave = now()->diffInDays($request->start_date, false);
        
        if ($daysPending > 7) return 'high';  // Pending too long
        if ($daysToLeave <= 3 && $daysToLeave >= 0) return 'high';  // Leave starts soon
        if ($daysPending > 3) return 'medium';
        return 'low';
    }
}
