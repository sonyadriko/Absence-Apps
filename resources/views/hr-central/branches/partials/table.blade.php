@if($branches->count() > 0)
    <table class="table table-hover" id="branchesDataTable">
        <thead>
            <tr>
                <th>Branch</th>
                <th>Address</th>
                <th>Location</th>
                <th>Status</th>
                <th>Employees</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($branches as $branch)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="branch-icon me-2">
                                <i class="fas fa-store text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $branch->name }}</div>
                                <div class="small text-muted">{{ $branch->code }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small">
                            {{ Str::limit($branch->address ?? 'No address', 50) }}
                            @if($branch->phone)
                                <br><i class="fas fa-phone me-1"></i>{{ $branch->phone }}
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="small">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            {{ $branch->latitude }}, {{ $branch->longitude }}
                            <br>
                            <i class="fas fa-circle me-1"></i>{{ $branch->radius }}m radius
                        </div>
                    </td>
                    <td>
                        @if($branch->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-info">{{ $branch->employees_count ?? 0 }} employees</span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('hr-central.branches.show', $branch->id) }}" class="btn btn-outline-info" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-outline-warning" onclick="editBranch({{ $branch->id }})" title="Edit Branch">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-{{ $branch->is_active ? 'secondary' : 'success' }}" 
                                    onclick="toggleBranchStatus({{ $branch->id }})" 
                                    title="{{ $branch->is_active ? 'Deactivate' : 'Activate' }} Branch">
                                <i class="fas fa-{{ $branch->is_active ? 'pause' : 'play' }}"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="fas fa-store fa-3x text-muted"></i>
        </div>
        <h5 class="text-muted">No branches found</h5>
        <p class="text-muted">Get started by creating your first branch.</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBranchModal">
            <i class="fas fa-plus me-2"></i>Add New Branch
        </button>
    </div>
@endif
