<?php

namespace Tests\Feature;

use App\Models\MonthlyReport;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_login_is_blocked_after_too_many_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), ['username' => 'guru', 'password' => 'salah']);
        }

        // Even the correct password is rejected while the limiter is locked.
        $response = $this->post(route('login.post'), ['username' => 'guru', 'password' => 'password']);

        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('username'));
        $this->assertGuest();
    }

    public function test_failed_attempts_for_one_username_do_not_lock_other_usernames(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), ['username' => 'guru', 'password' => 'salah']);
        }

        $this->post(route('login.post'), ['username' => 'admin', 'password' => 'password']);

        $this->assertAuthenticated();
    }

    public function test_successful_login_resets_failed_attempt_counter(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post(route('login.post'), ['username' => 'guru', 'password' => 'salah']);
        }

        $this->post(route('login.post'), ['username' => 'guru', 'password' => 'password']);
        $this->assertAuthenticated();
        $this->post(route('logout'));

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('login.post'), ['username' => 'guru', 'password' => 'salah']);
        }
        $this->post(route('login.post'), ['username' => 'guru', 'password' => 'password']);

        $this->assertAuthenticated();
    }

    public function test_login_successful_with_smart_redirect(): void
    {
        // 1. Wali Murid login redirects to parent dashboard
        $response = $this->post(route('login.post'), [
            'username' => 'walimurid',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('parent.dashboard'));

        // Logout
        $this->post(route('logout'));

        // 2. Guru login redirects to Tahfidz portal
        $response = $this->post(route('login.post'), [
            'username' => 'guru',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('modules.tahfidz'));

        // Logout
        $this->post(route('logout'));

        // 3. Super Admin login redirects to Platform Institutions
        $response = $this->post(route('login.post'), [
            'username' => 'superadmin',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('platform.institutions.index'));
    }

    public function test_institution_admin_manages_institution_profile(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $response = $this->actingAs($admin)->get(route('institution.edit'));

        $response->assertOk();
    }

    public function test_platform_super_admin_cannot_open_institution_pages_directly(): void
    {
        $superAdmin = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

        foreach (['institution.edit', 'dashboard', 'students.index', 'users.index', 'reports.index'] as $routeName) {
            $this->actingAs($superAdmin)->get(route($routeName))->assertForbidden();
        }
    }

    public function test_guru_tahfidz_access_boundaries(): void
    {
        $guru = User::where('role', User::ROLE_GURU)->first();

        // Can access Tahfidz module
        $response = $this->actingAs($guru)->get(route('modules.tahfidz'));
        $response->assertStatus(200);

        // Forbidden from Kesantrian module
        $forbiddenResponse = $this->actingAs($guru)->get(route('modules.kesantrian'));
        $forbiddenResponse->assertStatus(403);

        // Forbidden from Institution profile
        $instResponse = $this->actingAs($guru)->get(route('institution.edit'));
        $instResponse->assertStatus(403);
    }

    public function test_wali_murid_can_access_child_report_and_forbidden_from_admin(): void
    {
        $parent = User::where('role', User::ROLE_WALI_MURID)->has('children')->firstOrFail();
        $child = $parent->children->first();
        $report = MonthlyReport::where('student_id', $child->id)->where('status', 'published')->firstOrFail();

        // Can access Parent Dashboard
        $dashboardResponse = $this->actingAs($parent)->get(route('parent.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee($child->name);

        // Can preview child's PDF
        $pdfResponse = $this->actingAs($parent)->get(route('parent.reports.preview', $report->id));
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');

        // Forbidden from main admin dashboard
        $forbiddenResponse = $this->actingAs($parent)->get(route('dashboard'));
        $forbiddenResponse->assertStatus(403);
    }

    public function test_user_management_crud(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        // Index
        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertStatus(200);

        // Store
        $storeResponse = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Guru Baru',
            'username' => 'gurubaru',
            'email' => 'gurubaru@myra.id',
            'password' => 'password123',
            'role' => User::ROLE_GURU,
        ]);
        $storeResponse->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['username' => 'gurubaru']);
    }
}
