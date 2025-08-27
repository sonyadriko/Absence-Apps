<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\RBACService;

class CheckPermission
{
    protected $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @param  string|null  $redirectTo
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission, $redirectTo = null)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Build context from request for permission checking
        $context = $this->buildContext($request);
        
        // Check if user has the required permission
        if (!$this->rbacService->userHasPermission($user, $permission, $context)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Forbidden. You do not have permission to access this resource.',
                    'required_permission' => $permission,
                    'user_permissions' => $this->rbacService->getUserPermissions($user)->keys()->toArray()
                ], 403);
            }
            
            if ($redirectTo) {
                return redirect()->route($redirectTo)
                    ->with('error', 'You do not have permission to access that page.');
            }
            
            // Default redirect based on user role
            $userRoles = $this->rbacService->getUserActiveRoles($user);
            $primaryRole = $userRoles->first();
            
            if ($primaryRole) {
                $dashboardRoute = $this->getDashboardRoute($primaryRole->role->name);
                return redirect()->route($dashboardRoute)
                    ->with('error', 'You do not have permission to access that page.');
            }
            
            return redirect()->route('employee.dashboard')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }

    /**
     * Build context array from request for permission checking
     */
    private function buildContext(Request $request)
    {
        $context = [];
        
        // Extract branch_id from route parameters or request
        if ($request->route('branch')) {
            $context['branch_id'] = $request->route('branch')->id ?? $request->route('branch');
        } elseif ($request->has('branch_id')) {
            $context['branch_id'] = $request->input('branch_id');
        }
        
        // Extract employee_id from route parameters or request
        if ($request->route('employee')) {
            $context['employee_id'] = $request->route('employee')->id ?? $request->route('employee');
        } elseif ($request->has('employee_id')) {
            $context['employee_id'] = $request->input('employee_id');
        }
        
        // Add current date for time-based permissions
        $context['current_date'] = now()->toDateString();
        
        // Add request method for action-based permissions
        $context['method'] = $request->method();
        
        // Add IP and user agent for security context
        $context['ip'] = $request->ip();
        $context['user_agent'] = $request->userAgent();
        
        return $context;
    }
    
    /**
     * Get appropriate dashboard route for role
     */
    private function getDashboardRoute($roleName)
    {
        $dashboardRoutes = [
            'hr_central' => 'hr-central.dashboard',
            'branch_manager' => 'branch-manager.dashboard',
            'pengelola' => 'pengelola.dashboard',
            'shift_leader' => 'shift-leader.dashboard',
            'supervisor' => 'supervisor.dashboard',
            'senior_barista' => 'employee.dashboard',
            'employee' => 'employee.dashboard',
            'system_admin' => 'admin.dashboard'
        ];
        
        return $dashboardRoutes[$roleName] ?? 'employee.dashboard';
    }
}
