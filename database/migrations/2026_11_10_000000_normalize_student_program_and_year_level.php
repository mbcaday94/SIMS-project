<?php

use App\Models\Student;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $courseAliases = [
            'BSIT' => Student::COURSES[0],
            'Information Technology' => Student::COURSES[0],
            'BSIS' => Student::COURSES[1],
            'Information System' => Student::COURSES[1],
            'BSCS' => Student::COURSES[2],
            'Computer Science' => Student::COURSES[2],
            'BSCE' => Student::COURSES[3],
            'BSCpE' => Student::COURSES[3],
            'BSCPE' => Student::COURSES[3],
            'Computer Engineering' => Student::COURSES[3],
        ];

        foreach ($courseAliases as $alias => $course) {
            DB::table('students')->where('course', $alias)->update(['course' => $course]);
        }

        foreach ([
            '1' => '1st Year',
            '2' => '2nd Year',
            '3' => '3rd Year',
            '4' => '4th Year',
        ] as $year => $label) {
            DB::table('students')->where('year_level', (string) $year)->update(['year_level' => $label]);
        }
    }

    public function down(): void
    {
    }
};
