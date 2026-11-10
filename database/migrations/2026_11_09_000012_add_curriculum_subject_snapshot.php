<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_subject', function (Blueprint $table) {
            $table->string('curriculum_code')->nullable();
            $table->string('subject_name')->nullable();
            $table->unsignedTinyInteger('units')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_subject', function (Blueprint $table) {
            $table->dropColumn(['curriculum_code', 'subject_name', 'units']);
        });
    }
};
