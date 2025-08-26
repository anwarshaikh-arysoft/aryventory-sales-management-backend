<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'meeting_start_time',
        'meeting_end_time',
        'meeting_start_latitude',
        'meeting_start_longitude',
        'meeting_end_latitude',
        'meeting_end_longitude',
        'meeting_end_notes',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function recordedAudios()
    {
        return $this->hasMany(RecordedAudioForMeeting::class);
    }

    public function selfies()
    {
        return $this->hasMany(SelfieForMeeting::class);
    }

    public function shopPhotos()
    {
        return $this->hasMany(ShopPhotoForMeeting::class);
    }
}
