<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data Admin
        User::create([
            'name'     => 'Administrator SISIR',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        // Data Staff
        User::create([
            'name'     => 'Staff Pemasaran',
            'username' => 'staff',
            'password' => Hash::make('staff123'),
            'role'     => 'staff',
        ]);

        // Data Viewer
        User::create([
            'name'     => 'Viewer Regional 7',
            'username' => 'viewer',
            'password' => Hash::make('viewer123'),
            'role'     => 'viewer',
        ]);
    }
}
