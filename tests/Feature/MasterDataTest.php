<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);
        $this->user = User::where('role', User::ROLE_SUPER_ADMIN)->first();
    }

    public function test_dashboard_renders_correctly(): void
    {
        $latestReport = MonthlyReport::latest()->first();
        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Taqreer');
        if ($latestReport && $latestReport->student) {
            $response->assertSee($latestReport->student->name);
        }
    }

    public function test_institution_edit_and_update(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)->get(route('institution.edit'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Profil Lembaga');

        $logo = UploadedFile::fake()->image('logo.png');

        $updateResponse = $this->actingAs($this->user)->put(route('institution.update'), [
            'name' => 'PONDOK PESANTREN CONTOH UPDATED',
            'city' => 'SOLO',
            'director_name' => 'Ust. Fulan, S.Pd.',
            'director_title' => 'Mudir Pesantren',
            'accent_color' => '#10b981',
            'term_student' => 'Santri',
            'term_teacher' => 'Guru',
            'term_class' => 'Kelas',
            'logo' => $logo,
        ]);

        $updateResponse->assertRedirect(route('institution.edit'));
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('institutions', [
            'name' => 'PONDOK PESANTREN CONTOH UPDATED',
            'city' => 'SOLO',
            'director_title' => 'Mudir Pesantren',
            'accent_color' => '#10b981',
        ]);
    }

    public function test_classroom_crud(): void
    {
        // Index
        $response = $this->actingAs($this->user)->get(route('classrooms.index'));
        $response->assertStatus(200);

        // Store
        $storeResponse = $this->actingAs($this->user)->post(route('classrooms.store'), [
            'name' => '8A',
        ]);
        $storeResponse->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseHas('classrooms', ['name' => '8A']);

        $classroom = Classroom::where('name', '8A')->first();

        // Update
        $updateResponse = $this->actingAs($this->user)->put(route('classrooms.update', $classroom->id), [
            'name' => '8B',
        ]);
        $updateResponse->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseHas('classrooms', ['name' => '8B']);

        // Destroy
        $deleteResponse = $this->actingAs($this->user)->delete(route('classrooms.destroy', $classroom->id));
        $deleteResponse->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseMissing('classrooms', ['name' => '8B']);
    }

    public function test_student_crud(): void
    {
        $classroom = Classroom::first();

        // Index
        $response = $this->actingAs($this->user)->get(route('students.index'));
        $response->assertStatus(200);

        // Create page
        $createResponse = $this->actingAs($this->user)->get(route('students.create'));
        $createResponse->assertStatus(200);

        // Store
        $storeResponse = $this->actingAs($this->user)->post(route('students.store'), [
            'nis' => '0999',
            'name' => 'Muhammad Zaidan',
            'classroom_id' => $classroom->id,
            'gender' => 'L',
            'is_active' => '1',
        ]);
        $storeResponse->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', [
            'nis' => '0999',
            'name' => 'Muhammad Zaidan',
        ]);

        $student = Student::where('nis', '0999')->first();

        // Edit page
        $editResponse = $this->actingAs($this->user)->get(route('students.edit', $student->id));
        $editResponse->assertStatus(200);

        // Update
        $updateResponse = $this->actingAs($this->user)->put(route('students.update', $student->id), [
            'nis' => '0999',
            'name' => 'Muhammad Zaidan Updated',
            'classroom_id' => $classroom->id,
            'gender' => 'L',
            'is_active' => '1',
        ]);
        $updateResponse->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', [
            'nis' => '0999',
            'name' => 'Muhammad Zaidan Updated',
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->user)->delete(route('students.destroy', $student->id));
        $deleteResponse->assertRedirect(route('students.index'));
        $this->assertDatabaseMissing('students', ['nis' => '0999']);
    }
}
