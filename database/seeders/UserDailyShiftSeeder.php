<?php

namespace Database\Seeders;

use App\Models\UserDailyShift;
use Illuminate\Database\Seeder;

class UserDailyShiftSeeder extends Seeder
{
    public function run(): void
    {
        UserDailyShift::create([
            'user_id' => 2,
            'shift_date' => '2025-08-08',
            'shift_start' => '2025-08-08 09:00:00',
            'shift_end' => '2025-08-08 17:30:00',
            'break_start' => '2025-08-08 13:00:00',
            'break_end' => '2025-08-08 13:30:00',
            'total_break_mins' => 30,
            'notes' => 'Regular shift, on time.',
        ]);

        UserDailyShift::create([
            'user_id' => 4,
            'shift_date' => '2025-08-08',
            'shift_start' => '2025-08-08 09:15:00',
            'shift_end' => '2025-08-08 17:20:00',
            'break_start' => '2025-08-08 13:10:00',
            'break_end' => '2025-08-08 13:45:00',
            'total_break_mins' => 35,
            'notes' => 'Late arrival',
        ]);
    }
}
