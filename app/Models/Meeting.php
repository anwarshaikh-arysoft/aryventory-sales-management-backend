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
