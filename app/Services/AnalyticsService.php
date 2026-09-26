<?php

namespace App\Services;

use App\Helpers\SurahHelper;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TahfidzJournal;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get curriculum progress analytics for all classrooms.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getClassProgressAnalytics(): array
    {
        $classrooms = Classroom::with(['students' => function ($q) {
            $q->where('is_active', true)->orderBy('name');
        }])->orderBy('name')->get();

        $allSurahs = SurahHelper::all();
        $analytics = [];

        foreach ($classrooms as $classroom) {
            $targetJuz = $classroom->effective_target_juz;
            $students = $classroom->students;
            $totalStudents = $students->count();

            // Find surahs belonging to the target juz
            $targetSurahs = array_filter($allSurahs, function ($s) use ($targetJuz) {
                return $s['juz_start'] <= $targetJuz && $s['juz_end'] >= $targetJuz;
            });
            $targetSurahNumbers = array_keys($targetSurahs);
            $targetCount = count($targetSurahNumbers) ?: 20; // Default count benchmark

            $completedStudentsCount = 0;
            $inProgressStudentsCount = 0;
            $notStartedStudentsCount = 0;
            $studentProgressList = [];

            if ($totalStudents > 0) {
                $studentIds = $students->pluck('id')->toArray();

                // Fetch all passed or ziyadah journals for these students in target juz/surahs
                $journals = TahfidzJournal::whereIn('student_id', $studentIds)
                    ->where(function ($q) use ($targetJuz, $targetSurahNumbers) {
                        $q->where('juz', $targetJuz)
                            ->orWhereIn('surah_id', $targetSurahNumbers)
                            ->orWhere('type', 'ziyadah');
                    })
                    ->get()
                    ->groupBy('student_id');

                foreach ($students as $student) {
                    $studentJournals = $journals->get($student->id, collect());

                    // Count unique completed surahs or pages towards the target
                    $uniqueSurahs = $studentJournals->pluck('surah_id')->filter()->unique();
                    $uniqueSurahNames = $studentJournals->pluck('surah')->filter()->unique();
                    $completedUnits = max($uniqueSurahs->count(), $uniqueSurahNames->count());

                    // If student has direct page count accumulation
                    $totalPages = $studentJournals->sum('page_count');
                    if ($completedUnits === 0 && $totalPages > 0) {
                        $completedUnits = min($targetCount, (int) ceil($totalPages / 1.5));
                    }

                    $percentage = $targetCount > 0 ? min(100.0, round(($completedUnits / $targetCount) * 100, 1)) : 0.0;

                    if ($percentage >= 100.0) {
                        $completedStudentsCount++;
                        $statusLabel = 'Tuntas Target';
                    } elseif ($percentage > 0.0) {
                        $inProgressStudentsCount++;
                        $statusLabel = 'Sedang Berproses';
                    } else {
                        $notStartedStudentsCount++;
                        $statusLabel = 'Belum Mulai';
                    }

                    $studentProgressList[] = [
                        'student' => $student,
                        'completed_units' => $completedUnits,
                        'target_units' => $targetCount,
                        'percentage' => $percentage,
                        'status_label' => $statusLabel,
                        'last_journal' => $studentJournals->sortByDesc('date')->first(),
                    ];
                }
            }

            // Calculate class average percentage
            $sumPercentage = array_sum(array_column($studentProgressList, 'percentage'));
            $averagePercentage = $totalStudents > 0 ? round($sumPercentage / $totalStudents, 1) : 0.0;

            // Sort student progress descending to find top achievers
            usort($studentProgressList, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);
            $topAchievers = array_slice($studentProgressList, 0, 3);

            $analytics[] = [
                'classroom' => $classroom,
                'target_juz' => $targetJuz,
                'target_label' => $classroom->target_label,
                'total_students' => $totalStudents,
                'completed_students' => $completedStudentsCount,
                'in_progress_students' => $inProgressStudentsCount,
                'not_started_students' => $notStartedStudentsCount,
                'average_percentage' => $averagePercentage,
                'top_achievers' => $topAchievers,
                'all_students_progress' => $studentProgressList,
            ];
        }

        return $analytics;
    }

    /**
     * Get Early Warning list of students who need guidance / attention.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEarlyWarningStudents(?int $limit = null, ?int $classroomId = null): array
    {
        $studentsQuery = Student::where('is_active', true)->with(['classroom', 'parentUser']);

        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }

        $students = $studentsQuery->get();
        if ($students->isEmpty()) {
            return [];
        }

        $now = Carbon::now();
        $thirtyDaysAgo = Carbon::now()->subDays(30)->toDateString();

        $studentIds = $students->pluck('id')->toArray();
        $journals = TahfidzJournal::whereIn('student_id', $studentIds)
            ->whereDate('date', '>=', $thirtyDaysAgo)
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('student_id');

        $latestAllJournals = TahfidzJournal::whereIn('student_id', $studentIds)
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('student_id');

        $institution = Institution::first();
        $institutionName = $institution->name ?? 'SD/MI Contoh';

        $warningList = [];

        foreach ($students as $student) {
            $recentJournals = $journals->get($student->id, collect());
            $allStudentJournals = $latestAllJournals->get($student->id, collect());
            $latestJournal = $allStudentJournals->first();

            $riskScore = 0;
            $reasons = [];

            // 1. Check days since last setoran (Ziyadah or any)
            if (! $latestJournal) {
                $riskScore += 50;
                $daysInactive = 30;
                $reasons[] = 'Belum ada riwayat setoran halaqah tercatat';
            } else {
                $latestDate = Carbon::parse($latestJournal->date);
                $daysInactive = (int) $latestDate->diffInDays($now, false);

                if ($daysInactive >= 14) {
                    $riskScore += 40;
                    $reasons[] = "Tidak ada setoran selama {$daysInactive} hari terakhir";
                } elseif ($daysInactive >= 7) {
                    $riskScore += 25;
                    $reasons[] = "Belum menyetor hafalan selama {$daysInactive} hari";
                }
            }

            // 2. Check frequency of 'need_repeat' or low grades in last 30 days
            $repeatCount = $recentJournals->filter(function ($j) {
                return ($j->status === 'need_repeat')
                    || (is_string($j->grade) && str_contains(strtolower($j->grade), 'maqbul'))
                    || ($j->score !== null && $j->score < 70);
            })->count();

            if ($repeatCount >= 3) {
                $riskScore += 35;
                $reasons[] = "{$repeatCount}x berstatus 'Perlu Diulang' / remidi dalam 30 hari terakhir";
            } elseif ($repeatCount >= 1) {
                $riskScore += 15;
                $reasons[] = "{$repeatCount}x tercatat butuh pengulangan hafalan";
            }

            // 3. Check attendance absences (alpha / izin / sakit)
            $absentCount = $recentJournals->whereIn('attendance', ['alpa', 'izin', 'sakit'])->count();
            if ($absentCount >= 3) {
                $riskScore += 20;
                $reasons[] = "{$absentCount}x tidak hadir pada halaqah 30 hari terakhir";
            }

            // Only include in warning list if risk score is noticeable (>= 15)
            if ($riskScore >= 15) {
                if ($riskScore >= 50) {
                    $riskLevel = 'critical';
                    $riskLabel = 'Kritis (Tindakan Segera)';
                    $riskColor = 'rose';
                } elseif ($riskScore >= 25) {
                    $riskLevel = 'warning';
                    $riskLabel = 'Perlu Perhatian';
                    $riskColor = 'amber';
                } else {
                    $riskLevel = 'monitoring';
                    $riskLabel = 'Dalam Pantauan';
                    $riskColor = 'blue';
                }

                // Format WhatsApp URL for quick parent contact
                $parentPhone = $student->parent_phone ?: ($student->parentUser?->phone ?? '');
                // Clean phone number (e.g. 0812... -> 62812...)
                $cleanPhone = preg_replace('/[^0-9]/', '', $parentPhone);
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '62'.substr($cleanPhone, 1);
                }

                $primaryReason = $reasons[0] ?? 'Perlu bimbingan tahfizh';
                $waMessage = "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n"
                    ."Yth. Bapak/Ibu Wali dari ananda *{$student->name}* (Kelas {$student->classroom?->name}).\n"
                    ."Kami dari tim musyrif tahfizh *{$institutionName}* ingin menginformasikan perkembangan halaqah ananda:\n\n"
                    ."Catatan: {$primaryReason}.\n\n"
                    ."Mohon bantuan dan dukungannya di rumah untuk memotivasi ananda dalam muroja'ah hafalan Al-Qur'an. Jazakumullahu Khairan Katsiran.";

                $waLink = $cleanPhone ? 'https://wa.me/'.$cleanPhone.'?text='.urlencode($waMessage) : null;

                $warningList[] = [
                    'student' => $student,
                    'risk_score' => $riskScore,
                    'risk_level' => $riskLevel,
                    'risk_label' => $riskLabel,
                    'risk_color' => $riskColor,
                    'days_inactive' => $daysInactive ?? 0,
                    'repeat_count' => $repeatCount,
                    'reasons' => $reasons,
                    'primary_reason' => $primaryReason,
                    'latest_journal' => $latestJournal,
                    'parent_phone' => $parentPhone,
                    'wa_link' => $waLink,
                ];
            }
        }

        // Sort by risk score descending
        usort($warningList, fn ($a, $b) => $b['risk_score'] <=> $a['risk_score']);

        if ($limit !== null) {
            return array_slice($warningList, 0, $limit);
        }

        return $warningList;
    }

    /**
     * Get executive summary statistics for analytics dashboard.
     *
     * @return array<string, mixed>
     */
    public function getSummaryStats(): array
    {
        $classAnalytics = $this->getClassProgressAnalytics();
        $totalStudents = Student::where('is_active', true)->count();

        $totalCompleted = 0;
        $totalPercentages = 0;
        $activeClassesCount = count($classAnalytics);

        foreach ($classAnalytics as $ca) {
            $totalCompleted += $ca['completed_students'];
            $totalPercentages += $ca['average_percentage'];
        }

        $overallProgress = $activeClassesCount > 0 ? round($totalPercentages / $activeClassesCount, 1) : 0.0;
        $warningStudents = $this->getEarlyWarningStudents();

        $criticalCount = count(array_filter($warningStudents, fn ($s) => $s['risk_level'] === 'critical'));
        $warningCount = count(array_filter($warningStudents, fn ($s) => $s['risk_level'] === 'warning'));
        $monitoringCount = count(array_filter($warningStudents, fn ($s) => $s['risk_level'] === 'monitoring'));

        return [
            'total_students' => $totalStudents,
            'total_classes' => $activeClassesCount,
            'overall_curriculum_progress' => $overallProgress,
            'total_completed_target' => $totalCompleted,
            'total_warning_students' => count($warningStudents),
            'critical_count' => $criticalCount,
            'warning_count' => $warningCount,
            'monitoring_count' => $monitoringCount,
        ];
    }
}
