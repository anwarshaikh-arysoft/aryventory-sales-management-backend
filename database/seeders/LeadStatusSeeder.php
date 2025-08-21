<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeadStatus;
use Carbon\Carbon;

class LeadStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Interested', 'created_at' => '2025-08-07 09:30:00', 'updated_at' => '2025-08-07 09:30:00'],
            ['name' => 'Not Interested', 'created_at' => '2025-08-07 10:00:00', 'updated_at' => '2025-08-07 10:00:00'],
            ['name' => 'Visit Again', 'created_at' => '2025-08-07 09:30:00', 'updated_at' => '2025-08-07 09:30:00'],
            ['name' => 'Demo Scheduled', 'created_at' => '2025-08-07 10:00:00', 'updated_at' => '2025-08-07 10:00:00'],
            ['name' => 'Sold', 'created_at' => '2025-08-07 09:30:00', 'updated_at' => '2025-08-07 09:30:00'],
            ['name' => 'Already Using CRM', 'created_at' => '2025-08-07 10:00:00', 'updated_at' => '2025-08-07 10:00:00'],
        ];

        foreach ($statuses as $status) {
            LeadStatus::create($status);
        }
    }
}
