<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShopPhotoForMeeting;
use Carbon\Carbon;

class ShopPhotoForMeetingSeeder extends Seeder
{
    public function run(): void
    {
        ShopPhotoForMeeting::create([
            'meeting_id' => 1,
            'media' => 's3://sjdklfjsdlk.jpg',
            'created_at' => Carbon::create(2025, 8, 7, 9, 30),
            'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
        ]);
    }
}
