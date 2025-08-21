<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelfieForLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'media',
    ];

    /**
     * Get the lead this selfie belongs to.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
