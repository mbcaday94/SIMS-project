<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_student_quick_link_opens_student_list(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->withSession(['success' => 'Welcome back'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Main navigation"', false)
            ->assertSee('Admission')
            ->assertSee('class="sidebar-link active"', false)
            ->assertSee('Recently Added Students')
            ->assertSee('Recent Enrollments')
            ->assertSee('href="'.route('students.index').'"', false)
            ->assertDontSee('Manage Students')
            ->assertDontSee('Add Student')
            ->assertSee('href="'.route('reports.index').'"', false);
    }

    public function test_dashboard_shows_recent_student_and_enrollment_activity(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $student = Student::factory()->create([
            'first_name' => 'Jamie',
            'last_name' => 'Rivera',
            'course' => Student::COURSES[2],
            'year_level' => '2nd Year',
        ]);
        $subject = Subject::factory()->create(['code' => 'CS-210']);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'status' => 'completed',
        ]);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Jamie Rivera')
            ->assertSee('CS-210')
            ->assertSee('Completed');
    }

    public function test_student_module_pages_use_the_sidebar_navigation(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('aria-label="Main navigation"', false)
            ->assertSee('href="'.route('subjects.index').'"', false)
            ->assertSee('Sign out');
    }

    public function test_student_form_offers_the_four_approved_courses(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('students.create'))
            ->assertOk()
            ->assertSee('<select id="course" name="course"', false)
            ->assertSee('Select a course')
            ->assertSee('Bachelor of Science in Information Technology')
            ->assertSee('Bachelor of Science in Information System')
            ->assertSee('Bachelor of Science in Computer Science')
            ->assertSee('Bachelor of Science in Computer Engineering')
            ->assertSee('<select id="semester" name="semester"', false)
            ->assertSee('Select a semester')
            ->assertSee('First')
            ->assertSee('Second')
            ->assertSee('Summer')
            ->assertDontSee('name="section"', false)
            ->assertDontSee('Block / Section');
    }

    public function test_student_course_must_be_one_of_the_approved_choices(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => 'STU-INVALID-COURSE',
                'first_name' => 'Invalid',
                'last_name' => 'Course',
                'email' => 'invalid-course@example.com',
                'course' => 'Unapproved Program',
                'year_level' => '1st Year',
                'semester' => 'First',
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors('course');

        $this->assertDatabaseMissing('students', ['student_number' => 'STU-INVALID-COURSE']);
    }

    public function test_student_semester_must_be_one_of_the_available_choices(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => 'STU-INVALID-SEMESTER',
                'first_name' => 'Invalid',
                'last_name' => 'Semester',
                'email' => 'invalid-semester@example.com',
                'course' => Student::COURSES[0],
                'year_level' => '1st Year',
                'semester' => 'Fall',
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors('semester');

        $this->assertDatabaseMissing('students', ['student_number' => 'STU-INVALID-SEMESTER']);
    }

    public function test_staff_can_create_view_and_update_students(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);

        $studentData = [
            'student_number' => 'STU-1001',
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'email' => 'alex@example.com',
            'course' => Student::COURSES[2],
            'year_level' => '1st Year',
            'semester' => 'First',
        ];

        $this->post(route('students.store'), $studentData)
            ->assertRedirect(route('students.index'));

        $student = Student::where('student_number', 'STU-1001')->firstOrFail();

        $this->assertDatabaseHas('students', [
            ...$studentData,
            'section' => null,
        ]);
        $this->get(route('students.index'))->assertOk()->assertSee('alex@example.com');
        $this->get(route('students.show', $student))
            ->assertOk()
            ->assertSee(Student::COURSES[2])
            ->assertSee('First')
            ->assertSee('Block / Section')
            ->assertSee('—');

        $this->put(route('students.update', $student), [
            ...$studentData,
            'course' => Student::COURSES[0],
            'semester' => 'Second',
        ])->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'course' => Student::COURSES[0],
            'semester' => 'Second',
            'section' => null,
        ]);
    }

    public function test_student_email_must_be_unique(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Student::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($staff)
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => 'STU-1002',
                'first_name' => 'Sam',
                'last_name' => 'Lee',
                'email' => 'taken@example.com',
                'course' => Student::COURSES[2],
                'year_level' => '2nd Year',
                'section' => 'B',
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors('email');
    }

    public function test_student_photo_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('public');
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);

        $studentData = [
            'student_number' => 'STU-PHOTO',
            'first_name' => 'Photo',
            'last_name' => 'Student',
            'email' => 'photo@example.com',
            'course' => Student::COURSES[2],
            'year_level' => '1st Year',
            'semester' => 'First',
            'section' => 'C',
        ];
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=', true);

        $this->post(route('students.store'), [
            ...$studentData,
            'photo' => UploadedFile::fake()->createWithContent('student.png', $png),
        ])->assertRedirect(route('students.index'));

        $student = Student::where('student_number', 'STU-PHOTO')->firstOrFail();
        $this->assertNotNull($student->photo_path);
        Storage::disk('public')->assertExists($student->photo_path);

        $oldPhotoPath = $student->photo_path;
        $this->put(route('students.update', $student), [
            ...$studentData,
            'photo' => UploadedFile::fake()->createWithContent('replacement.png', $png),
        ])->assertRedirect(route('students.index'));

        $student->refresh();
        $this->assertNotSame($oldPhotoPath, $student->photo_path);
        Storage::disk('public')->assertMissing($oldPhotoPath);
        Storage::disk('public')->assertExists($student->photo_path);

        $this->get(route('students.show', $student))
            ->assertOk()
            ->assertSee(asset('storage/'.$student->photo_path));
    }

    public function test_student_photo_must_be_an_allowed_image_within_size_limit(): void
    {
        Storage::fake('public');
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => 'STU-BAD-PHOTO',
                'first_name' => 'Invalid',
                'last_name' => 'Photo',
                'email' => 'invalid-photo@example.com',
                'course' => Student::COURSES[2],
                'year_level' => '1st Year',
                'semester' => 'First',
                'section' => 'A',
                'photo' => UploadedFile::fake()->create('document.txt', 10, 'text/plain'),
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors('photo');
    }

    public function test_only_administrators_can_delete_students(): void
    {
        $student = Student::factory()->create();
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->delete(route('students.destroy', $student))
            ->assertForbidden();

        $administrator = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($administrator)
            ->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}
