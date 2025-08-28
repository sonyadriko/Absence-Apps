<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\RBACService;

class LeaveRequestEnhanced extends Model
{
    /**
     * Get approval timeline for this leave request
     */
    public function getApprovalTimeline()
    {
        return [
            [
                'level' => 1,
                'role' => 'pengelola',
                'title' => 'Pengelola Approval',
                'status' => $this->getPengelolaStatus(),
                'approved_by' => $this->pengelola_approved_by ? $this->pengelolaApprover->name ?? 'Unknown' : null,
                'approved_at' => $this->pengelola_approved_at,
                'notes' => $this->pengelola_notes,
                'is_current' => $this->isCurrentApprovalLevel(1),
                'icon' => 'fa-store',
                'color' => $this->getPengelolaStatus() === 'approved' ? 'success' : ($this->getPengelolaStatus() === 'pending' ? 'warning' : 'muted')
            ],
            [
                'level' => 2,
                'role' => 'branch_manager', 
                'title' => 'Branch Manager Approval',
                'status' => $this->getManagerStatus(),
                'approved_by' => $this->manager_approved_by ? $this->managerApprover->name ?? 'Unknown' : null,
                'approved_at' => $this->manager_approved_at,
                'notes' => $this->manager_notes,
                'is_current' => $this->isCurrentApprovalLevel(2),
                'icon' => 'fa-building',
                'color' => $this->getManagerStatus() === 'approved' ? 'success' : ($this->getManagerStatus() === 'pending' ? 'warning' : 'muted')
            ],
            [
                'level' => 3,
                'role' => 'hr_central',
                'title' => 'HR Central Approval', 
                'status' => $this->getHRStatus(),
                'approved_by' => $this->hr_approved_by ? $this->hrApprover->name ?? 'Unknown' : null,
                'approved_at' => $this->hr_approved_at,
                'notes' => $this->hr_notes,
                'is_current' => $this->isCurrentApprovalLevel(3),
                'icon' => 'fa-users',
                'color' => $this->getHRStatus() === 'approved' ? 'success' : ($this->getHRStatus() === 'pending' ? 'warning' : 'muted')
            ],
            [
                'level' => 4,
                'role' => 'final',
                'title' => 'Final Approval',
                'status' => $this->getFinalStatus(),
                'approved_by' => $this->final_approved_by ? $this->finalApprover->name ?? 'System' : null,
                'approved_at' => $this->final_approved_at,
                'notes' => null,
                'is_current' => $this->isCurrentApprovalLevel(4),
                'icon' => 'fa-check-circle',
                'color' => $this->getFinalStatus() === 'approved' ? 'success' : 'muted'
            ]
        ];
    }

    /**
     * Get next approver info
     */
    public function getNextApprover()
    {
        $rbacService = app(RBACService::class);
        $employee = $this->employee;
        
        if (!$employee || !$employee->branch) {
            return null;
        }

        switch ($this->status) {
            case 'pending':
                // Find pengelola for this branch
                return $this->findUserWithRoleForBranch('pengelola', $employee->branch_id);
                
            case 'approved_by_pengelola':
                // Find branch manager for this branch
                return $this->findUserWithRoleForBranch('branch_manager', $employee->branch_id);
                
            case 'approved_by_manager':
                // Find HR Central (global)
                return $this->findUserWithRole('hr_central');
                
            case 'approved_by_hr':
                // System auto-approval or HR final
                return [
                    'name' => 'System Auto-Approval',
                    'role' => 'system',
                    'action_needed' => false
                ];
                
            default:
                return null;
        }
    }

    /**
     * Find user with specific role for branch
     */
    private function findUserWithRoleForBranch($roleName, $branchId)
    {
        $rbacService = app(RBACService::class);
        
        // Get users with the required role who have access to this branch
        $users = User::whereHas('userRoles.role', function($query) use ($roleName) {
            $query->where('name', $roleName);
        })->whereHas('employee', function($query) use ($branchId, $roleName) {
            if ($roleName === 'pengelola') {
                // Pengelola assigned to this branch
                $query->whereHas('pengelolaBranchMaps', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            } elseif ($roleName === 'branch_manager') {
                // Manager assigned to this branch  
                $query->whereHas('managerBranchMaps', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }
        })->first();

        if ($users) {
            return [
                'id' => $users->id,
                'name' => $users->name,
                'role' => $roleName,
                'action_needed' => true
            ];
        }

        return [
            'name' => 'No ' . ucfirst(str_replace('_', ' ', $roleName)) . ' assigned',
            'role' => $roleName,
            'action_needed' => false,
            'error' => true
        ];
    }

    /**
     * Find user with specific role (global)
     */
    private function findUserWithRole($roleName)
    {
        $user = User::whereHas('userRoles.role', function($query) use ($roleName) {
            $query->where('name', $roleName);
        })->first();

        if ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $roleName,
                'action_needed' => true
            ];
        }

        return [
            'name' => 'No ' . ucfirst(str_replace('_', ' ', $roleName)) . ' available',
            'role' => $roleName,
            'action_needed' => false,
            'error' => true
        ];
    }

    /**
     * Check if given level is current approval level
     */
    public function isCurrentApprovalLevel($level)
    {
        switch ($level) {
            case 1:
                return $this->status === 'pending';
            case 2:
                return $this->status === 'approved_by_pengelola';
            case 3:
                return $this->status === 'approved_by_manager';
            case 4:
                return $this->status === 'approved_by_hr';
            default:
                return false;
        }
    }

    /**
     * Get pengelola approval status
     */
    public function getPengelolaStatus()
    {
        if ($this->pengelola_approved_by) {
            return 'approved';
        }
        return in_array($this->status, ['pending']) ? 'pending' : 'not_reached';
    }

    /**
     * Get manager approval status
     */
    public function getManagerStatus()
    {
        if ($this->manager_approved_by) {
            return 'approved';
        }
        return in_array($this->status, ['approved_by_pengelola']) ? 'pending' : 'not_reached';
    }

    /**
     * Get HR approval status
     */
    public function getHRStatus()
    {
        if ($this->hr_approved_by) {
            return 'approved';
        }
        return in_array($this->status, ['approved_by_manager']) ? 'pending' : 'not_reached';
    }

    /**
     * Get final approval status
     */
    public function getFinalStatus()
    {
        if ($this->final_approved_by || $this->status === 'approved') {
            return 'approved';
        }
        return in_array($this->status, ['approved_by_hr']) ? 'pending' : 'not_reached';
    }

    /**
     * Get approval progress percentage
     */
    public function getApprovalProgress()
    {
        switch ($this->status) {
            case 'pending':
                return 25;
            case 'approved_by_pengelola':
                return 50;
            case 'approved_by_manager':
                return 75;
            case 'approved_by_hr':
            case 'approved':
                return 100;
            case 'rejected':
                return 0;
            default:
                return 0;
        }
    }

    /**
     * Relationships for approvers
     */
    public function pengelolaApprover()
    {
        return $this->belongsTo(User::class, 'pengelola_approved_by');
    }

    public function managerApprover()
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    public function hrApprover()
    {
        return $this->belongsTo(User::class, 'hr_approved_by');
    }

    public function finalApprover()
    {
        return $this->belongsTo(User::class, 'final_approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
