<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="UserBreak",
 *     type="object",
 *     title="User Break",
 *     description="User break model for tracking individual breaks during shifts",
 *     @OA\Property(property="id", type="integer", description="Break ID"),
 *     @OA\Property(property="user_daily_shift_id", type="integer", description="Associated shift ID"),
 *     @OA\Property(property="break_start", type="string", format="date-time", description="Break start time"),
 *     @OA\Property(property="break_end", type="string", format="date-time", description="Break end time", nullable=true),
 *     @OA\Property(property="break_duration_mins", type="integer", description="Break duration in minutes", nullable=true),
 *     @OA\Property(property="break_type", type="string", enum={"lunch", "coffee", "personal", "other"}, description="Type of break"),
 *     @OA\Property(property="notes", type="string", description="Break notes", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Created timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Updated timestamp")
 * )
 */
class UserBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_daily_shift_id',
        'break_start',
        'break_end',
        'break_duration_mins',
        'break_type',
        'notes',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'break_duration_mins' => 'integer',
    ];

    /**
     * Get the shift that owns the break.
     */
    public function userDailyShift()
    {
        return $this->belongsTo(UserDailyShift::class);
    }

    /**
     * Get the user through the shift relationship.
     */
    public function user()
    {
        return $this->hasOneThrough(User::class, UserDailyShift::class, 'id', 'id', 'user_daily_shift_id', 'user_id');
    }

    /**
     * Check if the break is currently active (started but not ended).
     */
    public function isActive(): bool
    {
        return $this->break_start && !$this->break_end;
    }

    /**
     * Check if the break is completed.
     */
    public function isCompleted(): bool
    {
        return $this->break_start && $this->break_end;
    }

    /**
     * Calculate and update the break duration.
     */
    public function calculateDuration(): int
    {
        if (!$this->break_start || !$this->break_end) {
            return 0;
        }

        $duration = Carbon::parse($this->break_start)->diffInMinutes(Carbon::parse($this->break_end));
        $this->update(['break_duration_mins' => $duration]);
        
        return $duration;
    }

    /**
     * Scope to get only active breaks.
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('break_start')->whereNull('break_end');
    }

    /**
     * Scope to get only completed breaks.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('break_start')->whereNotNull('break_end');
    }

    /**
     * Scope to get breaks by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('break_type', $type);
    }
}