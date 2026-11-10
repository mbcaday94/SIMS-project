<div class="form-grid">
    <div class="form-group full">
        <label for="student_id">Student <span class="req">*</span></label>
        <select id="student_id" name="student_id" class="form-control no-icon @error('student_id') is-invalid @enderror" required>
            <option value="">Select a student</option>
            @foreach ($students as $student)
                <option value="{{ $student->id }}" data-curriculum-id="{{ $studentCurriculumIds[$student->id] ?? '' }}" data-year-level="{{ $studentYearLevels[$student->id] ?? '' }}" data-section-slots="{{ json_encode($studentSectionAvailability[$student->id]) }}" @selected((string) old('student_id', $enrollment->student_id ?? '') === (string) $student->id)>
                    {{ $student->student_number }} — {{ $student->last_name }}, {{ $student->first_name }} · {{ $student->course }} · {{ $studentYearLevels[$student->id] ?? 'Year level not set' }} · Section {{ $student->section ?? '—' }}
                </option>
            @endforeach
        </select>
        @error('student_id') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="enrollment_year_level">Year Level</label>
        <input
            id="enrollment_year_level"
            class="form-control no-icon"
            value="{{ old('student_id', $enrollment->student_id ?? '') ? ($studentYearLevels[old('student_id', $enrollment->student_id ?? '')] ?? 'Year level not set') : '' }}"
            placeholder="Select a student to view year level"
            readonly
            aria-live="polite"
        >
    </div>

    <div class="form-group">
        <label for="section">Block / Section <span class="req">*</span></label>
        <select id="section" name="section" class="form-control no-icon @error('section') is-invalid @enderror" required>
            <option value="">Select block / section</option>
            @foreach ($sections as $section)
                <option value="{{ $section }}" data-section-label="Section {{ $section }}" @selected(old('section', $enrollment->student->section ?? '') === $section)>Section {{ $section }}</option>
            @endforeach
        </select>
        @error('section') <p class="error-msg">{{ $message }}</p> @enderror
        <p class="form-hint" data-section-availability aria-live="polite">Select a student to view available slots for each block.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const studentSelect = document.querySelector('#student_id');
            const sectionSelect = document.querySelector('#section');
            const yearLevelDisplay = document.querySelector('#enrollment_year_level');
            if (!studentSelect || !sectionSelect || !yearLevelDisplay) return;

            const updateSectionAvailability = () => {
                const student = studentSelect.selectedOptions[0];
                yearLevelDisplay.value = student?.dataset.yearLevel || (student?.value ? 'Year level not set' : '');
                const availability = student?.dataset.sectionSlots
                    ? JSON.parse(student.dataset.sectionSlots)
                    : {};

                [...sectionSelect.options].forEach(option => {
                    if (!option.value) return;
                    const slots = availability[option.value];
                    option.disabled = Number.isInteger(slots) && slots === 0;
                    option.textContent = Number.isInteger(slots)
                        ? `${option.dataset.sectionLabel} — ${slots} ${slots === 1 ? 'slot' : 'slots'} available`
                        : `${option.dataset.sectionLabel} — select a student`;
                });

                const selectedSection = sectionSelect.selectedOptions[0];
                if (selectedSection?.disabled) sectionSelect.value = '';
            };

            studentSelect.addEventListener('change', updateSectionAvailability);
            updateSectionAvailability();
        });
    </script>

    @if (isset($enrollment))
        <div class="form-group full">
            <label for="subject_id">Subject <span class="req">*</span></label>
            <select id="subject_id" name="subject_id" class="form-control no-icon @error('subject_id') is-invalid @enderror" required>
                <option value="">Select a subject</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" data-subject-units="{{ $subject->units }}" hidden @selected((string) old('subject_id', $enrollment->subject_id) === (string) $subject->id)>
                        {{ $subject->code }} — {{ $subject->name }} ({{ $subject->units }} units)
                    </option>
                @endforeach
            </select>
            @error('subject_id') <p class="error-msg">{{ $message }}</p> @enderror
            <p class="subject-load-guidance" data-load-guidance aria-live="polite"></p>
            @error('subject_load') <p class="error-msg">{{ $message }}</p> @enderror
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const studentSelect = document.querySelector('#student_id');
                const semesterSelect = document.querySelector('#semester');
                const curriculumOptions = {{ Illuminate\Support\Js::from($curriculumOptions) }};
                const subjectSelect = document.querySelector('#subject_id');
                const guidance = document.querySelector('[data-load-guidance]');
                if (!studentSelect || !semesterSelect || !subjectSelect || !guidance) return;

                const updateSubjects = () => {
                    const student = studentSelect.selectedOptions[0];
                    const curriculumId = student?.dataset.curriculumId;
                    const yearLevel = student?.dataset.yearLevel;
                    const term = curriculumOptions[curriculumId]?.[yearLevel]?.[semesterSelect.value] ?? null;
                    const offeredIds = new Set([...(term?.required ?? []), ...(term?.electives ?? [])].map(subject => String(subject.id)));

                    [...subjectSelect.options].forEach(option => {
                        if (option.value) option.hidden = !offeredIds.has(option.value);
                    });
                    if (subjectSelect.value && !offeredIds.has(subjectSelect.value)) subjectSelect.value = '';

                    guidance.textContent = term
                        ? `Only subjects offered in this curriculum term and its approved electives are available. Required plan: ${term.required_units} units.`
                        : 'Select a student and semester with subjects in their curriculum. No other subjects are available for enrollment.';
                };

                studentSelect.addEventListener('change', updateSubjects);
                semesterSelect.addEventListener('change', updateSubjects);
                updateSubjects();
            });
        </script>
    @else
        @php
            $selectedSubjectIds = collect(old('subject_ids', []))->map(fn ($id) => (string) $id)->all();
        @endphp
        <div class="form-group full">
            <label id="subjects-label">Subjects <span class="req">*</span></label>
            <div class="subject-picker" data-subject-picker>
                <div class="subject-picker-toolbar">
                    <input type="search" class="form-control no-icon subject-search" data-subject-search placeholder="Search by subject code or name" aria-label="Search subjects">
                    <div class="subject-picker-actions">
                        <button type="button" class="btn btn-secondary btn-sm" data-select-visible>Select visible</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-clear-selection>Clear</button>
                    </div>
                </div>
                <p id="subject-selection-count" class="subject-selection-count" data-selection-count aria-live="polite">0 subjects selected</p>
                <p class="subject-load-guidance" data-load-guidance aria-live="polite">Choose a student to see the suggested courses for their year level and semester. Required subjects are selected automatically.</p>
                <div class="subject-options @error('subject_ids') is-invalid @enderror" role="group" aria-labelledby="subjects-label" aria-describedby="subject-selection-count">
                    @foreach ($subjects as $subject)
                        <label class="subject-option" data-subject-option data-search-text="{{ strtolower($subject->code.' '.$subject->name) }}" hidden>
                            <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" @checked(in_array((string) $subject->id, $selectedSubjectIds, true))>
                            <span class="subject-option-copy">
                                <strong>{{ $subject->code }}</strong>
                                <span>{{ $subject->name }}</span>
                            </span>
                            <span class="subject-option-units" data-subject-units data-catalog-units="{{ $subject->units }}">{{ $subject->units }} units</span>
                            <span class="subject-option-required" data-required-label hidden></span>
                        </label>
                    @endforeach
                </div>
            </div>
            @error('subject_ids') <p class="error-msg">{{ $message }}</p> @enderror
            @error('subject_ids.*') <p class="error-msg">{{ $message }}</p> @enderror
            @error('subject_load') <p class="error-msg">{{ $message }}</p> @enderror
        </div>
        <style>
            .subject-picker { overflow:hidden;border:1px solid #dfe5ef;border-radius:12px;background:#fff; }
            .subject-picker-toolbar { display:flex;align-items:center;gap:.65rem;padding:.7rem;border-bottom:1px solid #e8edf5;background:#f8fafc; }
            .subject-search { min-width:150px;flex:1; }
            .subject-picker-actions { display:flex;flex:none;gap:.4rem; }
            .subject-selection-count { padding:.55rem .8rem 0;color:#1e40af;font-size:.78rem;font-weight:700; }
            .subject-options { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.5rem;max-height:300px;overflow:auto;padding:.75rem; }
            .subject-option { display:flex;align-items:center;gap:.6rem;min-width:0;padding:.65rem .7rem;border:1px solid #e5e7eb;border-radius:9px;background:#fff;cursor:pointer;transition:border-color .15s,background .15s; }
            .subject-option:hover { border-color:#93c5fd;background:#f8fbff; }
            .subject-option.selected { border-color:#3b82f6;background:#eff6ff; }
            .subject-option input { width:16px;height:16px;flex:none;accent-color:#2563eb; }
            .subject-option-copy { display:grid;min-width:0;gap:.15rem; }
            .subject-option-copy strong { color:#1e3a8a;font-size:.75rem; }
            .subject-option-copy span { overflow:hidden;color:#374151;font-size:.78rem;text-overflow:ellipsis;white-space:nowrap; }
            .subject-option-units { flex:none;margin-left:auto;color:#6b7280;font-size:.7rem;white-space:nowrap; }
            .subject-option-required { flex:none;color:#047857;font-size:.65rem;font-weight:700;white-space:nowrap; }
            .subject-load-guidance { margin:.55rem .8rem;color:#475569;font-size:.78rem;line-height:1.45; }
            .subject-option[hidden] { display:none; }
            .subject-options.is-invalid { outline:1px solid #dc2626; }
            @media(max-width:640px) {
                .subject-picker-toolbar { align-items:stretch;flex-direction:column; }
                .subject-picker-actions { justify-content:flex-end; }
                .subject-options { grid-template-columns:1fr;max-height:260px; }
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const studentSelect = document.querySelector('#student_id');
                const semesterSelect = document.querySelector('#semester');
                const curriculumOptions = {{ Illuminate\Support\Js::from($curriculumOptions) }};
                const picker = document.querySelector('[data-subject-picker]');
                const guidance = document.querySelector('[data-load-guidance]');

                if (!studentSelect || !semesterSelect || !guidance) return;

                const selectedStudent = () => studentSelect.selectedOptions[0];
                const selectedTerm = () => {
                    const student = selectedStudent();
                    const curriculumId = student?.dataset.curriculumId;
                    const yearLevel = student?.dataset.yearLevel;
                    const semester = semesterSelect.value;
                    return curriculumOptions[curriculumId]?.[yearLevel]?.[semester] ?? null;
                };

                if (picker) {
                    const options = [...picker.querySelectorAll('[data-subject-option]')];
                    const search = picker.querySelector('[data-subject-search]');
                    const count = picker.querySelector('[data-selection-count]');
                    let activeTerm = null;

                    const updateCount = () => {
                        const selected = options.filter(option => option.querySelector('input').checked);
                        const units = selected.reduce((sum, option) => {
                            const id = option.querySelector('input').value;
                            const curriculumSubject = [...(activeTerm?.required ?? []), ...(activeTerm?.electives ?? [])]
                                .find(subject => String(subject.id) === id);
                            return sum + Number(curriculumSubject?.units ?? option.querySelector('[data-subject-units]').dataset.catalogUnits);
                        }, 0);
                        count.textContent = `${selected.length} ${selected.length === 1 ? 'subject' : 'subjects'} selected - ${units} units`;
                        options.forEach(option => option.classList.toggle('selected', option.querySelector('input').checked));
                    };

                    const updatePicker = () => {
                        activeTerm = selectedTerm();
                        const student = selectedStudent();
                        const yearLevel = student?.dataset.yearLevel ?? 'student year level';
                        const termSubjects = activeTerm ? [...activeTerm.required, ...activeTerm.electives] : [];
                        const allowed = new Map(termSubjects.map(subject => [String(subject.id), subject]));
                        const requiredIds = new Set((activeTerm?.required ?? []).map(subject => String(subject.id)));
                        const curriculumOrder = new Map(termSubjects.map((subject, index) => [String(subject.id), index]));
                        const query = search.value.trim().toLowerCase();

                        options.sort((left, right) => {
                            const leftId = left.querySelector('input').value;
                            const rightId = right.querySelector('input').value;
                            return (curriculumOrder.get(leftId) ?? Number.MAX_SAFE_INTEGER)
                                - (curriculumOrder.get(rightId) ?? Number.MAX_SAFE_INTEGER);
                        }).forEach(option => picker.querySelector('.subject-options').append(option));

                        options.forEach(option => {
                            const input = option.querySelector('input');
                            const subject = allowed.get(input.value);
                            const required = requiredIds.has(input.value);
                            option.hidden = !subject || !option.dataset.searchText.includes(query);
                            option.querySelector('[data-required-label]').hidden = !required;
                            option.querySelector('[data-required-label]').textContent = required ? 'Required' : '';
                            option.querySelector('[data-subject-units]').textContent = `${subject?.units ?? option.querySelector('[data-subject-units]').dataset.catalogUnits} units`;

                            if (!subject) input.checked = false;
                            if (required) input.checked = true;
                        });

                        if (!student?.value) {
                            guidance.textContent = 'Choose a student to see the suggested courses for their year level and semester. Required subjects are selected automatically.';
                        } else if (activeTerm) {
                            guidance.textContent = `Suggested courses for ${yearLevel} · ${semesterSelect.value} semester. Required plan: ${activeTerm.required_units} units. Required subjects are selected automatically; add approved electives as needed to reach at least 24 units.`;
                        } else if (student.dataset.curriculumId) {
                            guidance.textContent = 'No subjects are offered for this curriculum term. No subjects are available for enrollment.';
                        } else {
                            guidance.textContent = 'No matching curriculum was found for this student. Assign a matching curriculum before enrolling subjects.';
                        }

                        updateCount();
                    };

                    search.addEventListener('input', updatePicker);
                    picker.querySelectorAll('input[type="checkbox"]').forEach(input => {
                        input.addEventListener('change', () => {
                            if (selectedTerm()?.required.some(subject => String(subject.id) === input.value) && !input.checked) {
                                input.checked = true;
                            }
                            updateCount();
                        });
                    });
                    picker.querySelector('[data-select-visible]').addEventListener('click', () => {
                        options.filter(option => !option.hidden).forEach(option => {
                            option.querySelector('input').checked = true;
                        });
                        updateCount();
                    });
                    picker.querySelector('[data-clear-selection]').addEventListener('click', () => {
                        options.forEach(option => {
                            option.querySelector('input').checked = false;
                        });
                        updateCount();
                    });

                    studentSelect.addEventListener('change', updatePicker);
                    semesterSelect.addEventListener('change', updatePicker);
                    updatePicker();
                    return;
                }

                const subjectSelect = document.querySelector('#subject_id');
                if (!subjectSelect) return;
                const updateSubjectOptions = () => {
                    const term = selectedTerm();
                    const student = selectedStudent();
                    const allowed = new Set([...(term?.required ?? []), ...(term?.electives ?? [])].map(subject => String(subject.id)));

                    [...subjectSelect.options].forEach(option => {
                        if (option.value) option.hidden = !allowed.has(option.value);
                    });
                    if (subjectSelect.value && !allowed.has(subjectSelect.value)) subjectSelect.value = '';

                    if (term) {
                        guidance.textContent = `Only subjects offered in this curriculum term and its approved electives are available. Required plan: ${term.required_units} units.`;
                    } else if (student?.dataset.curriculumId) {
                        guidance.textContent = 'No subjects are offered for this curriculum term. No other subjects are available.';
                    } else {
                        guidance.textContent = 'No matching curriculum was found for this student. Assign a matching curriculum before enrolling subjects.';
                    }
                };
                studentSelect.addEventListener('change', updateSubjectOptions);
                semesterSelect.addEventListener('change', updateSubjectOptions);
                updateSubjectOptions();
            });
        </script>
    @endif

    <div class="form-group">
        <label for="school_year">School Year <span class="req">*</span></label>
        <input id="school_year" name="school_year" value="{{ old('school_year', $enrollment->school_year ?? '') }}" class="form-control no-icon @error('school_year') is-invalid @enderror" placeholder="2026-2027" pattern="\d{4}-\d{4}" required>
        @error('school_year') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="semester">Semester <span class="req">*</span></label>
        <select id="semester" name="semester" class="form-control no-icon @error('semester') is-invalid @enderror" required>
            <option value="">Select a semester</option>
            @foreach (['First', 'Second', 'Summer'] as $semester)
                <option value="{{ $semester }}" @selected(old('semester', $enrollment->semester ?? 'First') === $semester)>{{ $semester }}</option>
            @endforeach
        </select>
        @error('semester') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="status">Status <span class="req">*</span></label>
        <select id="status" name="status" class="form-control no-icon @error('status') is-invalid @enderror" required>
            @foreach (['enrolled' => 'Enrolled', 'completed' => 'Completed', 'dropped' => 'Dropped'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $enrollment->status ?? 'enrolled') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="error-msg">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label for="enrolled_at">Enrollment Date</label>
        <input id="enrolled_at" name="enrolled_at" type="date" value="{{ old('enrolled_at', isset($enrollment->enrolled_at) ? $enrollment->enrolled_at->format('Y-m-d') : '') }}" class="form-control no-icon @error('enrolled_at') is-invalid @enderror">
        @error('enrolled_at') <p class="error-msg">{{ $message }}</p> @enderror
    </div>
</div>

<div class="form-actions">
    <button type="submit" class="btn btn-primary">{{ isset($enrollment) ? 'Save Changes' : 'Enroll Student' }}</button>
    <a href="{{ route('enrollments.index') }}" class="btn btn-secondary">Cancel</a>
</div>
