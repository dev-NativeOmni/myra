<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guru;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->classroom = Classroom::where('name', '1')->first();
    }

    public function test_admin_can_export_students(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_non_admin_cannot_export_or_import_students(): void
    {
        $this->actingAs($this->guru)->get(route('students.export'))->assertForbidden();
        $this->actingAs($this->guru)->get(route('students.import.template'))->assertForbidden();
        $this->actingAs($this->guru)->post(route('students.import'))->assertForbidden();
    }

    public function test_import_creates_new_students_and_updates_existing_by_nis(): void
    {
        $existing = Student::where('classroom_id', $this->classroom->id)->first();

        $csv = "NIS,Nama Lengkap,Kelas,Jenis Kelamin,Status Aktif\n"
            ."{$existing->nis},Nama Sudah Diperbarui,1,L,Aktif\n"
            ."9999001,Santri Baru Dari Impor,1,P,Aktif\n";

        $file = UploadedFile::fake()->createWithContent('santri.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('students.import'), ['file' => $file]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'nis' => $existing->nis,
            'name' => 'Nama Sudah Diperbarui',
        ]);
        $this->assertDatabaseHas('students', [
            'nis' => '9999001',
            'name' => 'Santri Baru Dari Impor',
            'gender' => 'P',
            'classroom_id' => $this->classroom->id,
        ]);
    }

    public function test_import_skips_rows_with_unknown_classroom_and_reports_error(): void
    {
        $csv = "NIS,Nama Lengkap,Kelas,Jenis Kelamin,Status Aktif\n"
            ."9999002,Santri Kelas Tidak Ada,KELAS_TIDAK_ADA,L,Aktif\n"
            ."9999003,Santri Valid,1,L,Aktif\n";

        $file = UploadedFile::fake()->createWithContent('santri.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('students.import'), ['file' => $file]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('students', ['nis' => '9999002']);
        $this->assertDatabaseHas('students', ['nis' => '9999003', 'name' => 'Santri Valid']);
    }

    public function test_import_reports_error_when_required_column_is_missing(): void
    {
        $csv = "NIS,Nama Lengkap,Kelas,Jenis Kelamin,Status Aktif\n"
            .",Tanpa NIS,1,L,Aktif\n";

        $file = UploadedFile::fake()->createWithContent('santri.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('students.import'), ['file' => $file]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('students', ['name' => 'Tanpa NIS']);
    }

    public function test_import_preserves_leading_zeros_in_nis(): void
    {
        $csv = "NIS,Nama Lengkap,Kelas,Jenis Kelamin,Status Aktif\n"
            ."00456,Santri Nis Nol Depan,1,L,Aktif\n";

        $file = UploadedFile::fake()->createWithContent('santri.csv', $csv);

        $this->actingAs($this->admin)->post(route('students.import'), ['file' => $file]);

        $this->assertDatabaseHas('students', [
            'nis' => '00456',
            'name' => 'Santri Nis Nol Depan',
        ]);
    }

    public function test_admin_can_download_import_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.import.template'));

        $response->assertStatus(200);
    }
}
