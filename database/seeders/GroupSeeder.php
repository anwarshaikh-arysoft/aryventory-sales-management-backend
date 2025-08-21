<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use Carbon\Carbon;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        Group::insert([
            [
                'name' => 'Malad',
                'created_at' => Carbon::create(2025, 8, 7, 9, 30),
                'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
            ],
            [
                'name' => 'Goregaon',
                'created_at' => Carbon::create(2025, 8, 7, 10, 00),
                'updated_at' => Carbon::create(2025, 8, 7, 10, 00),
            ],
            [
                'name' => 'Jogeshwari',
                'created_at' => Carbon::create(2025, 8, 7, 9, 30),
                'updated_at' => Carbon::create(2025, 8, 7, 9, 30),
            ],
            [
                'name' => 'Andheri',
                'created_at' => Carbon::create(2025, 8, 7, 10, 00),
                'updated_at' => Carbon::create(2025, 8, 7, 10, 00),
            ],
            [
                'name' => 'Santacruz',
                'created_at' => Carbon::create(2025, 8, 7, 10, 00),
                'updated_at' => Carbon::create(2025, 8, 7, 10, 00),
            ],
        ]);
    }
}
