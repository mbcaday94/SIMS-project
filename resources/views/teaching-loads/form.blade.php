@extends('students.layout')

@section('title', $teachingLoad->exists ? 'Edit Subject Schedule' : 'Plot Subject Schedule')

@section('content')
    <style>
        .load-form-layout { display:grid;grid-template-columns:minmax(0,1fr) 310px;align-items:start;gap:1rem; }
        .load-form-card { min-width:0;padding:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.045); }
        .load-form-card h1 { margin-bottom:.35rem;color:#172554;font-size:1.35rem;font-weight:800; }
        .load-form-card > p { margin-bottom:1.3rem;color:#64748b;font-size:.82rem; }
        .load-form-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem; }
        .load-field { display:grid;align-content:start;gap:.4rem;min-width:0; }
        .load-field.full { grid-column:1/-1; }
        .load-field label,.load-field legend { color:#374151;font-size:.78rem;font-weight:700; }
        .load-field input,.load-field select { width:100%;min-height:42px;padding:.6rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font:inherit;font-size:.84rem; }
        .load-field input:focus,.load-field select:focus { outline:2px solid #bfdbfe;border-color:#3b82f6; }
        .load-days { display:flex;gap:.45rem;flex-wrap:wrap; }
        .load-day-option { display:flex;align-items:center;gap:.35rem;padding:.45rem .6rem;border:1px solid #e5e7eb;border-radius:8px;color:#374151;font-size:.75rem; }
        .load-day-option input { width:auto;min-height:auto; }
        .load-form-actions { display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1.3rem; }
        .load-error { color:#b91c1c;font-size:.74rem; }
        .conflict-panel { position:sticky;top:1rem;overflow:hidden;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.045); }
        .conflict-panel-header { padding:1rem 1.05rem;border-bottom:1px solid #edf0f6; }
        .conflict-panel-header h2 { color:#172554;font-size:.95rem;font-weight:750; }
        .conflict-panel-header p { margin-top:.3rem;color:#64748b;font-size:.73rem;line-height:1.5; }
        .conflict-panel-body { display:grid;gap:.65rem;padding:1rem 1.05rem; }
        .conflict-status { padding:.7rem .75rem;border-radius:9px;background:#f1f5f9;color:#64748b;font-size:.78rem;line-height:1.5; }
        .conflict-status.clear { background:#ecfdf3;color:#16803c; }
        .conflict-status.warning { background:#fff1f2;color:#b42332; }
        .conflict-item { display:grid;gap:.25rem;padding:.7rem .75rem;border:1px solid #fecaca;border-radius:9px;background:#fff8f8;color:#9f2532;font-size:.75rem;line-height:1.5; }
        .conflict-item strong { font-size:.78rem; }
        .conflict-item span { color:#7f3039; }
        @media(max-width:900px) { .load-form-layout { grid-template-columns:minmax(0,1fr) 270px; } }
        @media(max-width:760px) { .load-form-layout { grid-template-columns:1fr; }.conflict-panel { position:static;grid-row:1; } }
        @media(max-width:600px) { .load-form-grid { grid-template-columns:1fr; }.load-field.full { grid-column:auto; } }
    </style>

    <div class="load-form-layout">
    <section class="load-form-card">
        <h1>{{ $teachingLoad->exists ? 'Edit Subject Schedule' : 'Plot Subject Schedule' }}</h1>
        <p>Plot the subject, section, term, and weekly class schedule first. Faculty may be assigned now or later.</p>

        @if ($errors->any())
            <div class="alert alert-error" role="alert">Please review the highlighted errors below.</div>
        @endif

        <form method="POST" action="{{ $teachingLoad->exists ? route('teaching-loads.update', $teachingLoad) : route('teaching-loads.store') }}">
            @csrf
            @if ($teachingLoad->exists)
                @method('PUT')
            @endif

            <div class="load-form-grid">
                <div class="load-field">
                    <label for="faculty_id">Faculty <span style="font-weight:400;">(optional)</span></label>
                    <select id="faculty_id" name="faculty_id">
                        <option value="">Unassigned — assign later</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}" @selected((string) old('faculty_id', $teachingLoad->faculty_id) === (string) $faculty->id)>{{ $faculty->name }}{{ $faculty->employee_number ? ' · '.$faculty->employee_number : '' }}</option>
                        @endforeach
                    </select>
                    @error('faculty_id') <span class="load-error">{{ $message }}</span> @enderror
                    @if ($faculties->isEmpty())
                        <span class="load-error">No faculty yet. You can save the schedule now and <a href="{{ route('faculties.create') }}">add faculty</a> later.</span>
                    @endif
                </div>
                <div class="load-field">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" required>
                        <option value="">Select semester</option>
                        @foreach ($semesters as $term)
                            <option value="{{ $term }}" @selected(old('semester', $teachingLoad->semester) === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                    @error('semester') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <div class="load-field">
                    <label for="program">Program</label>
                    <select id="program" name="program" required>
                        <option value="">Select program</option>
                        @if ($teachingLoad->program && ! $programOptions->contains('program', old('program', $teachingLoad->program)))
                            <option value="{{ old('program', $teachingLoad->program) }}" data-curriculum-id="{{ old('program') === $teachingLoad->program ? $selectedCurriculumId : '' }}" @selected(true)>{{ old('program', $teachingLoad->program) }} (existing schedule)</option>
                        @endif
                        @foreach ($programOptions as $programOption)
                            <option value="{{ $programOption['program'] }}" data-curriculum-id="{{ $programOption['id'] }}" data-year-levels="{{ json_encode($programOption['year_levels']) }}" @selected(old('program', $teachingLoad->program) === $programOption['program'])>{{ $programOption['program'] }}</option>
                        @endforeach
                    </select>
                    @error('program') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <div class="load-field">
                    <label for="year_level">Year level</label>
                    <select id="year_level" name="year_level" required>
                        <option value="">Select program first</option>
                    </select>
                    @error('year_level') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <div class="load-field full">
                    <label for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" required disabled>
                        <option value="">Choose semester, program, and year level first</option>
                    </select>
                    @error('subject_id') <span class="load-error">{{ $message }}</span> @enderror
                    <span id="subject-offering-guidance" class="load-error" style="color:#64748b;" aria-live="polite">Subjects are organized by curriculum, year level, and semester.</span>
                    <span class="load-error" style="color:#64748b;">Subject catalog units are added to the faculty's term load. Minimum {{ $minimumFacultyUnits }} units, overload above {{ $minimumFacultyUnits }}, maximum {{ $maximumFacultyUnits }} units.</span>
                </div>
                <div class="load-field">
                    <label for="section">Section</label>
                    <input id="section" name="section" value="{{ old('section', $teachingLoad->section) }}" maxlength="50" placeholder="e.g. A" required>
                    @error('section') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <div class="load-field">
                    <label for="school_year">School year</label>
                    <input id="school_year" name="school_year" value="{{ old('school_year', $teachingLoad->school_year) }}" placeholder="2026-2027" pattern="\d{4}-\d{4}" required>
                    @error('school_year') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <fieldset class="load-field full" style="border:0;">
                    <legend>Lecture schedule</legend>
                    <div class="load-days">
                        @foreach ($days as $day)
                            <label class="load-day-option">
                                <input type="checkbox" name="days[]" value="{{ $day }}" @checked(in_array($day, old('days', $teachingLoad->days ?? []), true))>
                                {{ substr($day, 0, 3) }}
                            </label>
                        @endforeach
                    </div>
                    @error('days') <span class="load-error">{{ $message }}</span> @enderror
                    @error('days.*') <span class="load-error">{{ $message }}</span> @enderror
                </fieldset>
                <div class="load-field">
                    <label for="start_time">Lecture start time</label>
                    <input type="time" id="start_time" name="start_time" value="{{ old('start_time', $teachingLoad->start_time ? substr($teachingLoad->start_time, 0, 5) : '') }}" required>
                    @error('start_time') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <div class="load-field">
                    <label for="end_time">Lecture end time</label>
                    <input type="time" id="end_time" name="end_time" value="{{ old('end_time', $teachingLoad->end_time ? substr($teachingLoad->end_time, 0, 5) : '') }}" required>
                    @error('end_time') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <fieldset class="load-field full" style="border:0;">
                    <legend>Laboratory schedule <span style="font-weight:400;">(optional)</span></legend>
                    <div class="load-days">
                        @foreach ($days as $day)
                            <label class="load-day-option">
                                <input type="checkbox" name="lab_days[]" value="{{ $day }}" @checked(in_array($day, old('lab_days', $teachingLoad->lab_days ?? []), true))>
                                {{ substr($day, 0, 3) }}
                            </label>
                        @endforeach
                    </div>
                    @error('lab_days') <span class="load-error">{{ $message }}</span> @enderror
                    @error('lab_days.*') <span class="load-error">{{ $message }}</span> @enderror
                </fieldset>
                <div class="load-field">
                    <label for="lab_start_time">Laboratory start time</label>
                    <input type="time" id="lab_start_time" name="lab_start_time" value="{{ old('lab_start_time', $teachingLoad->lab_start_time ? substr($teachingLoad->lab_start_time, 0, 5) : '') }}">
                    @error('lab_start_time') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <div class="load-field">
                    <label for="lab_end_time">Laboratory end time</label>
                    <input type="time" id="lab_end_time" name="lab_end_time" value="{{ old('lab_end_time', $teachingLoad->lab_end_time ? substr($teachingLoad->lab_end_time, 0, 5) : '') }}">
                    @error('lab_end_time') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <div class="load-field">
                    <label for="lecture_room">Lecture room</label>
                    <select id="lecture_room" name="lecture_room" required>
                        <option value="">Select lecture room</option>
                        @foreach ($lectureRooms as $room)
                            <option value="{{ $room }}" @selected(old('lecture_room', $teachingLoad->lecture_room ?: $teachingLoad->room) === $room)>{{ $room }}</option>
                        @endforeach
                    </select>
                    @error('lecture_room') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                <div class="load-field">
                    <label for="lab_room">Computer laboratory <span style="font-weight:400;">(optional)</span></label>
                    <select id="lab_room" name="lab_room">
                        <option value="">Select computer laboratory</option>
                        @foreach ($labRooms as $room)
                            <option value="{{ $room }}" @selected(old('lab_room', $teachingLoad->lab_room) === $room)>{{ $room }}</option>
                        @endforeach
                    </select>
                    @error('lab_room') <span class="load-error">{{ $message }}</span> @enderror
                </div>
                @error('schedule')
                    <div class="load-field full"><span class="load-error" role="alert">{{ $message }}</span></div>
                @enderror
            </div>
            <div class="load-form-actions">
                <button type="submit" class="btn btn-primary">
                    @if ($teachingLoad->exists && $teachingLoad->faculty_id)
                        Save schedule and faculty
                    @elseif ($teachingLoad->exists)
                        Save schedule{{ old('faculty_id') ? ' and assign faculty' : '' }}
                    @elseif (old('faculty_id'))
                        Plot schedule and assign faculty
                    @else
                        Plot subject schedule
                    @endif
                </button>
                <a href="{{ route('teaching-loads.index') }}" class="btn btn-secondary">Cancel</a>
                @if ($teachingLoad->exists)
                    <button type="submit" form="delete-teaching-load" class="btn btn-danger" onclick="return confirm('Remove this teaching load?')">Remove assignment</button>
                @endif
            </div>
        </form>
        @if ($teachingLoad->exists)
            <form id="delete-teaching-load" method="POST" action="{{ route('teaching-loads.destroy', $teachingLoad) }}">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </section>
    <aside class="conflict-panel" aria-labelledby="conflict-panel-title">
        <div class="conflict-panel-header">
            <h2 id="conflict-panel-title">Schedule conflict preview</h2>
            <p>Conflicts for the selected faculty, school year, semester, days, and times appear here as you edit the schedule.</p>
        </div>
        <div class="conflict-panel-body" id="conflict-preview" aria-live="polite">
            <div class="conflict-status">Choose a school year and semester to check for conflicts.</div>
        </div>
    </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const programSelect = document.querySelector('#program');
            const yearSelect = document.querySelector('#year_level');
            const semesterSelect = document.querySelector('#semester');
            const subjectSelect = document.querySelector('#subject_id');
            const guidance = document.querySelector('#subject-offering-guidance');
            const curriculumOptions = {{ Illuminate\Support\Js::from($curriculumOptions) }};
            const initialYearLevel = @json(old('year_level', $teachingLoad->year_level));
            const initialSubjectId = @json((string) old('subject_id', $teachingLoad->subject_id ?? ''));
            if (!programSelect || !yearSelect || !semesterSelect || !subjectSelect || !guidance) return;

            const curriculum = () => {
                const curriculumId = programSelect.selectedOptions[0]?.dataset.curriculumId;
                return curriculumOptions[curriculumId] ?? {};
            };

            const updateYearLevels = (preferredYear = '') => {
                const years = Object.keys(curriculum()).filter(year => year !== 'approved_electives');
                yearSelect.replaceChildren(new Option(years.length ? 'Select year level' : 'No curriculum year levels available', ''));
                years.forEach(year => yearSelect.add(new Option(year, year)));
                if (years.includes(preferredYear)) yearSelect.value = preferredYear;
            };

            const updateSubjects = () => {
                const previousSubjectId = subjectSelect.value || initialSubjectId;
                const term = curriculum()[yearSelect.value]?.[semesterSelect.value];
                const categories = [
                    ['Required subjects', term?.required ?? []],
                    ['Approved electives', term?.electives ?? []],
                ];
                subjectSelect.replaceChildren(new Option('Select an offered subject', ''));
                let optionCount = 0;

                categories.forEach(([label, subjects]) => {
                    if (!subjects.length) return;
                    const group = document.createElement('optgroup');
                    group.label = label;
                    [...subjects]
                        .sort((first, second) => first.code.localeCompare(second.code, undefined, { numeric: true, sensitivity: 'base' }))
                        .forEach(subject => {
                            const option = new Option(
                                `${subject.code} · ${subject.name} (${subject.units} ${subject.units === 1 ? 'unit' : 'units'})`,
                                subject.id,
                            );
                            group.append(option);
                            optionCount++;
                        });
                    subjectSelect.append(group);
                });

                subjectSelect.disabled = optionCount === 0;
                if ([...subjectSelect.options].some(option => option.value === previousSubjectId)) {
                    subjectSelect.value = previousSubjectId;
                }

                guidance.textContent = optionCount
                    ? `${optionCount} subject${optionCount === 1 ? '' : 's'} offered for this program, year level, and semester.`
                    : 'No subjects are offered for this program, year level, and semester. Check the curriculum setup.';
            };

            programSelect.addEventListener('change', () => {
                updateYearLevels();
                updateSubjects();
            });
            yearSelect.addEventListener('change', updateSubjects);
            semesterSelect.addEventListener('change', updateSubjects);

            updateYearLevels(initialYearLevel || yearSelect.value);
            updateSubjects();
        });
    </script>

    <script>
        (() => {
            const existingSchedules = {{ Illuminate\Support\Js::from($conflictSchedules) }};
            const initialize = () => {
                const form = document.querySelector('.load-form-card form');
                const preview = document.getElementById('conflict-preview');
                if (!form || !preview) return;

                const field = (name) => form.elements.namedItem(name)?.value ?? '';
                const selectedDays = (name) => Array.from(form.querySelectorAll(`input[name="${name}"]:checked`), (input) => input.value);
                const overlaps = (first, second) =>
                    first.days.some((day) => second.days.includes(day))
                    && first.start_time < second.end_time
                    && first.end_time > second.start_time;
                const formatTime = (time) => {
                    if (!time) return '';
                    const [hour, minute] = time.split(':').map(Number);
                    const suffix = hour >= 12 ? 'PM' : 'AM';
                    return `${hour % 12 || 12}:${String(minute).padStart(2, '0')} ${suffix}`;
                };

                const renderConflicts = () => {
                    const schoolYear = field('school_year').trim();
                    const semester = field('semester');
                    const facultyId = field('faculty_id');
                    const meetings = [];
                    const lectureDays = selectedDays('days[]');
                    const startTime = field('start_time');
                    const endTime = field('end_time');
                    const hasLectureInput = lectureDays.length > 0 || startTime || endTime;
                    const hasIncompleteLecture = hasLectureInput && !(lectureDays.length && startTime && endTime);
                    if (lectureDays.length && startTime && endTime) {
                        meetings.push({ type: 'Lecture', days: lectureDays, start_time: startTime, end_time: endTime, room: field('lecture_room') });
                    }

                    const labDays = selectedDays('lab_days[]');
                    const labStart = field('lab_start_time');
                    const labEnd = field('lab_end_time');
                    const hasLabInput = labDays.length > 0 || labStart || labEnd;
                    const hasIncompleteLab = hasLabInput && !(labDays.length && labStart && labEnd);
                    if (labDays.length && labStart && labEnd) {
                        meetings.push({ type: 'Laboratory', days: labDays, start_time: labStart, end_time: labEnd, room: field('lab_room') });
                    }

                    const conflicts = [];
                    if (meetings.length === 2 && overlaps(meetings[0], meetings[1])) {
                        conflicts.push({
                            title: 'This class has an internal schedule conflict',
                            detail: 'The lecture and laboratory overlap on at least one selected day.',
                        });
                    }

                    if (schoolYear && semester) {
                        existingSchedules
                            .filter((schedule) => schedule.school_year === schoolYear && schedule.semester === semester)
                            .forEach((schedule) => {
                                schedule.meetings.forEach((existingMeeting) => {
                                    meetings.forEach((meeting) => {
                                        if (!overlaps(meeting, existingMeeting)) return;

                                        const days = meeting.days.filter((day) => existingMeeting.days.includes(day)).join(', ');
                                        const time = `${formatTime(existingMeeting.start_time)}–${formatTime(existingMeeting.end_time)}`;
                                        if (facultyId && String(schedule.faculty_id) === facultyId) {
                                            conflicts.push({
                                                title: `${schedule.faculty_name} is already assigned at this time`,
                                                detail: `${schedule.subject_code} · ${existingMeeting.type} · ${days} ${time}`,
                                            });
                                        }
                                        if (meeting.room && existingMeeting.room
                                            && existingMeeting.room.trim().toLocaleLowerCase() === meeting.room.trim().toLocaleLowerCase()) {
                                            conflicts.push({
                                                title: `Room ${meeting.room} is already booked`,
                                                detail: `${schedule.subject_code} · ${existingMeeting.type} · ${days} ${time}`,
                                            });
                                        }
                                    });
                                });
                            });
                    }

                    preview.replaceChildren();
                    if (conflicts.length) {
                        conflicts.forEach((conflict) => {
                            const item = document.createElement('div');
                            item.className = 'conflict-item';
                            const title = document.createElement('strong');
                            title.textContent = conflict.title;
                            const detail = document.createElement('span');
                            detail.textContent = conflict.detail;
                            item.append(title, detail);
                            preview.append(item);
                        });
                        return;
                    }

                    const status = document.createElement('div');
                    const incompleteSchedule = hasIncompleteLecture || hasIncompleteLab;
                    status.className = `conflict-status${meetings.length && schoolYear && semester && !incompleteSchedule ? ' clear' : ''}`;
                    status.textContent = incompleteSchedule
                        ? 'Select at least one day and enter both class times to preview conflicts.'
                        : !schoolYear || !semester
                            ? 'Choose a school year and semester to check for conflicts.'
                            : meetings.length
                                ? 'No schedule or faculty conflicts found for the selected term.'
                                : 'Select the scheduled days and class times to check for conflicts.';
                    preview.append(status);
                };

                form.addEventListener('input', renderConflicts);
                form.addEventListener('change', renderConflicts);
                renderConflicts();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialize, { once: true });
            } else {
                initialize();
            }
        })();
    </script>
@endsection
