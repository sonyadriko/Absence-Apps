<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'work_shift_id',
        'effective_date',
        'end_date',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'notes'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'monday' => 'boolean',
        'tuesday' => 'boolean',
        'wednesday' => 'boolean',
        'thursday' => 'boolean',
        'friday' => 'boolean',
        'saturday' => 'boolean',
        'sunday' => 'boolean'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function workShift()
    {
        return $this->belongsTo(WorkShift::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('effective_date', '<=', Carbon::now())
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', Carbon::now());
                     });
    }

    public function scopeForDate($query, $date)
    {
        $date = Carbon::parse($date);
        return $query->where('effective_date', '<=', $date)
                     ->where(function ($q) use ($date) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', $date);
                     });
    }

    // Helpers
    public function isActiveOn($date)
    {
        $date = Carbon::parse($date);
        $dayOfWeek = strtolower($date->format('l'));
        
        // Check if schedule is active on this date
        if ($date->lt($this->effective_date)) {
            return false;
        }
        
        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }
        
        // Check if employee works on this day of week
        return $this->$dayOfWeek;
    }

    public function getWorkingDaysAttribute()
    {
        $days = [];
        $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($weekDays as $day) {
            if ($this->$day) {
                $days[] = ucfirst($day);
            }
        }
        
        return $days;
    }

    public function getWorkingDaysCountAttribute()
    {
        return count($this->working_days);
    }
}
