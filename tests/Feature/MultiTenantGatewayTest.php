<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\TenantContext;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);
    }

    public function test_guest_can_open_login_without_selecting_an_institution(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_logged_in_staff_opening_home_lands_on_their_own_start_page(): void
    {
        $this->seed(SampleDataSeeder::class);
        $guru = User::withoutGlobalScopes()->where('role', User::ROLE_GURU)->firstOrFail();

        $response = $this->actingAs($guru)->get(route('home'));

        $response->assertRedirect(route('modules.tahfidz'));
    }

    public function test_gateway_page_renders_successfully(): void
    {
        $response = $this->get(route('gateway.index'));
        $response->assertOk();
        $response->assertSee('Taqreer Multi-Tenant');
        $response->assertSee('TAQREER-DEMO');
    }

    public function test_invalid_token_returns_error(): void
    {
        $response = $this->post(route('gateway.verify'), [
            'token' => 'TOKEN-TIDAK-ADA',
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse(TenantContext::hasTenant());
    }

    public function test_valid_token_sets_tenant_session_and_redirects_to_login(): void
    {
        $response = $this->post(route('gateway.verify'), [
            'token' => 'taqreer-demo', // test case insensitivity
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('active_institution_id');
    }

    public function test_direct_portal_link_sets_tenant_session(): void
    {
        $response = $this->get(route('gateway.direct', 'TAQREER-DEMO'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('active_institution_id');
    }

    public function test_user_can_login_under_active_tenant(): void
    {
        // 1. Set tenant
        $this->post(route('gateway.verify'), ['token' => 'TAQREER-DEMO']);

        // 2. Login as superadmin
        $response = $this->post(route('login.post'), [
            'username' => 'superadmin',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('platform.institutions.index'));
    }

    public function test_tenant_data_isolation_between_institutions(): void
    {
        // Institution A (from seeder)
        $instA = Institution::where('token', 'TAQREER-DEMO')->first();

        // Institution B (from seeder)
        $instB = Institution::where('token', 'ALHIKMAH-DEMO')->first();

        // Create classroom & student for Inst B
        TenantContext::setTenant($instB);
        $classroomB = Classroom::create([
            'institution_id' => $instB->id,
            'name' => 'Kelas Khusus Inst B',
        ]);
        $studentB = Student::create([
            'institution_id' => $instB->id,
            'classroom_id' => $classroomB->id,
            'name' => 'Santri Khusus Lembaga B',
            'nis' => 'NIS-B-999',
            'gender' => 'L',
            'status' => 'aktif',
        ]);

        // Now set context back to Inst A
        TenantContext::setTenant($instA);

        // Assert Inst A query cannot see Inst B's classroom or student
        $this->assertNull(Classroom::find($classroomB->id));
        $this->assertNull(Student::find($studentB->id));
        $this->assertFalse(Student::where('name', 'Santri Khusus Lembaga B')->exists());
    }

    public function test_super_admin_can_manage_institutions(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->get(route('platform.institutions.index'));
        $response->assertOk();
        $response->assertSee('Manajemen Lembaga (SaaS)');

        // Create new institution
        $storeResponse = $this->actingAs($superAdmin)->post(route('platform.institutions.store'), [
            'name' => 'Pesantren Baru',
            'token' => 'PESANTREN-BARU',
            'city' => 'Surabaya',
            'director_name' => 'K.H. Mustofa',
            'director_title' => 'Pengasuh',
            'accent_color' => '#10B981',
        ]);

        $storeResponse->assertRedirect(route('platform.institutions.index'));
        $this->assertDatabaseHas('institutions', ['token' => 'PESANTREN-BARU']);
    }

    public function test_gateway_reset_clears_tenant_and_logs_out(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->post(route('gateway.reset'));

        $this->assertGuest();
        $response->assertRedirect(route('gateway.index'));
    }
}
