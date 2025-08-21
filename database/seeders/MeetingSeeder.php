<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        Meeting::create([
            'lead_id' => 1,
            'meeting_start_time' => '2025-08-07 09:30:00',
            'meeting_end_time' => '2025-08-07 09:30:00',
            'created_at' => '2025-08-07 09:30:00',
            'updated_at' => '2025-08-07 09:30:00',
        ]);
    }
}
