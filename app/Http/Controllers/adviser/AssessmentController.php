<?php

namespace App\Http\Controllers\adviser;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $assessments = Assessment::with(['student.section', 'module', 'readingScript'])
            ->where('status', 'completed')
            ->whereHas('module', fn ($query) => $query->where('adviser_id', $request->user()->id))
            ->whereHas('student.section', fn ($section) => $section->where('adviser_id', $request->user()->id)
                ->whereHas('modules', fn ($modules) => $modules->whereColumn('modules.id', 'assessments.module_id')))
            ->latest()
            ->get()
            ->map(fn ($assessment) => $this->payload($assessment));

        return response()->json(['assessments' => $assessments]);
    }

    public function show(Request $request, Assessment $assessment)
    {
        abort_unless($this->isAccessibleTo($assessment, $request), 404);
        $assessment->load(['student.section', 'module', 'readingScript']);
        return response()->json(['assessment' => $this->payload($assessment, true)]);
    }

    public function review(Request $request, Assessment $assessment)
    {
        abort_unless($this->isAccessibleTo($assessment, $request), 404);

        $data = $request->validate([
            'fluency_rating' => ['nullable', 'in:fluent,developing,needs_support'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $assessment->update([
            ...$data,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['assessment' => $this->payload($assessment->fresh()->load(['student.section', 'module', 'readingScript']), true)]);
    }

    public function audio(Request $request, Assessment $assessment)
    {
        abort_unless($this->isAccessibleTo($assessment, $request), 404);

        $path = Storage::disk('local')->path($assessment->audio_path);
        abort_unless(file_exists($path), 404);

        return response()->file($path);
    }

    private function payload(Assessment $assessment, bool $details = false): array
    {
        $data = [
            'id' => $assessment->id,
            'student_id' => $assessment->student_id,
            'module_id' => $assessment->module_id,
            'student' => [
                'name' => trim("{$assessment->student->first_name} {$assessment->student->last_name}"),
                'student_code' => $assessment->student->student_code,
                'section' => $assessment->student->section?->section_name,
            ],
            'module' => $assessment->module->title,
            'script_title' => $assessment->readingScript->title,
            'accuracy' => $assessment->accuracy,
            'correct_words' => $assessment->correct_words,
            'incorrect_words' => $assessment->incorrect_words,
            'total_words' => $assessment->total_words,
            'status' => $assessment->status,
            'fluency_rating' => $assessment->fluency_rating,
            'review_note' => $assessment->review_note,
            'reviewed_at' => $assessment->reviewed_at,
            'created_at' => $assessment->created_at,
        ];

        if ($details) {
            $data += [
                'script' => $assessment->script,
                'transcription' => $assessment->transcription,
                'audio_url' => "/api/adviser/assessments/{$assessment->id}/audio",
            ];
        }

        return $data;
    }

    private function isAccessibleTo(Assessment $assessment, Request $request): bool
    {
        return Assessment::whereKey($assessment->id)
            ->whereHas('module', fn ($query) => $query->where('adviser_id', $request->user()->id))
            ->whereHas('student.section', fn ($section) => $section->where('adviser_id', $request->user()->id)
                ->whereHas('modules', fn ($modules) => $modules->whereColumn('modules.id', 'assessments.module_id')))
            ->exists();
    }
}
