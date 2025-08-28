<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\RBACService;

class RoleManagementController extends Controller
{
    protected $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
        
        // Apply permission middleware (will be created later)
        $this->middleware(['auth']); // Add permission middleware later
    }

    /**
     * Display roles management page (WEB)
     */
    public function index()
    {
        // Check if this is an API request
        if (request()->is('api/*') || request()->expectsJson()) {
            return $this->indexApi();
        }
        
        // Web interface
        $roles = Role::withCount(['users', 'permissions'])
                    ->orderBy('hierarchy_level', 'desc')
                    ->get();
        
        $permissions = Permission::select('group', DB::raw('count(*) as count'))
                                ->groupBy('group')
                                ->get();

        $stats = [
            'total_roles' => Role::count(),
            'system_roles' => Role::where('is_system_role', true)->count(),
            'custom_roles' => Role::where('is_system_role', false)->count(),
            'total_permissions' => Permission::count(),
            'users_with_roles' => User::whereHas('userRoles')->count()
        ];

        return view('admin.roles.index', compact('roles', 'permissions', 'stats'));
    }
    
    /**
     * API version of index
     */
    public function indexApi()
    {
        $roles = Role::with('permissions')
                    ->withCount('userRoles')
                    ->orderBy('hierarchy_level', 'desc')
                    ->get();
                    
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        $permissionGroups = $permissions->groupBy('group');
        
        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
            'permission_groups' => $permissionGroups
        ]);
    }

    /**
     * Show role details
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'userRoles.user.employee']);
        $allPermissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        
        return view('admin.roles.show', compact('role', 'allPermissions'));
    }

    /**
     * Store new custom role
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:roles,name|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'hierarchy_level' => 'required|integer|min:1|max:99',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $role = $this->rbacService->createRole(
                $request->name,
                $request->display_name,
                [
                    'description' => $request->description,
                    'color' => $request->color,
                    'hierarchy_level' => $request->hierarchy_level,
                    'is_active' => true,
                    'is_system_role' => false
                ]
            );

            // Assign permissions
            $role->syncPermissions($request->permissions);

            return response()->json([
                'success' => true,
                'role' => $role->load('permissions'),
                'message' => "Custom role '{$role->display_name}' created successfully!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update custom role
     */
    public function update(Request $request, Role $role)
    {
        if ($role->is_system_role) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be modified.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'hierarchy_level' => 'required|integer|min:1|max:99',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $role->update([
                'display_name' => $request->display_name,
                'description' => $request->description,
                'color' => $request->color,
                'hierarchy_level' => $request->hierarchy_level
            ]);

            $role->syncPermissions($request->permissions);
            $this->rbacService->clearAllCache();

            return response()->json([
                'success' => true,
                'role' => $role->load('permissions'),
                'message' => "Role '{$role->display_name}' updated successfully!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete custom role
     */
    public function destroy(Role $role)
    {
        if ($role->is_system_role) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted.'
            ], 403);
        }

        if ($role->userRoles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete role because it is assigned to {$role->userRoles()->count()} user(s)."
            ], 400);
        }

        try {
            $roleName = $role->display_name;
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => "Custom role '{$roleName}' deleted successfully!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create role from coffee shop templates
     */
    public function createFromTemplate(Request $request)
    {
        $templates = [
            'coffee_manager' => [
                'display_name' => 'Coffee Shop Manager',
                'description' => 'Manager for a specific coffee shop location',
                'color' => '#e74c3c',
                'hierarchy_level' => 45,
                'permissions' => ['branch.view.assigned', 'employee.view.branch', 'attendance.view.branch', 'schedule.create.branch', 'leave.approve.level1']
            ],
            'barista_senior' => [
                'display_name' => 'Senior Barista',
                'description' => 'Experienced barista with training responsibilities',
                'color' => '#f39c12',
                'hierarchy_level' => 25,
                'permissions' => ['attendance.view.own', 'schedule.view.own', 'employee.view.branch']
            ],
            'cashier_lead' => [
                'display_name' => 'Lead Cashier',
                'description' => 'Lead cashier with additional responsibilities',
                'color' => '#9b59b6',
                'hierarchy_level' => 20,
                'permissions' => ['attendance.view.own', 'schedule.view.own', 'report.view.own']
            ]
        ];

        $validator = Validator::make($request->all(), [
            'template' => 'required|in:coffee_manager,barista_senior,cashier_lead',
            'name' => 'required|string|max:50|unique:roles,name|regex:/^[a-z_]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $template = $templates[$request->template];
            
            $role = $this->rbacService->createRole(
                $request->name,
                $template['display_name'],
                $template
            );

            $role->syncPermissions($template['permissions']);

            return response()->json([
                'success' => true,
                'role' => $role->load('permissions'),
                'message' => "Role '{$role->display_name}' created from template!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating role: ' . $e->getMessage()
            ], 500);
        }
    }
}
