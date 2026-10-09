<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\MonthlyReport;
use App\Models\Student;
use App\Models\TahfidzJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeedMitqDummyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'MITQ Baitul Hikmah',
            'token' => 'MITQ',
            'city' => 'Kota Asli',
            'director_name' => 'Ust. Mudir Asli',
            'director_title' => 'Mudir',
        ]);
    }

    public function test_fills_existing_institution_with_accounts_students_journals_and_reports(): void
    {
        $this->artisan('app:seed-mitq-dummy', ['--password' => 'rahasia-demo'])->assertSuccessful();

        $users = User::withoutGlobalScopes()->where('institution_id', $this->institution->id)->get();
        $this->assertSame(
            ['admin', 'guru', 'guru', 'kesantrian', 'tu', 'wali_kelas', 'wali_murid', 'wali_murid'],
            $users->pluck('role')->sort()->values()->all(),
        );
        $this->assertTrue(Hash::check('rahasia-demo', $users->firstWhere('username', 'guru_mitq')->password));
        $this->assertSame(['MITQ-001', 'MITQ-003'], $users->firstWhere('username', 'walimurid_mitq')->children()->orderBy('nis')->pluck('nis')->all());

        $this->assertSame(15, Student::withoutGlobalScopes()->where('institution_id', $this->institution->id)->where('is_active', true)->count());
        $this->assertSame(45, MonthlyReport::withoutGlobalScopes()->where('institution_id', $this->institution->id)->count());

        $journals = TahfidzJournal::withoutGlobalScopes()->where('institution_id', $this->institution->id);
        $this->assertTrue((clone $journals)->where('status', 'need_repeat')->exists());
        $this->assertTrue((clone $journals)->where('attendance', 'alpa')->exists());
        $this->assertTrue((clone $journals)->where('type', 'murajaah')->exists());
    }

    public function test_does_not_overwrite_the_institution_profile(): void
    {
        $this->artisan('app:seed-mitq-dummy')->assertSuccessful();

        $this->institution->refresh();
        $this->assertSame('Kota Asli', $this->institution->city);
        $this->assertSame('Ust. Mudir Asli', $this->institution->director_name);
    }

    public function test_running_twice_does_not_duplicate_data(): void
    {
        $this->artisan('app:seed-mitq-dummy')->assertSuccessful();
        $journalCount = TahfidzJournal::withoutGlobalScopes()->count();

        $this->artisan('app:seed-mitq-dummy')->assertSuccessful();

        $this->assertSame($journalCount, TahfidzJournal::withoutGlobalScopes()->count());
        $this->assertSame(15, Student::withoutGlobalScopes()->count());
        $this->assertSame(45, MonthlyReport::withoutGlobalScopes()->count());
    }

    public function test_leaves_another_institutions_user_with_a_colliding_username_untouched(): void
    {
        $other = Institution::create([
            'name' => 'Lembaga Lain',
            'token' => 'MIT-Q',
            'city' => 'Kota Lain',
            'director_name' => 'Ust. Lain',
            'director_title' => 'Mudir',
        ]);
        $otherUser = User::factory()->create([
            'institution_id' => $other->id,
            'username' => 'admin_mitq',
            'password' => Hash::make('sandi-lembaga-lain'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->artisan('app:seed-mitq-dummy')->assertSuccessful();

        $otherUser = User::withoutGlobalScopes()->findOrFail($otherUser->id);
        $this->assertSame($other->id, $otherUser->institution_id);
        $this->assertTrue(Hash::check('sandi-lembaga-lain', $otherUser->password));
    }

    public function test_fails_when_the_institution_does_not_exist(): void
    {
        $this->artisan('app:seed-mitq-dummy', ['--token' => 'TIDAK-ADA'])->assertFailed();

        $this->assertSame(0, Student::withoutGlobalScopes()->count());
    }

    public function test_every_dummy_account_can_open_its_home_page(): void
    {
        $this->artisan('app:seed-mitq-dummy')->assertSuccessful();

        User::withoutGlobalScopes()->where('institution_id', $this->institution->id)->get()
            ->each(fn (User $user) => $this->actingAs($user)->get(route($user->homeRouteName()))->assertOk());
    }

    public function test_struggling_dummy_students_appear_in_the_early_warning_board(): void
    {
        $this->artisan('app:seed-mitq-dummy')->assertSuccessful();
        $admin = User::withoutGlobalScopes()->where('username', 'admin_mitq')->firstOrFail();

        $this->actingAs($admin)->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Umar Faruq Hakim')
            ->assertSee('Yusuf Habibullah');
    }
}
