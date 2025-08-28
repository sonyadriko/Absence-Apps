<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\RBACService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends ApiController
{
    protected $rbacService;

    public function __construct(RBACService $rbacService)
    {
        $this->rbacService = $rbacService;
        $this->middleware('auth:sanctum', ['except' => ['login', 'register']]);
    }

    /**
     * User login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'device_name' => 'required|string|max:255', // Required for Sanctum
            'device_type' => 'nullable|in:mobile,web,tablet',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Attempt authentication
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->unauthorizedResponse('Invalid credentials');
        }

        $user = Auth::user();
        
        // Check if user has employee record
        if (!$user->employee) {
            return $this->forbiddenResponse('Employee profile not found. Please contact administrator.');
        }

        // Store user permissions in session for faster access
        $this->rbacService->storeUserPermissionsInSession($user);

        // Get user roles and permissions
        $userRoles = $this->rbacService->getUserActiveRoles($user);
        $primaryRole = $userRoles->first();
        $permissions = $this->rbacService->getUserPermissions($user);

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Create Sanctum token with abilities based on user roles
        $abilities = $permissions->pluck('name')->toArray();
        $deviceName = $request->device_name . ' (' . ($request->device_type ?? 'web') . ')';
        
        // Revoke old tokens for same device (optional - for single session per device)
        // $user->tokens()->where('name', $deviceName)->delete();
        
        $token = $user->createToken($deviceName, $abilities);

        // Log successful login
        // TODO: Fix audit log table structure
        // \App\Models\AuditLog::create([
        //     'user_id' => $user->id,
        //     'employee_id' => $user->employee->id,
        //     'action' => 'user_login',
        //     'model_type' => 'User',
        //     'model_id' => $user->id,
        //     'old_values' => null,
        //     'new_values' => json_encode([
        //         'device_name' => $request->device_name,
        //         'device_type' => $request->device_type,
        //         'login_time' => now()->toISOString(),
        //         'token_name' => $deviceName,
        //     ]),
        //     'ip_address' => $request->ip(),
        //     'user_agent' => $request->userAgent(),
        // ]);

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => null, // Sanctum tokens don't expire by default
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee' => [
                    'id' => $user->employee->id,
                    'employee_code' => $user->employee->employee_code,
                    'branch' => $user->employee->branch ? [
                        'id' => $user->employee->branch->id,
                        'name' => $user->employee->branch->name,
                        'code' => $user->employee->branch->code,
                        'latitude' => $user->employee->branch->latitude,
                        'longitude' => $user->employee->branch->longitude,
                        'geofence_radius' => $user->employee->branch->geofence_radius,
                    ] : null
                ],
                'primary_role' => $primaryRole ? [
                    'id' => $primaryRole->role->id,
                    'name' => $primaryRole->role->name,
                    'display_name' => $primaryRole->role->display_name,
                    'color' => $primaryRole->role->color,
                ] : null,
                'permissions' => $permissions->pluck('name')->toArray(),
            ]
        ], 'Login successful');
    }

    /**
     * User logout
     */
    public function logout(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if ($user) {
            // Log logout
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'employee_id' => $user->employee ? $user->employee->id : null,
                'action' => 'user_logout',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => null,
                'new_values' => json_encode([
                    'logout_time' => now()->toISOString(),
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Revoke current token
            $request->user()->currentAccessToken()->delete();
        }

        return $this->successResponse(null, 'Successfully logged out');
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if ($user) {
            // Log logout from all devices
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'employee_id' => $user->employee ? $user->employee->id : null,
                'action' => 'user_logout_all',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => null,
                'new_values' => json_encode([
                    'logout_time' => now()->toISOString(),
                    'devices_count' => $user->tokens()->count(),
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Revoke all tokens
            $user->tokens()->delete();
        }

        return $this->successResponse(null, 'Successfully logged out from all devices');
    }

    /**
     * Get user tokens (devices)
     */
    public function getTokens()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $tokens = $user->tokens()->get()->map(function($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'is_current' => $token->id === request()->user()->currentAccessToken()->id,
            ];
        });

        return $this->successResponse($tokens, 'Tokens retrieved successfully');
    }

    /**
     * Revoke specific token
     */
    public function revokeToken(Request $request, $tokenId)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $token = $user->tokens()->find($tokenId);
        
        if (!$token) {
            return $this->notFoundResponse('Token not found');
        }

        // Don't allow revoking current token
        if ($token->id === $request->user()->currentAccessToken()->id) {
            return $this->errorResponse('Cannot revoke current token. Use logout instead.');
        }

        $token->delete();

        return $this->successResponse(null, 'Token revoked successfully');
    }

    /**
     * Get current user profile
     */
    public function me()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $userRoles = $this->rbacService->getUserActiveRoles($user);
        $primaryRole = $userRoles->first();
        $permissions = $this->rbacService->getUserPermissions($user);

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'last_login_at' => $user->last_login_at,
            'employee' => $user->employee ? [
                'id' => $user->employee->id,
                'employee_code' => $user->employee->employee_code,
                'phone' => $user->employee->phone,
                'hire_date' => $user->employee->hire_date,
                'branch' => [
                    'id' => $user->employee->branch->id,
                    'name' => $user->employee->branch->name,
                    'code' => $user->employee->branch->code,
                    'address' => $user->employee->branch->address,
                    'phone' => $user->employee->branch->phone,
                    'latitude' => $user->employee->branch->latitude,
                    'longitude' => $user->employee->branch->longitude,
                    'geofence_radius' => $user->employee->branch->geofence_radius,
                ]
            ] : null,
            'roles' => $userRoles->map(function($userRole) {
                return [
                    'id' => $userRole->role->id,
                    'name' => $userRole->role->name,
                    'display_name' => $userRole->role->display_name,
                    'color' => $userRole->role->color,
                    'is_primary' => $userRole->is_primary,
                ];
            }),
            'primary_role' => $primaryRole ? [
                'id' => $primaryRole->role->id,
                'name' => $primaryRole->role->name,
                'display_name' => $primaryRole->role->display_name,
                'color' => $primaryRole->role->color,
            ] : null,
            'permissions' => $permissions->pluck('name')->toArray(),
            'accessible_branches' => $this->getAccessibleBranches()->map(function($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                ];
            }),
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Validate current password if changing password
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || 
                !Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect');
            }
        }

        try {
            $oldValues = [
                'name' => $user->name,
                'phone' => $user->employee ? $user->employee->phone : null,
            ];

            // Update user
            $user->update([
                'name' => $request->name,
                'password' => $request->filled('password') ? Hash::make($request->password) : $user->password,
            ]);

            // Update employee phone if exists
            if ($user->employee && $request->filled('phone')) {
                $user->employee->update([
                    'phone' => $request->phone,
                ]);
            }

            // Log profile update
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'employee_id' => $user->employee ? $user->employee->id : null,
                'action' => 'profile_updated',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode([
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'password_changed' => $request->filled('password'),
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse(null, 'Profile updated successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update profile');
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect');
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Log password change
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'employee_id' => $user->employee ? $user->employee->id : null,
                'action' => 'password_changed',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => null,
                'new_values' => json_encode([
                    'password_changed_at' => now()->toISOString(),
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse(null, 'Password changed successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to change password');
        }
    }
}
