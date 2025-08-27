<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'latitude',
        'longitude',
        'radius',
        'timezone',
        'phone',
        'is_active',
        'operating_hours',
        'settings'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius' => 'integer',
        'is_active' => 'boolean',
        'operating_hours' => 'array',
        'settings' => 'array'
    ];

    // Relationships
    public function employees()
    {
        return $this->hasMany(Employee::class, 'primary_branch_id');
    }

    public function attendanceEvents()
    {
        return $this->hasMany(AttendanceEvent::class);
    }

    public function employeeShiftSchedules()
    {
        return $this->hasMany(EmployeeShiftSchedule::class);
    }

    public function managerMaps()
    {
        return $this->hasMany(ManagerBranchMap::class);
    }

    public function pengelolaMaps()
    {
        return $this->hasMany(PengelolaBranchMap::class);
    }

    // Helper methods
    public function isWithinGeofence($latitude, $longitude)
    {
        $earthRadius = 6371000; // meters
        $lat1 = deg2rad($this->latitude);
        $lng1 = deg2rad($this->longitude);
        $lat2 = deg2rad($latitude);
        $lng2 = deg2rad($longitude);
        
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        
        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlng/2) * sin($dlng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return $distance <= $this->radius;
    }

    public function getActiveManagers()
    {
        return $this->managerMaps()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', now())
            ->where(function($query) {
                $query->whereNull('effective_until')
                      ->orWhereDate('effective_until', '>=', now());
            })
            ->with('user')
            ->get();
    }

    public function getActivePengelolas()
    {
        return $this->pengelolaMaps()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', now())
            ->where(function($query) {
                $query->whereNull('effective_until')
                      ->orWhereDate('effective_until', '>=', now());
            })
            ->with('user')
            ->get();
    }
}
