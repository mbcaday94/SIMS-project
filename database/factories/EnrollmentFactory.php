<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'school_year' => '2026-2027',
            'semester' => fake()->randomElement(['First', 'Second', 'Summer']),
            'status' => 'enrolled',
            'enrolled_at' => fake()->date(),
        ];
    }
}
