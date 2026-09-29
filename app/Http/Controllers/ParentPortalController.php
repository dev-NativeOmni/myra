<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ParentPortalController extends Controller
{
    /**
     * Display the parent dashboard for one of their linked children.
     * Parents with several children switch between them with ?student={id}.
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $children = $user->children()->with('classroom')->get();
        $student = $children->firstWhere('id', (int) $request->query('student')) ?? $children->first();

        $reports = collect();
        $trends = null;
        if ($student) {
            $reports = MonthlyReport::with(['student.classroom', 'record'])
                ->where('student_id', $student->id)
                ->where('status', 'published')
                ->latest('report_date')
                ->get();
            $trends = $student->getProgressTrends();
        }

        $latestReport = $reports->first();

        return view('parent.dashboard', compact('user', 'children', 'student', 'reports', 'latestReport', 'trends'));
    }

    /**
     * Preview the PDF of a published report belonging to one of the parent's children.
     */
    public function previewReport(Request $request, $reportId): Response
    {
        $childIds = $request->user()->children()->pluck('students.id');

        if ($childIds->isEmpty()) {
            abort(403, 'Akun Anda belum terhubung dengan data santri.');
        }

        $report = MonthlyReport::with(['student.classroom', 'record'])
            ->whereIn('student_id', $childIds)
            ->where('status', 'published')
            ->where('id', $reportId)
            ->firstOrFail();
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

        return $pdf->stream("Laporan_Bulanan_{$student->nis}_{$student->name}_{$report->period_title}.pdf");
    }
}
