<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_api_requires_authentication(): void
    {
        $this->getJson('/api/students')->assertUnauthorized();
    }

    public function test_staff_can_list_and_read_students(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $student = Student::factory()->create();

        $this->withBasicAuth($user->email, 'password')
            ->getJson('/api/students')
            ->assertOk()
            ->assertJsonPath('data.0.id', $student->id)
            ->assertJsonPath('data.0.student_number', $student->student_number);

        $this->withBasicAuth($user->email, 'password')
            ->getJson("/api/students/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.email', $student->email);
    }

    public function test_staff_can_create_and_update_students(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $data = [
            'student_number' => 'API-1001',
            'first_name' => 'Taylor',
            'last_name' => 'Reyes',
            'email' => 'taylor@example.com',
            'course' => Student::COURSES[0],
            'year_level' => '2nd Year',
            'semester' => 'First',
            'section' => 'A',
        ];

        $response = $this->withBasicAuth($user->email, 'password')
            ->postJson('/api/students', $data)
            ->assertCreated()
            ->assertJsonPath('data.student_number', 'API-1001')
            ->assertJsonPath('data.semester', 'First')
            ->assertJsonPath('data.section', 'A');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('students', $data);

        $this->withBasicAuth($user->email, 'password')
            ->putJson("/api/students/{$id}", [...$data, 'year_level' => '3rd Year'])
            ->assertOk()
            ->assertJsonPath('data.year_level', '3rd Year')
            ->assertJsonPath('data.semester', 'First');
    }

    public function test_api_validates_student_fields(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $student = Student::factory()->create();

        $this->withBasicAuth($user->email, 'password')
            ->postJson('/api/students', [
                'student_number' => $student->student_number,
                'first_name' => '',
                'last_name' => 'Lee',
                'email' => 'not-an-email',
                'course' => 'Computing',
                'year_level' => '1st Year',
                'semester' => 'Fall',
                'section' => 'F',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_number', 'first_name', 'email', 'course', 'semester', 'section']);
    }

    public function test_api_cannot_assign_more_than_50_students_to_a_block(): void
    {
        Student::factory()->count(50)->create([
            'course' => Student::COURSES[0],
            'year_level' => '2nd Year',
            'section' => 'A',
        ]);
        $user = User::factory()->create(['role' => 'staff']);

        $this->withBasicAuth($user->email, 'password')
            ->postJson('/api/students', [
                'student_number' => 'API-BLOCK-OVERFLOW',
                'first_name' => 'Extra',
                'last_name' => 'Student',
                'email' => 'api-overflow@example.com',
                'course' => Student::COURSES[0],
                'year_level' => '2nd Year',
                'section' => 'A',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('section');

        $this->assertDatabaseMissing('students', ['student_number' => 'API-BLOCK-OVERFLOW']);
    }

    public function test_staff_cannot_delete_students(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $student = Student::factory()->create();

        $this->withBasicAuth($staff->email, 'password')
            ->deleteJson("/api/students/{$student->id}")
            ->assertForbidden();
    }

    public function test_administrator_can_delete_students_unless_they_have_enrollments(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $enrolledStudent = Student::factory()->create();
        $subject = Subject::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $enrolledStudent->id,
            'subject_id' => $subject->id,
        ]);

        $this->withBasicAuth($administrator->email, 'password')
            ->deleteJson("/api/students/{$enrolledStudent->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This student cannot be deleted while they have enrollments.');

        $student = Student::factory()->create();

        $this->withBasicAuth($administrator->email, 'password')
            ->deleteJson("/api/students/{$student->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}
