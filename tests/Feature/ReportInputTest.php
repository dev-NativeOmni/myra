<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportInputTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guru;

    protected User $kesantrian;

    protected User $waliKelas;

    protected User $tu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);
        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->kesantrian = User::where('role', User::ROLE_KESANTRIAN)->first();
        $this->waliKelas = User::where('role', User::ROLE_WALI_KELAS)->first();
        $this->tu = User::where('role', User::ROLE_TU)->first();

        // These tests exercise module-level behavior, not classroom scoping,
        // so assign every classroom to the classroom-scoped staff roles.
        $classroomIds = Classroom::pluck('id');
        $this->guru->classrooms()->sync($classroomIds);
        $this->kesantrian->classrooms()->sync($classroomIds);
        $this->waliKelas->classrooms()->sync($classroomIds);
    }

    public function test_reports_index_and_show(): void
    {
        $report = MonthlyReport::first();

        $indexResponse = $this->actingAs($this->admin)->get(route('reports.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Rekapitulasi Laporan Bulanan Santri');

        $showResponse = $this->actingAs($this->admin)->get(route('reports.show', $report->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($report->student->name);
    }

    public function test_create_single_and_classroom_reports(): void
    {
        $classroom = Classroom::first();
        $student = Student::first();

        // Create single
        $singleResponse = $this->actingAs($this->admin)->post(route('reports.store'), [
            'mode' => 'single',
            'student_id' => $student->id,
            'period_title' => 'SEPTEMBER-OKTOBER 2026',
            'report_date' => '2026-10-01',
            'cutoff_date' => '2026-09-30',
            'status' => 'draft',
        ]);
        $singleResponse->assertRedirect();
        $this->assertDatabaseHas('monthly_reports', [
            'student_id' => $student->id,
            'period_title' => 'SEPTEMBER-OKTOBER 2026',
        ]);

        // Create classroom batch
        $batchResponse = $this->actingAs($this->admin)->post(route('reports.store'), [
            'mode' => 'classroom',
            'classroom_id' => $classroom->id,
            'period_title' => 'NOVEMBER-DESEMBER 2026',
            'report_date' => '2026-12-01',
            'cutoff_date' => '2026-11-30',
            'status' => 'draft',
        ]);
        $batchResponse->assertRedirect(route('reports.index', ['period_title' => 'NOVEMBER-DESEMBER 2026']));
        $this->assertDatabaseHas('monthly_reports', [
            'period_title' => 'NOVEMBER-DESEMBER 2026',
        ]);
    }

    public function test_update_all_in_one_report_record(): void
    {
        $report = MonthlyReport::first();

        $response = $this->actingAs($this->admin)->put(route('reports.update-record', $report->id), [
            'tahfidz_setoran' => 'Ziyadah 2 Juz',
            'tahfidz_akumulasi' => '35 Juz',
            'tahfidz_rincian_juz' => '1-30, 1-5',
            'tahfidz_notes' => 'Catatan Tahfidz Baru',
            'adab_ibadah' => 'A',
            'adab_akhlak' => 'A',
            'adab_kerapian' => 'A',
            'adab_kedisiplinan' => 'A',
            'body_height_cm' => 135,
            'body_weight_kg' => 30,
            'is_baligh' => 'Sudah',
            'kesantrian_notes' => 'Catatan Kesantrian Baru',
            'academic_notes' => 'Catatan Akademik Baru',
            'last_spp' => 'September 2026',
            'last_laundry' => 'September 2026',
            'registration_status' => 'Lunas',
        ]);

        $response->assertRedirect(route('reports.show', $report->id));
        $this->assertDatabaseHas('report_records', [
            'monthly_report_id' => $report->id,
            'tahfidz_setoran' => 'Ziyadah 2 Juz',
            'body_height_cm' => 135,
            'is_baligh' => 'Sudah',
            'academic_notes' => 'Catatan Akademik Baru',
        ]);
    }

    public function test_modular_portals_rendering_and_updating(): void
    {
        $report = MonthlyReport::first();

        // Tahfidz portal with guru tahfidz
        $this->actingAs($this->guru)->get(route('modules.tahfidz'))->assertStatus(200);
        $tahfidzUpdate = $this->actingAs($this->guru)->put(route('modules.tahfidz.update', $report->id), [
            'tahfidz_setoran' => 'Muraja\'ah 5 Juz',
            'tahfidz_notes' => 'Sangat lancar',
        ]);
        $tahfidzUpdate->assertRedirect();
        $this->assertDatabaseHas('report_records', ['monthly_report_id' => $report->id, 'tahfidz_setoran' => 'Muraja\'ah 5 Juz']);

        // Kesantrian portal with kesantrian
        $this->actingAs($this->kesantrian)->get(route('modules.kesantrian'))->assertStatus(200);
        $kesantrianUpdate = $this->actingAs($this->kesantrian)->put(route('modules.kesantrian.update', $report->id), [
            'adab_ibadah' => 'A (Sangat Baik)',
            'kesantrian_notes' => 'Disiplin sholat berjamaah',
        ]);
        $kesantrianUpdate->assertRedirect();
        $this->assertDatabaseHas('report_records', ['monthly_report_id' => $report->id, 'adab_ibadah' => 'A (Sangat Baik)']);

        // Akademik portal with wali kelas
        $this->actingAs($this->waliKelas)->get(route('modules.akademik'))->assertStatus(200);
        $akademikUpdate = $this->actingAs($this->waliKelas)->put(route('modules.akademik.update', $report->id), [
            'academic_notes' => 'Nilai matematika sangat memuaskan',
        ]);
        $akademikUpdate->assertRedirect();
        $this->assertDatabaseHas('report_records', ['monthly_report_id' => $report->id, 'academic_notes' => 'Nilai matematika sangat memuaskan']);

        // Administrasi portal with TU
        $this->actingAs($this->tu)->get(route('modules.administrasi'))->assertStatus(200);
        $adminUpdate = $this->actingAs($this->tu)->put(route('modules.administrasi.update', $report->id), [
            'last_spp' => 'Oktober 2026',
            'registration_status' => 'Lunas',
        ]);
        $adminUpdate->assertRedirect();
        $this->assertDatabaseHas('report_records', ['monthly_report_id' => $report->id, 'last_spp' => 'Oktober 2026']);
    }
}
