<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Super Admin becomes a single platform-level account that monitors all
 * institutions and is not part of any of them; each institution is run by its
 * own Admin accounts. The oldest Super Admin account is kept, any other Super
 * Admin accounts are deleted (they cannot be restored by rolling back), and a
 * partial unique index keeps a second one from ever being created.
 *
 * Also adds the log of Super Admin support sessions (impersonation).
 */
return new class extends Migration
{
    public function up(): void
    {
        $platformAdminId = DB::table('users')->where('role', 'super_admin')->orderBy('id')->value('id');

        if ($platformAdminId !== null) {
            DB::table('users')->where('role', 'super_admin')->where('id', '!=', $platformAdminId)->delete();
            DB::table('users')->where('id', $platformAdminId)->update(['institution_id' => null]);
        }

        DB::statement("CREATE UNIQUE INDEX users_single_super_admin ON users (role) WHERE role = 'super_admin'");

        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impersonator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('impersonated_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('started_at')->useCurrent()->index();
            $table->timestamp('ended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
        DB::statement('DROP INDEX users_single_super_admin');
    }
};
