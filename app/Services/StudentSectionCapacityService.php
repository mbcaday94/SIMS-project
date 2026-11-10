<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Validation\ValidationException;

class StudentSectionCapacityService
{
    public const MAXIMUM_STUDENTS_PER_BLOCK = 50;

    /**
     * @param  array<string, mixed>  $data
     */
    public function ensureCapacity(array $data, ?Student $student = null): void
    {
        if (empty($data['section'])) {
            return;
        }

        $sameBlock = $student !== null
            && $student->course === $data['course']
            && $student->year_level === $data['year_level']
            && $student->section === $data['section'];

        if ($sameBlock) {
            return;
        }

        $studentCount = Student::query()
            ->where('course', $data['course'])
            ->where('year_level', $data['year_level'])
            ->where('section', $data['section'])
            ->count();

        if ($studentCount >= self::MAXIMUM_STUDENTS_PER_BLOCK) {
            throw ValidationException::withMessages([
                'section' => 'This block is full. A block can have at most '.self::MAXIMUM_STUDENTS_PER_BLOCK.' students.',
            ]);
        }
    }
}
