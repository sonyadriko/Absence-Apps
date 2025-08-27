<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiController extends Controller
{
    /**
     * Return success response
     */
    protected function successResponse($data = null, $message = 'Success', $code = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return error response
     */
    protected function errorResponse($message = 'Error', $code = Response::HTTP_BAD_REQUEST, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return validation error response
     */
    protected function validationErrorResponse($errors, $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorizedResponse($message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return forbidden response
     */
    protected function forbiddenResponse($message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return not found response
     */
    protected function notFoundResponse($message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return server error response
     */
    protected function serverErrorResponse($message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Return paginated response
     */
    protected function paginatedResponse($paginator, $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get authenticated user
     */
    protected function getAuthenticatedUser()
    {
        return auth('sanctum')->user();
    }

    /**
     * Get authenticated employee
     */
    protected function getAuthenticatedEmployee()
    {
        $user = $this->getAuthenticatedUser();
        return $user ? $user->employee : null;
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission($permission): bool
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return false;
        }

        $rbacService = app(\App\Services\RBACService::class);
        return $rbacService->userHasPermission($user, $permission);
    }

    /**
     * Get user's accessible branches
     */
    protected function getAccessibleBranches()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return collect([]);
        }

        $rbacService = app(\App\Services\RBACService::class);

        if ($rbacService->userHasPermission($user, 'branch.view.all')) {
            return \App\Models\Branch::all();
        }

        if ($rbacService->userHasPermission($user, 'branch.view.assigned')) {
            $employee = $user->employee;
            if ($employee) {
                return \App\Models\Branch::whereHas('managerBranchMaps', function($query) use ($employee) {
                    $query->where('employee_id', $employee->id);
                })->orWhereHas('pengelolaBranchMaps', function($query) use ($employee) {
                    $query->where('employee_id', $employee->id);
                })->get();
            }
        }

        $employee = $user->employee;
        return $employee ? collect([$employee->branch]) : collect([]);
    }

    /**
     * Validate branch access for user
     */
    protected function validateBranchAccess($branchId): bool
    {
        $accessibleBranches = $this->getAccessibleBranches();
        return $accessibleBranches->contains('id', $branchId);
    }

    /**
     * Get user's primary role
     */
    protected function getUserPrimaryRole()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return null;
        }

        $rbacService = app(\App\Services\RBACService::class);
        $userRoles = $rbacService->getUserActiveRoles($user);
        return $userRoles->first();
    }
}
