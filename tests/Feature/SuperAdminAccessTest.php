<?php

use App\Models\User;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

test('advisers are forbidden from management endpoints', function () {
    $adviser = User::factory()->create(['role' => 'adviser']);

    $this->actingAs($adviser)->postJson('/api/superadmin/advisers', [])->assertForbidden();
    $this->actingAs($adviser)->postJson('/api/adviser/students', [])->assertForbidden();
    $this->actingAs($adviser)->postJson('/api/adviser/sections', [])->assertForbidden();
});

test('super admins can onboard an adviser with a hashed password', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($admin)->postJson('/api/superadmin/advisers', [
        'name' => 'New Adviser',
        'email' => 'new.adviser@example.com',
        'password' => 'secure-password',
    ])->assertCreated()->assertJsonPath('adviser.role', 'adviser');

    $adviser = User::where('email', 'new.adviser@example.com')->firstOrFail();
    expect(Hash::check('secure-password', $adviser->password))->toBeTrue();
});

test('student sessions cannot access super admin management APIs', function () {
    $this->withSession(['student_id' => 1])
        ->getJson('/api/superadmin/dashboard')
        ->assertUnauthorized();
});

test('super admin list endpoints support filters and pagination', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $adviser = User::factory()->create(['role' => 'adviser', 'name' => 'Visible Adviser']);
    User::factory()->create(['role' => 'adviser', 'name' => 'Visible Adviser Two']);
    $section = Section::create([
        'adviser_id' => $adviser->id,
        'adviser_name' => $adviser->name,
        'year_level' => 'Grade 7',
        'section_name' => 'Rizal',
        'school_year' => '2026-2027',
        'status' => 'active',
    ]);
    Section::create([
        'adviser_id' => null,
        'adviser_name' => null,
        'year_level' => 'Grade 8',
        'section_name' => 'Bonifacio',
        'school_year' => '2026-2027',
        'status' => 'active',
    ]);
    Student::create([
        'student_code' => 'STU-7301', 'section_id' => $section->id, 'first_name' => 'Active', 'last_name' => 'Learner',
        'gender' => 'Female', 'birthdate' => '2012-01-01', 'gurdian_name' => 'Guardian',
        'gurdian_contact' => '09170000000', 'address' => 'Address', 'status' => 'active',
    ]);

    $this->actingAs($admin)->getJson('/api/superadmin/advisers?search=Visible&per_page=1')
        ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'data');
    $this->actingAs($admin)->getJson('/api/superadmin/sections?year_level=Grade%207&per_page=1')
        ->assertOk()->assertJsonPath('total', 1)->assertJsonCount(1, 'data');
    $this->actingAs($admin)->getJson("/api/superadmin/students?status=active&section_id={$section->id}&per_page=1")
        ->assertOk()->assertJsonPath('total', 1)->assertJsonCount(1, 'data');
});
