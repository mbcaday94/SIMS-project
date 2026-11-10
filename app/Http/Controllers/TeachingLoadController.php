<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Curriculum;
use App\Models\Subject;
use App\Models\TeachingLoad;
use App\Services\CurriculumLoadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeachingLoadController extends Controller
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    private const SEMESTERS = ['First', 'Second', 'Summer'];

    private const MINIMUM_FACULTY_UNITS = 18;

    private const MAXIMUM_FACULTY_UNITS = 30;

    private const LECTURE_ROOMS = ['RM. 101', 'RM. 102', 'RM. 103', 'RM. 104', 'RM. 105'];

    private const LAB_ROOMS = ['ComLab 1', 'ComLab 2', 'ComLab 3', 'ComLab 4', 'ComLab 5'];

    public function index(Request $request): View
    {
        $schoolYears = TeachingLoad::query()
            ->select('school_year')
            ->distinct()
            ->orderByDesc('school_year')
            ->pluck('school_year')
            ->all();

        $filters = $request->validate([
            'school_year' => ['nullable', 'string', Rule::in($schoolYears)],
            'semester' => ['nullable', 'string', Rule::in(self::SEMESTERS)],
        ]);
        $schoolYear = $filters['school_year'] ?? null;
        $semester = $filters['semester'] ?? null;

        $loads = TeachingLoad::query()
            ->with(['faculty', 'subject'])
            ->when($schoolYear, fn (Builder $query) => $query->where('school_year', $schoolYear))
            ->when($semester, fn (Builder $query) => $query->where('semester', $semester))
            ->orderBy('start_time')
            ->get();

        $facultyUnitLoads = $loads
            ->filter(fn (TeachingLoad $load) => $load->faculty !== null)
            ->groupBy(fn (TeachingLoad $load) => implode('|', [
                $load->faculty_id,
                $load->school_year,
                $load->semester,
            ]))
            ->map(fn ($facultyLoads) => [
                'faculty' => $facultyLoads->first()->faculty,
                'school_year' => $facultyLoads->first()->school_year,
                'semester' => $facultyLoads->first()->semester,
                'assignments' => $facultyLoads->count(),
                'units' => $facultyLoads->sum('units'),
            ])
            ->sortBy(fn (array $load) => $load['school_year'].$load['semester'].$load['faculty']->name)
            ->values();

        $schedule = collect(self::DAYS)
            ->mapWithKeys(fn (string $day) => [
                $day => $loads
                    ->flatMap(fn (TeachingLoad $load) => collect($load->meetings())
                        ->filter(fn (array $meeting) => in_array($day, $meeting['days'], true))
                        ->map(fn (array $meeting) => ['load' => $load, ...$meeting]))
                    ->sortBy('start_time')
                    ->values(),
            ]);

        return view('teaching-loads.index', [
            'schedule' => $schedule,
            'schoolYears' => $schoolYears,
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'semesters' => self::SEMESTERS,
            'loadCount' => $loads->count(),
            'facultyUnitLoads' => $facultyUnitLoads,
            'minimumFacultyUnits' => self::MINIMUM_FACULTY_UNITS,
            'maximumFacultyUnits' => self::MAXIMUM_FACULTY_UNITS,
        ]);
    }

    public function create(): View
    {
        return view('teaching-loads.form', $this->formOptions(new TeachingLoad));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $this->ensureSubjectIsOffered($validated);
        $validated = $this->normalizeLaboratorySchedule($validated);
        $this->ensureNoScheduleConflict($validated);
        $validated['units'] = Subject::findOrFail($validated['subject_id'])->units;
        $this->ensureFacultyUnitLimit($validated);
        TeachingLoad::create($validated);

        return redirect()->route('teaching-loads.index', [
            'school_year' => $validated['school_year'],
            'semester' => $validated['semester'],
        ])->with(
            'success',
            $validated['faculty_id'] ? 'Subject schedule plotted and faculty assigned.' : 'Subject schedule plotted. Faculty can be assigned later.',
        );
    }

    public function edit(TeachingLoad $teachingLoad): View
    {
        return view('teaching-loads.form', $this->formOptions($teachingLoad));
    }

    public function update(Request $request, TeachingLoad $teachingLoad): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $this->ensureSubjectIsOffered($validated);
        $validated = $this->normalizeLaboratorySchedule($validated);
        $this->ensureNoScheduleConflict($validated, $teachingLoad);
        $validated['units'] = Subject::findOrFail($validated['subject_id'])->units;
        $this->ensureFacultyUnitLimit($validated, $teachingLoad);
        $teachingLoad->update($validated);

        return redirect()->route('teaching-loads.index', [
            'school_year' => $validated['school_year'],
            'semester' => $validated['semester'],
        ])->with('success', 'Teaching load updated.');
    }

    public function destroy(TeachingLoad $teachingLoad): RedirectResponse
    {
        $teachingLoad->delete();

        return redirect()->route('teaching-loads.index', [
            'school_year' => $teachingLoad->school_year,
            'semester' => $teachingLoad->semester,
        ])->with('success', 'Teaching load removed.');
    }

    private function formOptions(TeachingLoad $teachingLoad): array
    {
        $curricula = Curriculum::query()->with('subjects')->latest('id')->get();
        $curriculumLoad = app(CurriculumLoadService::class);
        $programOptions = $curricula
            ->unique('program')
            ->map(fn (Curriculum $curriculum) => [
                'id' => $curriculum->id,
                'program' => $curriculum->program,
                'year_levels' => array_keys($curriculumLoad->curriculumOptions(collect([$curriculum]))[$curriculum->id] ?? []),
            ])
            ->values();
        $curriculumOptions = $curriculumLoad->curriculumOptions($curricula);

        return [
            'teachingLoad' => $teachingLoad,
            'faculties' => Faculty::query()->orderBy('name')->get(),
            'subjects' => Subject::query()->orderBy('code')->get(),
            'programOptions' => $programOptions,
            'curriculumOptions' => $curriculumOptions,
            'selectedCurriculumId' => $curriculumLoad->findCurriculumForProgram($teachingLoad->program ?? '', $curricula)?->id,
            'conflictSchedules' => TeachingLoad::query()
                ->with(['faculty:id,name', 'subject:id,code'])
                ->when($teachingLoad->exists, fn (Builder $query) => $query->where('id', '!=', $teachingLoad->id))
                ->get()
                ->map(fn (TeachingLoad $load) => [
                    'faculty_id' => $load->faculty_id,
                    'faculty_name' => $load->faculty?->name,
                    'subject_code' => $load->subject?->code,
                    'school_year' => $load->school_year,
                    'semester' => $load->semester,
                    'meetings' => array_map(fn (array $meeting) => [
                        ...$meeting,
                        'start_time' => substr($meeting['start_time'], 0, 5),
                        'end_time' => substr($meeting['end_time'], 0, 5),
                    ], $load->meetings()),
                ])
                ->values(),
            'days' => self::DAYS,
            'semesters' => self::SEMESTERS,
            'lectureRooms' => self::LECTURE_ROOMS,
            'labRooms' => self::LAB_ROOMS,
            'minimumFacultyUnits' => self::MINIMUM_FACULTY_UNITS,
            'maximumFacultyUnits' => self::MAXIMUM_FACULTY_UNITS,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureSubjectIsOffered(array $data): void
    {
        $curricula = Curriculum::query()->with('subjects')->latest('id')->get();
        $curriculumLoad = app(CurriculumLoadService::class);
        $curriculum = $curriculumLoad->findCurriculumForProgram($data['program'], $curricula);

        if (! $curriculum) {
            return;
        }

        $offerings = $curriculumLoad->subjectOfferings(
            $data['program'],
            $data['year_level'],
            $data['semester'],
            $curricula,
        );
        $offeredIds = collect([...$offerings['required'], ...$offerings['electives']])
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if (! $offeredIds->contains((int) $data['subject_id'])) {
            throw ValidationException::withMessages([
                'subject_id' => 'Choose a subject offered for the selected program, year level, and semester.',
            ]);
        }
    }

    private function rules(): array
    {
        return [
            'faculty_id' => ['nullable', 'integer', Rule::exists('faculties', 'id')],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'program' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:50'],
            'school_year' => ['required', 'string', 'regex:/^\\d{4}-\\d{4}$/'],
            'semester' => ['required', 'string', Rule::in(self::SEMESTERS)],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'string', 'distinct', Rule::in(self::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'lab_days' => ['nullable', 'array', 'min:1', 'required_with:lab_start_time,lab_end_time'],
            'lab_days.*' => ['required', 'string', 'distinct', Rule::in(self::DAYS)],
            'lab_start_time' => ['nullable', 'required_with:lab_days', 'date_format:H:i'],
            'lab_end_time' => ['nullable', 'required_with:lab_days', 'date_format:H:i', 'after:lab_start_time'],
            'lecture_room' => ['required', 'string', Rule::in(self::LECTURE_ROOMS)],
            'lab_room' => ['nullable', 'required_with:lab_days', 'string', Rule::in(self::LAB_ROOMS)],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeLaboratorySchedule(array $data): array
    {
        $data['lab_days'] = $data['lab_days'] ?? null;
        $data['lab_start_time'] = $data['lab_start_time'] ?? null;
        $data['lab_end_time'] = $data['lab_end_time'] ?? null;
        $data['lab_room'] = $data['lab_days'] ? ($data['lab_room'] ?? null) : null;
        $data['room'] = $data['lecture_room'];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureNoScheduleConflict(array $data, ?TeachingLoad $ignore = null): void
    {
        $candidate = new TeachingLoad($data);
        $candidateMeetings = $candidate->meetings();

        if (isset($candidateMeetings[1])) {
            $lecture = $candidateMeetings[0];
            $lab = $candidateMeetings[1];
            if ($this->meetingsOverlap($lecture, $lab)) {
                throw ValidationException::withMessages([
                    'schedule' => 'The lecture and laboratory schedules cannot overlap.',
                ]);
            }
        }

        $existingLoads = TeachingLoad::query()
            ->with(['faculty', 'subject'])
            ->where('school_year', $data['school_year'])
            ->where('semester', $data['semester'])
            ->when($ignore, fn (Builder $query) => $query->where('id', '!=', $ignore->id))
            ->get();

        foreach ($candidateMeetings as $candidateMeeting) {
            foreach ($existingLoads as $existing) {
                foreach ($existing->meetings() as $existingMeeting) {
                    if (! $this->meetingsOverlap($candidateMeeting, $existingMeeting)) {
                        continue;
                    }

                    if ($data['faculty_id'] !== null && (int) $existing->faculty_id === (int) $data['faculty_id']) {
                        throw ValidationException::withMessages([
                            'schedule' => "{$existing->faculty->name} already has {$existing->subject->code} scheduled during an overlapping time.",
                        ]);
                    }

                    if (mb_strtolower(trim($existingMeeting['room'])) === mb_strtolower(trim($candidateMeeting['room']))) {
                        throw ValidationException::withMessages([
                            'schedule' => "Room {$candidateMeeting['room']} is already booked during an overlapping time.",
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @param  array{days: array<int, string>, start_time: string, end_time: string}  $first
     * @param  array{days: array<int, string>, start_time: string, end_time: string}  $second
     */
    private function meetingsOverlap(array $first, array $second): bool
    {
        return array_intersect($first['days'], $second['days']) !== []
            && $first['start_time'] < $second['end_time']
            && $first['end_time'] > $second['start_time'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureFacultyUnitLimit(array $data, ?TeachingLoad $ignore = null): void
    {
        if ($data['faculty_id'] === null) {
            return;
        }

        $currentUnits = TeachingLoad::query()
            ->where('faculty_id', $data['faculty_id'])
            ->where('school_year', $data['school_year'])
            ->where('semester', $data['semester'])
            ->when($ignore, fn (Builder $query) => $query->where('id', '!=', $ignore->id))
            ->sum('units');
        $totalUnits = $currentUnits + $data['units'];

        if ($totalUnits > self::MAXIMUM_FACULTY_UNITS) {
            throw ValidationException::withMessages([
                'faculty_id' => "This assignment would bring the faculty member to {$totalUnits} units for {$data['school_year']} {$data['semester']}. The maximum load is ".self::MAXIMUM_FACULTY_UNITS.' units.',
            ]);
        }
    }
}
