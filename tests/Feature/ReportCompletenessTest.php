<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $waliMurid;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        $this->waliMurid = User::where('role', User::ROLE_WALI_MURID)->first();
        $this->classroom = Classroom::first();
    }

    public function test_admin_can_view_completeness_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('reports.completeness', [
            'classroom_id' => $this->classroom->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Status Kelengkapan Laporan');
    }

    public function test_wali_murid_cannot_view_completeness_dashboard(): void
    {
        $response = $this->actingAs($this->waliMurid)->get(route('reports.completeness'));

        $response->assertForbidden();
    }

    public function test_dashboard_correctly_counts_complete_and_incomplete_modules(): void
    {
        $students = Student::where('classroom_id', $this->classroom->id)->take(2)->get();
        $periodTitle = 'KELENGKAPAN TEST 2026';

        // Student A: every module fully filled in.
        $reportA = MonthlyReport::create([
            'student_id' => $students[0]->id,
            'period_title' => $periodTitle,
            'report_date' => '2026-09-30',
            'cutoff_date' => '2026-09-30',
            'status' => 'draft',
        ]);
        ReportRecord::create([
            'monthly_report_id' => $reportA->id,
            'tahfidz_setoran' => 'Ziyadah 2 Juz',
            'tahfidz_akumulasi' => '30 Juz',
            'tahfidz_rincian_juz' => '1-30',
            'tahfidz_notes' => 'Lancar',
            'adab_ibadah' => 'A (Sangat Baik)',
            'adab_akhlak' => 'A (Sangat Baik)',
            'adab_kerapian' => 'A (Sangat Baik)',
            'adab_kedisiplinan' => 'A (Sangat Baik)',
            'body_height_cm' => 140,
            'body_weight_kg' => 35,
            'is_baligh' => 'Belum',
            'kesantrian_notes' => 'Baik',
            'academic_notes' => 'Sangat baik',
            'last_spp' => 'September 2026',
            'last_laundry' => 'September 2026',
            'registration_status' => 'Lunas',
        ]);

        // Student B: only tahfidz filled, everything else left empty.
        $reportB = MonthlyReport::create([
            'student_id' => $students[1]->id,
            'period_title' => $periodTitle,
            'report_date' => '2026-09-30',
            'cutoff_date' => '2026-09-30',
            'status' => 'draft',
        ]);
        ReportRecord::create([
            'monthly_report_id' => $reportB->id,
            'tahfidz_setoran' => 'Tahsin',
            'tahfidz_akumulasi' => '10 Juz',
            'tahfidz_rincian_juz' => '1-10',
            'tahfidz_notes' => 'Cukup',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('reports.completeness', [
            'classroom_id' => $this->classroom->id,
            'period_title' => $periodTitle,
        ]));

        $response->assertStatus(200);
        $response->assertSee($students[0]->name);
        $response->assertSee($students[1]->name);
        $response->assertSeeText('2/2'); // Tahfidz: both students complete
        $response->assertSeeText('1/2'); // Other modules: only student A complete
        $response->assertSee('Siap Cetak');
        $response->assertSee('Belum Lengkap');
    }

    public function test_publish_classroom_only_publishes_complete_draft_reports(): void
    {
        $students = Student::where('classroom_id', $this->classroom->id)->take(2)->get();
        $otherStudent = Student::where('classroom_id', '!=', $this->classroom->id)->first();
        $period = 'TERBIT TEST 2026';

        $complete = $this->makeReport($students[0], $period, fullyComplete: true);
        $incomplete = $this->makeReport($students[1], $period, fullyComplete: false);
        $otherClassroom = $this->makeReport($otherStudent, $period, fullyComplete: true);

        $response = $this->actingAs($this->superAdmin)->post(route('reports.publish-classroom'), [
            'classroom_id' => $this->classroom->id,
            'period_title' => $period,
        ]);

        $response->assertRedirect(route('reports.completeness', [
            'classroom_id' => $this->classroom->id,
            'period_title' => $period,
        ]));
        $response->assertSessionHas('success');

        $this->assertSame('published', $complete->fresh()->status);
        $this->assertSame('draft', $incomplete->fresh()->status);
        $this->assertSame('draft', $otherClassroom->fresh()->status);
    }

    public function test_only_admins_can_publish_reports(): void
    {
        $guru = User::where('role', User::ROLE_GURU)->first();

        $this->actingAs($guru)->post(route('reports.publish-classroom'), [
            'classroom_id' => $this->classroom->id,
            'period_title' => 'APA SAJA',
        ])->assertForbidden();
    }

    private function makeReport(Student $student, string $period, bool $fullyComplete): MonthlyReport
    {
        $report = MonthlyReport::create([
            'student_id' => $student->id,
            'period_title' => $period,
            'report_date' => '2026-09-30',
            'cutoff_date' => '2026-09-30',
            'status' => 'draft',
        ]);

        $record = ['monthly_report_id' => $report->id, 'tahfidz_setoran' => 'Ziyadah'];

        if ($fullyComplete) {
            $record += [
                'tahfidz_akumulasi' => '30 Juz', 'tahfidz_rincian_juz' => '1-30', 'tahfidz_notes' => 'Lancar',
                'adab_ibadah' => 'A (Sangat Baik)', 'adab_akhlak' => 'A (Sangat Baik)',
                'adab_kerapian' => 'A (Sangat Baik)', 'adab_kedisiplinan' => 'A (Sangat Baik)',
                'body_height_cm' => 140, 'body_weight_kg' => 35, 'is_baligh' => 'Belum',
                'kesantrian_notes' => 'Baik', 'academic_notes' => 'Baik',
                'last_spp' => 'September 2026', 'last_laundry' => 'September 2026', 'registration_status' => 'Lunas',
            ];
        }

        ReportRecord::create($record);

        return $report;
    }
}
