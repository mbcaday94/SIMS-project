<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_subject', function (Blueprint $table) {
            $table->decimal('lecture_units', 5, 2)->nullable();
            $table->decimal('lab_units', 5, 2)->nullable();
            $table->decimal('contact_hours', 5, 2)->nullable();
            $table->decimal('lecture_hours', 5, 2)->nullable();
            $table->decimal('lab_hours', 5, 2)->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('co_requisites')->nullable();
            $table->string('course_group')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_subject', function (Blueprint $table) {
            $table->dropColumn([
                'lecture_units',
                'lab_units',
                'contact_hours',
                'lecture_hours',
                'lab_hours',
                'prerequisites',
                'co_requisites',
                'course_group',
            ]);
        });
    }
};
