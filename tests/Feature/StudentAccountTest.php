<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingLoad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_student_login_linked_to_the_student(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $student = Student::factory()->create();

        $this->actingAs($administrator)
            ->post(route('students.account.store', $student), [
                'password' => 'student-password-123',
                'password_confirmation' => 'student-password-123',
            ])
            ->assertRedirect(route('students.show', $student))
            ->assertSessionHas('success');

        $student->refresh();
        $this->assertNotNull($student->user_id);
        $this->assertSame($student->email, $student->user->email);
        $this->assertSame('student', $student->user->role);
        $this->assertTrue(Hash::check('student-password-123', $student->user->password));
    }

    public function test_staff_cannot_create_student_logins(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $student = Student::factory()->create();

        $this->actingAs($staff)
            ->post(route('students.account.store', $student), [
                'password' => 'student-password-123',
                'password_confirmation' => 'student-password-123',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('users', 1);
        $this->assertNull($student->fresh()->user_id);
    }

    public function test_student_login_shows_only_its_own_enrolled_subjects(): void
    {
        $student = Student::factory()->create();
        $studentUser = User::factory()->create(['role' => 'student']);
        $student->user()->associate($studentUser)->save();

        $otherStudent = Student::factory()->create();
        $ownSubject = Subject::factory()->create(['name' => 'My Mathematics']);
        $otherSubject = Subject::factory()->create(['name' => 'Private History']);

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $ownSubject->id,
        ]);
        Enrollment::factory()->create([
            'student_id' => $otherStudent->id,
            'subject_id' => $otherSubject->id,
        ]);

        $this->post('/login', [
            'email' => $studentUser->email,
            'password' => 'password',
        ])->assertRedirect(route('student.enrollments'));

        $this->get(route('student.enrollments'))
            ->assertOk()
            ->assertSee('aria-label="Student navigation"', false)
            ->assertSee('My Enrolled Subjects')
            ->assertSee('Sign out')
            ->assertDontSee('href="'.route('students.index').'"', false)
            ->assertSee('My Mathematics')
            ->assertDontSee('Private History');
    }

    public function test_student_enrollment_view_shows_matching_subject_schedule_and_faculty(): void
    {
        $student = Student::factory()->create();
        $studentUser = User::factory()->create(['role' => 'student']);
        $student->user()->associate($studentUser)->save();
        $subject = Subject::factory()->create([
            'code' => 'CS201',
            'name' => 'Data Structures',
            'units' => 3,
        ]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);
        $faculty = Faculty::create(['name' => 'Alex Rivera']);
        TeachingLoad::create([
            'faculty_id' => $faculty->id,
            'subject_id' => $subject->id,
            'units' => $subject->units,
            'program' => 'BS Information Technology',
            'year_level' => 'First Year',
            'section' => 'A',
            'school_year' => '2026-2027',
            'semester' => 'First',
            'days' => ['Monday', 'Wednesday'],
            'start_time' => '09:00',
            'end_time' => '10:30',
            'lab_days' => ['Friday'],
            'lab_start_time' => '13:00',
            'lab_end_time' => '15:00',
            'room' => 'Room 204',
        ]);

        $this->actingAs($studentUser)
            ->get(route('student.enrollments'))
            ->assertOk()
            ->assertSee('CS201')
            ->assertSee('Data Structures')
            ->assertSee('Lecture · Monday, Wednesday')
            ->assertSee('Monday, Wednesday')
            ->assertSee('9:00 AM')
            ->assertSee('10:30 AM')
            ->assertSee('Laboratory · Friday')
            ->assertSee('1:00 PM')
            ->assertSee('3:00 PM')
            ->assertSee('Alex Rivera')
            ->assertSee('Room 204')
            ->assertSee('Section A');
    }

    public function test_student_can_search_and_filter_own_subjects_by_school_year_and_semester(): void
    {
        $student = Student::factory()->create();
        $studentUser = User::factory()->create(['role' => 'student']);
        $student->user()->associate($studentUser)->save();

        $firstTermSubject = Subject::factory()->create([
            'code' => 'CS-SEARCH-1',
            'name' => 'Data Search Systems',
        ]);
        $secondTermSubject = Subject::factory()->create([
            'code' => 'CS-SEARCH-2',
            'name' => 'Data Structures',
        ]);
        $otherYearSubject = Subject::factory()->create([
            'code' => 'CS-SEARCH-3',
            'name' => 'Network Design',
        ]);
        $privateSubject = Subject::factory()->create([
            'code' => 'PRIVATE-1',
            'name' => 'Private Student Subject',
        ]);

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $firstTermSubject->id,
            'school_year' => '2025-2026',
            'semester' => 'First',
        ]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $secondTermSubject->id,
            'school_year' => '2025-2026',
            'semester' => 'Second',
        ]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $otherYearSubject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);
        Enrollment::factory()->create([
            'student_id' => Student::factory()->create()->id,
            'subject_id' => $privateSubject->id,
            'school_year' => '2025-2026',
            'semester' => 'First',
        ]);

        $this->actingAs($studentUser)
            ->get(route('student.enrollments', [
                'search' => 'Data',
                'school_year' => '2025-2026',
                'semester' => 'Second',
            ]))
            ->assertOk()
            ->assertSee('Data Structures')
            ->assertDontSee('Data Search Systems')
            ->assertDontSee('Network Design')
            ->assertDontSee('Private Student Subject')
            ->assertSee('value="Data"', false)
            ->assertSee('<option value="2025-2026" selected>', false)
            ->assertSee('<option value="Second" selected>', false);

        $this->get(route('student.enrollments', ['search' => 'CS-SEARCH-3']))
            ->assertOk()
            ->assertSee('Network Design')
            ->assertDontSee('Data Search Systems');
    }

    public function test_student_enrollment_pagination_uses_compact_styled_controls(): void
    {
        $student = Student::factory()->create();
        $studentUser = User::factory()->create(['role' => 'student']);
        $student->user()->associate($studentUser)->save();

        foreach (range(1, 11) as $index) {
            $subject = Subject::factory()->create([
                'code' => 'PAG'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'name' => 'Pagination Subject '.$index,
            ]);
            Enrollment::factory()->create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
            ]);
        }

        $this->actingAs($studentUser)
            ->get(route('student.enrollments'))
            ->assertOk()
            ->assertSee('Showing 1–10 of 11 subjects')
            ->assertSee('page-item active', false)
            ->assertSee('PAG');
    }

    public function test_student_sees_a_clear_message_when_schedule_or_faculty_is_unassigned(): void
    {
        $student = Student::factory()->create();
        $studentUser = User::factory()->create(['role' => 'student']);
        $student->user()->associate($studentUser)->save();
        $subject = Subject::factory()->create(['code' => 'CS202']);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_year' => '2026-2027',
            'semester' => 'First',
        ]);

        $this->actingAs($studentUser)
            ->get(route('student.enrollments'))
            ->assertOk()
            ->assertSee('Schedule not yet plotted')
            ->assertSee('Not assigned');
    }

    public function test_student_cannot_access_staff_or_administrator_pages(): void
    {
        $student = Student::factory()->create();
        $user = User::factory()->create(['role' => 'student']);
        $student->user()->associate($user)->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('student.enrollments'));
        $this->get(route('students.index'))->assertForbidden();
        $this->get(route('subjects.index'))->assertForbidden();
        $this->get(route('enrollments.index'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get('/admin')->assertForbidden();
    }

    public function test_unlinked_student_login_is_denied_access_to_the_portal(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)
            ->get(route('student.enrollments'))
            ->assertForbidden();
    }

    public function test_student_cannot_use_staff_student_api(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->withBasicAuth($user->email, 'password')
            ->getJson('/api/students')
            ->assertForbidden();
    }

    public function test_student_account_cannot_be_created_if_email_is_already_a_user_login(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $student = Student::factory()->create(['email' => 'existing@example.com']);
        User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($administrator)
            ->from(route('students.show', $student))
            ->post(route('students.account.store', $student), [
                'password' => 'student-password-123',
                'password_confirmation' => 'student-password-123',
            ])
            ->assertRedirect(route('students.show', $student))
            ->assertSessionHasErrors('email');

        $this->assertNull($student->fresh()->user_id);
    }
}
