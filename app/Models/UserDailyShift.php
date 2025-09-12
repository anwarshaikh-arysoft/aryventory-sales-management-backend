<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="UserDailyShift",
 *     type="object",
 *     title="User Daily Shift",
 *     description="User daily shift model",
 *     @OA\Property(property="id", type="integer", description="Shift ID"),
 *     @OA\Property(property="user_id", type="integer", description="User ID"),
 *     @OA\Property(property="shift_date", type="string", format="date", description="Shift date"),
 *     @OA\Property(property="shift_start", type="string", format="date-time", description="Shift start time"),
 *     @OA\Property(property="shift_end", type="string", format="date-time", description="Shift end time", nullable=true),
 *     @OA\Property(property="break_start", type="string", format="date-time", description="Break start time", nullable=true),
 *     @OA\Property(property="break_end", type="string", format="date-time", description="Break end time", nullable=true),
 *     @OA\Property(property="total_break_mins", type="integer", description="Total break minutes", nullable=true),
 *     @OA\Property(property="notes", type="string", description="Shift notes", nullable=true),
 *     @OA\Property(property="shift_start_selfie_image", type="string", description="Start shift selfie image URL", nullable=true),
 *     @OA\Property(property="shift_end_selfie_image", type="string", description="End shift selfie image URL", nullable=true),
 *     @OA\Property(property="shift_start_latitude", type="number", format="float", description="Start location latitude", nullable=true),
 *     @OA\Property(property="shift_start_longitude", type="number", format="float", description="Start location longitude", nullable=true),
 *     @OA\Property(property="shift_end_latitude", type="number", format="float", description="End location latitude", nullable=true),
 *     @OA\Property(property="shift_end_longitude", type="number", format="float", description="End location longitude", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Created timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Updated timestamp")
 * )
 */
class UserDailyShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_date',
        'shift_start',
        'shift_end',
        'break_start',
        'break_end',
        'total_break_mins',
        'notes',
        'shift_start_selfie_image',
        'shift_end_selfie_image',
        'shift_start_latitude',
        'shift_start_longitude',
        'shift_end_latitude',
        'shift_end_longitude',
    ];

    protected $casts = [
        'shift_start' => 'datetime',
        'shift_end' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'shift_start_latitude' => 'float',
        'shift_start_longitude' => 'float',
        'shift_end_latitude' => 'float',
        'shift_end_longitude' => 'float',
        'total_break_mins' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all breaks for this shift.
     */
    public function breaks()
    {
        return $this->hasMany(UserBreak::class);
    }

    /**
     * Get the active break (if any).
     */
    public function activeBreak()
    {
        return $this->hasOne(UserBreak::class)->whereNotNull('break_start')->whereNull('break_end');
    }

    /**
     * Start the a new break
     * 
     */
    public function startNewBreak()
    {
        $this->breaks()->create(['break_start' => now()]);
        return $this->activeBreak()->first();
    }

    /** 
     * End the active break and calculate the break duration
     * 
     */
    public function endActiveBreak()
    {
        $activeBreak = $this->activeBreak()->first();

        if ($activeBreak) {
            $activeBreak->break_end = now();
            $activeBreak->break_duration_mins = $activeBreak->break_start->diffInMinutes(now());
            $activeBreak->save();
        }

        return $activeBreak;
    }

    /**
     * Get all completed breaks for this shift.
     */
    public function completedBreaks()
    {
        return $this->hasMany(UserBreak::class)->whereNotNull('break_start')->whereNotNull('break_end');
    }

    /**
     * Calculate total break time from all breaks.
     */
    public function calculateTotalBreakTime(): float
    {
        return $this->completedBreaks()->sum('break_duration_mins');
    }    

    /**
     * Get total break time (either from completed breaks or legacy total_break_mins).
     */
    public function getTotalBreakTimeAttribute(): int
    {
        $completedBreaksTime = $this->calculateTotalBreakTime();

        // If we have completed breaks, use that total
        if ($completedBreaksTime > 0) {
            return $completedBreaksTime;
        }

        // Fallback to legacy total_break_mins for backward compatibility
        return $this->total_break_mins ?? 0;
    }

    /**
     * Check if user is currently on a break.
     */
    public function isOnBreak(): bool
    {
        return $this->activeBreak()->exists();
    }

    /**
     * Get the current active break.
     */
    public function getCurrentBreak()
    {
        return $this->activeBreak()->first();
    }
}
