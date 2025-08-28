<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_number',
        'full_name',
        'email',
        'phone',
        'position_id',
        'primary_branch_id',
        'hire_date',
        'status',
        'employment_type',
        'hourly_rate',
        'department',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'avatar',
        'face_encoding',
        'allowed_branches',
        'settings',
        'last_attendance_sync'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'status' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function primaryBranch()
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }
}
