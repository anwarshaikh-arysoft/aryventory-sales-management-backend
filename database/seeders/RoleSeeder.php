<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::insert([
            [
                'id' => 1,
                'name' => 'Admin',
                'created_at' => Carbon::createFromFormat('n/j/Y H:i', '8/7/2025 9:30'),
                'updated_at' => Carbon::createFromFormat('n/j/Y H:i', '8/7/2025 9:30'),
            ],
            [
                'id' => 2,
                'name' => 'Manager',
                'created_at' => Carbon::createFromFormat('n/j/Y H:i', '8/7/2025 10:00'),
                'updated_at' => Carbon::createFromFormat('n/j/Y H:i', '8/7/2025 10:00'),
            ],
            [
                'id' => 3,
                'name' => 'Tele Caller',
                'created_at' => Carbon::createFromFormat('n/j/Y H:i', '8/7/2025 9:30'),
                'updated_at' => Carbon::createFromFormat('n/j/Y H:i', '8/7/2025 9:30'),
            ],
            [
                'id' => 4,
                'name' => 'Sales Executive',
                'created_at' => Carbon::createFromFormat('n/j/Y H:i', '8/7/2025 10:00'),
                'updated_at' => Carbon::createFromFormat('n/j/Y H:i', '8/7/2025 10:00'),
            ],
        ]);
    }
}

