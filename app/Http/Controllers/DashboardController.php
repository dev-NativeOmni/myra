<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\MonthlyReport;
use App\Models\Student;
use App\Services\AnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Display the main dashboard.
     */
    public function index(): View
    {
        $institution = Institution::first();
        $totalStudents = Student::count();
        $totalClassrooms = Classroom::count();
        $totalReports = MonthlyReport::count();
        $recentReports = MonthlyReport::with(['student.classroom'])
            ->latest()
            ->take(5)
            ->get();

        $classAnalytics = $this->analyticsService->getClassProgressAnalytics();
        $earlyWarningStudents = $this->analyticsService->getEarlyWarningStudents(4); // Top 4 priority
        $summaryStats = $this->analyticsService->getSummaryStats();

        return view('dashboard', compact(
            'institution',
            'totalStudents',
            'totalClassrooms',
            'totalReports',
            'recentReports',
            'classAnalytics',
            'earlyWarningStudents',
            'summaryStats'
        ));
    }
}
