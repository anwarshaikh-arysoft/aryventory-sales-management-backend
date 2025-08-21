<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CurrentSystem;
use Carbon\Carbon;

class CurrentSystemSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Manual/Register',
                'created_at' => Carbon::createFromFormat('d/m/Y H:i', '8/7/2025 9:30'),
                'updated_at' => Carbon::createFromFormat('d/m/Y H:i', '8/7/2025 9:30'),
            ],
            [
                'name' => 'Excel Sheets',
                'created_at' => Carbon::createFromFormat('d/m/Y H:i', '8/7/2025 10:00'),
                'updated_at' => Carbon::createFromFormat('d/m/Y H:i', '8/7/2025 10:00'),
            ],
            [
                'name' => 'Other Software',
                'created_at' => Carbon::createFromFormat('d/m/Y H:i', '8/7/2025 9:30'),
                'updated_at' => Carbon::createFromFormat('d/m/Y H:i', '8/7/2025 9:30'),
            ],
            [
                'name' => 'No System',
                'created_at' => Carbon::createFromFormat('d/m/Y H:i', '8/7/2025 10:00'),
                'updated_at' => Carbon::createFromFormat('d/m/Y H:i', '8/7/2025 10:00'),
            ],
        ];

        foreach ($data as $item) {
            CurrentSystem::create($item);
        }
    }
}
