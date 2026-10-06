<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_button_links_to_latest_report(): void
    {
        $this->seed(SampleDataSeeder::class);
        $admin = User::where('role', User::ROLE_ADMIN)->first();
        $latestReportId = MonthlyReport::max('id');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertSee('href="'.route('reports.preview', $latestReportId).'"', false);
    }

    public function test_preview_button_is_hidden_when_no_reports_exist(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Pratinjau PDF Sampel');
    }

    public function test_active_menu_link_is_marked_as_current_page(): void
    {
        $this->seed(SampleDataSeeder::class);
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $response = $this->actingAs($admin)->get(route('students.index'));

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('students.index'), '/').'"\s+aria-current="page"/',
            $response->getContent()
        );
        $this->assertDoesNotMatchRegularExpression(
            '/href="'.preg_quote(route('dashboard'), '/').'"\s+aria-current="page"/',
            $response->getContent()
        );
    }

    public function test_guru_sample_preview_links_to_report_of_own_classroom(): void
    {
        $this->seed(SampleDataSeeder::class);
        $ownClassroom = Classroom::whereHas('students.monthlyReports')->first();
        $guru = User::where('role', User::ROLE_GURU)->first();
        $guru->classrooms()->sync([$ownClassroom->id]);
        $latestOwnReportId = MonthlyReport::whereHas('student', fn ($query) => $query->where('classroom_id', $ownClassroom->id))->max('id');

        $response = $this->actingAs($guru)->get(route('modules.tahfidz'));

        $response->assertSee('href="'.route('reports.preview', $latestOwnReportId).'"', false);
    }

    public function test_dashboard_menu_is_hidden_from_classroom_staff(): void
    {
        $this->seed(SampleDataSeeder::class);
        $guru = User::where('role', User::ROLE_GURU)->first();

        $response = $this->actingAs($guru)->get(route('modules.tahfidz'));

        $response->assertDontSee('href="'.route('dashboard').'"', false);
    }
}
