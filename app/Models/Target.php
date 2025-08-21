<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_meeting_targets',
        'closure_target',
        'revenue_targets',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
