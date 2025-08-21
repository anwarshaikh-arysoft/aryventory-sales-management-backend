<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MonthlySalesVolume;
use Carbon\Carbon;

class MonthlySalesVolumeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['volume' => '₹0 - ₹50,000',         'created_at' => Carbon::create(2025, 8, 7, 9, 30)],
            ['volume' => '₹50,000 - ₹1,00,000',  'created_at' => Carbon::create(2025, 8, 7, 10, 00)],
            ['volume' => '₹1,00,000 - ₹5,00,000','created_at' => Carbon::create(2025, 8, 7, 9, 30)],
            ['volume' => '₹5,00,000 - ₹10,00,000','created_at' => Carbon::create(2025, 8, 7, 10, 00)],
            ['volume' => '₹10,00,000+',          'created_at' => Carbon::create(2025, 8, 7, 9, 30)],
        ];

        foreach ($data as &$item) {
            $item['updated_at'] = $item['created_at'];
        }

        MonthlySalesVolume::insert($data);
    }
}
