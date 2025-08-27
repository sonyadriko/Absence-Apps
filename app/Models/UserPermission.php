<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_id',
        'type',
        'scope_data',
        'effective_from',
        'effective_until',
        'is_active',
        'granted_by',
        'reason'
    ];

    protected $casts = [
        'scope_data' => 'array',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean'
    ];

    const TYPE_GRANT = 'grant';
    const TYPE_DENY = 'deny';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->whereDate('effective_from', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('effective_until')
                           ->orWhereDate('effective_until', '>=', now());
                     });
    }

    public function scopeGrant($query)
    {
        return $query->where('type', self::TYPE_GRANT);
    }

    public function scopeDeny($query)
    {
        return $query->where('type', self::TYPE_DENY);
    }

    // Helper methods
    public function isGrant()
    {
        return $this->type === self::TYPE_GRANT;
    }

    public function isDeny()
    {
        return $this->type === self::TYPE_DENY;
    }

    public function isEffective($date = null)
    {
        $checkDate = $date ? $date : now();
        
        return $this->is_active && 
               $this->effective_from <= $checkDate &&
               (is_null($this->effective_until) || $this->effective_until >= $checkDate);
    }
}
