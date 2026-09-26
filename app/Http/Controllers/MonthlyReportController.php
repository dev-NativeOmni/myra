<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\ModuleField;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class MonthlyReportController extends Controller
{
    /**
     * Display a listing of monthly reports.
     */
    public function index(Request $request): View
    {
        $classrooms = Classroom::orderBy('name')->get();
        $periods = MonthlyReport::select('period_title')->distinct()->pluck('period_title');

        $query = MonthlyReport::with(['student.classroom', 'record'])->latest();

        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        }

        if ($request->filled('period_title')) {
            $query->where('period_title', $request->period_title);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('nis', "%{$search}%");
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        return view('reports.index', compact('reports', 'classrooms', 'periods'));
    }

    /**
     * Show the form for creating a new monthly report or batch generation.
     */
    public function create(): View
    {
        $classrooms = Classroom::with('students')->orderBy('name')->get();
        $students = Student::where('is_active', true)->with('classroom')->orderBy('name')->get();

        return view('reports.create', compact('classrooms', 'students'));
    }

    /**
     * Store newly created monthly report(s).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_title' => 'required|string|max:100',
            'report_date' => 'required|date',
            'cutoff_date' => 'required|date',
            'status' => 'required|in:draft,published',
            'mode' => 'required|in:single,classroom',
            'student_id' => 'required_if:mode,single|nullable|exists:students,id',
            'classroom_id' => 'required_if:mode,classroom|nullable|exists:classrooms,id',
        ]);

        if ($validated['mode'] === 'single') {
            $report = MonthlyReport::firstOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'period_title' => $validated['period_title'],
                ],
                [
                    'report_date' => $validated['report_date'],
                    'cutoff_date' => $validated['cutoff_date'],
                    'status' => $validated['status'],
                ]
            );

            ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);

            return redirect()->route('reports.edit-record', $report->id)
                ->with('success', 'Laporan bulanan berhasil dibuat. Silakan lengkapi data nilai capaian.');
        } else {
            $students = Student::where('classroom_id', $validated['classroom_id'])
                ->where('is_active', true)
                ->get();

            $createdCount = 0;
            foreach ($students as $student) {
                $report = MonthlyReport::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'period_title' => $validated['period_title'],
                    ],
                    [
                        'report_date' => $validated['report_date'],
                        'cutoff_date' => $validated['cutoff_date'],
                        'status' => $validated['status'],
                    ]
                );

                ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);
                $createdCount++;
            }

            return redirect()->route('reports.index', ['period_title' => $validated['period_title']])
                ->with('success', "Berhasil membuat {$createdCount} lembar laporan untuk santri di kelas terpilih.");
        }
    }

    /**
     * Display the specified monthly report detail.
     */
    public function show($id): View
    {
        $report = MonthlyReport::with(['student.classroom', 'record'])->findOrFail($id);
        $institution = Institution::first() ?? new Institution;

        return view('reports.show', compact('report', 'institution'));
    }

    /**
     * Remove the specified monthly report.
     */
    public function destroy($id): RedirectResponse
    {
        $report = MonthlyReport::findOrFail($id);
        $report->delete();

        return redirect()->route('reports.index')->with('success', 'Laporan bulanan berhasil dihapus.');
    }

    /**
     * Preview the monthly report as a PDF stream.
     */
    public function preview($id): Response
    {
        $report = MonthlyReport::with(['student.classroom', 'record'])->findOrFail($id);
        $student = $report->student;
        $record = $report->record ?? new ReportRecord;
        $institution = Institution::first() ?? new Institution([
            'name' => 'PONDOK PESANTREN CONTOH',
            'city' => 'KOTA CONTOH',
            'director_name' => 'Ust. Fulan, S.Pd.',
            'director_title' => 'Direktur Pesantren',
            'accent_color' => '#059669',
        ]);

        $pdf = Pdf::loadView('reports.monthly_pdf', [
            'report' => $report,
            'student' => $student,
            'record' => $record,
            'institution' => $institution,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("Laporan_Bulanan_{$student->nis}_{$student->name}.pdf");
    }

    /**
     * Show the report completeness dashboard: which students are still missing
     * data in which evaluation module, for a selected classroom and period.
     */
    public function completeness(Request $request): View
    {
        $classrooms = Classroom::orderBy('name')->get();
        $selectedClassroomId = $request->query('classroom_id');
        if (! $selectedClassroomId && $classrooms->isNotEmpty()) {
            $selectedClassroomId = $classrooms->first()->id;
        }

        $existingPeriods = MonthlyReport::select('period_title')->distinct()->pluck('period_title')->toArray();
        $selectedPeriod = $request->query('period_title', $existingPeriods[0] ?? null);

        // Only modules that still have at least one active field are worth tracking;
        // a module with every field disabled has nothing left to fill in.
        $moduleFields = collect(ModuleField::MODULES)->keys()->mapWithKeys(
            fn ($module) => [$module => ModuleField::getActiveFields($module)]
        )->filter(fn ($fields) => $fields->isNotEmpty());

        $reports = collect();
        $moduleCompleteCounts = array_fill_keys($moduleFields->keys()->all(), 0);
        $readyToPublishCount = 0;

        if ($selectedClassroomId && $selectedPeriod) {
            $reports = MonthlyReport::with(['student', 'record'])
                ->whereHas('student', function ($q) use ($selectedClassroomId) {
                    $q->where('classroom_id', $selectedClassroomId);
                })
                ->where('period_title', $selectedPeriod)
                ->join('students', 'monthly_reports.student_id', '=', 'students.id')
                ->orderBy('students.name')
                ->select('monthly_reports.*')
                ->get();

            foreach ($reports as $report) {
                $record = $report->record ?? new ReportRecord;
                foreach ($moduleFields->keys() as $module) {
                    if ($record->isModuleComplete($module)) {
                        $moduleCompleteCounts[$module]++;
                    }
                }

                if ($report->status !== 'published' && $record->isFullyComplete()) {
                    $readyToPublishCount++;
                }
            }
        }

        $totalStudents = $reports->count();

        return view('reports.completeness', compact(
            'classrooms',
            'selectedClassroomId',
            'existingPeriods',
            'selectedPeriod',
            'moduleFields',
            'moduleCompleteCounts',
            'reports',
            'totalStudents',
            'readyToPublishCount'
        ));
    }

    /**
     * Publish every complete draft report of a classroom for a period,
     * making them visible in the parent portal. Incomplete reports are left as draft.
     */
    public function publishClassroom(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'period_title' => 'required|string',
        ]);

        $drafts = MonthlyReport::with('record')
            ->where('period_title', $validated['period_title'])
            ->where('status', '!=', 'published')
            ->whereHas('student', fn ($q) => $q->where('classroom_id', $validated['classroom_id']))
            ->get();

        $published = 0;
        foreach ($drafts as $report) {
            if (($report->record ?? new ReportRecord)->isFullyComplete()) {
                $report->update(['status' => 'published']);
                $published++;
            }
        }

        $skipped = $drafts->count() - $published;
        $message = "{$published} laporan berhasil diterbitkan ke portal wali murid.";
        if ($skipped > 0) {
            $message .= " {$skipped} laporan belum lengkap sehingga tetap berstatus draft.";
        }

        return redirect()->route('reports.completeness', [
            'classroom_id' => $validated['classroom_id'],
            'period_title' => $validated['period_title'],
        ])->with('success', $message);
    }

    /**
     * Show the batch export configuration page.
     */
    public function batchExportForm(): View
    {
        $classrooms = Classroom::withCount('students')->orderBy('name')->get();
        $periods = MonthlyReport::select('period_title')->distinct()->pluck('period_title');

        return view('reports.batch_export', compact('classrooms', 'periods'));
    }

    /**
     * Handle batch export per classroom (Merged PDF or ZIP Archive).
     */
    public function batchExport(Request $request): Response|BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'period_title' => 'required|string',
            'classroom_id' => 'required|exists:classrooms,id',
            'format' => 'required|in:merged_pdf,zip',
        ]);

        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $periodTitle = $validated['period_title'];

        $reports = MonthlyReport::with(['student.classroom', 'record'])
            ->where('period_title', $periodTitle)
            ->whereHas('student', function ($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id);
            })
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', "Tidak ditemukan data laporan untuk Kelas {$classroom->name} pada periode '{$periodTitle}'.");
        }

        $institution = Institution::first() ?? new Institution([
            'name' => 'PONDOK PESANTREN CONTOH',
            'city' => 'KOTA CONTOH',
            'director_name' => 'Ust. Fulan, S.Pd.',
            'director_title' => 'Direktur Pesantren',
            'accent_color' => '#059669',
        ]);

        $cleanPeriod = str_replace([' ', '/', '\\', ':'], '_', $periodTitle);

        // Option 1: Merged Multi-page PDF
        if ($validated['format'] === 'merged_pdf') {
            $pdf = Pdf::loadView('reports.batch_pdf', [
                'reports' => $reports,
                'classroom' => $classroom,
                'periodTitle' => $periodTitle,
                'institution' => $institution,
            ])->setPaper('a4', 'portrait');

            return $pdf->download("Laporan_Kelas_{$classroom->name}_{$cleanPeriod}_Gabungan.pdf");
        }

        // Option 2: ZIP Archive containing individual PDFs
        $zipFileName = "Laporan_Kelas_{$classroom->name}_{$cleanPeriod}.zip";
        $zipFilePath = storage_path("app/{$zipFileName}");

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($reports as $rep) {
                $student = $rep->student;
                $record = $rep->record ?? new ReportRecord;

                $singlePdf = Pdf::loadView('reports.monthly_pdf', [
                    'report' => $rep,
                    'student' => $student,
                    'record' => $record,
                    'institution' => $institution,
                ])->setPaper('a4', 'portrait')->output();

                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $student->name);
                $singlePdfName = "{$student->nis}_{$safeName}.pdf";
                $zip->addFromString($singlePdfName, $singlePdf);
            }
            $zip->close();
        }

        return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
    }
}
