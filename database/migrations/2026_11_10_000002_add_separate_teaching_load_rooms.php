<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_loads', function (Blueprint $table) {
            $table->string('lecture_room')->nullable();
            $table->string('lab_room')->nullable();
        });

        DB::table('teaching_loads')->update(['lecture_room' => DB::raw('room')]);
        DB::table('teaching_loads')
            ->whereNotNull('lab_days')
            ->update(['lab_room' => DB::raw('room')]);
    }

    public function down(): void
    {
        Schema::table('teaching_loads', function (Blueprint $table) {
            $table->dropColumn(['lecture_room', 'lab_room']);
        });
    }
};
