<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group',
        'resource',
        'action',
        'scope',
        'is_system_permission'
    ];

    protected $casts = [
        'is_system_permission' => 'boolean'
    ];

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
                    ->withTimestamps();
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    // Scopes
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByResource($query, $resource)
    {
        return $query->where('resource', $resource);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByScope($query, $scope)
    {
        return $query->where('scope', $scope);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system_permission', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system_permission', false);
    }

    // Helper methods
    public function getFullName()
    {
        return $this->resource . '.' . $this->action . '.' . $this->scope;
    }

    public function isSystemPermission()
    {
        return $this->is_system_permission;
    }

    public function canBeDeleted()
    {
        return !$this->is_system_permission && $this->roles()->count() === 0;
    }

    // Permission patterns for coffee shop system
    public static function getPermissionPatterns()
    {
        return [
            // Attendance permissions
            'attendance' => [
                'view.all' => 'View all attendance records',
                'view.branch' => 'View branch attendance records',
                'view.own' => 'View own attendance records',
                'create.all' => 'Create attendance for anyone',
                'create.branch' => 'Create attendance for branch employees',
                'edit.all' => 'Edit all attendance records',
                'edit.branch' => 'Edit branch attendance records',
                'delete.all' => 'Delete all attendance records',
                'approve.corrections' => 'Approve attendance corrections'
            ],
            
            // Schedule permissions
            'schedule' => [
                'view.all' => 'View all schedules',
                'view.branch' => 'View branch schedules',
                'view.own' => 'View own schedule',
                'create.all' => 'Create schedules for anyone',
                'create.branch' => 'Create schedules for branch',
                'edit.all' => 'Edit all schedules',
                'edit.branch' => 'Edit branch schedules',
                'delete.all' => 'Delete all schedules',
                'manage.roster' => 'Manage daily roster'
            ],
            
            // Employee permissions
            'employee' => [
                'view.all' => 'View all employees',
                'view.branch' => 'View branch employees',
                'view.own' => 'View own profile',
                'create.all' => 'Create employee records',
                'edit.all' => 'Edit all employee records',
                'edit.branch' => 'Edit branch employee records',
                'delete.all' => 'Delete employee records'
            ],
            
            // Branch permissions
            'branch' => [
                'view.all' => 'View all branches',
                'view.assigned' => 'View assigned branches',
                'create' => 'Create new branches',
                'edit.all' => 'Edit all branches',
                'edit.assigned' => 'Edit assigned branches',
                'delete' => 'Delete branches'
            ],
            
            // Report permissions
            'report' => [
                'view.all' => 'View all reports',
                'view.branch' => 'View branch reports',
                'view.own' => 'View own reports',
                'export.all' => 'Export all reports',
                'export.branch' => 'Export branch reports'
            ],
            
            // Leave permissions
            'leave' => [
                'view.all' => 'View all leave requests',
                'view.branch' => 'View branch leave requests',
                'view.own' => 'View own leave requests',
                'create.own' => 'Create own leave requests',
                'create.others' => 'Create leave requests for others',
                'approve.level1' => 'First level leave approval',
                'approve.level2' => 'Second level leave approval',
                'approve.final' => 'Final leave approval'
            ],
            
            // System permissions
            'system' => [
                'user.manage' => 'Manage users',
                'role.manage' => 'Manage roles and permissions',
                'policy.manage' => 'Manage attendance policies',
                'settings.manage' => 'Manage system settings',
                'audit.view' => 'View audit logs'
            ]
        ];
    }

    public static function generateSystemPermissions()
    {
        $patterns = self::getPermissionPatterns();
        $permissions = [];
        
        foreach ($patterns as $group => $actions) {
            foreach ($actions as $actionScope => $description) {
                $parts = explode('.', $actionScope);
                $action = $parts[0];
                $scope = $parts[1] ?? 'all';
                
                $permissions[] = [
                    'name' => $group . '.' . $actionScope,
                    'display_name' => $description,
                    'description' => $description,
                    'group' => $group,
                    'resource' => $group,
                    'action' => $action,
                    'scope' => $scope,
                    'is_system_permission' => true
                ];
            }
        }
        
        return $permissions;
    }
}
