<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RecordedAudioForLead;
use Carbon\Carbon;

class RecordedAudioForLeadSeeder extends Seeder
{
    public function run(): void
    {
        RecordedAudioForLead::create([
            'lead_id' => 1,
            'media' => 's3://sjdklfjsdlk.3gp',
            'created_at' => Carbon::create(2025, 8, 7, 9, 30),
            'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
        ]);
    }
}
