<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class StudentController extends Controller
{
    // ──────────────────────────────────────────────
    //  INDEX — List all students
    // ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('search');

        $students = Student::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%");
                });
            })
            ->orderBy('course')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('students.index', compact('students', 'search'));
    }

    // ──────────────────────────────────────────────
    //  CREATE — Show form
    // ──────────────────────────────────────────────

    public function create()
    {
        return view('students.create');
    }

    // ──────────────────────────────────────────────
    //  STORE — Save new student
    // ──────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_number' => ['required', 'string', 'max:50', 'unique:students,student_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:students,email'],
            'course' => ['required', 'string', Rule::in(Student::COURSES)],
            'year_level' => ['required', 'string', 'max:50'],
            'semester' => ['required', 'string', Rule::in(Student::SEMESTERS)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('student-photos', 'public');
            if ($photoPath === false) {
                throw new RuntimeException('Unable to save the uploaded student photo.');
            }

            $validated['photo_path'] = $photoPath;
        }
        unset($validated['photo']);

        Student::create($validated);

        return redirect()->route('students.index')
            ->with('success', "Student {$validated['first_name']} {$validated['last_name']} has been added successfully.");
    }

    // ──────────────────────────────────────────────
    //  SHOW — View single student
    // ──────────────────────────────────────────────

    public function show(Student $student)
    {
        return view('students.show', compact('student'));
    }

    // ──────────────────────────────────────────────
    //  EDIT — Show edit form
    // ──────────────────────────────────────────────

    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    // ──────────────────────────────────────────────
    //  UPDATE — Save changes
    // ──────────────────────────────────────────────

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'student_number' => ['required', 'string', 'max:50', Rule::unique('students', 'student_number')->ignore($student->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('students', 'email')->ignore($student->id)],
            'course' => ['required', 'string', Rule::in(Student::COURSES)],
            'year_level' => ['required', 'string', 'max:50'],
            'semester' => ['required', 'string', Rule::in(Student::SEMESTERS)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldPhotoPath = $student->photo_path;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('student-photos', 'public');
            if ($photoPath === false) {
                throw new RuntimeException('Unable to save the uploaded student photo.');
            }

            $validated['photo_path'] = $photoPath;
        }
        unset($validated['photo']);

        $student->update($validated);

        if (isset($validated['photo_path']) && $oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()->route('students.index')
            ->with('success', "Student {$student->first_name} {$student->last_name} has been updated successfully.");
    }

    // ──────────────────────────────────────────────
    //  DESTROY — Delete student
    // ──────────────────────────────────────────────

    public function destroy(Student $student)
    {
        if ($student->enrollments()->exists()) {
            return redirect()->route('students.index')
                ->with('error', 'This student cannot be deleted while they have enrollments.');
        }

        $name = "{$student->first_name} {$student->last_name}";
        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', "Student {$name} has been deleted.");
    }
}
