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

class StudentProgressTrendTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guru;

    protected User $waliMurid;

    protected Student $student;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->waliMurid = User::where('role', User::ROLE_WALI_MURID)->first();
        $this->student = Student::first();
        $this->classroom = Classroom::first();
    }

    public function test_student_progress_service_extracts_correct_trend_data(): void
    {
        // Create 2 monthly reports with different metrics
        $rep1 = MonthlyReport::create([
            'student_id' => $this->student->id,
            'period_title' => 'JULI 2026',
            'report_date' => '2026-07-31',
            'cutoff_date' => '2026-07-31',
            'status' => 'published',
        ]);
        ReportRecord::create([
            'monthly_report_id' => $rep1->id,
            'tahfidz_akumulasi' => '5 Juz',
            'adab_ibadah' => 'A (Sangat Baik)',
            'adab_akhlak' => 'B (Baik)',
            'adab_kerapian' => 'B (Baik)',
            'adab_kedisiplinan' => 'A (Sangat Baik)',
            'body_height_cm' => 140,
            'body_weight_kg' => 35,
        ]);

        $rep2 = MonthlyReport::create([
            'student_id' => $this->student->id,
            'period_title' => 'AGUSTUS 2026',
            'report_date' => '2026-08-31',
            'cutoff_date' => '2026-08-31',
            'status' => 'published',
        ]);
        ReportRecord::create([
            'monthly_report_id' => $rep2->id,
            'tahfidz_akumulasi' => '7 Juz',
            'adab_ibadah' => 'A (Sangat Baik)',
            'adab_akhlak' => 'A (Sangat Baik)',
            'adab_kerapian' => 'A (Sangat Baik)',
            'adab_kedisiplinan' => 'A (Sangat Baik)',
            'body_height_cm' => 142,
            'body_weight_kg' => 36,
        ]);

        $trends = $this->student->getProgressTrends();

        $this->assertTrue($trends['has_data']);
        $this->assertGreaterThanOrEqual(2, $trends['total_periods']);
        $this->assertContains('JULI 2026', $trends['labels']);
        $this->assertContains('AGUSTUS 2026', $trends['labels']);

        // Check Tahfidz Juz
        $this->assertContains(5.0, $trends['tahfidz']['juz']);
        $this->assertContains(7.0, $trends['tahfidz']['juz']);

        // Check Adab Average: Rep 1 (4+3+3+4)/4 = 3.5; Rep 2 (4+4+4+4)/4 = 4.0
        $this->assertContains(3.5, $trends['adab']['average']);
        $this->assertContains(4.0, $trends['adab']['average']);

        // Check Physical Growth
        $this->assertContains(140.0, $trends['physical']['height']);
        $this->assertContains(142.0, $trends['physical']['height']);
        $this->assertContains(35.0, $trends['physical']['weight']);
        $this->assertContains(36.0, $trends['physical']['weight']);
    }

    public function test_admin_and_staff_can_access_student_show_profile_and_trends(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('students.show', $this->student->id));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Profil &amp; Tren Progres Santri', false);
        $responseAdmin->assertSee($this->student->name);
        $responseAdmin->assertSee('Grafik Tren Progres Bulanan Santri');
        $responseAdmin->assertSee('studentTahfidzChart');
        $responseAdmin->assertSee('studentAdabChart');
        $responseAdmin->assertSee('studentPhysicalChart');

        $responseGuru = $this->actingAs($this->guru)->get(route('students.show', $this->student->id));
        $responseGuru->assertStatus(200);
    }

    public function test_parent_can_view_monthly_trend_charts_in_parent_dashboard(): void
    {
        // Link parent user to student
        $this->waliMurid->children()->sync([$this->student->id]);

        $response = $this->actingAs($this->waliMurid)->get(route('parent.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Portal Wali Santri');
        $response->assertSee($this->student->name);
        $response->assertSee('Grafik Tren Perkembangan Ananda');
        $response->assertSee('parentTahfidzChart');
        $response->assertSee('parentAdabChart');
        $response->assertSee('parentPhysicalChart');
    }
}
