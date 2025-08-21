<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RecordedAudioForMeeting;
use Carbon\Carbon;

class RecordedAudioForMeetingSeeder extends Seeder
{
    public function run(): void
    {
        RecordedAudioForMeeting::create([
            'meeting_id' => 1,
            'media' => 's3://sjdklfjsdlk.3gp',
            'created_at' => Carbon::create(2025, 8, 7, 9, 30),
            'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
        ]);
    }
}
