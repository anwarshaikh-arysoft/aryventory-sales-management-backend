<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Preference;

class PreferenceSeeder extends Seeder
{
    public function run(): void
    {
        Preference::insert([
            ['name' => 'Location Tracking', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Push Notification', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Auto Sync', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
