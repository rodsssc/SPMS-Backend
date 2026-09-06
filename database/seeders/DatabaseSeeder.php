<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Section;
use App\Models\Student;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'rods@example.com'],
            [
                'name' => 'Rods',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Section::firstOrCreate(
        //     ['adviser_name' => 'John Doe','section_name' => 'Rizal','year_level' => '8','school_year' => '2023-2024','status' => 'active'],
        //     [
        //         'adviser_id' => User::where('email', 'rods@example.com')->first()->id,
        //     ],
        // );

        // Student::create([
        //     'student_code' => 'STU-2024-001',
        //     'section_id' => $section_id = Section::where('section_name', 'Rizal')->first()->id,
        //     'first_name' => 'Juan',
        //     'last_name' => 'Santos',
        //     'gender' => 'Male',
        //     'birthdate' => '2009-03-15',
        //     'gurdian_name' => 'Maria Santos',
        //     'gurdian_contact' => '09123456789',
        //     'address' => 'Kalibo, Aklan',
        //     'status' => 'active',
        // ]);

        // Student::create([
        //     'student_code' => 'STU-2024-002',
        //     'section_id' => $section_id = Section::where('section_name', 'Rizal')->first()->id,
        //     'first_name' => 'Ana',
        //     'last_name' => 'Cruz',
        //     'gender' => 'Female',
        //     'birthdate' => '2009-07-22',
        //     'gurdian_name' => 'Jose Cruz',
        //     'gurdian_contact' => '09187654321',
        //     'address' => 'Iloilo City',
        //     'status' => 'active',
        // ]);

    }
}