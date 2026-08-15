<?php

namespace App\Http\Controllers\adviser;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    public function index()
    {
        return response()->json(
            Section::where('user_id', auth()->id())->get()
        );
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
        $validated['user_id'] = auth()->id();

        $section = Section::create($validated);

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section
        ], 201);
    }

    public function show(Section $section)
    {
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