<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeadFollowUp;
use Carbon\Carbon;

class LeadFollowUpSeeder extends Seeder
{
    public function run(): void
    {
        LeadFollowUp::create([
            'lead_id' => 1,
            'followup_date' => Carbon::create(2025, 8, 10, 14, 0), // 2:00 PM
            'user_id' => 2,
            'notes' => 'Asked to call after lunch',
            'status' => 'Interested',
            'created_at' => Carbon::create(2025, 8, 7, 12, 15),
        ]);

        LeadFollowUp::create([
            'lead_id' => 1,
            'followup_date' => Carbon::create(2025, 8, 15, 10, 30), // 10:30 AM
            'user_id' => 2,
            'notes' => 'Waiting for price confirmation',
            'status' => 'Follow-up',
            'created_at' => Carbon::create(2025, 8, 10, 11, 2),
        ]);
    }
}
