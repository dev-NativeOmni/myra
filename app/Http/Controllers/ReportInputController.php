<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ModuleField;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportInputController extends Controller
{
    /**
     * Show comprehensive edit form for all 4 modules of a specific report.
     */
    public function editRecord($reportId): View
    {
        $report = MonthlyReport::with(['student.classroom', 'record'])->findOrFail($reportId);
        $record = $report->record ?? ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);

        return view('reports.input_all', compact('report', 'record'));
    }

    /**
     * Update all 4 modules for a specific report.
     */
    public function updateRecord(Request $request, $reportId): RedirectResponse
    {
        $report = MonthlyReport::with('student')->findOrFail($reportId);
        abort_unless($request->user()->canEditClassroom($report->student->classroom_id), 403, 'Akses Ditolak: kelas ini bukan tanggung jawab Anda.');

        $record = ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);

        $validated = $request->validate([
            // Tahfidz
            'tahfidz_setoran' => 'nullable|string|max:100',
            'tahfidz_akumulasi' => 'nullable|string|max:100',
            'tahfidz_rincian_juz' => 'nullable|string|max:100',
            'tahfidz_notes' => 'nullable|string',

            // Kesantrian
            'adab_ibadah' => 'nullable|string|max:20',
            'adab_akhlak' => 'nullable|string|max:20',
            'adab_kerapian' => 'nullable|string|max:20',
            'adab_kedisiplinan' => 'nullable|string|max:20',
            'body_height_cm' => 'nullable|integer|min:50|max:250',
            'body_weight_kg' => 'nullable|integer|min:10|max:200',
            'is_baligh' => 'nullable|in:Belum,Sudah',
            'kesantrian_notes' => 'nullable|string',

            // Akademik
            'academic_notes' => 'nullable|string',

            // Administrasi
            'last_spp' => 'nullable|string|max:50',
            'last_laundry' => 'nullable|string|max:50',
            'registration_status' => 'nullable|string|max:50',
        ]);

        $record->update($validated);

        if ($request->has('period_title') || $request->has('report_date') || $request->has('cutoff_date') || $request->has('status')) {
            $report->update($request->only(['period_title', 'report_date', 'cutoff_date', 'status']));
        }

        return redirect()->route('reports.show', $report->id)
            ->with('success', 'Seluruh data evaluasi santri berhasil diperbarui.');
    }

    /**
     * Dedicated Module View: Tahfidz
     */
    public function moduleTahfidz(Request $request): View
    {
        $request->merge(['tab' => 'tahfidz']);

        return $this->spreadsheet($request);
    }

    /**
     * Update Tahfidz evaluation for a specific report.
     */
    public function updateTahfidz(Request $request, $reportId): RedirectResponse
    {
        $report = MonthlyReport::with('student')->findOrFail($reportId);
        abort_unless($request->user()->canEditClassroom($report->student->classroom_id), 403, 'Akses Ditolak: kelas ini bukan tanggung jawab Anda.');

        $record = ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);

        $validated = $request->validate([
            'tahfidz_setoran' => 'nullable|string|max:100',
            'tahfidz_akumulasi' => 'nullable|string|max:100',
            'tahfidz_rincian_juz' => 'nullable|string|max:100',
            'tahfidz_notes' => 'nullable|string',
        ]);

        $record->update($validated);

        return back()->with('success', "Capaian Tahfidz ananda {$report->student->name} berhasil disimpan.");
    }

    /**
     * Unified Spreadsheet Matrix View for Tahfidz, Kesantrian, Wali Kelas, and TU.
     */
    public function spreadsheet(Request $request): View
    {
        $classrooms = Classroom::orderBy('name')->get();
        $selectedClassroomId = $request->query('classroom_id');
        if (! $selectedClassroomId && $classrooms->isNotEmpty()) {
            $selectedClassroomId = $classrooms->first()->id;
        }

        $existingPeriods = MonthlyReport::select('period_title')->distinct()->pluck('period_title')->toArray();
        $defaultPeriod = strtoupper(Carbon::now()->locale('id')->translatedFormat('F Y'));
        $selectedPeriod = $request->query('period_title', $existingPeriods[0] ?? $defaultPeriod);

        // Ensure current selectedPeriod is in options list
        if (! in_array($selectedPeriod, $existingPeriods, true)) {
            array_unshift($existingPeriods, $selectedPeriod);
        }

        $user = auth()->user();
        $defaultTab = 'all';
        if ($user) {
            if ($user->role === 'guru') {
                $defaultTab = 'tahfidz';
            } elseif ($user->role === 'kesantrian') {
                $defaultTab = 'kesantrian';
            } elseif ($user->role === 'wali_kelas') {
                $defaultTab = 'akademik';
            } elseif ($user->role === 'tu') {
                $defaultTab = 'administrasi';
            }
        }
        $selectedTab = $request->query('tab', $defaultTab);

        // Ensure draft report & record existence for all active students in class & period
        if ($selectedClassroomId && $selectedPeriod) {
            $students = Student::where('classroom_id', $selectedClassroomId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $defaultReportDate = now()->toDateString();
            $defaultCutoffDate = now()->endOfMonth()->toDateString();

            foreach ($students as $student) {
                $report = MonthlyReport::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'period_title' => $selectedPeriod,
                    ],
                    [
                        'report_date' => $defaultReportDate,
                        'cutoff_date' => $defaultCutoffDate,
                        'status' => 'draft',
                    ]
                );

                ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);
            }
        }

        // Fetch reports for display
        $reports = MonthlyReport::with(['student.classroom', 'record'])
            ->whereHas('student', function ($q) use ($selectedClassroomId) {
                if ($selectedClassroomId) {
                    $q->where('classroom_id', $selectedClassroomId);
                }
            })
            ->where('period_title', $selectedPeriod)
            ->join('students', 'monthly_reports.student_id', '=', 'students.id')
            ->orderBy('students.name')
            ->select('monthly_reports.*')
            ->get();

        $tahfidzFields = ModuleField::getActiveFields(ModuleField::MODULE_TAHFIDZ);
        $kesantrianFields = ModuleField::getActiveFields(ModuleField::MODULE_KESANTRIAN);
        $akademikFields = ModuleField::getActiveFields(ModuleField::MODULE_AKADEMIK);
        $administrasiFields = ModuleField::getActiveFields(ModuleField::MODULE_ADMINISTRASI);

        return view('input_modules.spreadsheet', compact(
            'classrooms',
            'selectedClassroomId',
            'existingPeriods',
            'selectedPeriod',
            'selectedTab',
            'reports',
            'tahfidzFields',
            'kesantrianFields',
            'akademikFields',
            'administrasiFields'
        ));
    }

    /**
     * Batch store/update multiple student report records from spreadsheet matrix.
     */
    public function batchStore(Request $request): RedirectResponse
    {
        $recordsData = $request->input('records', []);
        $updatedCount = 0;

        $systemColumns = [
            'tahfidz_setoran', 'tahfidz_akumulasi', 'tahfidz_rincian_juz', 'tahfidz_notes',
            'adab_ibadah', 'adab_akhlak', 'adab_kerapian', 'adab_kedisiplinan',
            'body_height_cm', 'body_weight_kg', 'is_baligh', 'kesantrian_notes',
            'academic_notes', 'last_spp', 'last_laundry', 'registration_status',
        ];

        // Each role may only write fields belonging to its own module, and only for
        // classrooms it is responsible for; the rest is view-only.
        $user = $request->user();
        $editableModules = $user->editableModules();
        $responsibleClassroomIds = $user->responsibleClassroomIds();
        $fieldModules = ModuleField::fieldModuleMap();

        foreach ($recordsData as $reportId => $data) {
            $report = MonthlyReport::with('student')->find($reportId);
            if (! $report) {
                continue;
            }

            if ($responsibleClassroomIds !== null && ! in_array($report->student->classroom_id, $responsibleClassroomIds, true)) {
                continue;
            }

            $record = ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);
            $updateData = [];
            $customFields = $record->custom_fields ?? [];
            $hasAllowedChanges = false;

            foreach ($data as $key => $val) {
                $module = $fieldModules[$key] ?? null;
                if (! $module || ! in_array($module, $editableModules, true)) {
                    continue;
                }

                $hasAllowedChanges = true;

                if (in_array($key, $systemColumns, true)) {
                    if ($key === 'body_height_cm' || $key === 'body_weight_kg') {
                        $updateData[$key] = ($val !== '' && $val !== null) ? (int) $val : null;
                    } elseif ($key === 'is_baligh') {
                        $updateData[$key] = $val ?: 'Belum';
                    } else {
                        $updateData[$key] = ($val !== '' && $val !== null) ? $val : null;
                    }
                } else {
                    $customFields[$key] = ($val !== '' && $val !== null) ? $val : null;
                }
            }

            if ($hasAllowedChanges) {
                $updateData['custom_fields'] = $customFields;
                $record->update($updateData);
                $updatedCount++;
            }
        }

        return redirect()->route('modules.spreadsheet', [
            'classroom_id' => $request->input('classroom_id'),
            'period_title' => $request->input('period_title'),
            'tab' => $request->input('tab', 'all'),
        ])->with('success', "Berhasil menyimpan perubahan data penilaian untuk {$updatedCount} santri.");
    }

    /**
     * Dedicated Module View: Kesantrian / Asrama
     */
    public function moduleKesantrian(Request $request): View
    {
        $request->merge(['tab' => 'kesantrian']);

        return $this->spreadsheet($request);
    }

    /**
     * Update Kesantrian evaluation for a specific report.
     */
    public function updateKesantrian(Request $request, $reportId): RedirectResponse
    {
        $report = MonthlyReport::with('student')->findOrFail($reportId);
        abort_unless($request->user()->canEditClassroom($report->student->classroom_id), 403, 'Akses Ditolak: kelas ini bukan tanggung jawab Anda.');

        $record = ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);

        $validated = $request->validate([
            'adab_ibadah' => 'nullable|string|max:20',
            'adab_akhlak' => 'nullable|string|max:20',
            'adab_kerapian' => 'nullable|string|max:20',
            'adab_kedisiplinan' => 'nullable|string|max:20',
            'body_height_cm' => 'nullable|integer|min:50|max:250',
            'body_weight_kg' => 'nullable|integer|min:10|max:200',
            'is_baligh' => 'nullable|in:Belum,Sudah',
            'kesantrian_notes' => 'nullable|string',
        ]);

        $record->update($validated);

        return back()->with('success', "Data Kesantrian ananda {$report->student->name} berhasil disimpan.");
    }

    /**
     * Dedicated Module View: Akademik
     */
    public function moduleAkademik(Request $request): View
    {
        $request->merge(['tab' => 'akademik']);

        return $this->spreadsheet($request);
    }

    /**
     * Update Akademik evaluation for a specific report.
     */
    public function updateAkademik(Request $request, $reportId): RedirectResponse
    {
        $report = MonthlyReport::with('student')->findOrFail($reportId);
        abort_unless($request->user()->canEditClassroom($report->student->classroom_id), 403, 'Akses Ditolak: kelas ini bukan tanggung jawab Anda.');

        $record = ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);

        $validated = $request->validate([
            'academic_notes' => 'nullable|string',
        ]);

        $record->update($validated);

        return back()->with('success', "Catatan Akademik ananda {$report->student->name} berhasil disimpan.");
    }

    /**
     * Dedicated Module View: Administrasi
     */
    public function moduleAdministrasi(Request $request): View
    {
        $request->merge(['tab' => 'administrasi']);

        return $this->spreadsheet($request);
    }

    /**
     * Update Administrasi evaluation for a specific report.
     */
    public function updateAdministrasi(Request $request, $reportId): RedirectResponse
    {
        $report = MonthlyReport::with('student')->findOrFail($reportId);
        abort_unless($request->user()->canEditClassroom($report->student->classroom_id), 403, 'Akses Ditolak: kelas ini bukan tanggung jawab Anda.');

        $record = ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);

        $validated = $request->validate([
            'last_spp' => 'nullable|string|max:50',
            'last_laundry' => 'nullable|string|max:50',
            'registration_status' => 'nullable|string|max:50',
        ]);

        $record->update($validated);

        return back()->with('success', "Data Administrasi ananda {$report->student->name} berhasil disimpan.");
    }
}
