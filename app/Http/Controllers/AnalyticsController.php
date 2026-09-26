<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\AnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Display the analytics and early warning monitoring board.
     */
    public function index(Request $request): View
    {
        $selectedClassroomId = $request->query('classroom_id') ? (int) $request->query('classroom_id') : null;
        $selectedRiskLevel = $request->query('risk_level');

        $classrooms = Classroom::orderBy('name')->get();
        $summaryStats = $this->analyticsService->getSummaryStats();
        $classAnalytics = $this->analyticsService->getClassProgressAnalytics();

        $earlyWarningStudents = $this->analyticsService->getEarlyWarningStudents(null, $selectedClassroomId);

        // Filter by risk level if requested
        if ($selectedRiskLevel) {
            $earlyWarningStudents = array_values(array_filter($earlyWarningStudents, function ($s) use ($selectedRiskLevel) {
                return $s['risk_level'] === $selectedRiskLevel;
            }));
        }

        return view('analytics.index', compact(
            'classrooms',
            'summaryStats',
            'classAnalytics',
            'earlyWarningStudents',
            'selectedClassroomId',
            'selectedRiskLevel'
        ));
    }

    /**
     * Update classroom curriculum target.
     */
    public function updateTarget(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'target_juz' => 'required|integer|min:1|max:30',
            'target_description' => 'nullable|string|max:150',
        ]);

        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $classroom->update([
            'target_juz' => $validated['target_juz'],
            'target_description' => $validated['target_description'],
        ]);

        return redirect()->route('analytics.index')
            ->with('success', "Alhamdulillah, target kurikulum Kelas {$classroom->name} berhasil diperbarui.");
    }
}
