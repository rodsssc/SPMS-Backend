<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@spms.local'],
            [
                'name' => 'SPMS Super Admin',
                'role' => 'superadmin',
                'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'spmspassword')),
            ]
        );
    }
}
