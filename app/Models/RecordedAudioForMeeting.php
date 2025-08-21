<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordedAudioForMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'media',
    ];

    /**
     * Get the meeting this audio belongs to.
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
