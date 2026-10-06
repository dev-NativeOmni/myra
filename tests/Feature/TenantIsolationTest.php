<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\MonthlyReport;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Institution A comes from the sample data; institution B is a second, separate
 * institution. Its admin must never see or change A's data and vice versa.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminA;

    private User $adminB;

    private Institution $institutionB;

    private Student $studentA;

    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->adminA = User::withoutGlobalScopes()->where('role', User::ROLE_ADMIN)->firstOrFail();
        $this->studentA = Student::withoutGlobalScopes()->where('institution_id', $this->adminA->institution_id)->orderBy('id')->firstOrFail();

        $this->institutionB = Institution::create([
            'name' => 'Pesantren Kedua',
            'token' => 'KEDUA-01',
            'city' => 'Kota Kedua',
            'director_name' => 'Ust. Kedua',
            'director_title' => 'Mudir',
        ]);
        $this->adminB = User::factory()->create(['role' => User::ROLE_ADMIN, 'institution_id' => $this->institutionB->id]);
        $classroomB = Classroom::create(['name' => 'B1', 'institution_id' => $this->institutionB->id]);
        $this->studentB = Student::create([
            'institution_id' => $this->institutionB->id,
            'classroom_id' => $classroomB->id,
            'nis' => $this->studentA->nis,
            'name' => 'Santri Lembaga Kedua',
            'gender' => 'L',
            'is_active' => true,
        ]);
    }

    public function test_admin_only_sees_students_of_own_institution(): void
    {
        $response = $this->actingAs($this->adminB)->get(route('students.index'));

        $response->assertSee($this->studentB->name);
        $response->assertDontSee($this->studentA->name);
    }

    public function test_admin_cannot_open_student_or_report_of_another_institution(): void
    {
        $reportA = MonthlyReport::withoutGlobalScopes()->where('student_id', $this->studentA->id)->firstOrFail();

        $this->actingAs($this->adminB)->get(route('students.show', $this->studentA->id))->assertNotFound();
        $this->actingAs($this->adminB)->get(route('reports.preview', $reportA->id))->assertNotFound();
        $this->actingAs($this->adminB)->get(route('reports.show', $reportA->id))->assertNotFound();
    }

    public function test_same_nis_is_allowed_in_another_institution_but_not_twice_in_one(): void
    {
        $classroomB = Classroom::withoutGlobalScopes()->where('institution_id', $this->institutionB->id)->firstOrFail();

        $response = $this->actingAs($this->adminB)->post(route('students.store'), [
            'nis' => $this->studentA->nis,
            'name' => 'Duplikat Dalam Lembaga',
            'classroom_id' => $classroomB->id,
            'gender' => 'P',
        ]);

        $response->assertSessionHasErrors('nis');
        $this->assertSame(1, Student::withoutGlobalScopes()->where('institution_id', $this->institutionB->id)->count());
        $this->assertSame($this->studentA->nis, $this->studentB->nis);
    }

    public function test_audit_log_only_lists_entries_of_own_institution(): void
    {
        AuditLog::create(['institution_id' => $this->adminA->institution_id, 'student_name' => 'Log Milik Lembaga A', 'field' => 'status', 'new_value' => 'published']);
        AuditLog::create(['institution_id' => $this->institutionB->id, 'student_name' => 'Log Milik Lembaga B', 'field' => 'status', 'new_value' => 'published']);

        $response = $this->actingAs($this->adminB)->get(route('audit-logs.index'));

        $response->assertSee('Log Milik Lembaga B');
        $response->assertDontSee('Log Milik Lembaga A');
    }

    public function test_academic_calendar_settings_are_kept_per_institution(): void
    {
        $this->actingAs($this->adminA);
        TenantContext::setTenant($this->adminA->institution);
        Setting::set('national_holidays_2026', json_encode(['2026-03-03']));

        $this->actingAs($this->adminB);
        TenantContext::setTenant($this->institutionB);

        $this->assertNull(Setting::get('national_holidays_2026'));
        $this->assertNotContains('2026-03-03', Setting::getNationalHolidays(2026));
    }

    public function test_report_pdf_uses_the_identity_of_the_reports_own_institution(): void
    {
        $reportB = MonthlyReport::create(['institution_id' => $this->institutionB->id, 'student_id' => $this->studentB->id, 'period_title' => 'TES', 'report_date' => '2026-09-01', 'cutoff_date' => '2026-08-30', 'status' => 'draft']);
        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('stream')->andReturn(response('pdf'));
        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(fn (string $view, array $data) => $data['institution']->is($this->institutionB))
            ->andReturn($pdf);

        $response = $this->actingAs($this->adminB)->get(route('reports.preview', $reportB->id));

        $response->assertOk();
    }

    public function test_institution_admin_manages_own_token_in_profile(): void
    {
        $response = $this->actingAs($this->adminB)->put(route('institution.update'), [
            'name' => 'Pesantren Kedua',
            'token' => 'kedua-baru',
            'city' => 'Kota Kedua',
            'director_name' => 'Ust. Kedua',
            'director_title' => 'Mudir',
            'accent_color' => '#059669',
            'term_student' => 'Santri',
            'term_teacher' => 'Guru',
            'term_class' => 'Kelas',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('KEDUA-BARU', $this->institutionB->fresh()->token);
        $this->assertNotSame('KEDUA-BARU', $this->adminA->institution->fresh()->token);
    }

    public function test_users_of_a_deactivated_institution_cannot_log_in_or_keep_working(): void
    {
        $this->institutionB->update(['is_active' => false]);

        $loginResponse = $this->post(route('login.post'), ['username' => $this->adminB->username, 'password' => 'password']);
        $sessionResponse = $this->actingAs($this->adminB)->get(route('students.index'));

        $loginResponse->assertSessionHasErrors('username');
        $this->assertGuest();
        $sessionResponse->assertRedirect(route('login'));
    }
}
