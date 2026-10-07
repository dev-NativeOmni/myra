<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\TenantContext;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $guru = User::withoutGlobalScopes()->where('role', User::ROLE_GURU)->firstOrFail();

        $response = $this->actingAs($guru)->get(route('home'));

        $response->assertRedirect(route('modules.tahfidz'));
    }

    public function test_gateway_page_renders_successfully(): void
    {
        $response = $this->get(route('gateway.index'));
        $response->assertOk();
        $response->assertSee('Myra Multi-Tenant');
        $response->assertSee('MYRA-DEMO');
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
            'token' => 'myra-demo', // test case insensitivity
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('active_institution_id');
    }

    public function test_direct_portal_link_sets_tenant_session(): void
    {
        $response = $this->get(route('gateway.direct', 'MYRA-DEMO'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('active_institution_id');
    }

    public function test_user_can_login_under_active_tenant(): void
    {
        // 1. Set tenant
        $this->post(route('gateway.verify'), ['token' => 'MYRA-DEMO']);

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
        $instA = Institution::where('token', 'MYRA-DEMO')->first();

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

    public function test_super_admin_creates_institution_together_with_its_first_admin(): void
    {
        $superAdmin = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

        $response = $this->actingAs($superAdmin)->post(route('platform.institutions.store'), [
            'name' => 'Pesantren Baru',
            'token' => 'PESANTREN-BARU',
            'city' => 'Surabaya',
            'director_name' => 'K.H. Mustofa',
            'director_title' => 'Pengasuh',
            'accent_color' => '#10B981',
            'admin_name' => 'Admin Pesantren Baru',
            'admin_username' => 'admin_pesantren_baru',
            'admin_password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('platform.institutions.index'));
        $institution = Institution::where('token', 'PESANTREN-BARU')->firstOrFail();
        $admin = User::withoutGlobalScopes()->where('username', 'admin_pesantren_baru')->firstOrFail();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame($institution->id, $admin->institution_id);
        $this->assertTrue(Hash::check('rahasia123', $admin->password));
    }

    public function test_creating_institution_requires_its_first_admin_account(): void
    {
        $superAdmin = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

        $response = $this->actingAs($superAdmin)->post(route('platform.institutions.store'), [
            'name' => 'Pesantren Tanpa Admin',
            'city' => 'Surabaya',
            'director_name' => 'K.H. Mustofa',
            'director_title' => 'Pengasuh',
        ]);

        $response->assertSessionHasErrors(['admin_name', 'admin_username', 'admin_password']);
        $this->assertDatabaseMissing('institutions', ['name' => 'Pesantren Tanpa Admin']);
    }

    public function test_gateway_reset_clears_tenant_and_logs_out(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->post(route('gateway.reset'));

        $this->assertGuest();
        $response->assertRedirect(route('gateway.index'));
    }

    public function test_super_admin_cannot_edit_an_institutions_own_settings(): void
    {
        $superAdmin = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
        $institution = Institution::firstOrFail();

        $platformEdit = $this->actingAs($superAdmin)->put('/platform/institutions/'.$institution->id, ['name' => 'Diubah Platform']);
        $profileEdit = $this->actingAs($superAdmin)->put(route('institution.update'), ['name' => 'Diubah Platform']);

        $platformEdit->assertNotFound();
        $profileEdit->assertForbidden();
        $this->assertNotSame('Diubah Platform', $institution->fresh()->name);
    }
}
