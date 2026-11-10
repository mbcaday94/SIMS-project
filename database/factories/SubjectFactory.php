<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SUB-###'),
            'name' => fake()->unique()->words(3, true),
            'units' => fake()->numberBetween(1, 6),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
