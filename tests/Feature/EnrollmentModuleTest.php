<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_enrollment_quick_link_opens_enrollment_list(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('enrollments.index').'"', false);
    }

    public function test_dashboard_displays_live_record_counts(): void
    {
        Student::factory()->count(2)->create();
        Subject::factory()->create();
        Enrollment::factory()->create([
            'school_year' => '2025-2026',
            'semester' => 'Second',
        ]);
        Enrollment::factory()->create([
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Students')
            ->assertSee('Subjects')
            ->assertSee('Enrollments')
            ->assertSee('This Semester')
            ->assertSee('<strong>2</strong>', false)
            ->assertSee('<strong>1</strong>', false);

        $this->assertSame(4, substr_count($response->getContent(), 'class="stat-card"'));
    }

    public function test_enrollment_list_groups_subjects_under_one_clickable_student(): void
    {
        $student = Student::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'Bersabal',
            'student_number' => '117-0020',
        ]);
        $subjects = Subject::factory()->count(3)->create(['units' => 3]);
        foreach ($subjects as $subject) {
            Enrollment::factory()->create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'school_year' => '2026-2027',
                'semester' => 'First',
            ]);
        }

        $response = $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('Bersabal, Maria')
            ->assertSee('117-0020')
            ->assertSee('3 subjects')
            ->assertSee('9 units')
            ->assertSee('class="student-enrollment-group"', false)
            ->assertSee('<summary class="student-group-summary">', false);

        $this->assertSame(1, substr_count($response->getContent(), 'class="student-enrollment-group"'));
        foreach ($subjects as $subject) {
            $response->assertSee($subject->code);
        }
    }

    public function test_enrollment_list_separates_students_by_program(): void
    {
        $bsitStudent = Student::factory()->create([
            'first_name' => 'Taylor',
            'last_name' => 'Student',
            'course' => 'BSIT',
        ]);
        $bsisStudent = Student::factory()->create([
            'first_name' => 'Jordan',
            'last_name' => 'Student',
            'course' => 'BSIS',
        ]);
        $subject = Subject::factory()->create();

        foreach ([$bsitStudent, $bsisStudent] as $student) {
            Enrollment::factory()->create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
            ]);
        }

        $response = $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('BSIS')
            ->assertSee('BSIT')
            ->assertSee('Jordan')
            ->assertSee('Taylor');

        $this->assertSame(2, substr_count($response->getContent(), 'class="program-enrollment-group"'));
        $this->assertLessThan(
            strpos($response->getContent(), 'class="program-enrollment-group" aria-labelledby="program-1"'),
            strpos($response->getContent(), 'class="program-enrollment-group" aria-labelledby="program-0"')
        );
    }

    public function test_enrollment_search_keeps_student_groups_and_matches_subjects(): void
    {
        $student = Student::factory()->create(['first_name' => 'Maria', 'last_name' => 'Bersabal']);
        $matchingSubject = Subject::factory()->create(['code' => 'CS101', 'name' => 'Computing']);
        $otherSubject = Subject::factory()->create(['code' => 'HIS101', 'name' => 'History']);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $matchingSubject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $otherSubject->id,
            'school_year' => '2025-2026',
            'semester' => 'Second',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('enrollments.index', ['search' => 'CS101']))
            ->assertOk()
            ->assertSee('Bersabal, Maria')
            ->assertSee('CS101')
            ->assertDontSee('HIS101')
            ->assertSee('1 enrollment found');
    }

    public function test_enrollment_list_can_search_student_id_and_filter_by_school_year_and_semester(): void
    {
        $student = Student::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'Bersabal',
            'student_number' => '117-0020',
        ]);
        $matchingSubject = Subject::factory()->create(['code' => 'CS-TERM-MATCH']);
        $sameYearSubject = Subject::factory()->create(['code' => 'CS-YEAR-ONLY']);
        $sameSemesterSubject = Subject::factory()->create(['code' => 'CS-SEMESTER-ONLY']);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $matchingSubject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $sameYearSubject->id,
            'school_year' => '2026-2027',
            'semester' => 'Second',
        ]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $sameSemesterSubject->id,
            'school_year' => '2025-2026',
            'semester' => 'First',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('enrollments.index', [
                'search' => '117-0020',
                'school_year' => '2026-2027',
                'semester' => 'First',
            ]))
            ->assertOk()
            ->assertSee('Bersabal, Maria')
            ->assertSee('CS-TERM-MATCH')
            ->assertDontSee('CS-YEAR-ONLY')
            ->assertDontSee('CS-SEMESTER-ONLY')
            ->assertSee('1 enrollment found')
            ->assertSee('value="117-0020"', false)
            ->assertSee('All school years')
            ->assertSee('All semesters');
    }

    public function test_enrollment_pagination_does_not_split_a_students_subjects(): void
    {
        $groupedStudent = Student::factory()->create(['last_name' => 'Aardvark']);
        $subjects = Subject::factory()->count(2)->create();
        foreach ($subjects as $subject) {
            Enrollment::factory()->create([
                'student_id' => $groupedStudent->id,
                'subject_id' => $subject->id,
                'school_year' => '2026-2027',
                'semester' => 'First',
            ]);
        }
        for ($index = 0; $index < 10; $index++) {
            $student = Student::factory()->create(['last_name' => chr(66 + $index).'Student']);
            Enrollment::factory()->create(['student_id' => $student->id]);
        }

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('11 students')
            ->assertSee('12 enrollments')
            ->assertSee($subjects[0]->code)
            ->assertSee($subjects[1]->code);
    }

    public function test_staff_can_enroll_view_and_update_a_student(): void
    {
        $subject = Subject::factory()->create(['units' => 24]);
        $student = $this->createStudentWithCurriculum([$subject], ['First Semester', 'Second Semester']);
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $enrollmentData = [
            'student_id' => $student->id,
            'section' => 'B',
            'subject_id' => $subject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
            'status' => 'enrolled',
            'enrolled_at' => '2026-08-15',
        ];

        $this->post(route('enrollments.store'), $enrollmentData)
            ->assertRedirect();

        $enrollment = Enrollment::firstOrFail();
        $enrollmentAttributes = $enrollmentData;
        unset($enrollmentAttributes['section']);
        $this->assertDatabaseHas('enrollments', [
            ...$enrollmentAttributes,
            'enrolled_at' => '2026-08-15 00:00:00',
        ]);
        $this->assertDatabaseHas('students', ['id' => $student->id, 'section' => 'B']);
        $this->get(route('enrollments.index'))->assertOk()->assertSee($student->student_number);
        $this->get(route('enrollments.show', $enrollment))->assertOk()->assertSee($subject->code);

        $this->put(route('enrollments.update', $enrollment), [
            ...$enrollmentData,
            'semester' => 'Second',
            'status' => 'completed',
        ])->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'semester' => 'Second',
            'status' => 'completed',
        ]);
    }

    public function test_duplicate_student_subject_term_is_rejected(): void
    {
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('enrollments.create'))
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'section' => 'A',
                'subject_id' => $subject->id,
                'school_year' => '2026-2027',
                'semester' => 'First',
                'status' => 'enrolled',
            ])
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors('student_id');
    }

    public function test_staff_can_enroll_a_student_in_multiple_subjects_at_once(): void
    {
        $subjects = Subject::factory()->count(3)->create(['units' => 8]);
        $student = $this->createStudentWithCurriculum($subjects);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('enrollments.create'))
            ->assertOk()
            ->assertSee('<label for="enrollment_year_level">Year Level</label>', false)
            ->assertSee('id="enrollment_year_level"', false)
            ->assertSee('placeholder="Select a student to view year level"', false)
            ->assertSee('<select id="section" name="section"', false)
            ->assertSee('Select block / section')
            ->assertSee('Section A')
            ->assertSee('name="subject_ids[]"', false)
            ->assertSee('Select visible');

        $this->post(route('enrollments.store'), [
            'student_id' => $student->id,
            'section' => 'A',
            'subject_ids' => $subjects->pluck('id')->all(),
            'school_year' => '2026-2027',
            'semester' => 'First',
            'status' => 'enrolled',
            'enrolled_at' => '2026-08-15',
        ])
            ->assertRedirect(route('enrollments.index'))
            ->assertSessionHas('success', '3 subjects enrolled successfully.');

        $this->assertDatabaseCount('enrollments', 3);
        foreach ($subjects as $subject) {
            $this->assertDatabaseHas('enrollments', [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'school_year' => '2026-2027',
                'semester' => 'First',
                'status' => 'enrolled',
            ]);
        }
        $this->assertDatabaseHas('students', ['id' => $student->id, 'section' => 'A']);
    }

    public function test_block_capacity_is_checked_during_enrollment_and_student_gets_the_selected_section(): void
    {
        Student::factory()->count(50)->create([
            'course' => 'TST',
            'year_level' => 'First Year',
            'section' => 'A',
        ]);
        $subject = Subject::factory()->create(['units' => 24]);
        $student = $this->createStudentWithCurriculum([$subject]);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('enrollments.create'))
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'section' => 'A',
                'subject_id' => $subject->id,
                'school_year' => '2026-2027',
                'semester' => 'First',
                'status' => 'enrolled',
            ])
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors('section');

        $this->assertNull($student->fresh()->section);
        $this->assertDatabaseCount('enrollments', 0);

        $this->post(route('enrollments.store'), [
            'student_id' => $student->id,
            'section' => 'B',
            'subject_id' => $subject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
            'status' => 'enrolled',
        ])->assertRedirect();

        $this->assertDatabaseHas('students', ['id' => $student->id, 'section' => 'B']);
    }

    public function test_enrollment_form_shows_remaining_slots_for_each_section_by_student_program_and_year(): void
    {
        Student::factory()->count(2)->create([
            'course' => 'TST',
            'year_level' => 'First Year',
            'section' => 'A',
        ]);
        Student::factory()->count(3)->create([
            'course' => 'TST',
            'year_level' => 'First Year',
            'section' => 'B',
        ]);
        Subject::factory()->create();
        $student = Student::factory()->create([
            'course' => 'TST',
            'year_level' => 'First Year',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('enrollments.create'))
            ->assertOk()
            ->assertViewHas('studentSectionAvailability', function ($availability) use ($student) {
                return $availability[$student->id]['A'] === 48
                    && $availability[$student->id]['B'] === 47
                    && $availability[$student->id]['C'] === 50;
            })
            ->assertSee('data-section-slots=', false)
            ->assertSee('data-section-label="Section A"', false)
            ->assertSee('select a student');
    }

    public function test_enrollment_rejects_subjects_without_a_matching_curriculum_offering(): void
    {
        $student = Student::factory()->create(['course' => 'Unlisted Program']);
        $subject = Subject::factory()->create(['units' => 24]);
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $this->from(route('enrollments.create'))
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'section' => 'A',
                'subject_id' => $subject->id,
                'school_year' => '2026-2027',
                'semester' => 'First',
                'status' => 'enrolled',
            ])
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors('subject_load');
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_curriculum_enrollment_requires_the_complete_term_and_approved_electives_to_reach_24_units(): void
    {
        $student = Student::factory()->create([
            'course' => 'BSIT',
            'year_level' => 'First Year',
        ]);
        $curriculum = Curriculum::create([
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'name' => 'Revision 3',
            'source_filename' => 'curriculum.xlsx',
        ]);
        $firstRequired = Subject::factory()->create(['code' => 'IT101', 'units' => 1]);
        $secondRequired = Subject::factory()->create(['code' => 'IT102', 'units' => 1]);
        $approvedElective = Subject::factory()->create(['code' => 'ITELECT', 'units' => 1]);
        $outsideSubject = Subject::factory()->create(['code' => 'OUTSIDE', 'units' => 24]);

        $curriculum->subjects()->attach($firstRequired->id, [
            'year_level' => 'First Year',
            'semester' => 'First Semester',
            'curriculum_code' => 'IT101',
            'units' => 11,
        ]);
        $curriculum->subjects()->attach($secondRequired->id, [
            'year_level' => 'First Year',
            'semester' => 'First Semester',
            'curriculum_code' => 'IT102',
            'units' => 12,
        ]);
        $curriculum->subjects()->attach($approvedElective->id, [
            'year_level' => 'Electives',
            'semester' => 'Electives',
            'curriculum_code' => 'ITELECT',
            'units' => 3,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $this->get(route('enrollments.create'))
            ->assertOk()
            ->assertSee('data-curriculum-id="'.$curriculum->id.'"', false)
            ->assertSee('data-year-level="First Year"', false)
            ->assertSee("yearLevelDisplay.value = student?.dataset.yearLevel", false)
            ->assertSee('IT101')
            ->assertSee('ITELECT')
            ->assertSee('option.hidden = !subject', false)
            ->assertSee('No other subjects are available.', false);

        $payload = [
            'student_id' => $student->id,
            'section' => 'A',
            'subject_ids' => [$firstRequired->id, $approvedElective->id],
            'school_year' => '2026-2027',
            'semester' => 'First',
            'status' => 'enrolled',
        ];
        $this->from(route('enrollments.create'))
            ->post(route('enrollments.store'), $payload)
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors('subject_load');
        $this->assertDatabaseCount('enrollments', 0);

        $payload['subject_ids'] = [$firstRequired->id, $secondRequired->id];
        $this->post(route('enrollments.store'), $payload)
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors('subject_load');
        $this->assertDatabaseCount('enrollments', 0);

        $payload['subject_ids'][] = $approvedElective->id;
        $this->post(route('enrollments.store'), $payload)
            ->assertRedirect(route('enrollments.index'));
        $this->assertDatabaseCount('enrollments', 3);

        $payload['school_year'] = '2027-2028';
        $payload['subject_ids'][] = $outsideSubject->id;
        $this->from(route('enrollments.create'))
            ->post(route('enrollments.store'), $payload)
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors('subject_load');
        $this->assertDatabaseCount('enrollments', 3);
    }

    public function test_first_year_first_semester_suggestions_match_ordinal_curriculum_labels(): void
    {
        $student = Student::factory()->create([
            'course' => 'BSIT',
            'year_level' => '1st Year',
        ]);
        $curriculum = Curriculum::create([
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'name' => 'Ordinal Labels Curriculum',
            'source_filename' => 'curriculum.xlsx',
        ]);
        $firstSemesterSubject = Subject::factory()->create(['code' => 'IT-FIRST', 'units' => 24]);
        $secondSemesterSubject = Subject::factory()->create(['code' => 'IT-SECOND', 'units' => 24]);
        $curriculum->subjects()->attach($firstSemesterSubject->id, [
            'year_level' => '1st Year',
            'semester' => '1st Semester',
            'curriculum_code' => 'IT-FIRST',
            'units' => 24,
        ]);
        $curriculum->subjects()->attach($secondSemesterSubject->id, [
            'year_level' => '1st Year',
            'semester' => '2nd Semester',
            'curriculum_code' => 'IT-SECOND',
            'units' => 24,
        ]);

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)
            ->get(route('enrollments.create'))
            ->assertOk()
            ->assertSee('data-year-level="First Year"', false)
            ->assertSee('<option value="First" selected>First</option>', false)
            ->assertSee('IT-FIRST')
            ->assertSee('IT-SECOND');

        $this->post(route('enrollments.store'), [
            'student_id' => $student->id,
            'section' => 'A',
            'subject_ids' => [$firstSemesterSubject->id],
            'school_year' => '2026-2027',
            'semester' => 'First',
            'status' => 'enrolled',
        ])->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'subject_id' => $firstSemesterSubject->id,
            'semester' => 'First',
        ]);
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
            'section' => 'A',
            'subject_id' => $secondSemesterSubject->id,
        ]);
    }

    public function test_bsit_first_year_first_semester_suggestions_match_the_reference_curriculum(): void
    {
        $student = Student::factory()->create([
            'course' => 'BSIT',
            'year_level' => '1',
            'section' => 'A',
        ]);
        $curriculum = Curriculum::create([
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'name' => 'Rev. 3, 2025',
            'source_filename' => 'curriculum.xlsx',
        ]);
        $expectedCourses = [
            'ITE 10' => 3,
            'ITE 11' => 3,
            'ITE 12' => 3,
            'CSC 102' => 3,
            'PC' => 3,
            'STS' => 3,
            'PATHFit 1' => 2,
            'SE 1' => 0,
            'NSTP 1' => 3,
        ];

        foreach ($expectedCourses as $code => $units) {
            $subject = Subject::factory()->create(['code' => $code, 'units' => $units]);
            $curriculum->subjects()->attach($subject->id, [
                'year_level' => 'First Year',
                'semester' => 'First Semester',
                'curriculum_code' => $code,
                'units' => $units,
            ]);
        }

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('enrollments.create'))
            ->assertOk()
            ->assertSee('· BSIT · First Year · Section A')
            ->assertViewHas('curriculumOptions', function (array $options) use ($curriculum, $expectedCourses) {
                $suggestedCodes = array_column(
                    $options[$curriculum->id]['First Year']['First']['required'],
                    'code'
                );

                return $suggestedCodes === array_keys($expectedCourses)
                    && $options[$curriculum->id]['First Year']['First']['required_units'] === 23;
            });
    }

    public function test_subjects_from_another_semester_are_rejected(): void
    {
        $student = Student::factory()->create([
            'course' => 'BSIT',
            'year_level' => 'First Year',
        ]);
        $curriculum = Curriculum::create([
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'name' => 'Semester Offerings',
            'source_filename' => 'curriculum.xlsx',
        ]);
        $firstSemesterSubject = Subject::factory()->create(['units' => 24]);
        $secondSemesterSubject = Subject::factory()->create(['units' => 24]);
        $curriculum->subjects()->attach($firstSemesterSubject->id, [
            'year_level' => 'First Year',
            'semester' => 'First Semester',
            'curriculum_code' => 'FIRST101',
            'units' => 24,
        ]);
        $curriculum->subjects()->attach($secondSemesterSubject->id, [
            'year_level' => 'First Year',
            'semester' => 'Second Semester',
            'curriculum_code' => 'SECOND101',
            'units' => 24,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('enrollments.create'))
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'section' => 'A',
                'subject_id' => $secondSemesterSubject->id,
                'school_year' => '2026-2027',
                'semester' => 'First',
                'status' => 'enrolled',
            ])
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors('subject_load');

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_multiple_subject_enrollment_is_rejected_atomically_if_one_subject_is_a_duplicate(): void
    {
        $student = Student::factory()->create();
        $subjects = Subject::factory()->count(2)->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subjects[0]->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('enrollments.create'))
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'section' => 'A',
                'subject_ids' => $subjects->pluck('id')->all(),
                'school_year' => '2026-2027',
                'semester' => 'First',
                'status' => 'enrolled',
            ])
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
            'subject_id' => $subjects[1]->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);
    }

    public function test_only_administrators_can_delete_enrollments(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->delete(route('enrollments.destroy', $enrollment))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'administrator']))
            ->delete(route('enrollments.destroy', $enrollment))
            ->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_enrollment_form_requires_existing_student_and_subject(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('enrollments.create'))
            ->post(route('enrollments.store'), [
                'student_id' => 999,
                'section' => 'A',
                'subject_id' => 999,
                'school_year' => '2026-2027',
                'semester' => 'First',
                'status' => 'enrolled',
            ])
            ->assertRedirect(route('enrollments.create'))
            ->assertSessionHasErrors(['student_id', 'subject_id']);
    }

    private function createStudentWithCurriculum(iterable $subjects, array $semesters = ['First Semester']): Student
    {
        $student = Student::factory()->create([
            'course' => 'TST',
            'year_level' => 'First Year',
        ]);
        $curriculum = Curriculum::create([
            'program' => 'Test Program (TST)',
            'name' => 'Test Curriculum',
            'source_filename' => 'test.xlsx',
        ]);

        foreach ($subjects as $subject) {
            foreach ($semesters as $semester) {
                $curriculum->subjects()->attach($subject->id, [
                    'year_level' => 'First Year',
                    'semester' => $semester,
                    'curriculum_code' => $subject->code,
                    'units' => $subject->units,
                ]);
            }
        }

        return $student;
    }
}
