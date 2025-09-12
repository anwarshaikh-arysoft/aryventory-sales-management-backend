<?php

namespace Database\Seeders;

use App\Models\UserDailyShift;
use App\Models\UserBreak;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserDailyShiftSeeder extends Seeder
{
    public function run(): void
    {
        // Create first shift with multiple breaks
        $shift1 = UserDailyShift::create([
            'user_id' => 2,
            'shift_date' => '2025-08-08',
            'shift_start' => '2025-08-08 09:00:00',
            'shift_end' => '2025-08-08 17:30:00',
            'notes' => 'Regular shift, on time.',
        ]);

        // Add multiple breaks for the first shift
        UserBreak::create([
            'user_daily_shift_id' => $shift1->id,
            'break_start' => '2025-08-08 10:30:00',
            'break_end' => '2025-08-08 10:45:00',
            'break_duration_mins' => 15,
            'break_type' => 'coffee',
            'notes' => 'Morning coffee break',
        ]);

        UserBreak::create([
            'user_daily_shift_id' => $shift1->id,
            'break_start' => '2025-08-08 13:00:00',
            'break_end' => '2025-08-08 13:30:00',
            'break_duration_mins' => 30,
            'break_type' => 'lunch',
            'notes' => 'Lunch break',
        ]);

        UserBreak::create([
            'user_daily_shift_id' => $shift1->id,
            'break_start' => '2025-08-08 15:30:00',
            'break_end' => '2025-08-08 15:40:00',
            'break_duration_mins' => 10,
            'break_type' => 'personal',
            'notes' => 'Personal break',
        ]);

        // Update total break time for the shift
        $shift1->update(['total_break_mins' => 55]); // 15 + 30 + 10

        // Create second shift with different break pattern
        $shift2 = UserDailyShift::create([
            'user_id' => 4,
            'shift_date' => '2025-08-08',
            'shift_start' => '2025-08-08 09:15:00',
            'shift_end' => '2025-08-08 17:20:00',
            'notes' => 'Late arrival',
        ]);

        // Add breaks for the second shift
        UserBreak::create([
            'user_daily_shift_id' => $shift2->id,
            'break_start' => '2025-08-08 12:00:00',
            'break_end' => '2025-08-08 12:15:00',
            'break_duration_mins' => 15,
            'break_type' => 'coffee',
            'notes' => 'Coffee break',
        ]);

        UserBreak::create([
            'user_daily_shift_id' => $shift2->id,
            'break_start' => '2025-08-08 13:10:00',
            'break_end' => '2025-08-08 13:45:00',
            'break_duration_mins' => 35,
            'break_type' => 'lunch',
            'notes' => 'Extended lunch break',
        ]);

        // Update total break time for the shift
        $shift2->update(['total_break_mins' => 50]); // 15 + 35

        // Create a shift with an active break (for testing)
        $shift3 = UserDailyShift::create([
            'user_id' => 3,
            'shift_date' => Carbon::today()->toDateString(),
            'shift_start' => Carbon::today()->setTime(9, 0),
            'notes' => 'Current shift with active break',
        ]);

        // Add completed break
        UserBreak::create([
            'user_daily_shift_id' => $shift3->id,
            'break_start' => Carbon::today()->setTime(10, 30),
            'break_end' => Carbon::today()->setTime(10, 45),
            'break_duration_mins' => 15,
            'break_type' => 'coffee',
            'notes' => 'Completed coffee break',
        ]);

        // Add active break (no end time)
        UserBreak::create([
            'user_daily_shift_id' => $shift3->id,
            'break_start' => Carbon::now()->subMinutes(10),
            'break_type' => 'personal',
            'notes' => 'Currently on break',
        ]);

        // Update total break time for the shift (only completed breaks)
        $shift3->update(['total_break_mins' => 15]);
    }
}
