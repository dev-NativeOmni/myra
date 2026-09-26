<?php

namespace Tests\Feature;

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
        $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        $latestReportId = MonthlyReport::max('id');

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertSee('href="'.route('reports.preview', $latestReportId).'"', false);
    }

    public function test_preview_button_is_hidden_when_no_reports_exist(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Pratinjau PDF Sampel');
    }

    public function test_active_menu_link_is_marked_as_current_page(): void
    {
        $this->seed(SampleDataSeeder::class);
        $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();

        $response = $this->actingAs($superAdmin)->get(route('students.index'));

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('students.index'), '/').'"\s+aria-current="page"/',
            $response->getContent()
        );
        $this->assertDoesNotMatchRegularExpression(
            '/href="'.preg_quote(route('dashboard'), '/').'"\s+aria-current="page"/',
            $response->getContent()
        );
    }
}
