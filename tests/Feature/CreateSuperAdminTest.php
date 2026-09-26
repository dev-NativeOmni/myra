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
}
