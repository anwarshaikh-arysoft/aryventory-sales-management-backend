<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopPhotoForMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'media',
    ];

    /**
     * Get the meeting associated with this shop photo.
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
