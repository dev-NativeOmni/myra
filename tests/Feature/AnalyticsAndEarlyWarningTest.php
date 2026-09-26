<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\TahfidzJournal;
use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAndEarlyWarningTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
    protected User $parent;
    protected Classroom $class1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->parent = User::where('role', User::ROLE_WALI_MURID)->first();
        $this->class1 = Classroom::where('name', '1')->first();
    }

    public function test_admin_and_teacher_can_view_monitoring_and_analytics_page(): void
    {
        // 1. Admin access
        $adminResponse = $this->actingAs($this->admin)->get(route('analytics.index'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Monitoring &amp; Analitik Lembaga', false);
        $adminResponse->assertSee('Progress Capaian Target Kurikulum per Kelas');
        $adminResponse->assertSee('Early Warning System');

        // 2. Teacher access
        $guruResponse = $this->actingAs($this->guru)->get(route('analytics.index'));
        $guruResponse->assertStatus(200);

        // 3. Parent should be forbidden / redirected
        $parentResponse = $this->actingAs($this->parent)->get(route('analytics.index'));
        $parentResponse->assertStatus(403);
    }

    public function test_admin_can_update_classroom_curriculum_target(): void
    {
        $response = $this->actingAs($this->admin)->post(route('analytics.update-target'), [
            'classroom_id' => $this->class1->id,
            'target_juz' => 29,
            'target_description' => 'Target Khusus Semester 1: Tabarak (Juz 29)',
        ]);

        $response->assertRedirect(route('analytics.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(29, $this->class1->fresh()->target_juz);
        $this->assertEquals('Target Khusus Semester 1: Tabarak (Juz 29)', $this->class1->fresh()->target_description);
    }

    public function test_analytics_service_accurately_detects_early_warning_students(): void
    {
        $student = Student::where('classroom_id', $this->class1->id)->first();

        // 1. Create a repeated failure journal for this student
        TahfidzJournal::create([
            'student_id' => $student->id,
            'teacher_id' => $this->guru->id,
            'date' => Carbon::now()->subDays(2)->toDateString(),
            'attendance' => 'hadir',
            'type' => 'ziyadah',
            'juz' => 30,
            'surah' => 'An-Naba',
            'ayah_start' => 1,
            'ayah_end' => 10,
            'page_count' => 1,
            'grade' => 'Maqbul (D)',
            'status' => 'need_repeat',
            'notes' => 'Perlu diulang makhraj huruf',
        ]);

        TahfidzJournal::create([
            'student_id' => $student->id,
            'teacher_id' => $this->guru->id,
            'date' => Carbon::now()->subDays(4)->toDateString(),
            'attendance' => 'hadir',
            'type' => 'ziyadah',
            'juz' => 30,
            'surah' => 'An-Naba',
            'ayah_start' => 1,
            'ayah_end' => 10,
            'page_count' => 1,
            'grade' => 'Maqbul (D)',
            'status' => 'need_repeat',
            'notes' => 'Masih terbata-bata',
        ]);

        $service = app(AnalyticsService::class);
        $warningStudents = $service->getEarlyWarningStudents(null, $this->class1->id);

        $this->assertNotEmpty($warningStudents);
        $found = collect($warningStudents)->firstWhere('student.id', $student->id);
        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual(15, $found['risk_score']);
        $this->assertStringContainsString('pengulangan hafalan', implode(' ', $found['reasons']));
    }

    public function test_dashboard_displays_curriculum_progress_and_early_warning_widgets(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Capaian Target Kurikulum Kelas');
        $response->assertSee('Early Warning System');
        $response->assertSee('Kelas 1');
    }
}

