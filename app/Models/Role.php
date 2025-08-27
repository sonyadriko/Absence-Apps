<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'color',
        'hierarchy_level',
        'dashboard_config',
        'menu_config',
        'is_active',
        'is_system_role'
    ];

    protected $casts = [
        'dashboard_config' => 'array',
        'menu_config' => 'array',
        'is_active' => 'boolean',
        'is_system_role' => 'boolean',
        'hierarchy_level' => 'integer'
    ];

    // Relationships
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
                    ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
                    ->withPivot(['scope_data', 'effective_from', 'effective_until', 'is_active'])
                    ->withTimestamps();
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system_role', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system_role', false);
    }

    public function scopeByHierarchy($query, $order = 'desc')
    {
        return $query->orderBy('hierarchy_level', $order);
    }

    // Helper methods
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions()->where('name', $permission)->exists();
        }
        
        if ($permission instanceof Permission) {
            return $this->permissions()->where('id', $permission->id)->exists();
        }
        
        return false;
    }

    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }
        
        if ($permission && !$this->hasPermission($permission)) {
            $this->permissions()->attach($permission->id);
        }
        
        return $this;
    }

    public function revokePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }
        
        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
        
        return $this;
    }

    public function syncPermissions($permissions)
    {
        $permissionIds = [];
        
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $perm = Permission::where('name', $permission)->first();
                if ($perm) $permissionIds[] = $perm->id;
            } elseif ($permission instanceof Permission) {
                $permissionIds[] = $permission->id;
            } elseif (is_numeric($permission)) {
                $permissionIds[] = $permission;
            }
        }
        
        $this->permissions()->sync($permissionIds);
        return $this;
    }

    public function getDefaultDashboard()
    {
        return $this->dashboard_config ?? [
            'layout' => 'default',
            'widgets' => ['summary', 'recent_activity'],
            'theme' => 'light'
        ];
    }

    public function getMenuItems()
    {
        if ($this->menu_config) {
            return $this->menu_config;
        }
        
        // Generate menu based on permissions
        return $this->generateMenuFromPermissions();
    }

    private function generateMenuFromPermissions()
    {
        $menu = [];
        $permissions = $this->permissions;
        
        foreach ($permissions as $permission) {
            $group = $permission->group ?? 'general';
            
            if (!isset($menu[$group])) {
                $menu[$group] = [
                    'title' => ucfirst(str_replace('_', ' ', $group)),
                    'items' => []
                ];
            }
            
            // Add menu items based on permission patterns
            $this->addMenuItemsForPermission($menu[$group]['items'], $permission);
        }
        
        return $menu;
    }

    private function addMenuItemsForPermission(&$items, $permission)
    {
        $parts = explode('.', $permission->name);
        
        if (count($parts) >= 2) {
            $resource = $parts[0];
            $action = $parts[1];
            
            // Only add 'view' permissions to menu to avoid duplication
            if ($action === 'view') {
                $routeName = $resource . '.index';
                $title = ucfirst(str_replace('_', ' ', $resource));
                $icon = $this->getIconForResource($resource);
                
                $items[] = [
                    'title' => $title,
                    'route' => $routeName,
                    'icon' => $icon,
                    'permission' => $permission->name
                ];
            }
        }
    }

    private function getIconForResource($resource)
    {
        $icons = [
            'attendance' => 'fas fa-clock',
            'schedule' => 'fas fa-calendar',
            'employee' => 'fas fa-users',
            'branch' => 'fas fa-store',
            'report' => 'fas fa-chart-bar',
            'leave' => 'fas fa-calendar-alt',
            'user' => 'fas fa-user-cog',
            'audit' => 'fas fa-history',
            'policy' => 'fas fa-cogs'
        ];
        
        return $icons[$resource] ?? 'fas fa-circle';
    }

    public function canApprove($targetRole)
    {
        if ($targetRole instanceof self) {
            return $this->hierarchy_level > $targetRole->hierarchy_level;
        }
        
        return false;
    }

    public function getApprovalChain()
    {
        return self::where('hierarchy_level', '>', $this->hierarchy_level)
                   ->where('is_active', true)
                   ->orderBy('hierarchy_level')
                   ->get();
    }
}
