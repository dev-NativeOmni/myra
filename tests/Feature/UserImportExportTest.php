<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class UserImportExportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "Nama Lengkap,Username,Email,Peran,Kelas,NIS Santri,Password\n";

    protected User $superAdmin;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
    }

    public function test_admin_can_export_users(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_rows_match_import_columns_without_passwords(): void
    {
        Excel::fake();
        $guru = User::where('role', User::ROLE_GURU)->with('classrooms')->firstOrFail();
        $parent = User::where('role', User::ROLE_WALI_MURID)->with('student')->firstOrFail();

        $this->actingAs($this->admin)->get(route('users.export'));

        Excel::assertDownloaded('Data_Pengguna.xlsx', function (UsersExport $export) use ($guru, $parent) {
            $guruRow = $export->map($guru);
            $parentRow = $export->map($parent);

            return $export->headings() === ['Nama Lengkap', 'Username', 'Email', 'Peran', 'Kelas', 'NIS Santri', 'Password']
                && $guruRow[3] === User::ROLE_GURU
                && $guruRow[4] === $guru->classrooms->pluck('name')->implode(', ')
                && $parentRow[5] === $parent->student->nis
                && $guruRow[6] === null
                && $parentRow[6] === null;
        });
    }

    public function test_admin_can_download_import_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.import.template'));

        $response->assertOk();
    }

    public function test_non_admin_cannot_export_or_import_users(): void
    {
        $guru = User::where('role', User::ROLE_GURU)->first();

        $this->actingAs($guru)->get(route('users.export'))->assertForbidden();
        $this->actingAs($guru)->get(route('users.import.template'))->assertForbidden();
        $this->actingAs($guru)->post(route('users.import'))->assertForbidden();
    }

    public function test_import_creates_staff_with_classrooms_and_parent_linked_to_student(): void
    {
        $classrooms = Classroom::orderBy('name')->take(2)->get();
        $student = Student::first();
        $csv = self::HEADER
            ."Ustadz Impor,ustadz_impor,ustadz.impor@example.com,guru,\"{$classrooms[0]->name}, {$classrooms[1]->name}\",,rahasia123\n"
            ."Wali Impor,wali_impor,,wali_murid,,{$student->nis},rahasia456\n";

        $response = $this->importCsv($this->admin, $csv);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');
        $guru = User::where('username', 'ustadz_impor')->firstOrFail();
        $this->assertSame(User::ROLE_GURU, $guru->role);
        $this->assertSame($classrooms->pluck('id')->sort()->values()->all(), $guru->classrooms->pluck('id')->sort()->values()->all());
        $this->assertTrue(Hash::check('rahasia123', $guru->password));
        $parent = User::where('username', 'wali_impor')->firstOrFail();
        $this->assertSame(User::ROLE_WALI_MURID, $parent->role);
        $this->assertSame($student->id, $parent->student_id);
        $this->assertNull($parent->email);
    }

    public function test_import_updates_existing_user_and_keeps_password_when_blank(): void
    {
        $existing = User::where('role', User::ROLE_TU)->firstOrFail();
        $originalHash = $existing->password;
        $csv = self::HEADER."Nama Baru TU,{$existing->username},,tu,,,\n";

        $this->importCsv($this->admin, $csv);

        $existing->refresh();
        $this->assertSame('Nama Baru TU', $existing->name);
        $this->assertSame($originalHash, $existing->password);
    }

    public function test_import_rejects_new_user_with_missing_or_short_password(): void
    {
        $csv = self::HEADER
            ."Tanpa Password,tanpa_password,,tu,,,\n"
            ."Password Pendek,password_pendek,,tu,,,pendek1\n";

        $response = $this->importCsv($this->admin, $csv);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('users', ['username' => 'tanpa_password']);
        $this->assertDatabaseMissing('users', ['username' => 'password_pendek']);
    }

    public function test_import_rejects_unknown_classroom_and_unknown_student_nis(): void
    {
        $csv = self::HEADER
            ."Guru Kelas Salah,guru_kelas_salah,,guru,KELAS_TIDAK_ADA,,rahasia123\n"
            ."Wali NIS Salah,wali_nis_salah,,wali_murid,,NIS_TIDAK_ADA,rahasia123\n"
            ."Staf Valid,staf_valid,,tu,,,rahasia123\n";

        $response = $this->importCsv($this->admin, $csv);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('users', ['username' => 'guru_kelas_salah']);
        $this->assertDatabaseMissing('users', ['username' => 'wali_nis_salah']);
        $this->assertDatabaseHas('users', ['username' => 'staf_valid', 'role' => User::ROLE_TU]);
    }

    public function test_admin_cannot_create_or_modify_super_admin_via_import(): void
    {
        $csv = self::HEADER
            ."Super Baru,super_baru,,super_admin,,,rahasia123\n"
            ."Diambil Alih,{$this->superAdmin->username},,admin,,,rahasia123\n";

        $response = $this->importCsv($this->admin, $csv);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('users', ['username' => 'super_baru']);
        $this->assertSame(User::ROLE_SUPER_ADMIN, $this->superAdmin->fresh()->role);
    }

    public function test_super_admin_can_create_super_admin_via_import(): void
    {
        $csv = self::HEADER."Super Baru,super_baru,,super_admin,,,rahasia123\n";

        $this->importCsv($this->superAdmin, $csv);

        $this->assertDatabaseHas('users', ['username' => 'super_baru', 'role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_import_cannot_change_the_importers_own_role(): void
    {
        $csv = self::HEADER."{$this->admin->name},{$this->admin->username},,tu,,,\n";

        $response = $this->importCsv($this->admin, $csv);

        $response->assertSessionHasErrors();
        $this->assertSame(User::ROLE_ADMIN, $this->admin->fresh()->role);
    }

    private function importCsv(User $importer, string $csv): TestResponse
    {
        $file = UploadedFile::fake()->createWithContent('pengguna.csv', $csv);

        return $this->actingAs($importer)->post(route('users.import'), ['file' => $file]);
    }
}
