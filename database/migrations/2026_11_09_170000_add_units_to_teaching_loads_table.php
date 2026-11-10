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
            $table->unsignedTinyInteger('units')->default(0);
        });

        DB::table('teaching_loads')
            ->select(['id', 'subject_id'])
            ->orderBy('id')
            ->each(function (object $load): void {
                $units = DB::table('subjects')->where('id', $load->subject_id)->value('units') ?? 0;
                DB::table('teaching_loads')->where('id', $load->id)->update(['units' => $units]);
            });
    }

    public function down(): void
    {
        Schema::table('teaching_loads', function (Blueprint $table) {
            $table->dropColumn('units');
        });
    }
};
