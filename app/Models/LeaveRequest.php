<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'document_path',
        'status',
        'pengelola_approved_by',
        'pengelola_approved_at',
        'pengelola_notes',
        'manager_approved_by',
        'manager_approved_at',
        'manager_notes',
        'hr_approved_by',
        'hr_approved_at',
        'hr_notes',
        'final_approved_by',
        'final_approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'time',
        'end_time' => 'time',
        'total_days' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $dates = [
        'deleted_at',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    const DURATION_FULL_DAY = 'full_day';
    const DURATION_HALF_DAY = 'half_day';
    const DURATION_HOURLY = 'hourly';

    /**
     * Get the employee who requested the leave
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
     * Get the user who requested the leave
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the leave
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the leave
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get supporting document URL
     */
    public function getSupportingDocumentUrlAttribute()
    {
        if ($this->supporting_document_path) {
            return asset('storage/' . $this->supporting_document_path);
        }
        return null;
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for cancelled requests
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope for current year requests
     */
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('start_date', now()->year);
    }

    /**
     * Scope for specific year requests
     */
    public function scopeForYear($query, $year)
    {
        return $query->whereYear('start_date', $year);
    }
}
