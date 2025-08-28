<!-- Basic Information -->
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="code" class="form-label">Branch Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="code" name="code" 
                   placeholder="e.g., CFE001" 
                   value="{{ isset($branch) ? $branch->code : old('code') }}" required>
            <div class="form-text">Unique identifier for the branch</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" 
                   placeholder="e.g., Sudirman Branch" 
                   value="{{ isset($branch) ? $branch->name : old('name') }}" required>
        </div>
    </div>
</div>

<div class="mb-3">
    <label for="address" class="form-label">Address</label>
    <textarea class="form-control" id="address" name="address" rows="2" 
              placeholder="Enter branch address">{{ isset($branch) ? $branch->address : old('address') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone" 
                   placeholder="e.g., +62 21 1234567" 
                   value="{{ isset($branch) ? $branch->phone : old('phone') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="timezone" class="form-label">Timezone <span class="text-danger">*</span></label>
            <select class="form-select" id="timezone" name="timezone" required>
                <option value="Asia/Jakarta" {{ (isset($branch) ? $branch->timezone : old('timezone', 'Asia/Jakarta')) == 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB)</option>
                <option value="Asia/Makassar" {{ (isset($branch) ? $branch->timezone : old('timezone')) == 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                <option value="Asia/Jayapura" {{ (isset($branch) ? $branch->timezone : old('timezone')) == 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
            </select>
        </div>
    </div>
</div>

<!-- Location Settings -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location & Geofence</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="latitude" class="form-label">Latitude <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="latitude" name="latitude" 
                           step="0.00000001" min="-90" max="90" 
                           placeholder="e.g., -6.200000" 
                           value="{{ isset($branch) ? $branch->latitude : old('latitude') }}" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="longitude" class="form-label">Longitude <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="longitude" name="longitude" 
                           step="0.00000001" min="-180" max="180" 
                           placeholder="e.g., 106.816666" 
                           value="{{ isset($branch) ? $branch->longitude : old('longitude') }}" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="radius" class="form-label">Geofence Radius (meters) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="radius" name="radius" 
                           min="10" max="1000" 
                           placeholder="100" 
                           value="{{ isset($branch) ? $branch->radius : old('radius', 100) }}" required>
                    <div class="form-text">Attendance check-in radius in meters</div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Tip:</strong> You can use Google Maps to get precise coordinates. Right-click on the location and select "What's here?"
        </div>
    </div>
</div>

<!-- Operating Hours -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Operating Hours</h6>
    </div>
    <div class="card-body">
        @php
            $days = [
                'monday' => 'Monday',
                'tuesday' => 'Tuesday', 
                'wednesday' => 'Wednesday',
                'thursday' => 'Thursday',
                'friday' => 'Friday',
                'saturday' => 'Saturday',
                'sunday' => 'Sunday'
            ];
            $operatingHours = isset($branch) ? $branch->operating_hours : [];
        @endphp
        
        @foreach($days as $key => $day)
            <div class="row mb-2">
                <div class="col-md-2">
                    <strong>{{ $day }}</strong>
                </div>
                <div class="col-md-3">
                    <input type="time" class="form-control" 
                           name="operating_hours[{{ $key }}][open]" 
                           value="{{ $operatingHours[$key]['open'] ?? '08:00' }}"
                           {{ isset($operatingHours[$key]['is_closed']) && $operatingHours[$key]['is_closed'] ? 'disabled' : '' }}>
                </div>
                <div class="col-md-1 text-center">to</div>
                <div class="col-md-3">
                    <input type="time" class="form-control" 
                           name="operating_hours[{{ $key }}][close]" 
                           value="{{ $operatingHours[$key]['close'] ?? '22:00' }}"
                           {{ isset($operatingHours[$key]['is_closed']) && $operatingHours[$key]['is_closed'] ? 'disabled' : '' }}>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="operating_hours[{{ $key }}][is_closed]" 
                               id="closed_{{ $key }}" value="1"
                               onchange="toggleDayInputs('{{ $key }}')"
                               {{ isset($operatingHours[$key]['is_closed']) && $operatingHours[$key]['is_closed'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="closed_{{ $key }}">
                            Closed
                        </label>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Status -->
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                       {{ isset($branch) ? ($branch->is_active ? 'checked' : '') : 'checked' }}>
                <label class="form-check-label" for="is_active">
                    Branch is Active
                </label>
            </div>
            <div class="form-text">Inactive branches cannot be used for attendance</div>
        </div>
    </div>
</div>

<script>
function toggleDayInputs(day) {
    const checkbox = document.getElementById(`closed_${day}`);
    const openInput = document.querySelector(`input[name="operating_hours[${day}][open]"]`);
    const closeInput = document.querySelector(`input[name="operating_hours[${day}][close]"]`);
    
    if (checkbox.checked) {
        openInput.disabled = true;
        closeInput.disabled = true;
        openInput.value = '';
        closeInput.value = '';
    } else {
        openInput.disabled = false;
        closeInput.disabled = false;
        openInput.value = '08:00';
        closeInput.value = '22:00';
    }
}

// Auto-generate branch code based on name
document.getElementById('name').addEventListener('input', function() {
    if (!document.getElementById('code').value) {
        const name = this.value;
        const code = name.toUpperCase()
                        .replace(/[^A-Z0-9]/g, '')
                        .substring(0, 6);
        if (code) {
            document.getElementById('code').value = `CFE${code}`;
        }
    }
});
</script>
