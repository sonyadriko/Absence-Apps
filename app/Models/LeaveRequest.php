<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'document_path',
        'status',
        'pengelola_approved_by',
        'pengelola_approved_at',
        'pengelola_notes',
        'manager_approved_by',
        'manager_approved_at',
        'manager_notes',
        'hr_approved_by',
        'hr_approved_at',
        'hr_notes',
        'final_approved_by',
        'final_approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'time',
        'end_time' => 'time',
        'total_days' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $dates = [
        'deleted_at',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    const DURATION_FULL_DAY = 'full_day';
    const DURATION_HALF_DAY = 'half_day';
    const DURATION_HOURLY = 'hourly';

    /**
     * Get the employee who requested the leave
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave type
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the user who requested the leave
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the leave
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the leave
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get supporting document URL
     */
    public function getSupportingDocumentUrlAttribute()
    {
        if ($this->supporting_document_path) {
            return asset('storage/' . $this->supporting_document_path);
        }
        return null;
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for cancelled requests
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope for current year requests
     */
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('start_date', now()->year);
    }

    /**
     * Scope for specific year requests
     */
    public function scopeForYear($query, $year)
    {
        return $query->whereYear('start_date', $year);
    }

    // =============================================
    // APPROVAL TRACKING METHODS
    // =============================================

    /**
     * Get approval timeline for this leave request
     */
    public function getApprovalTimeline()
    {
        return [
            [
                'level' => 1,
                'role' => 'pengelola',
                'title' => 'Store Supervisor',
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
                'title' => 'Branch Manager',
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
                'title' => 'HR Central', 
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
        $employee = $this->employee;
        
        if (!$employee || !$employee->branch_id) {
            return null;
        }

        switch ($this->status) {
            case 'pending':
                return $this->findUserWithRoleForBranch('pengelola', $employee->branch_id);
                
            case 'approved_by_pengelola':
                return $this->findUserWithRoleForBranch('branch_manager', $employee->branch_id);
                
            case 'approved_by_manager':
                return $this->findUserWithRole('hr_central');
                
            case 'approved_by_hr':
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
     * Find user with specific role for branch
     */
    private function findUserWithRoleForBranch($roleName, $branchId)
    {
        $user = User::whereHas('userRoles.role', function($query) use ($roleName) {
            $query->where('name', $roleName);
        })->whereHas('employee', function($query) use ($branchId, $roleName) {
            if ($roleName === 'pengelola') {
                $query->whereHas('pengelolaBranchMaps', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            } elseif ($roleName === 'branch_manager') {
                $query->whereHas('managerBranchMaps', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }
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

    // =============================================
    // APPROVAL RELATIONSHIPS
    // =============================================

    /**
     * Pengelola who approved this request
     */
    public function pengelolaApprover()
    {
        return $this->belongsTo(User::class, 'pengelola_approved_by');
    }

    /**
     * Manager who approved this request
     */
    public function managerApprover()
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    /**
     * HR who approved this request
     */
    public function hrApprover()
    {
        return $this->belongsTo(User::class, 'hr_approved_by');
    }

    /**
     * Final approver
     */
    public function finalApprover()
    {
        return $this->belongsTo(User::class, 'final_approved_by');
    }
}
