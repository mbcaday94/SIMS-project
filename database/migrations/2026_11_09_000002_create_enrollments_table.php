<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('subject_id')->constrained();
            $table->string('school_year', 9);
            $table->string('semester');
            $table->string('status')->default('enrolled');
            $table->date('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'subject_id', 'school_year', 'semester'],
                'enrollments_student_subject_term_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
