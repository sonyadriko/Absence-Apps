<?php

namespace App\Http\Controllers\HRCentral;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $query = Employee::query()->with(['position', 'primaryBranch']);
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        // Filter by branch
        if ($request->has('branch') && $request->branch !== '') {
            $query->where('primary_branch_id', $request->branch);
        }
        
        // Filter by employment type
        if ($request->has('employment_type') && $request->employment_type !== '') {
            $query->where('employment_type', $request->employment_type);
        }
        
        $employees = $query->orderBy('created_at', 'desc')->get();
        
        // Get filter options
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        
        return view('hr-central.employees.index', compact('employees', 'branches', 'positions'));
    }
    
    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        return view('hr-central.employees.create', compact('branches', 'positions'));
    }
    
    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => 'required|string|max:255|unique:employees,employee_number',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:255',
            'position_id' => 'required|exists:positions,id',
            'primary_branch_id' => 'required|exists:branches,id',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive,terminated',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'hourly_rate' => 'nullable|numeric|min:0',
            'department' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $employee = Employee::create($request->all());
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully',
                'redirect' => route('hr-central.employees.index')
            ]);
        }
        
        return redirect()->route('hr-central.employees.index')
                        ->with('success', 'Employee created successfully');
    }
    
    /**
     * Display the specified employee
     */
    public function show(Employee $employee)
    {
        $employee->load(['position', 'primaryBranch']);
        return view('hr-central.employees.show', compact('employee'));
    }
    
    /**
     * Show the form for editing the specified employee
     */
    public function edit(Employee $employee)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        return view('hr-central.employees.edit', compact('employee', 'branches', 'positions'));
    }
    
    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => 'required|string|max:255|unique:employees,employee_number,' . $employee->id,
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:255',
            'position_id' => 'required|exists:positions,id',
            'primary_branch_id' => 'required|exists:branches,id',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive,terminated',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'hourly_rate' => 'nullable|numeric|min:0',
            'department' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $employee->update($request->all());
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully',
                'redirect' => route('hr-central.employees.index')
            ]);
        }
        
        return redirect()->route('hr-central.employees.index')
                        ->with('success', 'Employee updated successfully');
    }
    
    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting employee: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle employee status
     */
    public function toggleStatus(Employee $employee)
    {
        $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->update(['status' => $newStatus]);
        
        return response()->json([
            'success' => true,
            'message' => 'Employee status updated successfully',
            'status' => $newStatus
        ]);
    }
    
    /**
     * Export employees data
     */
    public function export()
    {
        $employees = Employee::with(['position', 'primaryBranch'])->get();
        
        $filename = 'employees_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($employees) {
            $file = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($file, [
                'Employee Number', 'Full Name', 'Email', 'Phone', 'Position', 
                'Branch', 'Status', 'Employment Type', 'Hire Date', 'Hourly Rate', 
                'Department', 'Created At'
            ]);
            
            // CSV data
            foreach ($employees as $employee) {
                fputcsv($file, [
                    $employee->employee_number,
                    $employee->full_name,
                    $employee->email,
                    $employee->phone,
                    $employee->position->name ?? 'N/A',
                    $employee->primaryBranch->name ?? 'N/A',
                    ucfirst($employee->status),
                    ucfirst(str_replace('_', ' ', $employee->employment_type)),
                    $employee->hire_date,
                    $employee->hourly_rate,
                    $employee->department,
                    $employee->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
