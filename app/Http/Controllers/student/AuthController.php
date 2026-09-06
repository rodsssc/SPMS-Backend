<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'student_code' => ['required', 'string'],
        ]);

        $student = Student::where('student_code', $request->student_code)
            ->where('status', 'active')
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Invalid student code or inactive student.'
            ], 401);
        }

        $request->session()->regenerate();

        $request->session()->put('student_id', $student->id);

        return response()->json([
            'message' => 'Student login successful.',
            'student' => [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'section_id' => $student->section_id,
                'gender' => $student->gender,
                'birthdate' => $student->birthdate,
                'status' => $student->status,
            ],
        ]);
    }

    public function logout(Request $request)
{
    $request->session()->forget('student_id');

    return response()->json([
        'message' => 'Student logout successful.'
    ]);
}

    public function user(Request $request)
    {
        $studentId = $request->session()->get('student_id');

        if (!$studentId) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $student = Student::with('section')->find($studentId);

        if (!$student) {
            $request->session()->forget('student_id');

            return response()->json([
                'message' => 'Student not found.'
            ], 404);
        }

        return response()->json([
            'student' => $student,
        ]);
    }
}