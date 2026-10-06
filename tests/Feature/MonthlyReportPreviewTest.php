<?php

namespace Tests\Feature;

use App\Models\MonthlyReport;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportPreviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test previewing monthly report PDF.
     */
    public function test_can_preview_monthly_report_pdf(): void
    {
        $this->seed(SampleDataSeeder::class);
        $user = User::where('role', User::ROLE_ADMIN)->first();

        $report = MonthlyReport::first();
        $this->assertNotNull($report, 'Monthly report seed should exist');

        $response = $this->actingAs($user)->get(route('reports.preview', ['id' => $report->id]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
