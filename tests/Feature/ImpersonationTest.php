<?php

namespace Tests\Feature;

use App\Http\Controllers\ImpersonationController;
use App\Models\ImpersonationLog;
use App\Models\Institution;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->superAdmin = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
        $this->admin = User::withoutGlobalScopes()->where('role', User::ROLE_ADMIN)->firstOrFail();
        $this->institution = $this->admin->institution;
    }

    public function test_super_admin_starts_support_session_as_institution_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('platform.institutions.impersonate', [$this->institution, $this->admin->id]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin);
        $response->assertSessionHas(ImpersonationController::IMPERSONATOR_KEY, $this->superAdmin->id);
        $this->assertDatabaseHas('impersonation_logs', [
            'impersonator_id' => $this->superAdmin->id,
            'impersonated_user_id' => $this->admin->id,
            'institution_id' => $this->institution->id,
            'ended_at' => null,
        ]);
    }

    public function test_support_banner_is_shown_while_impersonating(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('platform.institutions.impersonate', [$this->institution, $this->admin->id]));

        $response = $this->get(route('dashboard'));

        $response->assertSee('Mode bantuan Super Admin');
        $response->assertSee(route('impersonation.stop'), false);
    }

    public function test_stopping_returns_to_super_admin_and_closes_the_log(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('platform.institutions.impersonate', [$this->institution, $this->admin->id]));

        $response = $this->post(route('impersonation.stop'));

        $response->assertRedirect(route('platform.institutions.index'));
        $this->assertAuthenticatedAs($this->superAdmin);
        $response->assertSessionMissing(ImpersonationController::IMPERSONATOR_KEY);
        $this->assertNotNull(ImpersonationLog::firstOrFail()->ended_at);
    }

    public function test_logging_out_during_support_session_closes_the_log(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('platform.institutions.impersonate', [$this->institution, $this->admin->id]));

        $this->post(route('logout'));

        $this->assertNotNull(ImpersonationLog::firstOrFail()->ended_at);
    }

    public function test_only_admin_accounts_of_that_institution_can_be_impersonated(): void
    {
        $guru = User::withoutGlobalScopes()->where('role', User::ROLE_GURU)->firstOrFail();
        $otherInstitution = Institution::create(['name' => 'Lembaga Lain', 'token' => 'LAIN-01', 'city' => 'Kota', 'director_name' => 'Ust. A', 'director_title' => 'Mudir', 'accent_color' => '#059669']);

        $this->actingAs($this->superAdmin)
            ->post(route('platform.institutions.impersonate', [$this->institution, $guru->id]))
            ->assertNotFound();
        $this->actingAs($this->superAdmin)
            ->post(route('platform.institutions.impersonate', [$otherInstitution, $this->admin->id]))
            ->assertNotFound();

        $this->assertSame(0, ImpersonationLog::count());
    }

    public function test_institution_admin_cannot_start_impersonation(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('platform.institutions.impersonate', [$this->institution, $this->admin->id]));

        $response->assertForbidden();
    }

    public function test_impersonated_admin_cannot_reach_platform_pages(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('platform.institutions.impersonate', [$this->institution, $this->admin->id]));

        $response = $this->get(route('platform.institutions.index'));

        $response->assertForbidden();
    }

    public function test_stop_without_a_support_session_is_forbidden(): void
    {
        $response = $this->actingAs($this->admin)->post(route('impersonation.stop'));

        $response->assertForbidden();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_platform_page_lists_institution_admins_for_support(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('platform.institutions.index'));

        $response->assertOk();
        $response->assertSee($this->institution->name);
        $response->assertSee(route('platform.institutions.impersonate', [$this->institution, $this->admin->id]), false);
    }
}
