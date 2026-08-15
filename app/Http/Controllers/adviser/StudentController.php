<?php

namespace App\Http\Controllers\adviser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Section;

class StudentController extends Controller
{
    
    public function index()
    {
        return response()->json(
            Student::with('section')->get()
        );
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'student_code' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'gender' => 'required|string|max:10',
            'birthdate' => 'required|date',
            'gurdian_name' => 'required|string|max:255',
            'gurdian_contact' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        Student::create($validate);

        return response()->json([
            'message' => 'Student created successfully',
            'student' => $validate
        ], 201);
    }

    public function fetchSection(){
        
        $sections = Section::all();
        return response()->json($sections);
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
