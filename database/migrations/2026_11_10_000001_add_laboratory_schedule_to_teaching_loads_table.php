<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_loads', function (Blueprint $table) {
            $table->json('lab_days')->nullable()->after('days');
            $table->time('lab_start_time')->nullable()->after('end_time');
            $table->time('lab_end_time')->nullable()->after('lab_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_loads', function (Blueprint $table) {
            $table->dropColumn(['lab_days', 'lab_start_time', 'lab_end_time']);
        });
    }
};
