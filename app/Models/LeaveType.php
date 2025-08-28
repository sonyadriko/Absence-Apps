<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'requires_approval',
        'requires_document',
        'max_days_per_year',
        'is_paid',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'requires_document' => 'boolean',
        'max_days_per_year' => 'integer',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get leave requests for this type
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get leave balances for this type
     */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
