<?php

namespace App\Http\Controllers\adviser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'performance_level' => ['sometimes', 'in:all,highly_confident,confident,developing,needs_improvement'],
        ]);

        $query = Student::with('section')->orderBy('created_at', 'desc');

        $performanceBounds = match ($request->input('performance_level')) {
            'highly_confident' => [90, 100],
            'confident' => [75, 89],
            'developing' => [50, 74],
            'needs_improvement' => [0, 49],
            default => null,
        };

        if ($performanceBounds) {
            [$minimum, $maximum] = $performanceBounds;
            $query->whereIn('students.id', function ($performanceQuery) use ($request, $minimum, $maximum) {
                $performanceQuery->select('assessments.student_id')
                    ->from('assessments')
                    ->join('students as performance_students', 'performance_students.id', '=', 'assessments.student_id')
                    ->join('sections as performance_sections', 'performance_sections.id', '=', 'performance_students.section_id')
                    ->join('modules as performance_modules', 'performance_modules.id', '=', 'assessments.module_id')
                    ->where('assessments.status', 'completed')
                    ->where('performance_sections.adviser_id', $request->user()->id)
                    ->where('performance_modules.adviser_id', $request->user()->id)
                    ->whereExists(function ($moduleSectionQuery) {
                        $moduleSectionQuery->selectRaw('1')
                            ->from('module_section')
                            ->whereColumn('module_section.module_id', 'assessments.module_id')
                            ->whereColumn('module_section.section_id', 'performance_students.section_id');
                    })
                    ->groupBy('assessments.student_id')
                    ->havingRaw('ROUND(AVG(assessments.accuracy)) BETWEEN ? AND ?', [$minimum, $maximum]);
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('student_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        $perPage = $request->input('per_page', 10);

        return response()->json($query->paginate($perPage));
    }

    private function generateStudentCode(): string
    {
        // lockForUpdate() prevents two simultaneous requests
        // from generating the same code
        return DB::transaction(function () {
            $last = Student::orderBy('id', 'desc')->lockForUpdate()->first();

            $nextNumber = 1;
            if ($last && preg_match('/(\d+)$/', $last->student_code, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }

            return 'STU-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'gender' => 'required|string|max:10',
            'birthdate' => 'required|date',
            'gurdian_name' => 'required|string|max:255',
            'gurdian_contact' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $validate['student_code'] = $this->generateStudentCode();

        $student = Student::create($validate);

        return response()->json([
            'message' => 'Student created successfully',
            'student' => $student->load('section'),
        ], 201);
    }

    public function show($id)
    {
        $student = Student::with('section')->findOrFail($id);
        return response()->json($student);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validate = $request->validate([
            'student_code' => 'sometimes|required|string|max:255',
            'section_id' => 'sometimes|required|exists:sections,id',
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'gender' => 'sometimes|required|string|max:10',
            'birthdate' => 'sometimes|required|date',
            'gurdian_name' => 'sometimes|required|string|max:255',
            'gurdian_contact' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $student->update($validate);

        return response()->json($student->load('section'));
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully']);
    }
}
