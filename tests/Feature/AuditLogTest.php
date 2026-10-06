<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guru;

    protected MonthlyReport $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->guru = User::where('role', User::ROLE_GURU)->first();

        $student = Student::where('classroom_id', Classroom::first()->id)->first();
        $this->report = MonthlyReport::create([
            'student_id' => $student->id,
            'period_title' => 'AUDIT TEST 2026',
            'report_date' => '2026-09-30',
            'cutoff_date' => '2026-09-30',
            'status' => 'draft',
        ]);
        ReportRecord::create(['monthly_report_id' => $this->report->id]);
    }

    public function test_changing_a_field_records_who_changed_what(): void
    {
        $this->actingAs($this->guru)->post(route('modules.batch-store'), [
            'records' => [$this->report->id => ['tahfidz_notes' => 'Catatan pertama']],
        ]);
        $this->actingAs($this->guru)->post(route('modules.batch-store'), [
            'records' => [$this->report->id => ['tahfidz_notes' => 'Catatan revisi']],
        ]);

        $log = AuditLog::where('field', 'tahfidz_notes')->latest('id')->first();

        $this->assertSame($this->guru->id, $log->user_id);
        $this->assertSame($this->report->id, $log->monthly_report_id);
        $this->assertSame($this->report->student->name, $log->student_name);
        $this->assertSame('AUDIT TEST 2026', $log->period_title);
        $this->assertSame('Catatan pertama', $log->old_value);
        $this->assertSame('Catatan revisi', $log->new_value);
    }

    public function test_saving_unchanged_values_records_nothing(): void
    {
        $payload = ['records' => [$this->report->id => ['tahfidz_notes' => 'Sama']]];

        $this->actingAs($this->guru)->post(route('modules.batch-store'), $payload);
        $countAfterFirstSave = AuditLog::count();

        $this->actingAs($this->guru)->post(route('modules.batch-store'), $payload);

        $this->assertSame($countAfterFirstSave, AuditLog::count());
    }

    public function test_custom_field_changes_are_recorded_per_key(): void
    {
        $record = $this->report->record;
        $record->update(['custom_fields' => ['custom_hafalan' => 'Juz 1']]);
        $record->update(['custom_fields' => ['custom_hafalan' => 'Juz 2']]);

        $log = AuditLog::where('field', 'custom_hafalan')->latest('id')->first();

        $this->assertSame('Juz 1', $log->old_value);
        $this->assertSame('Juz 2', $log->new_value);
    }

    public function test_publishing_records_the_status_change_and_who_did_it(): void
    {
        $this->actingAs($this->admin);
        $this->report->update(['status' => 'published']);

        $log = AuditLog::where('field', 'status')->first();

        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('draft', $log->old_value);
        $this->assertSame('published', $log->new_value);
    }

    public function test_only_admins_can_view_the_audit_log_page(): void
    {
        AuditLog::factory()->create(['student_name' => 'Santri Terlihat']);

        $this->actingAs($this->admin)->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Santri Terlihat');

        $this->actingAs($this->guru)->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_entries_older_than_the_retention_period_are_pruned(): void
    {
        $old = AuditLog::factory()->create(['created_at' => now()->subDays(AuditLog::RETENTION_DAYS + 1)]);
        $recent = AuditLog::factory()->create();

        $this->artisan('model:prune', ['--model' => AuditLog::class])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}
