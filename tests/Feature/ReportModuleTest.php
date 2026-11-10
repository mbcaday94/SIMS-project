<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_link_opens_report_page(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('reports.index').'"', false);
    }

    public function test_report_displays_live_summary_and_visual_breakdowns(): void
    {
        $students = Student::factory()->count(2)->create([
            'course' => 'Computer Science',
            'year_level' => '1st Year',
        ]);
        $subject = Subject::factory()->create(['units' => 3]);
        Enrollment::factory()->create([
            'student_id' => $students[0]->id,
            'subject_id' => $subject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
            'status' => 'enrolled',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('SIMS Summary Report')
            ->assertSee('Print / Save as PDF')
            ->assertSee('Total Students')
            ->assertSee('Total Subject Units')
            ->assertSee('Students by Course')
            ->assertSee('Computer Science')
            ->assertSee('Students by Year Level')
            ->assertSee('Enrollments by School Year and Semester')
            ->assertSee('2026-2027 · First')
            ->assertSee('Enrollments by Status')
            ->assertSee('width:100%', false)
            ->assertSee('<strong>2</strong>', false);
    }

    public function test_report_handles_empty_database(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('No student records available yet.')
            ->assertSee('No enrollment records available yet.');
    }

    public function test_report_requires_authentication(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }
}
