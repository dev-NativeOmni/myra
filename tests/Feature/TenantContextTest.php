<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unlike actingAs(), a real request resolves the user from the session id, which
     * runs the tenant scope on User while the tenant itself is being resolved.
     */
    public function test_logged_in_request_resolves_user_from_session_without_recursion(): void
    {
        $this->seed(SampleDataSeeder::class);
        $superAdmin = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

        $response = $this->withSession([auth()->guard()->getName() => $superAdmin->id])
            ->get(route('platform.institutions.index'));

        $response->assertOk();
    }

    public function test_public_setup_endpoint_no_longer_exists(): void
    {
        $response = $this->get('/system/setup');

        $response->assertNotFound();
    }
}
