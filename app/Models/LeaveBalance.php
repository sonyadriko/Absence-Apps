<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'carry_forward_days',
        'remaining_days',
        'expires_at',
    ];

    protected $casts = [
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'carry_forward_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
        'expires_at' => 'date',
    ];

    /**
     * Get the employee who owns this balance
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave type
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Scope for current year balances
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Scope for specific year balances
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Update remaining days calculation
     */
    public function updateRemainingDays()
    {
        $this->remaining_days = $this->allocated_days + $this->carry_forward_days - $this->used_days;
        $this->save();
    }

    /**
     * Check if balance has enough days
     */
    public function hasEnoughDays($requestedDays)
    {
        return $this->remaining_days >= $requestedDays;
    }

    /**
     * Deduct days from balance
     */
    public function deductDays($days)
    {
        $this->used_days += $days;
        $this->updateRemainingDays();
    }

    /**
     * Add days back to balance (for cancelled leaves)
     */
    public function addDays($days)
    {
        $this->used_days = max(0, $this->used_days - $days);
        $this->updateRemainingDays();
    }
}
