<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Classroom-level scoping is new: existing guru/wali kelas/kesantrian accounts
     * have no classroom assigned yet, which would otherwise lock them out of every
     * edit action. Assign them to every existing classroom as a safe default so
     * access is not suddenly restricted; an admin can narrow this down per user
     * afterwards from the user management screen.
     */
    public function up(): void
    {
        $classroomIds = DB::table('classrooms')->pluck('id');

        if ($classroomIds->isEmpty()) {
            return;
        }

        $staffIds = DB::table('users')
            ->whereIn('role', User::CLASSROOM_SCOPED_ROLES)
            ->pluck('id');

        if ($staffIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($staffIds as $userId) {
            foreach ($classroomIds as $classroomId) {
                $rows[] = [
                    'classroom_id' => $classroomId,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        collect($rows)->chunk(500)->each(
            fn ($chunk) => DB::table('classroom_user')->insertOrIgnore($chunk->all())
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('classroom_user')
            ->whereIn('user_id', DB::table('users')->whereIn('role', User::CLASSROOM_SCOPED_ROLES)->pluck('id'))
            ->delete();
    }
};
