<?php

use App\Models\Module;
use App\Models\ReadingScript;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function assignedStudentScript(): array
{
    $adviser = User::factory()->create();
    $section = Section::create([
        'adviser_id' => $adviser->id,
        'adviser_name' => $adviser->name,
        'year_level' => '1',
        'section_name' => 'A',
        'school_year' => '2026-2027',
        'status' => 'active',
    ]);
    $student = Student::create([
        'student_code' => 'S-'.Str::uuid(),
        'section_id' => $section->id,
        'first_name' => 'Student',
        'last_name' => 'One',
        'gender' => 'male',
        'birthdate' => '2010-01-01',
        'gurdian_name' => 'Guardian One',
        'gurdian_contact' => '09123456789',
        'address' => 'Test address',
        'status' => 'active',
    ]);
    $module = Module::create([
        'adviser_id' => $adviser->id,
        'title' => 'Reading',
        'year_level' => '1',
    ]);
    $module->sections()->attach($section);
    $script = ReadingScript::create([
        'module_id' => $module->id,
        'title' => 'First passage',
        'content' => 'The student reads this passage.',
        'word_count' => 5,
    ]);

    return [$student, $script];
}

test('an assigned student receives a clear error when transcription is not configured', function () {
    config()->set('services.groq.api_key', null);
    [$student, $script] = assignedStudentScript();

    $wav = "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x44\xAC\x00\x00\x88\x58\x01\x00\x02\x00\x10\x00data\x00\x00\x00\x00";

    $response = $this->withHeader('Accept', 'application/json')
        ->withHeader('Referer', 'http://localhost:5173')
        ->withSession(['student_id' => $student->id])
        ->post("/api/student/assessments/{$script->id}/submit", [
            'audio' => UploadedFile::fake()->createWithContent('reading.wav', $wav),
        ]);

    $response->assertStatus(503)
        ->assertJsonPath('message', 'Speech transcription is not configured. Please contact your administrator.');
});

test('a student cannot submit an assessment belonging to another section', function () {
    [$student] = assignedStudentScript();
    [, $otherScript] = assignedStudentScript();

    $this->withHeader('Referer', 'http://localhost:5173')
        ->withSession(['student_id' => $student->id])
        ->get("/api/student/assessments/{$otherScript->id}")
        ->assertNotFound();
});
