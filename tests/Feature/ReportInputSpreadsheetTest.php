<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportInputSpreadsheetTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guru;

    protected User $kesantrian;

    protected User $waliKelas;

    protected User $tu;

    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->kesantrian = User::where('role', User::ROLE_KESANTRIAN)->first();
        $this->waliKelas = User::where('role', User::ROLE_WALI_KELAS)->first();
        $this->tu = User::where('role', User::ROLE_TU)->first();
        $this->classroom = Classroom::first();

        // These tests exercise module-level behavior, not classroom scoping,
        // so assign every classroom to the classroom-scoped staff roles by default.
        $classroomIds = Classroom::pluck('id');
        $this->guru->classrooms()->sync($classroomIds);
        $this->kesantrian->classrooms()->sync($classroomIds);
        $this->waliKelas->classrooms()->sync($classroomIds);
    }

    public function test_users_can_access_unified_spreadsheet_with_tabs(): void
    {
        // 1. Super Admin access
        $response = $this->actingAs($this->admin)->get(route('modules.spreadsheet', [
            'classroom_id' => $this->classroom->id,
            'tab' => 'all',
        ]));
        $response->assertStatus(200);
        $response->assertSee('Portal Input Modul Penilaian');
        $response->assertSee('Semua Modul');
        $response->assertSee('Tahfidz');
        $response->assertSee('Kesantrian', false);
        $response->assertSee('Wali Kelas (KBM)');
        $response->assertSee('Tata Usaha (TU)');

        // 2. Guru Tahfidz access
        $guruResponse = $this->actingAs($this->guru)->get(route('modules.spreadsheet', [
            'classroom_id' => $this->classroom->id,
            'tab' => 'tahfidz',
        ]));
        $guruResponse->assertStatus(200);

        // 3. Kesantrian access
        $kesantrianResponse = $this->actingAs($this->kesantrian)->get(route('modules.spreadsheet', [
            'classroom_id' => $this->classroom->id,
            'tab' => 'kesantrian',
        ]));
        $kesantrianResponse->assertStatus(200);

        // 4. Wali Kelas access
        $waliResponse = $this->actingAs($this->waliKelas)->get(route('modules.spreadsheet', [
            'classroom_id' => $this->classroom->id,
            'tab' => 'akademik',
        ]));
        $waliResponse->assertStatus(200);

        // 5. TU access
        $tuResponse = $this->actingAs($this->tu)->get(route('modules.spreadsheet', [
            'classroom_id' => $this->classroom->id,
            'tab' => 'administrasi',
        ]));
        $tuResponse->assertStatus(200);
    }

    public function test_legacy_module_routes_render_unified_spreadsheet(): void
    {
        $resTahfidz = $this->actingAs($this->guru)->get(route('modules.tahfidz', ['classroom_id' => $this->classroom->id]));
        $resTahfidz->assertStatus(200);
        $resTahfidz->assertSee('Portal Input Modul Penilaian');

        $resKesantrian = $this->actingAs($this->kesantrian)->get(route('modules.kesantrian', ['classroom_id' => $this->classroom->id]));
        $resKesantrian->assertStatus(200);
        $resKesantrian->assertSee('Portal Input Modul Penilaian');

        $resAkademik = $this->actingAs($this->waliKelas)->get(route('modules.akademik', ['classroom_id' => $this->classroom->id]));
        $resAkademik->assertStatus(200);
        $resAkademik->assertSee('Portal Input Modul Penilaian');

        $resTu = $this->actingAs($this->tu)->get(route('modules.administrasi', ['classroom_id' => $this->classroom->id]));
        $resTu->assertStatus(200);
        $resTu->assertSee('Portal Input Modul Penilaian');
    }

    public function test_batch_store_updates_kesantrian_akademik_and_tu_records(): void
    {
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
            'tab' => 'all',
            'records' => [
                $report->id => [
                    'tahfidz_setoran' => 'Ziyadah (4x setoran)',
                    'tahfidz_akumulasi' => '30 Juz',
                    'tahfidz_rincian_juz' => 'Juz 28, 29, 30',
                    'tahfidz_notes' => 'Alhamdulillah mutqin dan lancar.',
                    'adab_ibadah' => 'A (Sangat Baik)',
                    'adab_akhlak' => 'A (Sangat Baik)',
                    'adab_kerapian' => 'B (Baik)',
                    'adab_kedisiplinan' => 'A (Sangat Baik)',
                    'body_height_cm' => 142,
                    'body_weight_kg' => 36,
                    'is_baligh' => 'Belum',
                    'kesantrian_notes' => 'Sangat aktif shalat berjamaah tepat waktu.',
                    'academic_notes' => 'Memahami materi tajwid dengan sangat baik.',
                    'last_spp' => 'Agustus 2026',
                    'last_laundry' => 'Agustus 2026',
                    'registration_status' => 'Lunas',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('modules.batch-store'), $postData);
        $response->assertRedirect(route('modules.spreadsheet', [
            'classroom_id' => $this->classroom->id,
            'period_title' => 'AGUSTUS 2026',
            'tab' => 'all',
        ]));

        $this->assertDatabaseHas('report_records', [
            'monthly_report_id' => $report->id,
            'tahfidz_setoran' => 'Ziyadah (4x setoran)',
            'tahfidz_akumulasi' => '30 Juz',
            'tahfidz_rincian_juz' => 'Juz 28, 29, 30',
            'tahfidz_notes' => 'Alhamdulillah mutqin dan lancar.',
            'adab_ibadah' => 'A (Sangat Baik)',
            'body_height_cm' => 142,
            'kesantrian_notes' => 'Sangat aktif shalat berjamaah tepat waktu.',
            'academic_notes' => 'Memahami materi tajwid dengan sangat baik.',
            'last_spp' => 'Agustus 2026',
            'registration_status' => 'Lunas',
        ]);
    }

    public function test_batch_store_only_saves_fields_within_the_users_own_module(): void
    {
        $student = Student::where('classroom_id', $this->classroom->id)->first();
        $report = MonthlyReport::firstOrCreate([
            'student_id' => $student->id,
            'period_title' => 'AGUSTUS 2026',
        ], [
            'report_date' => '2026-08-31',
            'cutoff_date' => '2026-08-31',
            'status' => 'draft',
        ]);

        // Guru Tahfidz tries to submit data for every module in one request.
        $postData = [
            'classroom_id' => $this->classroom->id,
            'period_title' => 'AGUSTUS 2026',
            'tab' => 'all',
            'records' => [
                $report->id => [
                    'tahfidz_setoran' => 'Ziyadah (4x setoran)',
                    'tahfidz_notes' => 'Catatan tahfidz dari guru.',
                    'adab_ibadah' => 'A (Sangat Baik)',
                    'kesantrian_notes' => 'Catatan kesantrian yang tidak berhak diisi guru.',
                    'academic_notes' => 'Catatan akademik yang tidak berhak diisi guru.',
                    'last_spp' => 'Agustus 2026',
                    'registration_status' => 'Lunas',
                ],
            ],
        ];

        $this->actingAs($this->guru)->post(route('modules.batch-store'), $postData);

        $this->assertDatabaseHas('report_records', [
            'monthly_report_id' => $report->id,
            'tahfidz_setoran' => 'Ziyadah (4x setoran)',
            'tahfidz_notes' => 'Catatan tahfidz dari guru.',
        ]);

        $this->assertDatabaseMissing('report_records', [
            'monthly_report_id' => $report->id,
            'adab_ibadah' => 'A (Sangat Baik)',
        ]);
        $this->assertDatabaseMissing('report_records', [
            'monthly_report_id' => $report->id,
            'kesantrian_notes' => 'Catatan kesantrian yang tidak berhak diisi guru.',
        ]);
        $this->assertDatabaseMissing('report_records', [
            'monthly_report_id' => $report->id,
            'academic_notes' => 'Catatan akademik yang tidak berhak diisi guru.',
        ]);
        $this->assertDatabaseMissing('report_records', [
            'monthly_report_id' => $report->id,
            'last_spp' => 'Agustus 2026',
        ]);
        $this->assertDatabaseMissing('report_records', [
            'monthly_report_id' => $report->id,
            'registration_status' => 'Lunas',
        ]);
    }

    public function test_batch_store_blocks_edits_for_classrooms_not_assigned_to_the_user(): void
    {
        $otherClassroom = Classroom::where('id', '!=', $this->classroom->id)->first();

        // Guru is only assigned to the "other" classroom, not $this->classroom.
        $this->guru->classrooms()->sync([$otherClassroom->id]);

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
            'tab' => 'tahfidz',
            'records' => [
                $report->id => [
                    'tahfidz_setoran' => 'Ziyadah 3 Juz',
                    'tahfidz_notes' => 'Seharusnya tidak tersimpan.',
                ],
            ],
        ];

        $this->actingAs($this->guru)->post(route('modules.batch-store'), $postData);

        $this->assertDatabaseMissing('report_records', [
            'monthly_report_id' => $report->id,
            'tahfidz_setoran' => 'Ziyadah 3 Juz',
        ]);
    }

    public function test_dedicated_update_endpoint_rejects_classroom_not_assigned_to_the_user(): void
    {
        $otherClassroom = Classroom::where('id', '!=', $this->classroom->id)->first();
        $this->guru->classrooms()->sync([$otherClassroom->id]);

        $student = Student::where('classroom_id', $this->classroom->id)->first();
        $report = MonthlyReport::firstOrCreate([
            'student_id' => $student->id,
            'period_title' => 'AGUSTUS 2026',
        ], [
            'report_date' => '2026-08-31',
            'cutoff_date' => '2026-08-31',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->guru)->put(route('modules.tahfidz.update', $report->id), [
            'tahfidz_setoran' => 'Ziyadah 3 Juz',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('report_records', [
            'monthly_report_id' => $report->id,
            'tahfidz_setoran' => 'Ziyadah 3 Juz',
        ]);
    }
}
