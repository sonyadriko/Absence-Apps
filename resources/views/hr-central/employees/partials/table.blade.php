<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Position</th>
                <th>Branch</th>
                <th>Status</th>
                <th>Type</th>
                <th>Hire Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td>
                        <div>
                            <strong>{{ $employee->full_name }}</strong>
                            <br>
                            <small class="text-muted">{{ $employee->employee_number }}</small>
                            <br>
                            <small class="text-muted">{{ $employee->email ?: 'No email' }}</small>
                        </div>
                    </td>
                    <td>{{ $employee->position->name ?? 'N/A' }}</td>
                    <td>{{ $employee->primaryBranch->name ?? 'N/A' }}</td>
                    <td>
                        @php
                            $statusClass = match($employee->status) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'terminated' => 'danger',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }}">
                            {{ ucfirst($employee->status) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $employmentTypeClass = match($employee->employment_type) {
                                'full_time' => 'primary',
                                'part_time' => 'info',
                                'contract' => 'warning',
                                'intern' => 'secondary',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $employmentTypeClass }}">
                            {{ ucfirst(str_replace('_', ' ', $employee->employment_type)) }}
                        </span>
                    </td>
                    <td>{{ $employee->hire_date ? $employee->hire_date->format('d M Y') : 'N/A' }}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('hr-central.employees.show', $employee) }}" 
                               class="btn btn-outline-info" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-outline-warning" onclick="editEmployee({{ $employee->id }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-{{ $employee->status === 'active' ? 'secondary' : 'success' }}" 
                                    onclick="toggleEmployeeStatus({{ $employee->id }})" 
                                    title="{{ $employee->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                <i class="fas fa-{{ $employee->status === 'active' ? 'pause' : 'play' }}"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteEmployee({{ $employee->id }})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p>No employees found</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($employees->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            <small class="text-muted">
                Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} results
            </small>
        </div>
        <div>
            {{ $employees->links() }}
        </div>
    </div>
@endif
