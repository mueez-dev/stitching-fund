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
        // Create Super Admin user if not exists
        $superAdmin = User::firstOrNew([
            'email' => 'super@admin.com',
        ]);

        // Always update superadmin to ensure status is active
        $superAdmin->fill([
            'name' => 'Super Admin',
            'password' => Hash::make('asadmin!x123'), // Change this to a secure password
            'email_verified_at' => now(),
            'role' => 'Super Admin',
            'status' => 'active', // Super Admin should be active by default
        ])->save();
    }
}
