<?php

use App\Models\Section;
use App\Models\Student;
use App\Models\User;

test('adviser student list includes students assigned to other advisers and newly onboarded students', function () {
    $sectionOwner = User::factory()->create(['role' => 'adviser']);
    $viewer = User::factory()->create(['role' => 'adviser']);
    $admin = User::factory()->create(['role' => 'superadmin']);
    $section = Section::create([
        'adviser_id' => $sectionOwner->id,
        'adviser_name' => $sectionOwner->name,
        'year_level' => 'Grade 7',
        'section_name' => 'Rizal',
        'school_year' => '2026-2027',
        'status' => 'active',
    ]);

    $student = Student::create([
        'student_code' => 'STU-9001',
        'section_id' => $section->id,
        'first_name' => 'Existing',
        'last_name' => 'Student',
        'gender' => 'Female',
        'birthdate' => '2012-01-01',
        'gurdian_name' => 'Guardian',
        'gurdian_contact' => '09170000000',
        'address' => 'Sample Address',
        'status' => 'active',
    ]);

    $this->actingAs($admin)->postJson('/api/superadmin/students', [
        'section_id' => $section->id,
        'first_name' => 'New',
        'last_name' => 'Student',
        'gender' => 'Male',
        'birthdate' => '2012-02-02',
        'gurdian_name' => 'Guardian',
        'gurdian_contact' => '09170000001',
        'address' => 'Sample Address',
        'status' => 'active',
    ])->assertCreated();

    $this->actingAs($viewer)->getJson('/api/adviser/students?per_page=50')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['student_code' => $student->student_code]);

    $this->actingAs($viewer)->getJson("/api/adviser/students/{$student->id}")
        ->assertOk()
        ->assertJsonPath('id', $student->id);
});
