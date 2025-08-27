<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserRole;
use App\Models\UserPermission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RBACService
{
    protected $cachePrefix = 'rbac_';
    protected $cacheTtl = 3600; // 1 hour

    /**
     * Check if user has a specific permission
     */
    public function userHasPermission(User $user, string $permission, array $context = [])
    {
        $cacheKey = $this->cachePrefix . "user_{$user->id}_permission_{$permission}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user, $permission, $context) {
            // Check direct user permissions first (highest priority)
            $directPermission = $this->checkDirectUserPermission($user, $permission);
            if ($directPermission !== null) {
                return $directPermission;
            }
            
            // Check role-based permissions
            return $this->checkRoleBasedPermissions($user, $permission, $context);
        });
    }

    /**
     * Check direct user permissions (grant/deny)
     */
    private function checkDirectUserPermission(User $user, string $permission)
    {
        $userPermission = UserPermission::where('user_id', $user->id)
            ->whereHas('permission', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('effective_until')
                      ->orWhereDate('effective_until', '>=', now());
            })
            ->orderBy('type', 'desc') // 'grant' comes before 'deny'
            ->first();
            
        if ($userPermission) {
            return $userPermission->type === 'grant';
        }
        
        return null; // No direct permission found
    }

    /**
     * Check role-based permissions
     */
    private function checkRoleBasedPermissions(User $user, string $permission, array $context = [])
    {
        $activeRoles = $this->getUserActiveRoles($user);
        
        foreach ($activeRoles as $userRole) {
            $role = $userRole->role;
            
            if ($role->hasPermission($permission)) {
                // Check scope restrictions if any
                if ($this->checkScopeRestrictions($userRole, $context)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get user's active roles
     */
    public function getUserActiveRoles(User $user)
    {
        return UserRole::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('effective_until')
                      ->orWhereDate('effective_until', '>=', now());
            })
            ->with('role')
            ->get();
    }

    /**
     * Check scope restrictions for role
     */
    private function checkScopeRestrictions(UserRole $userRole, array $context = [])
    {
        $scopeData = $userRole->scope_data;
        
        if (!$scopeData) {
            return true; // No restrictions
        }
        
        // Check branch restrictions
        if (isset($scopeData['branches']) && isset($context['branch_id'])) {
            return in_array($context['branch_id'], $scopeData['branches']);
        }
        
        // Check employee restrictions
        if (isset($scopeData['employees']) && isset($context['employee_id'])) {
            return in_array($context['employee_id'], $scopeData['employees']);
        }
        
        // Check date restrictions
        if (isset($scopeData['date_range'])) {
            $currentDate = Carbon::today();
            $from = Carbon::parse($scopeData['date_range']['from']);
            $until = Carbon::parse($scopeData['date_range']['until']);
            
            return $currentDate->between($from, $until);
        }
        
        return true; // Default allow if no applicable restrictions
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, $role, array $scopeData = [], $effectiveFrom = null, $effectiveUntil = null)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        
        $userRole = UserRole::updateOrCreate([
            'user_id' => $user->id,
            'role_id' => $role->id,
        ], [
            'scope_data' => $scopeData,
            'effective_from' => $effectiveFrom ?? now(),
            'effective_until' => $effectiveUntil,
            'is_active' => true
        ]);
        
        $this->clearUserCache($user);
        
        return $userRole;
    }

    /**
     * Remove role from user
     */
    public function removeRole(User $user, $role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }
        
        if ($role) {
            UserRole::where('user_id', $user->id)
                    ->where('role_id', $role->id)
                    ->delete();
                    
            $this->clearUserCache($user);
        }
    }

    /**
     * Grant direct permission to user
     */
    public function grantPermission(User $user, $permission, User $grantedBy, array $scopeData = [], $reason = null)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }
        
        $userPermission = UserPermission::updateOrCreate([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'type' => 'grant'
        ], [
            'scope_data' => $scopeData,
            'effective_from' => now(),
            'effective_until' => null,
            'is_active' => true,
            'granted_by' => $grantedBy->id,
            'reason' => $reason
        ]);
        
        $this->clearUserCache($user);
        
        return $userPermission;
    }

    /**
     * Revoke direct permission from user
     */
    public function revokePermission(User $user, $permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }
        
        if ($permission) {
            UserPermission::where('user_id', $user->id)
                          ->where('permission_id', $permission->id)
                          ->delete();
                          
            $this->clearUserCache($user);
        }
    }

    /**
     * Get all permissions for user (from roles + direct)
     */
    public function getUserPermissions(User $user)
    {
        $cacheKey = $this->cachePrefix . "user_{$user->id}_all_permissions";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user) {
            $permissions = collect();
            
            // Get permissions from roles
            $activeRoles = $this->getUserActiveRoles($user);
            foreach ($activeRoles as $userRole) {
                $rolePermissions = $userRole->role->permissions;
                foreach ($rolePermissions as $permission) {
                    $permissions->put($permission->name, [
                        'permission' => $permission,
                        'source' => 'role',
                        'role' => $userRole->role->name,
                        'scope_data' => $userRole->scope_data
                    ]);
                }
            }
            
            // Get direct permissions
            $directPermissions = UserPermission::where('user_id', $user->id)
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('effective_until')
                          ->orWhereDate('effective_until', '>=', now());
                })
                ->with('permission')
                ->get();
                
            foreach ($directPermissions as $userPermission) {
                if ($userPermission->type === 'deny') {
                    // Remove permission if explicitly denied
                    $permissions->forget($userPermission->permission->name);
                } else {
                    // Add/override permission if granted
                    $permissions->put($userPermission->permission->name, [
                        'permission' => $userPermission->permission,
                        'source' => 'direct',
                        'type' => $userPermission->type,
                        'scope_data' => $userPermission->scope_data
                    ]);
                }
            }
            
            return $permissions;
        });
    }

    /**
     * Create new role dynamically
     */
    public function createRole(string $name, string $displayName, array $config = [])
    {
        return Role::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $config['description'] ?? null,
            'color' => $config['color'] ?? '#007bff',
            'hierarchy_level' => $config['hierarchy_level'] ?? 0,
            'dashboard_config' => $config['dashboard_config'] ?? null,
            'menu_config' => $config['menu_config'] ?? null,
            'is_active' => $config['is_active'] ?? true,
            'is_system_role' => $config['is_system_role'] ?? false
        ]);
    }

    /**
     * Create new permission dynamically
     */
    public function createPermission(string $name, string $displayName, array $config = [])
    {
        return Permission::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $config['description'] ?? null,
            'group' => $config['group'] ?? null,
            'resource' => $config['resource'] ?? null,
            'action' => $config['action'] ?? null,
            'scope' => $config['scope'] ?? null,
            'is_system_permission' => $config['is_system_permission'] ?? false
        ]);
    }

    /**
     * Get user's accessible branches based on roles and permissions
     */
    public function getUserAccessibleBranches(User $user)
    {
        $activeRoles = $this->getUserActiveRoles($user);
        $branchIds = collect();
        
        foreach ($activeRoles as $userRole) {
            $role = $userRole->role;
            
            // Check if role has global branch access
            if ($role->hasPermission('branch.view.all')) {
                return \App\Models\Branch::where('is_active', true)->get();
            }
            
            // Check scope restrictions
            if ($userRole->scope_data && isset($userRole->scope_data['branches'])) {
                $branchIds = $branchIds->merge($userRole->scope_data['branches']);
            }
        }
        
        // If no specific branches, check employee's assigned branches
        if ($branchIds->isEmpty() && $user->employee) {
            $branchIds->push($user->employee->primary_branch_id);
            if ($user->employee->allowed_branches) {
                $branchIds = $branchIds->merge($user->employee->allowed_branches);
            }
        }
        
        return \App\Models\Branch::whereIn('id', $branchIds->unique())->get();
    }

    /**
     * Clear user-specific cache
     */
    public function clearUserCache(User $user)
    {
        $patterns = [
            $this->cachePrefix . "user_{$user->id}_*"
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Clear all RBAC cache
     */
    public function clearAllCache()
    {
        // In production, you might want to use Cache::tags() for better cache management
        Cache::flush();
    }

    /**
     * Get role hierarchy for approval chains
     */
    public function getApprovalChain($fromRole)
    {
        if (is_string($fromRole)) {
            $fromRole = Role::where('name', $fromRole)->first();
        }
        
        if (!$fromRole) return collect();
        
        return Role::where('hierarchy_level', '>', $fromRole->hierarchy_level)
                   ->where('is_active', true)
                   ->orderBy('hierarchy_level')
                   ->get();
    }

    /**
     * Check if user can approve based on role hierarchy
     */
    public function canUserApprove(User $approver, User $requester)
    {
        $approverRoles = $this->getUserActiveRoles($approver);
        $requesterRoles = $this->getUserActiveRoles($requester);
        
        $maxApproverLevel = $approverRoles->max(fn($ur) => $ur->role->hierarchy_level);
        $maxRequesterLevel = $requesterRoles->max(fn($ur) => $ur->role->hierarchy_level);
        
        return $maxApproverLevel > $maxRequesterLevel;
    }

    /**
     * Generate role-specific dashboard config
     */
    public function generateDashboardConfig(Role $role)
    {
        $config = $role->getDefaultDashboard();
        $permissions = $role->permissions;
        
        // Add widgets based on permissions
        $widgets = [];
        
        if ($role->hasPermission('attendance.view.all') || $role->hasPermission('attendance.view.branch')) {
            $widgets[] = 'attendance_summary';
            $widgets[] = 'recent_attendance';
        }
        
        if ($role->hasPermission('schedule.view.all') || $role->hasPermission('schedule.view.branch')) {
            $widgets[] = 'upcoming_schedules';
        }
        
        if ($role->hasPermission('report.view.all') || $role->hasPermission('report.view.branch')) {
            $widgets[] = 'performance_charts';
        }
        
        if ($role->hasPermission('leave.approve.level1') || $role->hasPermission('leave.approve.level2')) {
            $widgets[] = 'pending_approvals';
        }
        
        $config['widgets'] = array_merge($config['widgets'] ?? [], $widgets);
        
        return $config;
    }
}
