<?php

namespace App\Http\Controllers\HRCentral;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    /**
     * Display a listing of branches
     */
    public function index(Request $request)
    {
        $query = Branch::query();
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }
        
        // Filter by active status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }
        
        $branches = $query->withCount('employees')->orderBy('created_at', 'desc')->paginate(10);
        
        // If this is an AJAX request, return JSON data like the roles controller
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'branches' => $branches->items(),
                'pagination' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'per_page' => $branches->perPage(),
                    'total' => $branches->total(),
                ]
            ]);
        }
        
        return view('hr-central.branches.index', compact('branches'));
    }
    
    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        return view('hr-central.branches.create');
    }
    
    /**
     * Store a newly created branch
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:branches,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10|max:1000',
            'timezone' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'operating_hours' => 'nullable|array',
            'operating_hours.*.open' => 'nullable|string',
            'operating_hours.*.close' => 'nullable|string',
            'operating_hours.*.is_closed' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $request->all();
        
        // Handle operating hours
        if ($request->has('operating_hours')) {
            $operatingHours = [];
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            foreach ($days as $day) {
                $operatingHours[$day] = [
                    'open' => $request->input("operating_hours.{$day}.open"),
                    'close' => $request->input("operating_hours.{$day}.close"),
                    'is_closed' => $request->boolean("operating_hours.{$day}.is_closed")
                ];
            }
            $data['operating_hours'] = $operatingHours;
        }
        
        $branch = Branch::create($data);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Branch created successfully',
                'redirect' => route('hr-central.branches.index')
            ]);
        }
        
        return redirect()->route('hr-central.branches.index')
                        ->with('success', 'Branch created successfully');
    }
    
    /**
     * Display the specified branch
     */
    public function show(Branch $branch)
    {
        $branch->loadCount('employees');
        return view('hr-central.branches.show', compact('branch'));
    }
    
    /**
     * Show the form for editing the specified branch
     */
    public function edit(Branch $branch)
    {
        return view('hr-central.branches.edit', compact('branch'));
    }
    
    /**
     * Update the specified branch
     */
    public function update(Request $request, Branch $branch)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10|max:1000',
            'timezone' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'operating_hours' => 'nullable|array',
            'operating_hours.*.open' => 'nullable|string',
            'operating_hours.*.close' => 'nullable|string',
            'operating_hours.*.is_closed' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $request->all();
        
        // Handle operating hours
        if ($request->has('operating_hours')) {
            $operatingHours = [];
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            foreach ($days as $day) {
                $operatingHours[$day] = [
                    'open' => $request->input("operating_hours.{$day}.open"),
                    'close' => $request->input("operating_hours.{$day}.close"),
                    'is_closed' => $request->boolean("operating_hours.{$day}.is_closed")
                ];
            }
            $data['operating_hours'] = $operatingHours;
        }
        
        $branch->update($data);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Branch updated successfully',
                'redirect' => route('hr-central.branches.index')
            ]);
        }
        
        return redirect()->route('hr-central.branches.index')
                        ->with('success', 'Branch updated successfully');
    }
    
    /**
     * Get employees for a specific branch
     */
    public function employees(Branch $branch)
    {
        try {
            $employees = $branch->employees()->with('position')->select([
                'id', 'employee_number', 'full_name', 'position_id', 'status', 'hire_date', 'employment_type'
            ])->get();
            
            // Transform the data for frontend
            $transformedEmployees = $employees->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_number,
                    'full_name' => $employee->full_name,
                    'position' => $employee->position->name ?? 'N/A',
                    'is_active' => $employee->status === 'active',
                    'joined_date' => $employee->hire_date,
                    'employment_type' => ucfirst(str_replace('_', ' ', $employee->employment_type))
                ];
            });
            
            return response()->json([
                'success' => true,
                'employees' => $transformedEmployees
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading employees: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove the specified branch
     */
    public function destroy(Branch $branch)
    {
        try {
            // Check if branch has related records
            if ($branch->employees()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete branch that has employees assigned to it'
                ], 422);
            }
            
            $branch->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Branch deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting branch: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle branch active status
     */
    public function toggleStatus(Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);
        
        return response()->json([
            'success' => true,
            'message' => 'Branch status updated successfully',
            'is_active' => $branch->is_active
        ]);
    }
    
    /**
     * Export branches data
     */
    public function export()
    {
        $branches = Branch::withCount('employees')->get();
        
        $filename = 'branches_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($branches) {
            $file = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($file, ['Code', 'Name', 'Address', 'Phone', 'Latitude', 'Longitude', 'Radius', 'Timezone', 'Status', 'Employees Count', 'Created At']);
            
            // CSV data
            foreach ($branches as $branch) {
                fputcsv($file, [
                    $branch->code,
                    $branch->name,
                    $branch->address,
                    $branch->phone,
                    $branch->latitude,
                    $branch->longitude,
                    $branch->radius,
                    $branch->timezone,
                    $branch->is_active ? 'Active' : 'Inactive',
                    $branch->employees_count,
                    $branch->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
