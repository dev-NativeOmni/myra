<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);
        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
    }

    public function test_batch_export_form_renders(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('reports.batch-export'));
        $response->assertStatus(200);
        $response->assertSee('Ekspor Massal PDF Rapor');
    }

    public function test_batch_export_merged_pdf(): void
    {
        $classroom = Classroom::first();
        $report = MonthlyReport::first();

        $response = $this->actingAs($this->superAdmin)->post(route('reports.batch-export.download'), [
            'period_title' => $report->period_title,
            'classroom_id' => $classroom->id,
            'format' => 'merged_pdf',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_batch_export_zip_archive(): void
    {
        $classroom = Classroom::first();
        $report = MonthlyReport::first();

        $response = $this->actingAs($this->superAdmin)->post(route('reports.batch-export.download'), [
            'period_title' => $report->period_title,
            'classroom_id' => $classroom->id,
            'format' => 'zip',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }
}
