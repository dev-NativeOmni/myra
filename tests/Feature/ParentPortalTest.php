<?php

namespace Tests\Feature;

use App\Models\MonthlyReport;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private Student $firstChild;

    private Student $secondChild;

    private Student $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        [$this->firstChild, $this->secondChild, $this->otherStudent] = Student::whereHas('monthlyReports', fn ($query) => $query->where('status', 'published'))
            ->orderBy('name')
            ->take(3)
            ->get()
            ->all();
        $this->parent = User::factory()->create(['role' => User::ROLE_WALI_MURID]);
        $this->parent->children()->sync([$this->firstChild->id, $this->secondChild->id]);
    }

    public function test_dashboard_shows_first_child_and_lets_parent_switch_to_another_child(): void
    {
        $defaultResponse = $this->actingAs($this->parent)->get(route('parent.dashboard'));
        $switchedResponse = $this->actingAs($this->parent)->get(route('parent.dashboard', ['student' => $this->secondChild->id]));

        $defaultResponse->assertViewHas('student', fn (Student $student) => $student->is($this->firstChild));
        $defaultResponse->assertSee(route('parent.dashboard', ['student' => $this->secondChild->id]), false);
        $switchedResponse->assertViewHas('student', fn (Student $student) => $student->is($this->secondChild));
    }

    public function test_dashboard_ignores_a_student_that_is_not_linked_to_the_parent(): void
    {
        $response = $this->actingAs($this->parent)->get(route('parent.dashboard', ['student' => $this->otherStudent->id]));

        $response->assertViewHas('student', fn (Student $student) => $student->is($this->firstChild));
        $response->assertDontSee($this->otherStudent->name);
    }

    public function test_parent_can_preview_published_report_of_each_linked_child(): void
    {
        foreach ([$this->firstChild, $this->secondChild] as $child) {
            $report = $this->publishedReportOf($child);

            $response = $this->actingAs($this->parent)->get(route('parent.reports.preview', $report->id));

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_parent_cannot_preview_report_of_unlinked_student(): void
    {
        $report = $this->publishedReportOf($this->otherStudent);

        $response = $this->actingAs($this->parent)->get(route('parent.reports.preview', $report->id));

        $response->assertNotFound();
    }

    public function test_parent_cannot_preview_draft_report_of_own_child(): void
    {
        $report = $this->publishedReportOf($this->firstChild);
        $report->update(['status' => 'draft']);

        $response = $this->actingAs($this->parent)->get(route('parent.reports.preview', $report->id));

        $response->assertNotFound();
    }

    private function publishedReportOf(Student $student): MonthlyReport
    {
        return MonthlyReport::where('student_id', $student->id)->where('status', 'published')->firstOrFail();
    }
}
