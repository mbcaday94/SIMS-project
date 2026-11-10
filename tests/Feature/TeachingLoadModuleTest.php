<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Curriculum;
use App\Models\Subject;
use App\Models\TeachingLoad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeachingLoadModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_add_faculty_and_assign_a_subject_load(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $subject = Subject::factory()->create(['code' => 'CS101', 'units' => 3]);

        $this->actingAs($staff)
            ->post(route('faculties.store'), [
                'name' => 'Alex Rivera',
                'employee_number' => 'F-104',
                'email' => 'alex@example.test',
            ])
            ->assertRedirect(route('faculties.index'));

        $faculty = Faculty::where('employee_number', 'F-104')->firstOrFail();

        $this->post(route('teaching-loads.store'), $this->loadData($faculty, $subject))
            ->assertRedirect(route('teaching-loads.index', [
                'school_year' => '2026-2027',
                'semester' => 'First',
            ]));

        $this->assertDatabaseHas('teaching_loads', [
            'faculty_id' => $faculty->id,
            'subject_id' => $subject->id,
            'units' => 3,
            'section' => 'A',
            'school_year' => '2026-2027',
        ]);

        $this->get(route('teaching-loads.index', [
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]))
            ->assertOk()
            ->assertSee('CS101')
            ->assertSee('Alex Rivera')
            ->assertSee('RM. 101')
            ->assertSee('3 units')
            ->assertSee('Monday');
    }

    public function test_schedule_shows_subject_units_and_totals_units_by_faculty_for_the_selected_term(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $firstSubject = Subject::factory()->create(['code' => 'CS301', 'units' => 3]);
        $secondSubject = Subject::factory()->create(['code' => 'CS302', 'units' => 4]);
        $otherTermSubject = Subject::factory()->create(['code' => 'CS303', 'units' => 5]);

        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $this->post(route('teaching-loads.store'), $this->loadData($faculty, $firstSubject));
        $this->post(route('teaching-loads.store'), $this->loadData($faculty, $secondSubject, [
            'section' => 'B',
            'days' => ['Tuesday'],
            'start_time' => '10:00',
            'end_time' => '11:00',
            'lecture_room' => 'RM. 102',
        ]));
        $this->post(route('teaching-loads.store'), $this->loadData($faculty, $otherTermSubject, [
            'school_year' => '2025-2026',
            'semester' => 'Second',
            'section' => 'C',
        ]));

        $this->get(route('teaching-loads.index', [
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]))
            ->assertOk()
            ->assertSee('3 units')
            ->assertSee('4 units')
            ->assertSee('7 units')
            ->assertDontSee('CS303');
    }

    public function test_faculty_load_is_labeled_regular_at_18_and_overload_above_18(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $eighteenUnitSubject = Subject::factory()->create(['code' => 'CS318', 'units' => 18]);
        $oneUnitSubject = Subject::factory()->create(['code' => 'CS101', 'units' => 1]);
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $this->post(route('teaching-loads.store'), $this->loadData($faculty, $eighteenUnitSubject));

        $this->get(route('teaching-loads.index', [
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]))
            ->assertOk()
            ->assertSee('18 units')
            ->assertSee('Regular load');

        $this->post(route('teaching-loads.store'), $this->loadData($faculty, $oneUnitSubject, [
            'days' => ['Tuesday'],
            'start_time' => '10:00',
            'end_time' => '11:00',
            'lecture_room' => 'RM. 102',
            'section' => 'B',
        ]))->assertRedirect();

        $this->get(route('teaching-loads.index', [
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]))
            ->assertOk()
            ->assertSee('19 units')
            ->assertSee('Overload');
    }

    public function test_assignment_that_exceeds_30_faculty_units_is_rejected(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $twentyEightUnits = Subject::factory()->create(['units' => 28]);
        $threeUnits = Subject::factory()->create(['units' => 3]);
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $this->post(route('teaching-loads.store'), $this->loadData($faculty, $twentyEightUnits));

        $this->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $threeUnits, [
                'days' => ['Tuesday'],
                'start_time' => '10:00',
                'end_time' => '11:00',
                'lecture_room' => 'RM. 102',
                'section' => 'B',
            ]))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('faculty_id');

        $this->assertDatabaseCount('teaching_loads', 1);
    }

    public function test_staff_can_plot_a_subject_schedule_before_assigning_faculty(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $subject = Subject::factory()->create(['code' => 'CS201']);
        $scheduleData = $this->loadData($faculty, $subject, ['faculty_id' => null]);

        $this->actingAs($staff)
            ->post(route('teaching-loads.store'), $scheduleData)
            ->assertRedirect(route('teaching-loads.index', [
                'school_year' => '2026-2027',
                'semester' => 'First',
            ]));

        $teachingLoad = TeachingLoad::firstOrFail();
        $this->assertNull($teachingLoad->faculty_id);

        $this->get(route('teaching-loads.index', [
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]))
            ->assertOk()
            ->assertSee('CS201')
            ->assertSee('Faculty not assigned');

        $this->put(route('teaching-loads.update', $teachingLoad), [
            ...$scheduleData,
            'faculty_id' => $faculty->id,
        ])->assertRedirect(route('teaching-loads.index', [
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]));

        $this->assertDatabaseHas('teaching_loads', [
            'id' => $teachingLoad->id,
            'faculty_id' => $faculty->id,
            'units' => $subject->units,
        ]);
    }

    public function test_schedule_form_shows_existing_faculty_and_time_conflict_preview_data(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $subject = Subject::factory()->create(['code' => 'CS-PREVIEW']);
        TeachingLoad::create($this->loadData($faculty, $subject));

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('teaching-loads.create'))
            ->assertOk()
            ->assertSee('Schedule conflict preview')
            ->assertSee('conflict-preview', false)
            ->assertSee('Alex Rivera')
            ->assertSee('CS-PREVIEW');
    }

    public function test_schedule_edit_route_opens_the_selected_teaching_load(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $subject = Subject::factory()->create(['code' => 'EDIT101']);
        $teachingLoad = TeachingLoad::create($this->loadData($faculty, $subject));

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('teaching-loads.edit', $teachingLoad))
            ->assertOk()
            ->assertSee('Edit Subject Schedule')
            ->assertSee('EDIT101');
    }

    public function test_schedule_form_orders_fields_and_organizes_subject_offerings(): void
    {
        $curriculum = Curriculum::create([
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'name' => 'Test Schedule Curriculum',
            'source_filename' => 'schedule.xlsx',
        ]);
        foreach ([
            ['code' => 'IT 102', 'name' => 'Second Offered Subject', 'semester' => 'First Semester'],
            ['code' => 'IT 101', 'name' => 'First Offered Subject', 'semester' => 'First Semester'],
            ['code' => 'IT 201', 'name' => 'Second Semester Subject', 'semester' => 'Second Semester'],
        ] as $subjectData) {
            $subject = Subject::factory()->create([
                'code' => $subjectData['code'],
                'name' => $subjectData['name'],
            ]);
            $curriculum->subjects()->attach($subject->id, [
                'year_level' => 'First Year',
                'semester' => $subjectData['semester'],
                'curriculum_code' => $subject->code,
                'units' => $subject->units,
            ]);
        }

        $response = $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('teaching-loads.create'))
            ->assertOk()
            ->assertSee('Required subjects')
            ->assertSee('Approved electives')
            ->assertSee('No subjects are offered for this program, year level, and semester.')
            ->assertSee('data-year-levels=', false);

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'id="semester"'), strpos($html, 'id="faculty_id"'));
        $this->assertLessThan(strpos($html, 'id="semester"'), strpos($html, 'id="subject_id"'));
        $this->assertStringContainsString('subject.code.localeCompare', $html);
    }

    public function test_schedule_rejects_a_subject_not_offered_for_the_selected_semester(): void
    {
        $curriculum = Curriculum::create([
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'name' => 'Test Schedule Curriculum',
            'source_filename' => 'schedule.xlsx',
        ]);
        $firstSemesterSubject = Subject::factory()->create(['code' => 'FIRST101']);
        $secondSemesterSubject = Subject::factory()->create(['code' => 'SECOND101']);
        $curriculum->subjects()->attach($firstSemesterSubject->id, [
            'year_level' => 'First Year',
            'semester' => 'First Semester',
            'curriculum_code' => 'FIRST101',
            'units' => 3,
        ]);
        $curriculum->subjects()->attach($secondSemesterSubject->id, [
            'year_level' => 'First Year',
            'semester' => 'Second Semester',
            'curriculum_code' => 'SECOND101',
            'units' => 3,
        ]);
        $faculty = Faculty::create(['name' => 'Alex Rivera']);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $secondSemesterSubject, [
                'program' => $curriculum->program,
                'semester' => 'First',
            ]))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseCount('teaching_loads', 0);
    }

    public function test_schedule_form_lists_lecture_rooms_and_computer_laboratories(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('teaching-loads.create'))
            ->assertOk()
            ->assertSee('Lecture room')
            ->assertSee('RM. 101')
            ->assertSee('RM. 105')
            ->assertSee('Computer laboratory')
            ->assertSee('ComLab 1')
            ->assertSee('ComLab 5');
    }

    public function test_lecture_and_laboratory_schedules_can_use_different_days_and_times(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $subject = Subject::factory()->create(['code' => 'CS210']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $subject, [
                'days' => ['Monday'],
                'start_time' => '09:00',
                'end_time' => '10:00',
                'lab_days' => ['Friday'],
                'lab_start_time' => '13:00',
                'lab_end_time' => '15:00',
            ]))
            ->assertRedirect();

        $teachingLoad = TeachingLoad::firstOrFail();
        $this->assertSame(['Friday'], $teachingLoad->lab_days);
        $this->assertStringStartsWith('13:00', $teachingLoad->lab_start_time);
        $this->assertStringStartsWith('15:00', $teachingLoad->lab_end_time);
        $this->assertSame('RM. 101', $teachingLoad->lecture_room);
        $this->assertSame('ComLab 1', $teachingLoad->lab_room);

        $this->get(route('teaching-loads.index', [
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]))
            ->assertOk()
            ->assertSee('Lecture · 9:00 AM–10:00 AM')
            ->assertSee('Laboratory · 1:00 PM–3:00 PM')
            ->assertSee('RM. 101')
            ->assertSee('ComLab 1')
            ->assertSee('Friday');
    }

    public function test_schedule_rejects_rooms_outside_the_lecture_and_lab_choices(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $subject = Subject::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $subject, [
                'lecture_room' => 'Room 204',
            ]))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('lecture_room');

        $this->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $subject, [
                'lab_days' => ['Friday'],
                'lab_start_time' => '13:00',
                'lab_end_time' => '15:00',
                'lab_room' => 'Lab 1',
            ]))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('lab_room');

        $this->assertDatabaseCount('teaching_loads', 0);
    }

    public function test_laboratory_time_conflicts_with_an_existing_lecture_schedule(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $firstSubject = Subject::factory()->create(['code' => 'CS211']);
        $secondSubject = Subject::factory()->create(['code' => 'CS212']);
        TeachingLoad::create($this->loadData($faculty, $firstSubject));

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $secondSubject, [
                'days' => ['Tuesday'],
                'lab_days' => ['Monday'],
                'lab_start_time' => '09:30',
                'lab_end_time' => '10:30',
                'lab_room' => 'ComLab 1',
            ]))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('schedule');

        $this->assertDatabaseCount('teaching_loads', 1);
    }

    public function test_laboratory_schedule_requires_days_and_both_times(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $subject = Subject::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $subject, [
                'lab_days' => ['Friday'],
                'lab_start_time' => '13:00',
            ]))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('lab_end_time');
    }

    public function test_overlapping_faculty_schedule_is_rejected(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $firstSubject = Subject::factory()->create(['code' => 'CS101']);
        $secondSubject = Subject::factory()->create(['code' => 'CS102']);
        TeachingLoad::create($this->loadData($faculty, $firstSubject));

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData(
                $faculty,
                $secondSubject,
                ['start_time' => '09:30', 'end_time' => '10:30', 'lecture_room' => 'RM. 102']
            ))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('schedule');
    }

    public function test_overlapping_room_schedule_is_rejected_even_for_another_faculty(): void
    {
        $subject = Subject::factory()->create();
        $firstFaculty = Faculty::create(['name' => 'Alex Rivera']);
        $secondFaculty = Faculty::create(['name' => 'Jamie Cruz']);
        TeachingLoad::create($this->loadData($firstFaculty, $subject));

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData($secondFaculty, $subject))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('schedule');
    }

    public function test_computer_laboratory_room_conflicts_are_checked_separately_from_lecture_rooms(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $firstSubject = Subject::factory()->create(['code' => 'CS-LAB-1']);
        $secondSubject = Subject::factory()->create(['code' => 'CS-LAB-2']);
        TeachingLoad::create($this->loadData($faculty, $firstSubject, [
            'lab_days' => ['Friday'],
            'lab_start_time' => '13:00',
            'lab_end_time' => '15:00',
            'lab_room' => 'ComLab 2',
        ]));

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('teaching-loads.create'))
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $secondSubject, [
                'faculty_id' => null,
                'days' => ['Tuesday'],
                'lecture_room' => 'RM. 102',
                'lab_days' => ['Friday'],
                'lab_start_time' => '13:30',
                'lab_end_time' => '14:30',
                'lab_room' => 'ComLab 2',
            ]))
            ->assertRedirect(route('teaching-loads.create'))
            ->assertSessionHasErrors('schedule');

        $this->assertDatabaseCount('teaching_loads', 1);
    }

    public function test_schedule_conflicts_do_not_cross_terms_or_non_overlapping_days(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $subject = Subject::factory()->create();
        TeachingLoad::create($this->loadData($faculty, $subject));

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)
            ->post(route('teaching-loads.store'), $this->loadData($faculty, $subject, [
                'days' => ['Tuesday'],
                'school_year' => '2026-2027',
                'semester' => 'First',
                'section' => 'B',
            ]))
            ->assertRedirect();

        $this->post(route('teaching-loads.store'), $this->loadData($faculty, $subject, [
            'school_year' => '2025-2026',
            'semester' => 'Second',
            'section' => 'C',
        ]))->assertRedirect();

        $this->assertDatabaseCount('teaching_loads', 3);
    }

    public function test_faculty_with_assigned_load_cannot_be_deleted(): void
    {
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        $subject = Subject::factory()->create();
        TeachingLoad::create($this->loadData($faculty, $subject));

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->delete(route('faculties.destroy', $faculty))
            ->assertRedirect(route('faculties.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function loadData(Faculty $faculty, Subject $subject, array $overrides = []): array
    {
        return [
            'faculty_id' => $faculty->id,
            'subject_id' => $subject->id,
            'program' => 'BS Information Technology',
            'year_level' => 'First Year',
            'section' => 'A',
            'school_year' => '2026-2027',
            'semester' => 'First',
            'days' => ['Monday'],
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'RM. 101',
            'lecture_room' => 'RM. 101',
            'lab_room' => 'ComLab 1',
            ...$overrides,
        ];
    }
}
