<?php

namespace App\Http\Controllers\adviser;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ReadingScript;
use App\Models\Student;
use App\Models\StudentReadingScript;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScriptController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            ReadingScript::whereHas('module', function ($query) {
                $query->where('adviser_id', auth()->id());
            })->when($request->filled('module_id'), function ($query) use ($request) {
                $query->where('module_id', $request->module_id);
            })->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'due_date' => 'nullable|date',
        ]);

        $validated['word_count'] = $this->wordCount($validated['content']);
        $this->validateWordCount($validated['word_count']);
        abort_unless($this->ownsModule($validated['module_id'], $request), 404);

        $script = ReadingScript::create($validated);

        return response()->json([
            'message' => 'Reading script created successfully',
            'script' => $script,
        ], 201);
    }

    public function show(Request $request, ReadingScript $script)
    {
        $this->authorizeScript($request, $script);

        return response()->json($script);
    }

    public function update(Request $request, ReadingScript $script)
    {
        $this->authorizeScript($request, $script);
        $validated = $request->validate([
            'module_id' => 'sometimes|exists:modules,id',
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|required|string',
            'due_date' => 'nullable|date',
        ]);

        if (array_key_exists('content', $validated)) {
            $validated['word_count'] = $this->wordCount($validated['content']);
            $this->validateWordCount($validated['word_count']);
        }

        if (array_key_exists('module_id', $validated)) {
            abort_unless($this->ownsModule($validated['module_id'], $request), 404);
        }

        $script->update($validated);

        return response()->json([
            'message' => 'Reading script updated successfully',
            'script' => $script,
        ]);
    }

    public function destroy(Request $request, ReadingScript $script)
    {
        $this->authorizeScript($request, $script);
        $script->delete();

        return response()->json([
            'message' => 'Reading script deleted successfully',
        ]);
    }

    public function updateStudentStatus(Request $request, ReadingScript $script, Student $student)
    {
        $this->authorizeScript($request, $script);
        abort_unless($student->section_id && $script->module->sections()->whereKey($student->section_id)->exists(), 404);
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,non-compliant',
        ]);

        $status = StudentReadingScript::updateOrCreate(
            ['student_id' => $student->id, 'reading_script_id' => $script->id],
            [
                'status' => $validated['status'],
                'completed_at' => $validated['status'] === 'completed' ? now() : null,
            ]
        );

        return response()->json([
            'message' => 'Student script status updated successfully',
            'status' => $status,
        ]);
    }

    private function wordCount(string $content): int
    {
        preg_match_all('/\S+/u', trim($content), $words);

        return count($words[0]);
    }

    private function validateWordCount(int $wordCount): void
    {
        if ($wordCount > 250) {
            throw ValidationException::withMessages([
                'content' => ['The reading script may not exceed 250 words.'],
            ]);
        }
    }

    private function authorizeScript(Request $request, ReadingScript $script): void
    {
        abort_unless($this->ownsModule($script->module_id, $request), 404);
    }

    private function ownsModule(int $moduleId, Request $request): bool
    {
        return Module::whereKey($moduleId)->where('adviser_id', $request->user()->id)->exists();
    }
}
