<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    private const SEMESTERS = ['First', 'Second', 'Summer'];

    public function index(Request $request)
    {
        $schoolYears = Enrollment::query()
            ->select('school_year')
            ->distinct()
            ->orderByDesc('school_year')
            ->pluck('school_year')
            ->all();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'school_year' => ['nullable', 'string', Rule::in($schoolYears)],
            'semester' => ['nullable', 'string', Rule::in(self::SEMESTERS)],
        ]);
        $search = $filters['search'] ?? null;
        $schoolYear = $filters['school_year'] ?? null;
        $semester = $filters['semester'] ?? null;

        $subjects = Subject::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($schoolYear || $semester, function ($query) use ($schoolYear, $semester) {
                $query->whereHas('enrollments', function ($query) use ($schoolYear, $semester) {
                    $query->when($schoolYear, fn ($query) => $query->where('school_year', $schoolYear))
                        ->when($semester, fn ($query) => $query->where('semester', $semester));
                });
            })
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('subjects.index', [
            'subjects' => $subjects,
            'search' => $search,
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'schoolYears' => $schoolYears,
            'semesters' => self::SEMESTERS,
        ]);
    }

    public function create()
    {
        return view('subjects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'min:0', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Subject::create($validated);

        return redirect()->route('subjects.index')
            ->with('success', "Subject {$validated['name']} has been created successfully.");
    }

    public function show(Subject $subject)
    {
        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        return view('subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('subjects', 'code')->ignore($subject->id)],
            'name' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'min:0', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $subject->update($validated);

        return redirect()->route('subjects.index')
            ->with('success', "Subject {$subject->name} has been updated successfully.");
    }

    public function destroy(Subject $subject)
    {
        if ($subject->enrollments()->exists() || $subject->curricula()->exists()) {
            return redirect()->route('subjects.index')
                ->with('error', 'This subject cannot be deleted because it is used by enrollments or program curricula.');
        }

        $name = $subject->name;
        $subject->delete();

        return redirect()->route('subjects.index')
            ->with('success', "Subject {$name} has been deleted.");
    }
}
