<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'salesadmin@ary-soft.com'], // unique check by email
            [
                'name'       => 'Super Admin',
                'phone'      => '9999999999',
                'designation'=> 'Administrator',
                'role_id'    => 1, // 👈 make sure 1 is your Admin role in roles table
                'group_id'   => null,
                'manager_id' => null,
                'password'   => Hash::make('arysoft123'), // 👈 change this
            ]
        );
    }
}
