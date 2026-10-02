<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ReadingScript;
use App\Models\Student;
use App\Models\StudentReadingScript;
use App\Services\SpeechService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $student = $this->student($request);
        $assessments = Assessment::where('student_id', $student->id)->get()->keyBy('reading_script_id');

        $scripts = ReadingScript::with('module')
            ->whereHas('module.sections', fn ($query) => $query->where('sections.id', $student->section_id))
            ->get()
            ->map(fn ($script) => [
                'id' => $script->id,
                'title' => $script->title,
                'module_id' => $script->module_id,
                'module_title' => $script->module->title,
                'status' => $assessments->has($script->id) ? 'completed' : 'not_started',
                'assessment_id' => $assessments->get($script->id)?->id,
            ]);

        return response()->json(['assessments' => $scripts]);
    }

    public function show(Request $request, ReadingScript $script)
    {
        $student = $this->student($request);
        abort_unless($this->isAssigned($student, $script), 404);

        $assessment = Assessment::where('student_id', $student->id)
            ->where('reading_script_id', $script->id)
            ->first();

        return response()->json(['assessment' => $this->payload($script, $assessment)]);
    }

    public function submit(Request $request, ReadingScript $script, SpeechService $speech)
    {
        $student = $this->student($request);
        abort_unless($this->isAssigned($student, $script), 404);
        
        \Log::info('Submit debug', [
    'has_file' => $request->hasFile('audio'),
    'all_files' => $request->allFiles(),
    'content_length' => $request->server('CONTENT_LENGTH'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
]);

    $request->validate(['audio' => ['required', 'file', 'mimes:webm,ogg,wav,mp3,m4a', 'max:15360']]);

    if (! $speech->configured()) {
        return response()->json([
            'message' => 'Speech transcription is not configured. Please contact your administrator.',
        ], 503);
    }

        $request->validate(['audio' => ['required', 'file', 'mimes:webm,ogg,wav,mp3,m4a', 'max:15360']]);

        if (! $speech->configured()) {
            return response()->json([
                'message' => 'Speech transcription is not configured. Please contact your administrator.',
            ], 503);
        }

        if (Assessment::where('student_id', $student->id)->where('reading_script_id', $script->id)->exists()) {
            return response()->json([
                'message' => 'This assessment has already been submitted.',
            ], 409);
        }

        try {
            $transcription = $speech->transcribe($request->file('audio'));
            $score = $this->score($script->content, $transcription);
            $audioPath = $request->file('audio')->store('assessments', 'local');

            $assessment = DB::transaction(function () use ($student, $script, $score, $transcription, $audioPath) {
                $assessment = Assessment::create(array_merge($score, [
                    'student_id' => $student->id,
                    'reading_script_id' => $script->id,
                    'module_id' => $script->module_id,
                    'script' => $script->content,
                    'transcription' => $transcription,
                    'audio_path' => $audioPath,
                    'status' => 'completed',
                ]));

                StudentReadingScript::updateOrCreate(
                    ['student_id' => $student->id, 'reading_script_id' => $script->id],
                    ['status' => 'completed', 'completed_at' => now()]
                );

                return $assessment;
            });

            return response()->json(['assessment' => $this->payload($script, $assessment)]);
        } catch (UniqueConstraintViolationException $exception) {
            Storage::disk('local')->delete($audioPath ?? '');

            return response()->json(['message' => 'This assessment has already been submitted.'], 409);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);
            Storage::disk('local')->delete($audioPath ?? '');

            return response()->json(['message' => 'We could not save your assessment. Please try again.'], 500);
        }
    }

    public function audio(Request $request, ReadingScript $script)
    {
        $student = $this->student($request);
        $assessment = Assessment::where('student_id', $student->id)->where('reading_script_id', $script->id)->firstOrFail();

        return Storage::disk('local')->response($assessment->audio_path);
    }

    private function student(Request $request): Student
    {
        return Student::findOrFail($request->session()->get('student_id'));
    }

    private function isAssigned(Student $student, ReadingScript $script): bool
    {
        return $script->module()->whereHas('sections', fn ($query) => $query->where('sections.id', $student->section_id))->exists();
    }

    private function payload(ReadingScript $script, ?Assessment $assessment): array
    {
        return [
            'id' => $script->id,
            'title' => $script->title,
            'module_id' => $script->module_id,
            'content' => $script->content,
            'assessment' => $assessment ? array_merge($assessment->only(['id', 'transcription', 'accuracy', 'total_words', 'correct_words', 'incorrect_words', 'status', 'created_at']), [
                'audio_url' => "/api/student/assessments/{$script->id}/audio",
                'word_results' => $this->wordResults($script->content, $assessment->transcription),
            ]) : null,
        ];
    }

    private function score(string $script, string $transcription): array
    {
        $expected = $this->words($script);
        $spoken = $this->words($transcription);
        $matches = array_fill(0, count($expected), false);
        $spokenIndex = 0;

        foreach ($expected as $index => $word) {
            while ($spokenIndex < count($spoken) && $spoken[$spokenIndex] !== $word) {
                $spokenIndex++;
            }
            if ($spokenIndex < count($spoken)) {
                $matches[$index] = true;
                $spokenIndex++;
            }
        }

        $correct = count(array_filter($matches));
        $total = count($expected);

        return [
            'accuracy' => $total ? (int) round(($correct / $total) * 100) : 0,
            'total_words' => $total,
            'correct_words' => $correct,
            'incorrect_words' => $total - $correct,
        ];
    }

    private function wordResults(string $script, string $transcription): array
    {
        $originalWords = preg_split('/\s+/', trim($script), -1, PREG_SPLIT_NO_EMPTY);
        $expected = $this->words($script);
        $spoken = $this->words($transcription);
        $spokenIndex = 0;

        return array_map(function ($word, $index) use ($expected, $spoken, &$spokenIndex) {
            while ($spokenIndex < count($spoken) && $spoken[$spokenIndex] !== $expected[$index]) {
                $spokenIndex++;
            }
            $matched = $spokenIndex < count($spoken);
            if ($matched) {
                $spokenIndex++;
            }

            return ['word' => $word, 'correct' => $matched];
        }, $originalWords, array_keys($originalWords));
    }

    private function words(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);

        return preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    }
}
