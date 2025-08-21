<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessType;
use Carbon\Carbon;

class BusinessTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Mobile Shop', 'created_at' => Carbon::create(2025, 8, 7, 9, 30)],
            ['name' => 'Electronics Store', 'created_at' => Carbon::create(2025, 8, 7, 10, 0)],
            ['name' => 'Computer Shop', 'created_at' => Carbon::create(2025, 8, 7, 9, 30)],
            ['name' => 'Accessories Shop', 'created_at' => Carbon::create(2025, 8, 7, 10, 0)],
            ['name' => 'Repair Center', 'created_at' => Carbon::create(2025, 8, 7, 9, 30)],
            ['name' => 'Other', 'created_at' => Carbon::create(2025, 8, 7, 10, 0)],
        ];

        foreach ($data as &$item) {
            $item['updated_at'] = $item['created_at'];
        }

        BusinessType::insert($data);
    }
}

