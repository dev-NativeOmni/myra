<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tahfidz_journals', function (Blueprint $table) {
            $table->string('attendance', 20)->nullable()->after('date'); // hadir, sakit, izin, alpa
            $table->string('status', 30)->nullable()->after('grade'); // passed, repeat, needs_improvement
            $table->decimal('score', 5, 2)->nullable()->after('status');
            $table->integer('surah_id')->nullable()->after('juz');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tahfidz_journals', function (Blueprint $table) {
            $table->dropColumn(['attendance', 'status', 'score', 'surah_id']);
        });
    }
};
