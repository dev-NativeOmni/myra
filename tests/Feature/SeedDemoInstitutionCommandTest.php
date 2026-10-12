<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\MonthlyReport;
use App\Models\Student;
use App\Models\TahfidzJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeedDemoInstitutionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_demo_institution_with_accounts_students_journals_and_reports(): void
    {
        $this->artisan('app:seed-demo-institution', ['--password' => 'rahasia-demo'])->assertSuccessful();

        $institution = Institution::where('token', Institution::DEMO_TOKEN)->firstOrFail();
        $this->assertSame('Pondok Pesantren Daarul Ilmi', $institution->name);
        $this->assertTrue($institution->is_active);

        $users = User::withoutGlobalScopes()->where('institution_id', $institution->id)->get();
        $this->assertSame(
            ['admin', 'guru', 'guru', 'kesantrian', 'tu', 'wali_kelas', 'wali_murid', 'wali_murid'],
            $users->pluck('role')->sort()->values()->all(),
        );
        $this->assertTrue(Hash::check('rahasia-demo', $users->firstWhere('username', 'guru_demo')->password));
        $this->assertSame(['DEMO-001', 'DEMO-003'], $users->firstWhere('username', 'walimurid_demo')->children()->orderBy('nis')->pluck('nis')->all());

        $this->assertSame(24, Student::withoutGlobalScopes()->where('institution_id', $institution->id)->where('is_active', true)->count());
        $this->assertSame(72, MonthlyReport::withoutGlobalScopes()->where('institution_id', $institution->id)->count());

        $journals = TahfidzJournal::withoutGlobalScopes()->where('institution_id', $institution->id);
        $this->assertTrue((clone $journals)->where('status', 'need_repeat')->exists());
        $this->assertTrue((clone $journals)->where('attendance', 'alpa')->exists());
        $this->assertTrue((clone $journals)->where('type', 'murajaah')->exists());
    }

    public function test_keeps_the_profile_of_an_existing_demo_institution(): void
    {
        Institution::create([
            'name' => 'Nama Demo Kustom',
            'token' => Institution::DEMO_TOKEN,
            'city' => 'Kota Kustom',
            'director_name' => 'Ust. Kustom',
            'director_title' => 'Mudir',
        ]);

        $this->artisan('app:seed-demo-institution')->assertSuccessful();

        $institution = Institution::where('token', Institution::DEMO_TOKEN)->sole();
        $this->assertSame('Nama Demo Kustom', $institution->name);
        $this->assertSame('Kota Kustom', $institution->city);
    }

    public function test_running_twice_does_not_duplicate_data(): void
    {
        $this->artisan('app:seed-demo-institution')->assertSuccessful();
        $journalCount = TahfidzJournal::withoutGlobalScopes()->count();

        $this->artisan('app:seed-demo-institution')->assertSuccessful();

        $this->assertSame($journalCount, TahfidzJournal::withoutGlobalScopes()->count());
        $this->assertSame(1, Institution::where('token', Institution::DEMO_TOKEN)->count());
        $this->assertSame(24, Student::withoutGlobalScopes()->count());
        $this->assertSame(72, MonthlyReport::withoutGlobalScopes()->count());
    }

    public function test_leaves_another_institutions_user_with_a_colliding_username_untouched(): void
    {
        $other = Institution::create([
            'name' => 'Lembaga Lain',
            'token' => 'LAIN',
            'city' => 'Kota Lain',
            'director_name' => 'Ust. Lain',
            'director_title' => 'Mudir',
        ]);
        $otherUser = User::factory()->create([
            'institution_id' => $other->id,
            'username' => 'admin_demo',
            'password' => Hash::make('sandi-lembaga-lain'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->artisan('app:seed-demo-institution')->assertSuccessful();

        $otherUser = User::withoutGlobalScopes()->findOrFail($otherUser->id);
        $this->assertSame($other->id, $otherUser->institution_id);
        $this->assertTrue(Hash::check('sandi-lembaga-lain', $otherUser->password));
    }

    public function test_every_demo_account_can_open_its_home_page(): void
    {
        $this->artisan('app:seed-demo-institution')->assertSuccessful();
        $institution = Institution::where('token', Institution::DEMO_TOKEN)->firstOrFail();

        User::withoutGlobalScopes()->where('institution_id', $institution->id)->get()
            ->each(fn (User $user) => $this->actingAs($user)->get(route($user->homeRouteName()))->assertOk());
    }

    public function test_struggling_demo_students_appear_in_the_early_warning_board(): void
    {
        $this->artisan('app:seed-demo-institution')->assertSuccessful();
        $admin = User::withoutGlobalScopes()->where('username', 'admin_demo')->firstOrFail();

        $this->actingAs($admin)->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Umar Faruq Hakim')
            ->assertSee('Yusuf Habibullah');
    }

    public function test_refresh_stays_within_a_small_number_of_queries(): void
    {
        // Production runs from GitHub Actions against a remote database, where each query costs a
        // round trip; per-row writes once made a nightly refresh take ten minutes.
        $this->artisan('app:seed-demo-institution')->assertSuccessful();

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->artisan('app:seed-demo-institution')->assertSuccessful();

        $this->assertLessThan(150, $queryCount);
    }
}
