<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'employee_shift_schedule_id',
        'date',
        'check_in',
        'check_out',
        'scheduled_start',
        'scheduled_end',
        'actual_check_in',
        'actual_check_out',
        'status',
        'late_minutes',
        'early_minutes',
        'total_work_minutes',
        'break_minutes',
        'overtime_minutes',
        'notes',
        'events_summary',
        'has_corrections',
        'correction_history',
        'last_computed_at',
        'computation_version',
        'location_data',
        'location_check_in',
        'location_check_out'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'status' => 'string',
        'events_summary' => 'array',
        'correction_history' => 'array',
        'location_data' => 'array',
        'has_corrections' => 'boolean'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}