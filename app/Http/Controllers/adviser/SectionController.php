<?php

namespace App\Http\Controllers\adviser;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $query = Section::where('adviser_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('section_name', 'like', "%{$search}%")
                ->orWhere('adviser_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('year_level') && $request->year_level !== 'all') {
            $query->where('year_level', $request->year_level);
        }

        return response()->json($query->get());
    }

    public function display_student(Request $request)
{
    $sections = Section::where('adviser_id', auth()->id())
        ->get()
        ->map(function ($section) use ($request) {
            $query = $section->students()->orderBy('last_name');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('student_code', 'like', "%{$search}%");
                });
            }

            $section->students = $query->limit(5)->get();
            return $section;
        });

    return response()->json($sections);
}



    public function store(Request $request)
    {
        $validated = $request->validate([
            
            'section_name' => 'required|string|max:255',
            'adviser_name' => 'required|string|max:255',
            'year_level' => 'required|string|max:255',
            'school_year' => 'required|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);
        $validated['adviser_id'] = auth()->id();

        $section = Section::create($validated);

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section
        ], 201);
    }

    public function show(Section $section)
    {
        abort_unless($section->adviser_id === auth()->id(), 404);
        return response()->json($section);
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'adviser_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'school_year' => 'sometimes|string|max:20',
        ]);

        $section->update($validated);

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section
        ]);
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully'
        ]);
    }
}
