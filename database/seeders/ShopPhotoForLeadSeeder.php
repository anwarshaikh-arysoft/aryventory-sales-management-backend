<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShopPhotoForLead;
use Carbon\Carbon;

class ShopPhotoForLeadSeeder extends Seeder
{
    public function run(): void
    {
        ShopPhotoForLead::create([
            'lead_id' => 1,
            'media' => 's3://sjdklfjsdlk.jpg',
            'created_at' => Carbon::create(2025, 8, 7, 9, 30),
            'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
        ]);
    }
}
