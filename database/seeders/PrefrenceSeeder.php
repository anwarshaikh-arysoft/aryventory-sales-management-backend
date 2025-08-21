<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prefrence;
use Carbon\Carbon;

class PrefrenceSeeder extends Seeder
{
    public function run(): void
    {
        Prefrence::insert([
            [
                'user_id'    => null,
                'name'       => 'Location Tracking',
                'status'     => true,
                'created_at' => Carbon::create(2025, 8, 7, 9, 30),
                'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
            ],
            [
                'user_id'    => null,
                'name'       => 'Push Notification',
                'status'     => true,
                'created_at' => Carbon::create(2025, 8, 7, 10, 0),
                'updated_at' => Carbon::create(2025, 8, 7, 10, 0),
            ],
            [
                'user_id'    => null,
                'name'       => 'Auto Sync',
                'status'     => true,
                'created_at' => Carbon::create(2025, 8, 7, 9, 30),
                'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
            ],
        ]);
    }
}
