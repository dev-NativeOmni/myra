<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_resets_password_of_the_platform_super_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'username' => 'platform', 'institution_id' => null]);

        $this->artisan('app:reset-password', ['username' => 'platform'])
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'sandi-baru-123')
            ->expectsQuestion('Ulangi password baru', 'sandi-baru-123')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('sandi-baru-123', $superAdmin->fresh()->password));
    }

    public function test_signs_the_account_out_of_existing_sessions(): void
    {
        $user = User::factory()->create(['username' => 'staf', 'remember_token' => 'token-lama']);
        DB::table('sessions')->insert(['id' => 'sesi-lama', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->artisan('app:reset-password', ['username' => 'staf'])
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'sandi-baru-123')
            ->expectsQuestion('Ulangi password baru', 'sandi-baru-123')
            ->assertSuccessful();

        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-lama']);
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_rejects_mismatched_or_short_password_and_keeps_the_old_one(): void
    {
        $user = User::factory()->create(['username' => 'staf']);
        $originalHash = $user->password;

        $this->artisan('app:reset-password', ['username' => 'staf'])
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'sandi-baru-123')
            ->expectsQuestion('Ulangi password baru', 'berbeda-123')
            ->assertFailed();
        $this->artisan('app:reset-password', ['username' => 'staf'])
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'pendek')
            ->expectsQuestion('Ulangi password baru', 'pendek')
            ->assertFailed();

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_reports_unknown_username(): void
    {
        $this->artisan('app:reset-password', ['username' => 'tidak_ada'])
            ->expectsOutputToContain('tidak ditemukan')
            ->assertFailed();
    }
}
