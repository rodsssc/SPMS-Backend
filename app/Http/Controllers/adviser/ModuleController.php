<?php

namespace App\Http\Controllers\adviser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;


class ModuleController extends Controller
{
   public function index()
{
    return response()->json(
        Module::with('adviser')
            ->where('adviser_id', auth()->id())
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
}
