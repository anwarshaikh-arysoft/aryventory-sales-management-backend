<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Lead",
 *     type="object",
 *     title="Lead",
 *     description="Lead model representing a potential customer",
 *     @OA\Property(property="id", type="integer", description="Lead ID"),
 *     @OA\Property(property="created_by", type="integer", description="User ID who created the lead"),
 *     @OA\Property(property="assigned_to", type="integer", description="User ID assigned to the lead"),
 *     @OA\Property(property="last_updated_by", type="integer", description="User ID who last updated the lead"),
 *     @OA\Property(property="shop_name", type="string", description="Name of the shop/business"),
 *     @OA\Property(property="contact_person", type="string", description="Contact person name"),
 *     @OA\Property(property="mobile_number", type="string", description="Primary mobile number"),
 *     @OA\Property(property="alternate_number", type="string", description="Alternate mobile number", nullable=true),
 *     @OA\Property(property="email", type="string", format="email", description="Email address", nullable=true),
 *     @OA\Property(property="address", type="string", description="Business address"),
 *     @OA\Property(property="area_locality", type="string", description="Area or locality"),
 *     @OA\Property(property="pincode", type="string", description="Pincode"),
 *     @OA\Property(property="branches", type="integer", description="Number of branches", nullable=true),
 *     @OA\Property(property="gps_location", type="string", description="GPS coordinates", nullable=true),
 *     @OA\Property(property="business_type", type="integer", description="Business type ID"),
 *     @OA\Property(property="current_system", type="integer", description="Current system ID"),
 *     @OA\Property(property="lead_status", type="integer", description="Lead status ID"),
 *     @OA\Property(property="plan_interest", type="string", description="Plan of interest"),
 *     @OA\Property(property="next_follow_up_date", type="string", format="date", description="Next follow-up date", nullable=true),
 *     @OA\Property(property="meeting_notes", type="string", description="Meeting notes", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", description="Completion timestamp", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Created timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Updated timestamp"),
 *     @OA\Property(property="created_by_user", type="object", ref="#/components/schemas/User", description="User who created the lead"),
 *     @OA\Property(property="assigned_to_user", type="object", ref="#/components/schemas/User", description="User assigned to the lead"),
 *     @OA\Property(property="last_updated_by_user", type="object", ref="#/components/schemas/User", description="User who last updated the lead"),
 *     @OA\Property(property="business_type_data", type="object", description="Business type details"),
 *     @OA\Property(property="current_system_data", type="object", description="Current system details"),
 *     @OA\Property(property="lead_status_data", type="object", description="Lead status details")
 * )
 */
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
