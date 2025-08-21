<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeadHistory;
use Carbon\Carbon;

class LeadHistorySeeder extends Seeder
{
    public function run(): void
    {
        LeadHistory::create([
            'lead_id' => 1,
            'updated_by' => 2,
            'status_before' => 'Cold',
            'status_after' => 'Interested',
            'timestamp' => Carbon::create(2025, 8, 7, 9, 30),
            'notes' => 'Lead showed interest after follow-up.',
        ]);
    }
}
