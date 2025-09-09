<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDailyShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_date',
        'shift_start',
        'shift_end',
        'break_start',
        'break_end',
        'total_break_mins',
        'notes',
        'shift_start_selfie_image',
        'shift_end_selfie_image',
        'shift_start_latitude',
        'shift_start_longitude',
        'shift_end_latitude',
        'shift_end_longitude',
    ];

    protected $casts = [
        'shift_start' => 'datetime',
        'shift_end' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'shift_start_latitude' => 'float',
        'shift_start_longitude' => 'float',
        'shift_end_latitude' => 'float',
        'shift_end_longitude' => 'float',
        'total_break_mins' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

