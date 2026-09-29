<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets one parent (wali murid) account be linked to several students (siblings).
 * Existing single links in users.student_id are moved into the pivot table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'student_id']);
        });

        DB::table('parent_student')->insertUsing(
            ['user_id', 'student_id', 'created_at', 'updated_at'],
            DB::table('users')
                ->select('id', 'student_id', DB::raw('CURRENT_TIMESTAMP'), DB::raw('CURRENT_TIMESTAMP'))
                ->where('role', 'wali_murid')
                ->whereNotNull('student_id')
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('role')->constrained('students')->nullOnDelete();
        });

        // A single column can only hold one child; keep the first linked one.
        DB::table('parent_student')
            ->selectRaw('user_id, MIN(student_id) as student_id')
            ->groupBy('user_id')
            ->get()
            ->each(fn ($link) => DB::table('users')->where('id', $link->user_id)->update(['student_id' => $link->student_id]));

        Schema::dropIfExists('parent_student');
    }
};
