<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'event_type',
        'source',
        'event_time',
        'event_date',
        'latitude',
        'longitude',
        'location_accuracy',
        'is_within_geofence',
        'location_address',
        'selfie_path',
        'selfie_verified',
        'face_confidence',
        'device_id',
        'user_agent',
        'ip_address',
        'employee_shift_schedule_slot_id',
        'metadata',
        'notes',
        'event_hash',
        'is_correction',
        'corrected_by',
        'correction_reason',
        'is_processed',
        'processed_at'
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'event_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'location_accuracy' => 'integer',
        'is_within_geofence' => 'boolean',
        'selfie_verified' => 'boolean',
        'face_confidence' => 'decimal:2',
        'metadata' => 'array',
        'is_correction' => 'boolean',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime'
    ];

    // Constants for event types
    const TYPE_CHECK_IN = 'check_in';
    const TYPE_CHECK_OUT = 'check_out';
    const TYPE_MANUAL_ADJUST = 'manual_adjust';
    const TYPE_BREAK_START = 'break_start';
    const TYPE_BREAK_END = 'break_end';

    // Constants for sources
    const SOURCE_MOBILE = 'mobile';
    const SOURCE_KIOSK = 'kiosk';
    const SOURCE_WEB = 'web';
    const SOURCE_FP_DEVICE = 'fp_device';
    const SOURCE_MANUAL = 'manual';

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employeeShiftScheduleSlot()
    {
        return $this->belongsTo(EmployeeShiftScheduleSlot::class);
    }

    public function correctedBy()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    // Scopes
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('event_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    public function scopeCheckIn($query)
    {
        return $query->where('event_type', self::TYPE_CHECK_IN);
    }

    public function scopeCheckOut($query)
    {
        return $query->where('event_type', self::TYPE_CHECK_OUT);
    }

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeWithinGeofence($query)
    {
        return $query->where('is_within_geofence', true);
    }

    public function scopeCorrections($query)
    {
        return $query->where('is_correction', true);
    }

    // Helper methods
    public function generateHash()
    {
        return md5($this->employee_id . $this->event_type . $this->event_time . $this->source);
    }

    public function isCheckIn()
    {
        return $this->event_type === self::TYPE_CHECK_IN;
    }

    public function isCheckOut()
    {
        return $this->event_type === self::TYPE_CHECK_OUT;
    }

    public function isManualAdjustment()
    {
        return $this->event_type === self::TYPE_MANUAL_ADJUST;
    }

    public function getSourcePriority()
    {
        $priorities = [
            self::SOURCE_FP_DEVICE => 1,
            self::SOURCE_KIOSK => 2,
            self::SOURCE_MOBILE => 3,
            self::SOURCE_WEB => 4,
            self::SOURCE_MANUAL => 5,
        ];
        
        return $priorities[$this->source] ?? 99;
    }

    public function getLocationString()
    {
        if ($this->latitude && $this->longitude) {
            return "Lat: {$this->latitude}, Lng: {$this->longitude}" . 
                   ($this->location_accuracy ? " (±{$this->location_accuracy}m)" : '');
        }
        
        return 'Location not available';
    }

    public function hasValidSelfie()
    {
        return $this->selfie_path && $this->selfie_verified;
    }

    // Boot method to auto-generate hash
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($event) {
            if (empty($event->event_hash)) {
                $event->event_hash = $event->generateHash();
            }
            if (empty($event->event_date)) {
                $event->event_date = Carbon::parse($event->event_time)->toDateString();
            }
        });
    }
}
