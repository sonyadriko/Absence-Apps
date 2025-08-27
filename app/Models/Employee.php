<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_number',
        'full_name',
        'email',
        'phone',
        'position_id',
        'hire_date',
        'status',
        'department',
        'address',
        'avatar'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'status' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}