<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'preferences'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'preferences' => 'array'
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isHR()
    {
        return $this->role === 'hr';
    }

    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    // New flexible system roles
    public function isHRCentral()
    {
        return $this->role === 'hr_central';
    }

    public function isBranchManager()
    {
        return $this->role === 'branch_manager';
    }

    public function isPengelola()
    {
        return $this->role === 'pengelola';
    }

    // Role mappings for branch access
    public function managerBranchMaps()
    {
        return $this->hasMany(ManagerBranchMap::class);
    }

    public function pengelolaBranchMaps()
    {
        return $this->hasMany(PengelolaBranchMap::class);
    }

    // RBAC relationships
    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot('scope_type', 'scope_id', 'scope_data', 'effective_from', 'effective_until', 'is_active')
                    ->withTimestamps();
    }

    // Audit relationships
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function createdSchedules()
    {
        return $this->hasMany(EmployeeShiftSchedule::class, 'created_by');
    }

    // Helper methods for branch access
    public function getAccessibleBranches()
    {
        if ($this->isHRCentral()) {
            // HR Central can access all branches
            return Branch::where('is_active', true)->get();
        }

        if ($this->isBranchManager()) {
            // Branch Manager can access mapped branches
            return Branch::whereIn('id', 
                $this->managerBranchMaps()
                    ->where('is_active', true)
                    ->whereDate('effective_from', '<=', now())
                    ->where(function($query) {
                        $query->whereNull('effective_until')
                              ->orWhereDate('effective_until', '>=', now());
                    })
                    ->pluck('branch_id')
            )->get();
        }

        if ($this->isPengelola()) {
            // Pengelola can access up to 3 mapped branches
            return Branch::whereIn('id', 
                $this->pengelolaBranchMaps()
                    ->where('is_active', true)
                    ->whereDate('effective_from', '<=', now())
                    ->where(function($query) {
                        $query->whereNull('effective_until')
                              ->orWhereDate('effective_until', '>=', now());
                    })
                    ->pluck('branch_id')
            )->get();
        }

        if ($this->isEmployee() && $this->employee) {
            // Regular employee can only access their primary branch and allowed branches
            $branchIds = [$this->employee->primary_branch_id];
            if ($this->employee->allowed_branches) {
                $branchIds = array_merge($branchIds, $this->employee->allowed_branches);
            }
            return Branch::whereIn('id', array_unique($branchIds))->get();
        }

        return collect(); // No access by default
    }

    public function canAccessBranch($branchId)
    {
        return $this->getAccessibleBranches()->pluck('id')->contains($branchId);
    }

    public function hasRole($role)
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        return $this->role === $role;
    }

    // Scope for active users
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
