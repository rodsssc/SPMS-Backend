<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ReadingScript;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentReadingScript;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@spms.local'],
            ['name' => 'SPMS Super Admin', 'role' => 'superadmin', 'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'password'))]
        );

        $advisers = collect([
            ['name' => 'Jhon Rod', 'email' => 'rods@example.com'],
            ['name' => 'Jose Reyes', 'email' => 'jose.reyes@example.com'],
        ])->map(function (array $adviser) {
            return User::updateOrCreate(
                ['email' => $adviser['email']],
                ['name' => $adviser['name'], 'role' => 'adviser', 'password' => Hash::make('password')]
            );
        });

        $grades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $sectionNames = ['Rizal', 'Bonifacio', 'Mabini', 'Luna', 'Aguinaldo', 'Del Pilar', 'Jacinto', 'Silang', 'Quezon', 'Laurel'];

        $sections = collect($sectionNames)->map(function (string $name, int $index) use ($advisers, $grades) {
            $adviser = $advisers[$index % $advisers->count()];

            return Section::updateOrCreate(
                ['adviser_id' => $adviser->id, 'section_name' => $name],
                [
                    'adviser_name' => $adviser->name,
                    'year_level' => $grades[$index % count($grades)],
                    'school_year' => '2026-2027',
                    'status' => 'active',
                ]
            );
        });

        $modules = collect(range(1, 10))->map(function (int $number) use ($advisers, $grades) {
            $adviser = $advisers[($number - 1) % $advisers->count()];

            return Module::updateOrCreate(
                ['adviser_id' => $adviser->id, 'title' => "Reading Module {$number}"],
                [
                    'year_level' => $grades[($number - 1) % count($grades)],
                    'description' => "Reading practice module {$number}.",
                ]
            );
        });

        $students = collect(range(1, 10))->map(function (int $number) use ($sections) {
            return Student::updateOrCreate(
                ['student_code' => 'STU-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT)],
                [
                    'section_id' => $sections[$number - 1]->id,
                    'first_name' => "Student{$number}",
                    'last_name' => 'Demo',
                    'middle_name' => null,
                    'gender' => $number % 2 === 0 ? 'Female' : 'Male',
                    'birthdate' => '2012-01-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                    'gurdian_name' => "Guardian {$number}",
                    'gurdian_contact' => '0917000' . str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                    'address' => 'Sample Address',
                    'status' => 'active',
                ]
            );
        });

        $modules->each(function (Module $module) use ($sections) {
            $matchingSection = $sections->firstWhere('year_level', $module->year_level);
            $module->sections()->syncWithoutDetaching([$matchingSection->id]);
        });

        $content = 'The students read this short passage carefully, practice clear pronunciation, and answer the activity after finishing the reading.';
        $wordCount = count(preg_split('/\s+/', $content));

        $scripts = collect(range(1, 10))->map(function (int $number) use ($modules, $content, $wordCount) {
            return ReadingScript::updateOrCreate(
                ['module_id' => $modules[$number - 1]->id, 'title' => "Reading Script {$number}"],
                [
                    'content' => $content,
                    'word_count' => $wordCount,
                    'due_date' => now()->addDays($number)->toDateString(),
                ]
            );
        });

        $scripts->each(function (ReadingScript $script, int $index) use ($students) {
            StudentReadingScript::updateOrCreate(
                ['student_id' => $students[$index]->id, 'reading_script_id' => $script->id],
                [
                    'status' => ['pending', 'completed', 'non-compliant'][$index % 3],
                    'completed_at' => $index % 3 === 1 ? now()->subDays($index + 1) : null,
                ]
            );
        });
    }
}
