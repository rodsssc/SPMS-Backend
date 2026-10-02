<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'advisers' => User::where('role', 'adviser')->count(),
            'students' => Student::count(),
            'sections' => Section::count(),
            'assigned_sections' => Section::whereNotNull('adviser_id')->count(),
            'unassigned_sections' => Section::whereNull('adviser_id')->count(),
        ]);
    }

    public function advisers(Request $request)
    {
        return User::where('role', 'adviser')
            ->select('id', 'name', 'email', 'created_at')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')->paginate(min(max($request->integer('per_page', 10), 1), 100));
    }

    public function storeAdviser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'adviser';

        $adviser = User::create($data);

        return response()->json(['adviser' => $adviser->only('id', 'name', 'email', 'role')], 201);
    }

    public function updateAdviser(Request $request, User $user)
    {
        abort_unless($user->role === 'adviser', 404);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ]);
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);

        return response()->json(['adviser' => $user->only('id', 'name', 'email', 'role')]);
    }

    public function students(Request $request)
    {
        return Student::with('section')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%"));
            })
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('section_id') && $request->section_id !== 'all', fn ($query) => $query->where('section_id', $request->section_id))
            ->orderBy('last_name')->paginate(min(max($request->integer('per_page', 10), 1), 100));
    }

    public function storeStudent(Request $request)
    {
        $data = $this->validateStudent($request);
        $data['student_code'] = DB::transaction(function () {
            $last = Student::orderByDesc('id')->lockForUpdate()->first();
            $next = $last && preg_match('/(\d+)$/', $last->student_code, $matches) ? (int) $matches[1] + 1 : 1;
            return 'STU-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
        $student = Student::create($data);

        return response()->json(['student' => $student->load('section')], 201);
    }

    public function updateStudent(Request $request, Student $student)
    {
        $student->update($this->validateStudent($request, true));

        return response()->json(['student' => $student->fresh('section')]);
    }

    private function validateStudent(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];
        return $request->validate([
            'section_id' => [...$required, 'exists:sections,id'],
            'first_name' => [...$required, 'string', 'max:255'],
            'last_name' => [...$required, 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'gender' => [...$required, 'string', 'max:10'],
            'birthdate' => [...$required, 'date'],
            'gurdian_name' => [...$required, 'string', 'max:255'],
            'gurdian_contact' => [...$required, 'string', 'max:20'],
            'address' => [...$required, 'string', 'max:255'],
            'status' => [...$required, 'in:active,inactive'],
        ]);
    }

    public function sections(Request $request)
    {
        return Section::with('user:id,name,email')->withCount('students')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q->where('section_name', 'like', "%{$search}%")
                    ->orWhere('year_level', 'like', "%{$search}%")
                    ->orWhere('school_year', 'like', "%{$search}%")
                    ->orWhere('adviser_name', 'like', "%{$search}%"));
            })
            ->when($request->filled('year_level') && $request->year_level !== 'all', fn ($query) => $query->where('year_level', $request->year_level))
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('status', $request->status))
            ->orderBy('year_level')->orderBy('section_name')
            ->paginate(min(max($request->integer('per_page', 10), 1), 100));
    }

    public function storeSection(Request $request)
    {
        $data = $this->validateSection($request);
        $adviser = isset($data['adviser_id']) ? User::where('role', 'adviser')->findOrFail($data['adviser_id']) : null;
        $data['adviser_name'] = $adviser?->name;
        $section = Section::create($data);
        return response()->json(['section' => $section->load('user:id,name,email')], 201);
    }

    public function updateSection(Request $request, Section $section)
    {
        $data = $this->validateSection($request, true);
        if (array_key_exists('adviser_id', $data)) {
            $adviser = $data['adviser_id'] ? User::where('role', 'adviser')->findOrFail($data['adviser_id']) : null;
            $data['adviser_name'] = $adviser?->name;
        }
        $section->update($data);
        return response()->json(['section' => $section->fresh('user:id,name,email')]);
    }

    public function assignAdviser(Request $request, Section $section)
    {
        $data = $request->validate(['adviser_id' => ['nullable', 'exists:users,id']]);
        if ($data['adviser_id'] === null) {
            $section->update(['adviser_id' => null, 'adviser_name' => null]);
        } else {
            $adviser = User::where('role', 'adviser')->findOrFail($data['adviser_id']);
            $section->update(['adviser_id' => $adviser->id, 'adviser_name' => $adviser->name]);
        }
        return response()->json(['section' => $section->fresh('user:id,name,email')]);
    }

    private function validateSection(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];
        return $request->validate([
            'section_name' => [...$required, 'string', 'max:255'],
            'year_level' => [...$required, 'string', 'max:255'],
            'school_year' => [...$required, 'string', 'max:20'],
            'status' => [$partial ? 'sometimes' : 'nullable', 'in:active,inactive'],
            'adviser_id' => ['sometimes', 'nullable', 'exists:users,id'],
        ]) + ['status' => $request->input('status', 'active')];
    }
}
