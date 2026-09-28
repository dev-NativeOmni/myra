<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ModuleField;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected User $guru;

    protected User $waliMurid;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);
        ModuleField::seedDefaultFields();

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        $this->admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::create([
            'name' => 'Admin Staff',
            'email' => 'admin_test@taqreer.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->waliMurid = User::where('role', User::ROLE_WALI_MURID)->first();
        $this->classroom = Classroom::first();
    }

    public function test_admin_can_access_module_settings_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('module-settings.index'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Modul Penilaian');
        $response->assertSee('Tahfidz Al-Qur&#039;an', false);
        $response->assertSee('Kesantrian');
        $response->assertSee('Wali Kelas (Akademik)');
        $response->assertSee('Tata Usaha (Administrasi)');
    }

    public function test_non_admin_cannot_access_module_settings(): void
    {
        $responseGuru = $this->actingAs($this->guru)->get(route('module-settings.index'));
        $responseGuru->assertStatus(403);

        $responseWali = $this->actingAs($this->waliMurid)->get(route('module-settings.index'));
        $responseWali->assertStatus(403);
    }

    public function test_admin_can_create_custom_field(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('module-settings.store'), [
            'module' => 'kesantrian',
            'label' => 'Kerapian Lemari & Ranjang',
            'type' => 'select',
            'options_raw' => 'A (Sangat Rapi), B (Rapi), C (Cukup), D (Kurang)',
            'placeholder' => 'Pilih kondisi kerapian',
        ]);

        $response->assertRedirect(route('module-settings.index', ['module' => 'kesantrian']));
        $this->assertDatabaseHas('module_fields', [
            'module' => 'kesantrian',
            'label' => 'Kerapian Lemari & Ranjang',
            'type' => 'select',
            'is_system' => false,
        ]);
    }

    public function test_admin_can_update_existing_field(): void
    {
        $field = ModuleField::where('key', 'tahfidz_setoran')->first();
        $this->assertNotNull($field);

        $response = $this->actingAs($this->superAdmin)->put(route('module-settings.update', $field->id), [
            'label' => 'Capaian Setoran Baru (Ziyadah)',
            'type' => 'text',
            'order_index' => 1,
            'placeholder' => 'Contoh: 5 Halaman',
        ]);

        $response->assertRedirect(route('module-settings.index', ['module' => $field->module]));
        $this->assertDatabaseHas('module_fields', [
            'id' => $field->id,
            'label' => 'Capaian Setoran Baru (Ziyadah)',
            'placeholder' => 'Contoh: 5 Halaman',
        ]);
    }

    public function test_admin_can_toggle_field_status(): void
    {
        $field = ModuleField::where('key', 'tahfidz_akumulasi')->first();
        $this->assertNotNull($field);
        $this->assertTrue((bool) $field->is_active);

        $response = $this->actingAs($this->superAdmin)->patch(route('module-settings.toggle', $field->id));
        $response->assertRedirect();

        $field->refresh();
        $this->assertFalse((bool) $field->is_active);
    }

    public function test_admin_cannot_delete_system_field_but_can_delete_custom_field(): void
    {
        $systemField = ModuleField::where('is_system', true)->first();
        $this->assertNotNull($systemField);
        $resDeleteSystem = $this->actingAs($this->superAdmin)->delete(route('module-settings.destroy', $systemField->id));
        $resDeleteSystem->assertSessionHas('error');
        $this->assertDatabaseHas('module_fields', ['id' => $systemField->id]);

        $customField = ModuleField::create([
            'module' => 'akademik',
            'key' => 'custom_hafalan_matan',
            'label' => 'Hafalan Matan',
            'type' => 'text',
            'is_system' => false,
            'is_active' => true,
            'order_index' => 99,
        ]);

        $resDeleteCustom = $this->actingAs($this->superAdmin)->delete(route('module-settings.destroy', $customField->id));
        $resDeleteCustom->assertSessionHas('success');
        $this->assertDatabaseMissing('module_fields', ['id' => $customField->id]);
    }

    public function test_admin_can_reset_module_fields_to_default(): void
    {
        ModuleField::create([
            'module' => 'tahfidz',
            'key' => 'custom_extra',
            'label' => 'Extra Field',
            'type' => 'text',
            'is_system' => false,
            'is_active' => true,
            'order_index' => 10,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('module-settings.reset'), [
            'module' => 'tahfidz',
        ]);

        $response->assertRedirect(route('module-settings.index', ['module' => 'tahfidz']));
        $this->assertDatabaseMissing('module_fields', [
            'module' => 'tahfidz',
            'key' => 'custom_extra',
        ]);
    }

    public function test_custom_fields_saved_in_batch_store_and_rendered_in_preview(): void
    {
        $customField = ModuleField::create([
            'module' => 'kesantrian',
            'key' => 'custom_kerapian_kamar',
            'label' => 'Kerapian Kamar',
            'type' => 'text',
            'is_system' => false,
            'is_active' => true,
            'order_index' => 99,
        ]);

        $student = Student::where('classroom_id', $this->classroom->id)->first();
        $report = MonthlyReport::firstOrCreate([
            'student_id' => $student->id,
            'period_title' => 'AGUSTUS 2026',
        ], [
            'report_date' => '2026-08-31',
            'cutoff_date' => '2026-08-31',
            'status' => 'draft',
        ]);

        $postData = [
            'classroom_id' => $this->classroom->id,
            'period_title' => 'AGUSTUS 2026',
            'tab' => 'kesantrian',
            'records' => [
                $report->id => [
                    'adab_ibadah' => 'A (Sangat Baik)',
                    'custom_kerapian_kamar' => 'Sangat Rapi dan Bersih',
                ],
            ],
        ];

        $resSave = $this->actingAs($this->superAdmin)->post(route('modules.batch-store'), $postData);
        $resSave->assertRedirect();

        $rec = ReportRecord::where('monthly_report_id', $report->id)->first();
        $this->assertEquals('Sangat Rapi dan Bersih', $rec->getFieldValue('custom_kerapian_kamar'));

        // Check show & PDF rendering
        $showRes = $this->actingAs($this->superAdmin)->get(route('reports.show', $report->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Kerapian Kamar');
        $showRes->assertSee('Sangat Rapi dan Bersih');

        $pdfRes = $this->actingAs($this->superAdmin)->get(route('reports.preview', $report->id));
        $pdfRes->assertStatus(200);
    }
}
