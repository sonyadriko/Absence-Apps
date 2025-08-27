<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'late_minutes',
        'early_minutes',
        'notes',
        'location_check_in',
        'location_check_out'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
        'status' => 'string'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}