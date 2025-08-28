<div class="row g-3">
    <!-- Basic Information -->
    <div class="col-md-6">
        <label for="employee_number" class="form-label">Employee Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="employee_number" name="employee_number" 
               value="{{ isset($employee) ? $employee->employee_number : '' }}" required>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-6">
        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="full_name" name="full_name" 
               value="{{ isset($employee) ? $employee->full_name : '' }}" required>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-6">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email" 
               value="{{ isset($employee) ? $employee->email : '' }}" required>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-6">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" 
               value="{{ isset($employee) ? $employee->phone : '' }}">
        <div class="invalid-feedback"></div>
    </div>
    
    <!-- Position and Branch -->
    <div class="col-md-6">
        <label for="position_id" class="form-label">Position <span class="text-danger">*</span></label>
        <select class="form-select" id="position_id" name="position_id" required>
            <option value="">Select Position</option>
            @foreach($positions as $position)
                <option value="{{ $position->id }}" 
                        {{ (isset($employee) && $employee->position_id == $position->id) ? 'selected' : '' }}>
                    {{ $position->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-6">
        <label for="primary_branch_id" class="form-label">Primary Branch <span class="text-danger">*</span></label>
        <select class="form-select" id="primary_branch_id" name="primary_branch_id" required>
            <option value="">Select Branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" 
                        {{ (isset($employee) && $employee->primary_branch_id == $branch->id) ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>
    
    <!-- Employment Details -->
    <div class="col-md-6">
        <label for="hire_date" class="form-label">Hire Date <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="hire_date" name="hire_date" 
               value="{{ isset($employee) ? $employee->hire_date?->format('Y-m-d') : '' }}" required>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-6">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select" id="status" name="status" required>
            <option value="">Select Status</option>
            <option value="active" {{ (isset($employee) && $employee->status == 'active') ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ (isset($employee) && $employee->status == 'inactive') ? 'selected' : '' }}>Inactive</option>
            <option value="terminated" {{ (isset($employee) && $employee->status == 'terminated') ? 'selected' : '' }}>Terminated</option>
        </select>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-6">
        <label for="employment_type" class="form-label">Employment Type <span class="text-danger">*</span></label>
        <select class="form-select" id="employment_type" name="employment_type" required>
            <option value="">Select Type</option>
            <option value="full_time" {{ (isset($employee) && $employee->employment_type == 'full_time') ? 'selected' : '' }}>Full Time</option>
            <option value="part_time" {{ (isset($employee) && $employee->employment_type == 'part_time') ? 'selected' : '' }}>Part Time</option>
            <option value="contract" {{ (isset($employee) && $employee->employment_type == 'contract') ? 'selected' : '' }}>Contract</option>
            <option value="intern" {{ (isset($employee) && $employee->employment_type == 'intern') ? 'selected' : '' }}>Intern</option>
        </select>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-6">
        <label for="hourly_rate" class="form-label">Hourly Rate</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" 
                   step="0.01" min="0" value="{{ isset($employee) ? $employee->hourly_rate : '' }}">
        </div>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-12">
        <label for="department" class="form-label">Department</label>
        <input type="text" class="form-control" id="department" name="department" 
               value="{{ isset($employee) ? $employee->department : '' }}">
        <div class="invalid-feedback"></div>
    </div>
    
    <!-- Address -->
    <div class="col-md-12">
        <label for="address" class="form-label">Address</label>
        <textarea class="form-control" id="address" name="address" rows="2">{{ isset($employee) ? $employee->address : '' }}</textarea>
        <div class="invalid-feedback"></div>
    </div>
    
    <!-- Emergency Contact -->
    <div class="col-md-6">
        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
               value="{{ isset($employee) ? $employee->emergency_contact_name : '' }}">
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="col-md-6">
        <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
        <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
               value="{{ isset($employee) ? $employee->emergency_contact_phone : '' }}">
        <div class="invalid-feedback"></div>
    </div>
</div>
