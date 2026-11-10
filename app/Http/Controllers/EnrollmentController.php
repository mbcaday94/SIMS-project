<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Services\CurriculumLoadService;
use App\Services\StudentSectionCapacityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    private const SEMESTERS = ['First', 'Second', 'Summer'];
    private const SECTIONS = ['A', 'B', 'C', 'D', 'E'];

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

        $matchingEnrollments = function ($query) use ($search, $schoolYear, $semester) {
            return $query->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('school_year', 'like', "%{$search}%")
                        ->orWhere('semester', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($query) use ($search) {
                            $query->where('student_number', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subject', function ($query) use ($search) {
                            $query->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
                ->when($schoolYear, fn ($query) => $query->where('school_year', $schoolYear))
                ->when($semester, fn ($query) => $query->where('semester', $semester));
        };

        $students = Student::query()
            ->whereHas('enrollments', $matchingEnrollments)
            ->with([
                'enrollments' => fn ($query) => $matchingEnrollments($query)
                    ->with('subject')
                    ->latest('enrolled_at')
                    ->latest('id'),
            ])
            ->orderBy('course')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        $enrollmentCount = Enrollment::query()
            ->where($matchingEnrollments)
            ->count();

        return view('enrollments.index', [
            'students' => $students,
            'enrollmentCount' => $enrollmentCount,
            'search' => $search,
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'schoolYears' => $schoolYears,
            'semesters' => self::SEMESTERS,
        ]);
    }

    public function create()
    {
        return view('enrollments.create', $this->formOptions());
    }

    public function store(
        Request $request,
        CurriculumLoadService $curriculumLoad,
        StudentSectionCapacityService $sectionCapacity,
    )
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'section' => ['required', 'string', Rule::in(self::SECTIONS)],
            'subject_ids' => ['required_without:subject_id', 'array', 'min:1'],
            'subject_ids.*' => ['required', 'integer', 'distinct', Rule::exists('subjects', 'id')],
            'subject_id' => ['required_without:subject_ids', 'nullable', 'integer', Rule::exists('subjects', 'id')],
            'school_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', 'string', Rule::in(['First', 'Second', 'Summer'])],
            'status' => ['required', 'string', Rule::in(['enrolled', 'completed', 'dropped'])],
            'enrolled_at' => ['nullable', 'date'],
        ]);

        $subjectIds = array_values(array_unique($validated['subject_ids'] ?? [$validated['subject_id']]));
        $student = Student::findOrFail($validated['student_id']);
        $sectionCapacity->ensureCapacity([
            'course' => $student->course,
            'year_level' => $student->year_level,
            'section' => $validated['section'],
        ], $student);
        $duplicateExists = Enrollment::query()
            ->where('student_id', $validated['student_id'])
            ->where('school_year', $validated['school_year'])
            ->where('semester', $validated['semester'])
            ->whereIn('subject_id', $subjectIds)
            ->exists();

        if ($duplicateExists) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'The student is already enrolled in one or more selected subjects for this school year and semester.']);
        }

        $loadError = $curriculumLoad->validateLoad(
            $student,
            $validated['school_year'],
            $validated['semester'],
            $subjectIds,
        );

        if ($loadError) {
            return back()->withInput()->withErrors(['subject_load' => $loadError]);
        }

        $enrollments = DB::transaction(function () use ($validated, $subjectIds, $student) {
            $student->update(['section' => $validated['section']]);

            return collect($subjectIds)->map(fn ($subjectId) => Enrollment::create([
                'student_id' => $validated['student_id'],
                'subject_id' => $subjectId,
                'school_year' => $validated['school_year'],
                'semester' => $validated['semester'],
                'status' => $validated['status'],
                'enrolled_at' => $validated['enrolled_at'] ?? null,
            ]));
        });

        if (count($enrollments) === 1 && ! isset($validated['subject_ids'])) {
            return redirect()->route('enrollments.show', $enrollments->first())
                ->with('success', 'Enrollment created successfully.');
        }

        $count = $enrollments->count();

        return redirect()->route('enrollments.index')
            ->with('success', "{$count} ".Str::plural('subject', $count).' enrolled successfully.');
    }

    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['student', 'subject']);

        return view('enrollments.show', compact('enrollment'));
    }

    public function edit(Enrollment $enrollment)
    {
        return view('enrollments.edit', [
            ...$this->formOptions(),
            'enrollment' => $enrollment,
        ]);
    }

    public function update(
        Request $request,
        Enrollment $enrollment,
        CurriculumLoadService $curriculumLoad,
        StudentSectionCapacityService $sectionCapacity,
    )
    {
        $validated = $request->validate($this->rules($request, $enrollment));
        $student = Student::findOrFail($validated['student_id']);
        $sectionCapacity->ensureCapacity([
            'course' => $student->course,
            'year_level' => $student->year_level,
            'section' => $validated['section'],
        ], $student);

        $termChanged = (int) $validated['student_id'] !== $enrollment->student_id
            || (int) $validated['subject_id'] !== $enrollment->subject_id
            || $validated['school_year'] !== $enrollment->school_year
            || $validated['semester'] !== $enrollment->semester;

        if ($termChanged) {
            $loadError = $curriculumLoad->validateLoad(
                $student,
                $validated['school_year'],
                $validated['semester'],
                $validated['status'] === 'dropped' ? [] : [$validated['subject_id']],
                $enrollment,
            );

            if ($loadError) {
                return back()->withInput()->withErrors(['subject_load' => $loadError]);
            }

            $oldTermChanged = (int) $validated['student_id'] !== $enrollment->student_id
                || $validated['school_year'] !== $enrollment->school_year
                || $validated['semester'] !== $enrollment->semester;

            if ($oldTermChanged && $enrollment->status !== 'dropped') {
                $oldLoadError = $curriculumLoad->validateLoad(
                    $enrollment->student,
                    $enrollment->school_year,
                    $enrollment->semester,
                    [],
                    $enrollment,
                );

                if ($oldLoadError) {
                    return back()->withInput()->withErrors(['subject_load' => $oldLoadError]);
                }
            }
        }

        $enrollmentData = $validated;
        unset($enrollmentData['section']);

        DB::transaction(function () use ($enrollment, $student, $validated, $enrollmentData) {
            $student->update(['section' => $validated['section']]);
            $enrollment->update($enrollmentData);
        });

        return redirect()->route('enrollments.show', $enrollment)
            ->with('success', 'Enrollment updated successfully.');
    }

    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();

        return redirect()->route('enrollments.index')
            ->with('success', 'Enrollment deleted successfully.');
    }

    private function formOptions(): array
    {
        $students = Student::query()->orderBy('last_name')->orderBy('first_name')->get();
        $sectionCounts = $students
            ->filter(fn (Student $student) => $student->section !== null)
            ->groupBy(fn (Student $student) => serialize([$student->course, $student->year_level, $student->section]))
            ->map(fn ($studentsInSection) => $studentsInSection->count());
        $curricula = Curriculum::query()->with('subjects')->latest('id')->get();
        $curriculumLoad = app(CurriculumLoadService::class);

        return [
            'students' => $students,
            'subjects' => Subject::query()->orderBy('code')->get(),
            'studentCurriculumIds' => $students->mapWithKeys(fn (Student $student) => [
                $student->id => $curriculumLoad->findCurriculumForStudent($student, $curricula)?->id,
            ]),
            'studentYearLevels' => $students->mapWithKeys(fn (Student $student) => [
                $student->id => $curriculumLoad->yearLevelForStudent($student),
            ]),
            'studentSectionAvailability' => $students->mapWithKeys(fn (Student $student) => [
                $student->id => collect(self::SECTIONS)->mapWithKeys(function (string $section) use ($student, $sectionCounts) {
                    $count = $sectionCounts->get(serialize([$student->course, $student->year_level, $section]), 0);
                    if ($student->section === $section) {
                        $count--;
                    }

                    return [$section => max(0, StudentSectionCapacityService::MAXIMUM_STUDENTS_PER_BLOCK - $count)];
                }),
            ]),
            'curriculumOptions' => $curriculumLoad->curriculumOptions($curricula),
            'sections' => self::SECTIONS,
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(Request $request, ?Enrollment $enrollment = null): array
    {
        $uniqueEnrollment = Rule::unique('enrollments', 'student_id')
            ->where('subject_id', $request->input('subject_id'))
            ->where('school_year', $request->input('school_year'))
            ->where('semester', $request->input('semester'));

        if ($enrollment) {
            $uniqueEnrollment->ignore($enrollment->id);
        }

        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id'), $uniqueEnrollment],
            'section' => ['required', 'string', Rule::in(self::SECTIONS)],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'school_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', 'string', Rule::in(['First', 'Second', 'Summer'])],
            'status' => ['required', 'string', Rule::in(['enrolled', 'completed', 'dropped'])],
            'enrolled_at' => ['nullable', 'date'],
        ];
    }
}
