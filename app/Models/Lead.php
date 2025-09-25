<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'assigned_to',
        'last_updated_by',
        'shop_name',
        'contact_person',
        'mobile_number',
        'alternate_number',
        'email',
        'address',
        'area_locality',
        'pincode',
        'branches',
        'gps_location',
        'business_type',
        'current_system',
        'lead_status',
        'plan_interest',
        'next_follow_up_date',
        'meeting_notes',
        'completed_at',        
    ];

    protected $casts = [
        'completed_at' => 'datetime', // ✅ Makes it Carbon instance
    ];

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }
    
    public function recordedAudios()
    {
        return $this->hasMany(RecordedAudioForLead::class);
    }

    public function selfies()
    {
        return $this->hasMany(SelfieForLead::class);
    }

    public function shopPhotos()
    {
        return $this->hasMany(ShopPhotoForLead::class);
    }

    public function histories()
    {
        return $this->hasMany(LeadHistory::class);
    }

    public function followUps()
    {
        return $this->hasMany(LeadFollowUp::class);
    }

    // User relationships
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function lastUpdatedByUser()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    // Reference data relationships
    public function businessTypeData()
    {
        return $this->belongsTo(BusinessType::class, 'business_type');
    }

    public function currentSystemData()
    {
        return $this->belongsTo(CurrentSystem::class, 'current_system');
    }

    public function leadStatusData()
    {
        return $this->belongsTo(LeadStatus::class, 'lead_status');
    }    
}
