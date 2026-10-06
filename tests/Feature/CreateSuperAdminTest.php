<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_super_admin_with_hashed_password(): void
    {
        $this->artisan('app:create-super-admin', ['username' => 'direktur', '--name' => 'Direktur'])
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia-kuat')
            ->expectsQuestion('Ulangi password', 'rahasia-kuat')
            ->assertSuccessful();

        $user = User::where('username', 'direktur')->first();
        $this->assertSame(User::ROLE_SUPER_ADMIN, $user->role);
        $this->assertSame('Direktur', $user->name);
        $this->assertTrue(Hash::check('rahasia-kuat', $user->password));
    }

    public function test_rejects_mismatched_password_confirmation(): void
    {
        $this->artisan('app:create-super-admin', ['username' => 'direktur'])
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia-kuat')
            ->expectsQuestion('Ulangi password', 'berbeda-sekali')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['username' => 'direktur']);
    }

    public function test_rejects_password_shorter_than_eight_characters(): void
    {
        $this->artisan('app:create-super-admin', ['username' => 'direktur'])
            ->expectsQuestion('Password (minimal 8 karakter)', 'pendek')
            ->expectsQuestion('Ulangi password', 'pendek')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['username' => 'direktur']);
    }

    public function test_rejects_existing_username(): void
    {
        User::factory()->create(['username' => 'direktur']);

        $this->artisan('app:create-super-admin', ['username' => 'direktur'])
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia-kuat')
            ->expectsQuestion('Ulangi password', 'rahasia-kuat')
            ->assertFailed();

        $this->assertSame(1, User::where('username', 'direktur')->count());
    }

    public function test_refuses_to_create_a_second_super_admin(): void
    {
        User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'username' => 'platform']);

        $this->artisan('app:create-super-admin', ['username' => 'kedua'])
            ->expectsOutputToContain('Hanya boleh ada satu Super Admin')
            ->assertFailed();

        $this->assertSame(1, User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->count());
    }

    public function test_super_admin_is_not_attached_to_any_institution(): void
    {
        $this->artisan('app:create-super-admin', ['username' => 'platform'])
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia-kuat')
            ->expectsQuestion('Ulangi password', 'rahasia-kuat')
            ->assertSuccessful();

        $this->assertNull(User::withoutGlobalScopes()->where('username', 'platform')->value('institution_id'));
    }
}
