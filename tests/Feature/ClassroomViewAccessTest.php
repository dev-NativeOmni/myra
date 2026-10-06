<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomViewAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $ownClassroom;

    private Classroom $otherClassroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        [$this->ownClassroom, $this->otherClassroom] = Classroom::whereHas('students.monthlyReports')->take(2)->get()->all();
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->guru->classrooms()->sync([$this->ownClassroom->id]);
    }

    public function test_guru_can_preview_report_of_own_classroom(): void
    {
        $report = $this->reportIn($this->ownClassroom);

        $response = $this->actingAs($this->guru)->get(route('reports.preview', $report->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_guru_cannot_preview_report_of_other_classroom(): void
    {
        $report = $this->reportIn($this->otherClassroom);

        $response = $this->actingAs($this->guru)->get(route('reports.preview', $report->id));

        $response->assertForbidden();
    }

    public function test_guru_cannot_view_student_profile_of_other_classroom(): void
    {
        $student = Student::where('classroom_id', $this->otherClassroom->id)->first();

        $response = $this->actingAs($this->guru)->get(route('students.show', $student));

        $response->assertForbidden();
    }

    public function test_admin_can_preview_report_of_any_classroom(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();
        $report = $this->reportIn($this->otherClassroom);

        $response = $this->actingAs($admin)->get(route('reports.preview', $report->id));

        $response->assertOk();
    }

    public function test_completeness_falls_back_to_own_classroom_when_other_is_requested(): void
    {
        $response = $this->actingAs($this->guru)->get(route('reports.completeness', ['classroom_id' => $this->otherClassroom->id]));

        $response->assertViewHas('selectedClassroomId', $this->ownClassroom->id);
        $response->assertViewHas('classrooms', fn ($classrooms) => $classrooms->pluck('id')->all() === [$this->ownClassroom->id]);
    }

    private function reportIn(Classroom $classroom): MonthlyReport
    {
        return MonthlyReport::whereHas('student', fn ($query) => $query->where('classroom_id', $classroom->id))->firstOrFail();
    }
}
