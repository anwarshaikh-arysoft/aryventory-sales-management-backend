<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Target;
use Carbon\Carbon;

class TargetSeeder extends Seeder
{
    public function run(): void
    {
        Target::create([
            'user_id' => 1,
            'daily_meeting_targets' => 15,
            'closure_target' => 5,
            'revenue_targets' => 15000,
            'created_at' => Carbon::create(2025, 8, 7, 9, 30),
            'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
        ]);
    }
}
