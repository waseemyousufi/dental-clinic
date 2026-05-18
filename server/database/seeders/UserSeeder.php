<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Dentist',
            'email' => 'dentist@clinic.com',
            'password' => 'Dentist@123',
        ]);

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@clinic.com',
            'password' => 'SuperAdmin@123',
        ]);

        User::create([
            'name' => 'Receptionist',
            'email' => 'receptionist@clinic.com',
            'password' => 'Receptionist@123',
        ]);
    }
}
