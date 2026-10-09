<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountSeedingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
    }

    public function test_admin_generates_demo_accounts_for_own_institution(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.seed-dummy'));

        $response->assertRedirect(route('users.index'));
        $this->assertSame(
            [User::ROLE_GURU, User::ROLE_KESANTRIAN, User::ROLE_TU, User::ROLE_WALI_KELAS, User::ROLE_WALI_MURID],
            User::withoutGlobalScopes()
                ->where('institution_id', $this->admin->institution_id)
                ->whereIn('username', ['guru_myrademo', 'walikelas_myrademo', 'kesantrian_myrademo', 'tu_myrademo', 'walimurid_myrademo'])
                ->orderBy('role')
                ->pluck('role')
                ->all(),
        );
    }

    public function test_demo_seeding_does_not_take_over_another_institutions_user_with_colliding_username(): void
    {
        $otherInstitution = Institution::create([
            'name' => 'Pesantren Lain',
            'token' => 'MYRADEMO',
            'city' => 'Kota Lain',
            'director_name' => 'Ust. Lain',
            'director_title' => 'Mudir',
        ]);
        $otherUser = User::factory()->create([
            'institution_id' => $otherInstitution->id,
            'username' => 'guru_myrademo',
            'password' => Hash::make('rahasia-lembaga-lain'),
            'role' => User::ROLE_GURU,
        ]);

        $this->actingAs($this->admin)->post(route('users.seed-dummy'))->assertRedirect(route('users.index'));

        $otherUser = User::withoutGlobalScopes()->findOrFail($otherUser->id);
        $this->assertSame($otherInstitution->id, $otherUser->institution_id);
        $this->assertTrue(Hash::check('rahasia-lembaga-lain', $otherUser->password));
    }

    public function test_non_admin_cannot_generate_demo_accounts(): void
    {
        $guru = User::where('role', User::ROLE_GURU)->first();

        $this->actingAs($guru)->post(route('users.seed-dummy'))->assertForbidden();

        $this->assertFalse(User::withoutGlobalScopes()->where('username', 'guru_myrademo')->exists());
    }

    public function test_super_admin_seeds_demo_students_for_an_institution_without_students(): void
    {
        $institution = Institution::create([
            'name' => 'Pesantren Baru',
            'token' => 'BARU-01',
            'city' => 'Kota Baru',
            'director_name' => 'Ust. Baru',
            'director_title' => 'Mudir',
        ]);
        $superAdmin = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->first();

        $this->actingAs($superAdmin)->post(route('platform.institutions.seed-dummy', $institution))
            ->assertRedirect(route('platform.institutions.index'));

        $this->assertSame(6, Student::withoutGlobalScopes()->where('institution_id', $institution->id)->where('is_active', true)->count());
    }

    public function test_demo_tools_are_unavailable_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->actingAs($this->admin)->get(route('users.index'))
            ->assertOk()
            ->assertDontSee(route('users.seed-dummy'));
        $this->actingAs($this->admin)
            ->withSession(['_token' => 'csrf-token'])
            ->post(route('users.seed-dummy'), ['_token' => 'csrf-token'])
            ->assertNotFound();
        $this->get(route('gateway.index'))->assertDontSee(route('gateway.direct', 'MYRA-DEMO'));

        $this->assertFalse(User::withoutGlobalScopes()->where('username', 'guru_myrademo')->exists());
    }
}
