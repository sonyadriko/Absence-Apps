<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'day',
        'start_time',
        'end_time',
        'break_duration',
        'is_working_day'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_working_day' => 'boolean',
        'day' => 'string'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}