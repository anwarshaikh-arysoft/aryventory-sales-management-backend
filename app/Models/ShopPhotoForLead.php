<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopPhotoForLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'media',
    ];

    /**
     * Get the lead this shop photo belongs to.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
