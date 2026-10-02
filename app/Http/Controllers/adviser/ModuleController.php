<?php

namespace App\Http\Controllers\adviser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Module;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;


class ModuleController extends Controller
{
   public function index()
{
    return response()->json(
        Module::with(['adviser', 'sections'])
            ->withCount('readingScripts')
            ->where('adviser_id', Auth::id())
            ->get()
    );
}

    public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255|unique:modules,title',
        'description' => 'nullable|string|max:1000',
        'year_level' =>'required'
    ]);

    
    $validated['adviser_id'] = auth()->id();
    try {
        $module = Module::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Module created successfully',
            'data' => $module
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create module',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function assignSections(Request $request, Module $module)
    {
        $validated = $request->validate([
            'section_ids' => 'required|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        $sections = Section::where('adviser_id', auth()->id())
            ->where('year_level', $module->year_level)
            ->whereIn('id', $validated['section_ids'])
            ->get();

        if ($sections->count() !== count($validated['section_ids'])) {
            return response()->json([
                'message' => 'Selected sections must match the module year level.',
            ], 422);
        }

        $module->sections()->sync($sections->pluck('id'));

        return response()->json([
            'message' => 'Module assigned successfully',
            'module' => $module->load('sections'),
        ]);
    }

    public function students(Request $request, Module $module)
    {
        $sectionIds = $module->sections()->pluck('sections.id');

        $students = Student::with([
            'section',
            'readingScripts' => function ($query) use ($module) {
                $query->where('module_id', $module->id);
            },
        ])->whereIn('section_id', $sectionIds);

        if ($request->filled('section_id')) {
            $students->where('section_id', $request->section_id);
        }

        return response()->json($students->get());
    }
}
