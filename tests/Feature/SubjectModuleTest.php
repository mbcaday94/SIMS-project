<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_subject_quick_link_opens_subject_list(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('subjects.index').'"', false);
    }

    public function test_staff_can_create_view_and_update_subjects(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $subjectData = [
            'code' => 'CS101',
            'name' => 'Introduction to Computing',
            'units' => 3,
            'description' => 'Foundational computing concepts.',
        ];

        $this->post(route('subjects.store'), $subjectData)
            ->assertRedirect(route('subjects.index'));

        $subject = Subject::where('code', 'CS101')->firstOrFail();

        $this->assertDatabaseHas('subjects', $subjectData);
        $this->get(route('subjects.index'))->assertOk()->assertSee('CS101');
        $this->get(route('subjects.show', $subject))->assertOk()->assertSee('Foundational computing concepts.');

        $this->put(route('subjects.update', $subject), [
            ...$subjectData,
            'name' => 'Computing Fundamentals',
            'units' => 4,
        ])->assertRedirect(route('subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'Computing Fundamentals',
            'units' => 4,
        ]);
    }

    public function test_subject_code_must_be_unique_and_units_must_be_non_negative(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        Subject::factory()->create(['code' => 'CS101']);

        $this->from(route('subjects.create'))
            ->post(route('subjects.store'), [
                'code' => 'CS101',
                'name' => 'Duplicate Code',
                'units' => -1,
            ])
            ->assertRedirect(route('subjects.create'))
            ->assertSessionHasErrors(['code', 'units']);
    }

    public function test_subject_list_can_be_filtered_by_school_year_and_semester(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $matchesBothFilters = Subject::factory()->create(['code' => 'CS-TERM-MATCH']);
        $matchesSchoolYearOnly = Subject::factory()->create(['code' => 'CS-YEAR-ONLY']);
        $matchesSemesterOnly = Subject::factory()->create(['code' => 'CS-SEMESTER-ONLY']);

        Enrollment::factory()->create([
            'subject_id' => $matchesBothFilters->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);
        Enrollment::factory()->create([
            'subject_id' => $matchesSchoolYearOnly->id,
            'school_year' => '2026-2027',
            'semester' => 'Second',
        ]);
        Enrollment::factory()->create([
            'subject_id' => $matchesSemesterOnly->id,
            'school_year' => '2025-2026',
            'semester' => 'First',
        ]);

        $this->get(route('subjects.index', ['school_year' => '2026-2027', 'semester' => 'First']))
            ->assertOk()
            ->assertSee('CS-TERM-MATCH')
            ->assertDontSee('CS-YEAR-ONLY')
            ->assertDontSee('CS-SEMESTER-ONLY')
            ->assertSee('All school years')
            ->assertSee('All semesters');

        $this->get(route('subjects.index', ['school_year' => '2026-2027']))
            ->assertOk()
            ->assertSee('CS-TERM-MATCH')
            ->assertSee('CS-YEAR-ONLY')
            ->assertDontSee('CS-SEMESTER-ONLY');

        $this->get(route('subjects.index', ['semester' => 'First']))
            ->assertOk()
            ->assertSee('CS-TERM-MATCH')
            ->assertSee('CS-SEMESTER-ONLY')
            ->assertDontSee('CS-YEAR-ONLY');
    }

    public function test_only_administrators_can_delete_subjects(): void
    {
        $subject = Subject::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->delete(route('subjects.destroy', $subject))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'administrator']))
            ->delete(route('subjects.destroy', $subject))
            ->assertRedirect(route('subjects.index'));

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_enrolled_subject_cannot_be_deleted(): void
    {
        $subject = Subject::factory()->create();
        $student = Student::factory()->create();
        Enrollment::create([
            'subject_id' => $subject->id,
            'student_id' => $student->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'administrator']))
            ->delete(route('subjects.destroy', $subject))
            ->assertRedirect(route('subjects.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_student_with_enrollments_cannot_be_deleted(): void
    {
        $enrollment = Enrollment::create([
            'student_id' => Student::factory()->create()->id,
            'subject_id' => Subject::factory()->create()->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);
        $administrator = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($administrator)
            ->delete(route('students.destroy', $enrollment->student))
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('students', ['id' => $enrollment->student_id]);
    }
}
