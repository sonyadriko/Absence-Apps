<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'type',
        'is_recurring',
        'description'
    ];

    protected $casts = [
        'date' => 'date',
        'type' => 'string',
        'is_recurring' => 'boolean'
    ];
}