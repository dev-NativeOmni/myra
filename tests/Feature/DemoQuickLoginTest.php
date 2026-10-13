<?php

namespace Tests\Feature;

use App\Console\Commands\SeedDemoInstitution;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoQuickLoginTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $demo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('app:seed-demo-institution', ['--password' => 'rahasia-demo'])->assertSuccessful();
        $this->demo = Institution::where('token', Institution::DEMO_TOKEN)->firstOrFail();
    }

    public function test_demo_login_page_offers_one_click_login_for_every_demo_account(): void
    {
        $this->get(route('gateway.direct', Institution::DEMO_TOKEN))->assertRedirect(route('login'));

        $response = $this->get(route('login'));

        $response->assertOk()->assertSee('Masuk Cepat Akun Demo');
        foreach (SeedDemoInstitution::accountUsernames() as $username) {
            $response->assertSee(route('login.demo', $username), false);
        }
        $response->assertDontSee('rahasia-demo');
    }

    public function test_one_click_login_signs_in_the_demo_account_and_opens_its_home_page(): void
    {
        $guru = User::where('username', 'guru_demo')->firstOrFail();

        $this->post(route('login.demo', 'guru_demo'))->assertRedirect(route('modules.tahfidz'));

        $this->assertAuthenticatedAs($guru);
    }

    public function test_one_click_login_refuses_accounts_the_demo_seeder_did_not_create(): void
    {
        User::factory()->create([
            'institution_id' => $this->demo->id,
            'username' => 'staf_tambahan',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->post(route('login.demo', 'staf_tambahan'))->assertNotFound();

        $this->assertGuest();
    }

    public function test_one_click_login_is_unavailable_while_the_demo_institution_is_inactive(): void
    {
        $this->demo->update(['is_active' => false]);

        $this->post(route('login.demo', 'admin_demo'))->assertNotFound();

        $this->assertGuest();
    }

    public function test_other_institutions_login_page_has_no_one_click_login(): void
    {
        Institution::create([
            'name' => 'Lembaga Asli',
            'token' => 'ASLI',
            'city' => 'Kota Asli',
            'director_name' => 'Ust. Asli',
            'director_title' => 'Mudir',
        ]);
        $this->get(route('gateway.direct', 'ASLI'));

        $this->get(route('login'))->assertOk()->assertDontSee('Masuk Cepat Akun Demo');
    }
}
