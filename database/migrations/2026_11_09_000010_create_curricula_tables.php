<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->string('program');
            $table->string('name');
            $table->string('source_filename');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['program', 'name']);
        });

        Schema::create('curriculum_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('year_level', 50);
            $table->string('semester', 50);
            $table->timestamps();

            $table->unique(
                ['curriculum_id', 'subject_id', 'year_level', 'semester'],
                'curriculum_subject_term_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_subject');
        Schema::dropIfExists('curricula');
    }
};
