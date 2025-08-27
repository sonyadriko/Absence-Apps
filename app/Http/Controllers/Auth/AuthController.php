<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RBACService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Employee;

class AuthController extends Controller
{
    protected $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login attempt
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Get user with employee relationship
            $user = Auth::user();
            $user->load(['employee', 'userRoles.role', 'userPermissions.permission']);
            
            // Store user roles and permissions in session for quick access
            $this->storeUserPermissionsInSession($user);
            
            // Redirect based on role
            return $this->redirectBasedOnRole($user);
        }

        return back()
            ->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])
            ->withInput($request->only('email'));
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')
            ->with('message', 'You have been logged out successfully.');
    }

    /**
     * Store user permissions in session for quick access
     */
    protected function storeUserPermissionsInSession($user)
    {
        // Get all user permissions (direct + role-based)
        $allPermissions = $this->rbacService->getUserPermissions($user);
        
        // Get user roles
        $userRoles = $user->userRoles->map(function ($userRole) {
            return [
                'role_id' => $userRole->role->id,
                'role_name' => $userRole->role->name,
                'role_slug' => $userRole->role->name, // Use name as slug
                'display_name' => $userRole->role->display_name,
                'scope_data' => $userRole->scope_data,
                'is_active' => $userRole->is_active,
                'dashboard_config' => $userRole->role->dashboard_config,
                'menu_config' => $userRole->role->menu_config,
            ];
        })->toArray();

        // Store in session
        session([
            'user_permissions' => $allPermissions->pluck('permission.name')->toArray(),
            'user_roles' => $userRoles,
            'user_primary_role' => $userRoles[0] ?? null, // First role as primary
        ]);
    }

    /**
     * Redirect user based on their primary role
     */
    protected function redirectBasedOnRole($user)
    {
        $primaryRole = session('user_primary_role');
        
        if (!$primaryRole) {
            // Fallback to employee dashboard if no role assigned
            return redirect()->route('employee.dashboard');
        }

        // Role-based redirection mapping
        $roleRouteMap = [
            'hr_central' => 'hr-central.dashboard',
            'branch_manager' => 'branch-manager.dashboard',
            'pengelola' => 'pengelola.dashboard',
            'system_admin' => 'admin.dashboard',
            'shift_leader' => 'shift-leader.dashboard',
            'supervisor' => 'supervisor.dashboard',
            'senior_barista' => 'employee.dashboard', // senior barista uses same as employee
            'employee' => 'employee.dashboard'
        ];
        
        $routeName = $roleRouteMap[$primaryRole['role_slug']] ?? 'employee.dashboard';
        return redirect()->route($routeName);
    }

    /**
     * Get demo users for development (remove in production)
     */
    public function getDemoUsers()
    {
        if (!config('app.debug')) {
            abort(404);
        }

        return response()->json([
            [
                'role' => 'HR Central',
                'email' => 'hr@coffee.com',
                'password' => 'password',
                'description' => 'Full access to all branches and features'
            ],
            [
                'role' => 'Branch Manager', 
                'email' => 'manager@coffee.com',
                'password' => 'password',
                'description' => 'Manage multiple branches'
            ],
            [
                'role' => 'Pengelola',
                'email' => 'pengelola@coffee.com', 
                'password' => 'password',
                'description' => 'Manage up to 3 branches'
            ],
            [
                'role' => 'Employee',
                'email' => 'employee@coffee.com',
                'password' => 'password', 
                'description' => 'Basic employee access'
            ],
            [
                'role' => 'Admin',
                'email' => 'admin@coffee.com',
                'password' => 'password',
                'description' => 'System administration'
            ]
        ]);
    }
}
