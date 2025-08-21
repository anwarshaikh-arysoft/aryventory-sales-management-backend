<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelfieForMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'media',
    ];

    /**
     * Get the meeting that owns this selfie.
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
