<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ParentPortalController extends Controller
{
    /**
     * Display parent dashboard with child's reports.
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        $student = $user->student;

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

        return view('parent.dashboard', compact('user', 'student', 'reports', 'latestReport', 'trends'));
    }

    /**
     * Preview PDF specifically for child's report.
     */
    public function previewReport($reportId): Response
    {
        $user = Auth::user();
        $student = $user->student;

        if (! $student) {
            abort(403, 'Akun Anda belum terhubung dengan data santri.');
        }

        $report = MonthlyReport::with(['student.classroom', 'record'])
            ->where('student_id', $student->id)
            ->where('id', $reportId)
            ->firstOrFail();

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
