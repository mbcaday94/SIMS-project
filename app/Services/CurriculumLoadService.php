<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Collection;

class CurriculumLoadService
{
    public function curriculumForStudent(Student $student): ?Curriculum
    {
        return $this->findCurriculumForStudent($student, Curriculum::query()->with('subjects')->latest('id')->get());
    }

    public function findCurriculumForStudent(Student $student, Collection $curricula): ?Curriculum
    {
        return $this->findCurriculumForProgram($student->course, $curricula);
    }

    public function findCurriculumForProgram(string $program, Collection $curricula): ?Curriculum
    {
        $course = $this->normalize($program);
        if ($course === '') {
            return null;
        }

        return $curricula
            ->first(fn (Curriculum $curriculum) => in_array($course, $this->programAliases($curriculum->program), true));
    }

    /**
     * @return array{required: array<int, array<string, mixed>>, electives: array<int, array<string, mixed>>, required_units: int}
     */
    public function subjectOfferings(string $program, string $yearLevel, string $semester, Collection $curricula): array
    {
        $curriculum = $this->findCurriculumForProgram($program, $curricula);
        $year = $this->normalizeYearLevel($yearLevel);
        $term = $this->normalizeSemester($semester);

        if (! $curriculum || ! $year) {
            return ['required' => [], 'electives' => [], 'required_units' => 0];
        }

        $curriculumOptions = $this->curriculumOptions(collect([$curriculum]));

        return $curriculumOptions[$curriculum->id][$year][$term]
            ?? ['required' => [], 'electives' => [], 'required_units' => 0];
    }

    public function yearLevelForStudent(Student $student): ?string
    {
        return $this->normalizeYearLevel($student->year_level);
    }

    public function curriculumOptions(Collection $curricula): array
    {
        $options = [];

        foreach ($curricula as $curriculum) {
            foreach ($curriculum->subjects as $subject) {
                $year = $this->normalizeYearLevel($subject->pivot->year_level)
                    ?? trim($subject->pivot->year_level);
                if ($year === 'Electives') {
                    $options[$curriculum->id]['approved_electives'][] = [
                        'id' => $subject->id,
                        'code' => $subject->pivot->curriculum_code ?: $subject->code,
                        'name' => $subject->pivot->subject_name ?: $subject->name,
                        'units' => (int) ($subject->pivot->units ?? $subject->units),
                    ];

                    continue;
                }

                $semester = $this->normalizeSemester($subject->pivot->semester);
                $term = &$options[$curriculum->id][$year][$semester];
                $term ??= ['required' => [], 'electives' => [], 'required_units' => 0];

                $entry = [
                    'id' => $subject->id,
                    'code' => $subject->pivot->curriculum_code ?: $subject->code,
                    'name' => $subject->pivot->subject_name ?: $subject->name,
                    'units' => (int) ($subject->pivot->units ?? $subject->units),
                ];

                $term['required'][] = $entry;
                $term['required_units'] += $entry['units'];

                unset($term);
            }
        }

        foreach ($options as &$curriculumOptions) {
            $approvedElectives = $curriculumOptions['approved_electives'] ?? [];
            unset($curriculumOptions['approved_electives']);
            foreach ($curriculumOptions as &$yearOptions) {
                foreach ($yearOptions as &$term) {
                    $term['electives'] = $approvedElectives;
                }
                unset($term);
            }
            unset($yearOptions);
        }
        unset($curriculumOptions);

        return $options;
    }

    public function validateLoad(
        Student $student,
        string $schoolYear,
        string $semester,
        array $newSubjectIds,
        ?Enrollment $exclude = null
    ): ?string {
        $existingIds = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->where('status', '!=', 'dropped')
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude->id))
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $selectedIds = array_values(array_unique([...$existingIds, ...array_map('intval', $newSubjectIds)]));
        if ($selectedIds === []) {
            return null;
        }

        $curriculum = $this->curriculumForStudent($student);
        $yearLevel = $this->yearLevelForStudent($student);

        if (! $curriculum || ! $yearLevel) {
            return 'No curriculum matches this student’s program and year level. Assign a matching curriculum before enrolling subjects.';
        }

        $required = $curriculum->subjects
            ->filter(fn ($subject) => $this->normalizeYearLevel($subject->pivot->year_level) === $yearLevel
                && $this->curriculumSemesterMatches($subject->pivot->semester, $semester));

        if ($required->isEmpty()) {
            return "No subjects are offered for the student’s {$yearLevel} {$semester} semester in the {$curriculum->name} curriculum.";
        }

        $electives = $curriculum->subjects->filter(
            fn ($subject) => $this->normalizeYearLevel($subject->pivot->year_level) === 'Electives'
        );
        $requiredIds = $required->pluck('id')->map(fn ($id) => (int) $id)->all();
        $electiveIds = $electives->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allowedIds = array_values(array_unique([...$requiredIds, ...$electiveIds]));
        $missingIds = array_diff($requiredIds, $selectedIds);
        $unexpectedIds = array_diff($selectedIds, $allowedIds);

        if ($missingIds !== []) {
            $missingCodes = $required
                ->filter(fn ($subject) => in_array((int) $subject->id, $missingIds, true))
                ->pluck('pivot.curriculum_code')
                ->filter()
                ->implode(', ');

            return 'Select every required subject from the student’s '.$yearLevel.' '.$semester
                .' semester in the '.$curriculum->name.' curriculum.'
                .($missingCodes !== '' ? ' Missing: '.$missingCodes.'.' : '');
        }

        if ($unexpectedIds !== []) {
            return 'Selected subjects must belong to the student’s curriculum term or its approved electives.';
        }

        $unitsById = $required->concat($electives)
            ->keyBy(fn ($subject) => (int) $subject->id)
            ->map(fn ($subject) => (int) ($subject->pivot->units ?? $subject->units));
        $totalUnits = collect($selectedIds)->sum(fn ($id) => $unitsById->get($id, 0));

        if ($totalUnits < 24) {
            return "The selected curriculum plan totals {$totalUnits} units. Add approved elective subjects to reach at least 24 units.";
        }

        return null;
    }

    private function curriculumSemesterMatches(string $curriculumSemester, string $semester): bool
    {
        return $this->normalizeSemester($curriculumSemester) === $this->normalizeSemester($semester);
    }

    private function normalizeSemester(string $semester): string
    {
        $normalized = $this->normalize($semester);

        return match (true) {
            str_contains($normalized, 'FIRST') || str_contains($normalized, '1ST') || $normalized === '1' || str_ends_with($normalized, '1') => 'First',
            str_contains($normalized, 'SECOND') || str_contains($normalized, '2ND') || $normalized === '2' || str_ends_with($normalized, '2') => 'Second',
            str_contains($normalized, 'SUMMER') => 'Summer',
            default => trim($semester),
        };
    }

    private function normalizeYearLevel(?string $yearLevel): ?string
    {
        $normalized = $this->normalize($yearLevel);

        return match (true) {
            $normalized === 'ELECTIVES' => 'Electives',
            str_contains($normalized, 'FIRST') || str_contains($normalized, '1ST') || in_array($normalized, ['1', '1YEAR', 'YEAR1'], true) => 'First Year',
            str_contains($normalized, 'SECOND') || str_contains($normalized, '2ND') || in_array($normalized, ['2', '2YEAR', 'YEAR2'], true) => 'Second Year',
            str_contains($normalized, 'THIRD') || str_contains($normalized, '3RD') || in_array($normalized, ['3', '3YEAR', 'YEAR3'], true) => 'Third Year',
            str_contains($normalized, 'FOURTH') || str_contains($normalized, '4TH') || in_array($normalized, ['4', '4YEAR', 'YEAR4'], true) => 'Fourth Year',
            default => null,
        };
    }

    private function programAliases(string $program): array
    {
        preg_match_all('/\(([^)]+)\)/', $program, $matches);
        $aliases = [$this->normalize($program)];

        foreach ($matches[1] ?? [] as $alias) {
            $aliases[] = $this->normalize($alias);
        }

        return array_values(array_unique(array_filter($aliases)));
    }

    private function normalize(?string $value): string
    {
        return strtoupper((string) preg_replace('/[^a-zA-Z0-9]+/', '', $value ?? ''));
    }
}
