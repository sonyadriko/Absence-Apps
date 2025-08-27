<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'total_hours',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'total_hours' => 'integer',
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    const TYPE_SINGLE = 'single';
    const TYPE_SPLIT = 'split';

    // Relationships
    public function shiftSlots()
    {
        return $this->hasMany(ShiftSlot::class)->orderBy('order');
    }

    public function employeeShiftSchedules()
    {
        return $this->hasMany(EmployeeShiftSchedule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSingle($query)
    {
        return $query->where('type', self::TYPE_SINGLE);
    }

    public function scopeSplit($query)
    {
        return $query->where('type', self::TYPE_SPLIT);
    }

    // Helper methods
    public function isSplit()
    {
        return $this->type === self::TYPE_SPLIT;
    }

    public function getTotalDurationMinutes()
    {
        return $this->shiftSlots->sum('duration_minutes');
    }

    public function getTimeRange()
    {
        $slots = $this->shiftSlots;
        if ($slots->isEmpty()) {
            return null;
        }

        $firstSlot = $slots->first();
        $lastSlot = $slots->last();

        return [
            'start' => $firstSlot->start_time,
            'end' => $lastSlot->end_time
        ];
    }
}
