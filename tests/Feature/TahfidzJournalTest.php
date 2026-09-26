<?php

namespace Tests\Feature;

use App\Models\MonthlyReport;
use App\Models\Student;
use App\Models\TahfidzJournal;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzJournalTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected User $kesantrian;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->kesantrian = User::where('role', User::ROLE_KESANTRIAN)->first();
        $this->student = Student::first();
    }

    public function test_guru_can_view_and_create_journal_entry(): void
    {
        // 1. Index
        $response = $this->actingAs($this->guru)->get(route('tahfidz-journals.index'));
        $response->assertStatus(200);
        $response->assertSee('Jurnal Harian Tahfidz Al-Qur\'an');

        // 2. Create Page
        $createResponse = $this->actingAs($this->guru)->get(route('tahfidz-journals.create'));
        $createResponse->assertStatus(200);

        // 3. Store Entry
        $storeResponse = $this->actingAs($this->guru)->post(route('tahfidz-journals.store'), [
            'student_id' => $this->student->id,
            'date' => '2026-08-28',
            'type' => 'ziyadah',
            'juz' => 30,
            'surah' => 'Al-Fajr',
            'ayah_start' => 1,
            'ayah_end' => 30,
            'page_count' => 2,
            'grade' => 'Mumtaz (Sangat Baik)',
            'notes' => 'Lancar sekali tanpa salah.',
        ]);

        $storeResponse->assertRedirect(route('tahfidz-journals.index'));
        $this->assertDatabaseHas('tahfidz_journals', [
            'student_id' => $this->student->id,
            'surah' => 'Al-Fajr',
            'grade' => 'Mumtaz (Sangat Baik)',
        ]);
    }

    public function test_guru_can_update_and_delete_journal_entry(): void
    {
        $journal = TahfidzJournal::first();

        // Edit
        $editResponse = $this->actingAs($this->guru)->get(route('tahfidz-journals.edit', $journal->id));
        $editResponse->assertStatus(200);

        // Update
        $updateResponse = $this->actingAs($this->guru)->put(route('tahfidz-journals.update', $journal->id), [
            'student_id' => $journal->student_id,
            'date' => $journal->date->format('Y-m-d'),
            'type' => 'murajaah',
            'juz' => 30,
            'surah' => 'An-Nasr',
            'grade' => 'Jayyid Jiddan',
        ]);
        $updateResponse->assertRedirect(route('tahfidz-journals.index'));
        $this->assertDatabaseHas('tahfidz_journals', [
            'id' => $journal->id,
            'surah' => 'An-Nasr',
            'type' => 'murajaah',
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->guru)->delete(route('tahfidz-journals.destroy', $journal->id));
        $deleteResponse->assertRedirect(route('tahfidz-journals.index'));
        $this->assertDatabaseMissing('tahfidz_journals', ['id' => $journal->id]);
    }

    public function test_sync_journals_to_monthly_report(): void
    {
        $report = MonthlyReport::first();

        $response = $this->actingAs($this->guru)->post(route('reports.sync-tahfidz', $report->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('report_records', [
            'monthly_report_id' => $report->id,
        ]);

        $record = $report->fresh()->record;
        $this->assertStringContainsString('Ziyadah', $record->tahfidz_setoran);
        $this->assertNotEmpty($record->tahfidz_notes);
    }

    public function test_guru_can_access_spreadsheet_mode(): void
    {
        $response = $this->actingAs($this->guru)->get(route('tahfidz-journals.spreadsheet'));
        $response->assertStatus(200);
        $response->assertSee('Mode Spreadsheet');
        $response->assertSee($this->student->name);
    }

    public function test_guru_can_batch_store_journals_from_spreadsheet(): void
    {
        $response = $this->actingAs($this->guru)->post(route('tahfidz-journals.batch-store'), [
            'entries' => [
                [
                    'student_id' => $this->student->id,
                    'date' => '2026-08-29',
                    'type' => 'ziyadah',
                    'juz' => 30,
                    'surah' => 'Al-Ikhlas',
                    'ayah_start' => 1,
                    'ayah_end' => 4,
                    'page_count' => 1,
                    'grade' => 'Mumtaz (Sangat Baik)',
                    'notes' => 'Sangat lancar dan fasih.',
                ],
                [
                    // Empty row that should be ignored
                    'student_id' => null,
                    'type' => '',
                ]
            ]
        ]);

        $response->assertRedirect(route('tahfidz-journals.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tahfidz_journals', [
            'student_id' => $this->student->id,
            'surah' => 'Al-Ikhlas',
            'grade' => 'Mumtaz (Sangat Baik)',
        ]);
    }

    public function test_guru_can_save_ims_matrix_spreadsheet(): void
    {
        $response = $this->actingAs($this->guru)->post(route('tahfidz-journals.batch-store'), [
            'classroom_id' => $this->student->classroom_id,
            'month' => '2026-08',
            'week' => 'all',
            'records' => [
                $this->student->id => [
                    'dates' => [
                        '2026-08-28' => [
                            'attendance' => 'hadir',
                            'hafalans' => [
                                [
                                    'surah_id' => 112,
                                    'surah' => 'Al-Ikhlas',
                                    'ayah_start' => 1,
                                    'ayah_end' => 4,
                                    'type' => 'ziyadah',
                                    'grade' => 'Mumtaz (A)',
                                    'status' => 'passed',
                                ],
                                [
                                    'surah_id' => 113,
                                    'surah' => 'Al-Falaq',
                                    'ayah_start' => 1,
                                    'ayah_end' => 5,
                                    'type' => 'ziyadah',
                                    'grade' => 'Mumtaz (A)',
                                    'status' => 'passed',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('tahfidz-journals.spreadsheet', [
            'classroom_id' => $this->student->classroom_id,
            'month' => '2026-08',
            'week' => 'all',
        ]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tahfidz_journals', [
            'student_id' => $this->student->id,
            'surah' => 'Al-Ikhlas',
            'surah_id' => 112,
            'ayah_start' => 1,
            'ayah_end' => 4,
            'attendance' => 'hadir',
        ]);

        $this->assertDatabaseHas('tahfidz_journals', [
            'student_id' => $this->student->id,
            'surah' => 'Al-Falaq',
            'surah_id' => 113,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'attendance' => 'hadir',
        ]);
    }

    public function test_absent_attendance_clears_records_and_stores_marker(): void
    {
        // First create an existing journal
        TahfidzJournal::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->guru->id,
            'date' => '2026-08-28',
            'surah' => 'Al-Ikhlas',
            'ayah_start' => 1,
            'ayah_end' => 4,
            'type' => 'ziyadah',
            'attendance' => 'hadir',
        ]);

        // Submit matrix marking student as 'sakit'
        $response = $this->actingAs($this->guru)->post(route('tahfidz-journals.batch-store'), [
            'records' => [
                $this->student->id => [
                    'dates' => [
                        '2026-08-28' => [
                            'attendance' => 'sakit',
                            'hafalans' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('tahfidz_journals', [
            'student_id' => $this->student->id,
            'surah' => 'Al-Ikhlas',
        ]);

        $this->assertDatabaseHas('tahfidz_journals', [
            'student_id' => $this->student->id,
            'attendance' => 'sakit',
        ]);
    }

    public function test_guru_can_bulk_delete_journal_entries(): void
    {
        $journals = TahfidzJournal::take(3)->get();
        $this->assertCount(3, $journals);
        $ids = $journals->pluck('id')->toArray();

        $response = $this->actingAs($this->guru)->post(route('tahfidz-journals.bulk-destroy'), [
            'ids' => $ids,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('tahfidz_journals', ['id' => $id]);
        }
    }

    public function test_unauthorized_role_cannot_access_journals(): void
    {
        $response = $this->actingAs($this->kesantrian)->get(route('tahfidz-journals.index'));
        $response->assertStatus(403);

        $spreadsheetResponse = $this->actingAs($this->kesantrian)->get(route('tahfidz-journals.spreadsheet'));
        $spreadsheetResponse->assertStatus(403);
    }
}


