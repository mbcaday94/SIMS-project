<?php

namespace App\Http\Controllers;

use App\Models\TeachingLoad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function enrollments(Request $request): View
    {
        $student = $request->user()->student;
        abort_unless($student, 403, 'This student account is not linked to a student record.');

        $schoolYears = $student->enrollments()
            ->select('school_year')
            ->distinct()
            ->orderByDesc('school_year')
            ->pluck('school_year')
            ->all();
        $semesters = $student->enrollments()
            ->select('semester')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester')
            ->all();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'school_year' => ['nullable', 'string', Rule::in($schoolYears)],
            'semester' => ['nullable', 'string', Rule::in($semesters)],
        ]);
        $search = $filters['search'] ?? null;
        $schoolYear = $filters['school_year'] ?? null;
        $semester = $filters['semester'] ?? null;

        $enrollments = $student->enrollments()
            ->when($search, function ($query) use ($search) {
                $query->whereHas('subject', function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($schoolYear, fn ($query) => $query->where('school_year', $schoolYear))
            ->when($semester, fn ($query) => $query->where('semester', $semester))
            ->with('subject')
            ->latest('enrolled_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $enrollmentRows = $enrollments->getCollection();
        $teachingLoads = TeachingLoad::query()
            ->with('faculty')
            ->whereIn('subject_id', $enrollmentRows->pluck('subject_id')->unique())
            ->whereIn('school_year', $enrollmentRows->pluck('school_year')->unique())
            ->whereIn('semester', $enrollmentRows->pluck('semester')->unique())
            ->get()
            ->groupBy(fn (TeachingLoad $teachingLoad) => implode('|', [
                $teachingLoad->subject_id,
                $teachingLoad->school_year,
                $teachingLoad->semester,
            ]));

        return view('student-portal.enrollments', [
            'student' => $student,
            'enrollments' => $enrollments,
            'teachingLoads' => $teachingLoads,
            'schoolYears' => $schoolYears,
            'semesters' => $semesters,
            'search' => $search,
            'schoolYear' => $schoolYear,
            'semester' => $semester,
        ]);
    }
}
