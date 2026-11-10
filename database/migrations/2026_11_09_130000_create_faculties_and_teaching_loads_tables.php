<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('employee_number')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('teaching_loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('program');
            $table->string('year_level', 50);
            $table->string('section', 50);
            $table->string('school_year', 9);
            $table->string('semester', 20);
            $table->json('days');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room');
            $table->timestamps();

            $table->index(['school_year', 'semester']);
            $table->index(['faculty_id', 'school_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_loads');
        Schema::dropIfExists('faculties');
    }
};
