<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ShiftSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_shift_id',
        'name',
        'start_time',
        'end_time',
        'order',
        'duration_minutes',
        'is_overnight',
        'break_times'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'order' => 'integer',
        'duration_minutes' => 'integer',
        'is_overnight' => 'boolean',
        'break_times' => 'array'
    ];

    // Relationships
    public function workShift()
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function employeeShiftScheduleSlots()
    {
        return $this->hasMany(EmployeeShiftScheduleSlot::class);
    }

    // Helper methods
    public function calculateDurationMinutes()
    {
        $start = Carbon::createFromTimeString($this->start_time);
        $end = Carbon::createFromTimeString($this->end_time);
        
        // Handle overnight shifts
        if ($this->is_overnight && $end->lt($start)) {
            $end->addDay();
        }
        
        return $end->diffInMinutes($start);
    }

    public function getTotalBreakMinutes()
    {
        if (empty($this->break_times)) {
            return 0;
        }
        
        $totalMinutes = 0;
        foreach ($this->break_times as $break) {
            $breakStart = Carbon::createFromTimeString($break['start']);
            $breakEnd = Carbon::createFromTimeString($break['end']);
            
            // Handle overnight breaks
            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }
            
            $totalMinutes += $breakEnd->diffInMinutes($breakStart);
        }
        
        return $totalMinutes;
    }

    public function getWorkingMinutes()
    {
        return $this->duration_minutes - $this->getTotalBreakMinutes();
    }

    public function formatTimeRange()
    {
        return Carbon::parse($this->start_time)->format('H:i') . ' - ' . 
               Carbon::parse($this->end_time)->format('H:i');
    }
}
