<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordedAudioForLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'media',
    ];

    /**
     * Get the lead associated with this recorded audio.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
