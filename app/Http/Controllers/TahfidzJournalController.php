<?php

namespace App\Http\Controllers;

use App\Helpers\SurahHelper;
use App\Models\Classroom;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TahfidzJournal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TahfidzJournalController extends Controller
{
    /**
     * Display the spreadsheet-like grid input for fast batch entry matching IMS UX.
     */
    public function spreadsheet(Request $request): View
    {
        $classrooms = Classroom::orderBy('name')->get();
        $selectedClassroomId = $request->query('classroom_id');
        if (! $selectedClassroomId && $classrooms->isNotEmpty()) {
            $selectedClassroomId = $classrooms->first()->id;
        }

        $selectedMonth = $request->query('month', date('Y-m'));

        $selectedClass = $classrooms->firstWhere('id', $selectedClassroomId);
        $tahfizhDays = $selectedClass?->tahfizh_days ?? [1, 2, 3, 4, 5, 6];

        // Parse active days of the selected month according to class schedule and academic calendar
        $year = (int) date('Y', strtotime($selectedMonth.'-01'));
        $month = (int) date('m', strtotime($selectedMonth.'-01'));
        $daysInMonth = (int) date('t', strtotime($selectedMonth.'-01'));

        $holidays = Setting::getNationalHolidays($year);
        $classHolidaysRaw = Setting::get("class_holidays_{$year}");
        $classHolidays = $classHolidaysRaw ? json_decode($classHolidaysRaw, true) : [];

        $allDates = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $time = mktime(0, 0, 0, $month, $day, $year);
            $dayOfWeek = (int) date('N', $time);
            $dateString = date('Y-m-d', $time);

            $isClassHoliday = isset($classHolidays[$dateString]) && in_array((int) $selectedClass?->id, array_map('intval', (array) $classHolidays[$dateString]), true);

            if (in_array($dayOfWeek, $tahfizhDays, true) && ! in_array($dateString, $holidays, true) && ! $isClassHoliday) {
                $allDates[] = $dateString;
            }
        }

        // Group dates by week of the year
        $weeks = [];
        foreach ($allDates as $date) {
            $weekNum = date('W', strtotime($date));
            $weeks[$weekNum][] = $date;
        }

        $weeksList = [];
        $weekCounter = 1;
        $monthsName = [
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun',
            'Jul' => 'Jul', 'Aug' => 'Agt', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des',
        ];

        foreach ($weeks as $weekNum => $weekDates) {
            $startDate = reset($weekDates);
            $endDate = end($weekDates);

            $startDay = date('j', strtotime($startDate));
            $startMonth = $monthsName[date('M', strtotime($startDate))];

            $endDay = date('j', strtotime($endDate));
            $endMonth = $monthsName[date('M', strtotime($endDate))];

            if ($startMonth === $endMonth) {
                $label = "Pekan $weekCounter ($startDay - $endDay $startMonth)";
            } else {
                $label = "Pekan $weekCounter ($startDay $startMonth - $endDay $endMonth)";
            }

            $weeksList[$weekCounter] = [
                'label' => $label,
                'dates' => $weekDates,
            ];
            $weekCounter++;
        }

        $selectedWeek = $request->query('week', 'all');
        $dates = $allDates;
        if ($selectedWeek !== 'all' && isset($weeksList[$selectedWeek])) {
            $dates = $weeksList[$selectedWeek]['dates'];
        }

        $columns = [];
        foreach ($dates as $d) {
            $c = Carbon::parse($d)->locale('id');
            $columns[] = [
                'date' => $d,
                'label' => $c->translatedFormat('l'),
                'sub_label' => $c->translatedFormat('d M'),
            ];
        }

        $studentsQuery = Student::where('is_active', true)->with('classroom')->orderBy('name');
        if ($selectedClassroomId) {
            $studentsQuery->where('classroom_id', $selectedClassroomId);
        }
        $students = $studentsQuery->get();
        $studentIds = $students->pluck('id')->toArray();

        // 114 Surahs dataset with numbers, total ayah, and latin name
        $surahs = array_values(SurahHelper::all());

        // Load existing journals and map to [student_id][date]
        $attendancesMap = [];
        $hafalanRecordsMap = [];
        $lastHafalanMap = [];

        if (! empty($studentIds)) {
            $startDate = $selectedMonth.'-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            $journals = TahfidzJournal::whereIn('student_id', $studentIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('id')
                ->get();

            foreach ($journals as $journal) {
                $d = $journal->date->format('Y-m-d');
                $att = $journal->attendance ?: 'hadir';
                $attendancesMap[$journal->student_id][$d] = $att;

                if ($journal->surah || $journal->surah_id) {
                    $surahId = $journal->surah_id;
                    if (! $surahId && $journal->surah) {
                        $foundSurah = SurahHelper::findByName($journal->surah);
                        $surahId = $foundSurah ? $foundSurah['number'] : null;
                    }

                    $hafalanRecordsMap[$journal->student_id][$d][] = [
                        'id' => $journal->id,
                        'surah_id' => $surahId ? (string) $surahId : '',
                        'surah' => $journal->surah ?: '',
                        'ayah_start' => $journal->ayah_start,
                        'ayah_end' => $journal->ayah_end,
                        'score' => $journal->score !== null ? (string) $journal->score : '',
                        'grade' => $journal->grade ?: '',
                        'status' => $journal->status ?: 'passed',
                        'type' => $journal->type ?: 'ziyadah',
                        'notes' => $journal->notes ?: '',
                    ];
                }
            }

            // Calculate last hafalan & auto +1 next verse continuation for each student
            foreach ($students as $student) {
                $latest = TahfidzJournal::where('student_id', $student->id)
                    ->whereNotNull('surah')
                    ->orderBy('date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($latest) {
                    $sObj = $latest->surah_id ? SurahHelper::find($latest->surah_id) : SurahHelper::findByName($latest->surah);
                    if ($sObj) {
                        $lSurahId = (int) $sObj['number'];
                        $lAyahEnd = (int) ($latest->ayah_end ?: 1);
                        $totalAyah = (int) $sObj['total_ayah'];

                        if ($lAyahEnd < $totalAyah) {
                            $nSurahId = $lSurahId;
                            $nAyahStart = $lAyahEnd + 1;
                        } else {
                            $nSurahId = $lSurahId < 114 ? $lSurahId + 1 : 1;
                            $nAyahStart = 1;
                        }

                        $lastHafalanMap[$student->id] = [
                            'last_surah_id' => $lSurahId,
                            'last_ayah_end' => $lAyahEnd,
                            'next_surah_id' => $nSurahId,
                            'next_ayah_start' => $nAyahStart,
                        ];
                    } else {
                        $lastHafalanMap[$student->id] = null;
                    }
                } else {
                    $lastHafalanMap[$student->id] = null;
                }
            }
        }

        return view('tahfidz_journals.spreadsheet', compact(
            'classrooms',
            'selectedClassroomId',
            'selectedMonth',
            'weeksList',
            'selectedWeek',
            'dates',
            'columns',
            'students',
            'surahs',
            'attendancesMap',
            'hafalanRecordsMap',
            'lastHafalanMap'
        ));
    }

    /**
     * Batch store multiple journal entries from spreadsheet matrix or legacy form.
     */
    public function batchStore(Request $request): RedirectResponse|JsonResponse
    {
        $teacherId = Auth::id();

        // 1. Check if payload is in IMS Matrix structure: `records[student_id][dates][date]...`
        $records = $request->input('records');
        if (empty($records) && $request->has('records_json')) {
            $rawJson = $request->input('records_json');
            if (is_array($rawJson)) {
                $records = $rawJson;
            } elseif (is_string($rawJson) && filled($rawJson)) {
                $records = json_decode($rawJson, true);
            }
        }

        if (is_string($records) && filled($records)) {
            $records = json_decode($records, true);
        }

        if (is_array($records) && ! empty($records)) {
            $classroomId = $request->input('classroom_id');
            $selectedMonth = $request->input('month', date('Y-m'));
            $selectedWeek = $request->input('week', 'all');

            try {
                DB::transaction(function () use ($records, $teacherId) {
                    foreach ($records as $studentId => $studentData) {
                        $studentId = (int) $studentId;
                        $student = Student::find($studentId);
                        if (! $student) {
                            continue;
                        }

                        foreach ($studentData['dates'] ?? [] as $date => $cellData) {
                            $attendance = $cellData['attendance'] ?? null;
                            $hafalans = $cellData['hafalans'] ?? [];

                            // Filter valid hafalan items
                            $validHafalans = array_values(array_filter($hafalans, function ($h) {
                                return ! empty($h['surah_id']) || ! empty($h['surah']);
                            }));

                            // If hafalan is filled but attendance empty, default to 'hadir'
                            if (! empty($validHafalans) && empty($attendance)) {
                                $attendance = 'hadir';
                            }

                            // If absent (sakit, izin, alpa), clear any setoran entries and keep attendance
                            if (in_array($attendance, ['sakit', 'izin', 'alpa'], true)) {
                                TahfidzJournal::where('student_id', $studentId)
                                    ->whereDate('date', $date)
                                    ->delete();

                                TahfidzJournal::create([
                                    'student_id' => $studentId,
                                    'teacher_id' => $teacherId,
                                    'date' => $date,
                                    'attendance' => $attendance,
                                    'type' => 'ziyadah',
                                    'notes' => 'Presensi: '.ucfirst($attendance),
                                ]);

                                continue;
                            }

                            // If attendance is hadir or hafalans exist
                            if ($attendance === 'hadir' || ! empty($validHafalans)) {
                                $existing = TahfidzJournal::where('student_id', $studentId)
                                    ->whereDate('date', $date)
                                    ->get();

                                if (empty($validHafalans)) {
                                    // Record attendance only if no setoran
                                    if ($existing->isEmpty()) {
                                        TahfidzJournal::create([
                                            'student_id' => $studentId,
                                            'teacher_id' => $teacherId,
                                            'date' => $date,
                                            'attendance' => 'hadir',
                                            'type' => 'ziyadah',
                                        ]);
                                    } else {
                                        $existing->first()->update(['attendance' => 'hadir']);
                                    }

                                    continue;
                                }

                                // Delete previous entries and insert new list
                                TahfidzJournal::where('student_id', $studentId)
                                    ->whereDate('date', $date)
                                    ->delete();

                                foreach ($validHafalans as $h) {
                                    $surahId = ! empty($h['surah_id']) ? (int) $h['surah_id'] : null;
                                    $surahMeta = $surahId ? SurahHelper::find($surahId) : null;
                                    $surahName = $surahMeta ? $surahMeta['name_latin'] : ($h['surah'] ?? null);

                                    $ayahStart = filled($h['ayah_start'] ?? null) ? (int) $h['ayah_start'] : 1;
                                    $ayahEnd = filled($h['ayah_end'] ?? null) ? (int) $h['ayah_end'] : $ayahStart;

                                    $rawScore = $h['score'] ?? null;
                                    $score = (filled($rawScore) && is_numeric($rawScore)) ? (float) $rawScore : null;
                                    $grade = $h['grade'] ?? ($score ? $this->scoreToGrade($score) : null);
                                    $status = $h['status'] ?? 'passed';
                                    $type = in_array($h['type'] ?? '', ['ziyadah', 'murajaah', 'tahsin', 'tilawah', 'tasmi']) ? $h['type'] : 'ziyadah';
                                    $juz = $surahMeta ? $surahMeta['juz_start'] : null;

                                    TahfidzJournal::create([
                                        'student_id' => $studentId,
                                        'teacher_id' => $teacherId,
                                        'date' => $date,
                                        'attendance' => 'hadir',
                                        'type' => $type,
                                        'juz' => $juz,
                                        'surah_id' => $surahId,
                                        'surah' => $surahName,
                                        'ayah_start' => $ayahStart,
                                        'ayah_end' => $ayahEnd,
                                        'page_count' => ! empty($h['page_count']) ? (int) $h['page_count'] : null,
                                        'score' => $score,
                                        'grade' => $grade,
                                        'status' => $status,
                                        'notes' => $h['notes'] ?? null,
                                    ]);
                                }
                            }
                        }
                    }
                });

                if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Alhamdulillah, data perkembangan tahfidz kelas berhasil disimpan.',
                        'redirect' => route('tahfidz-journals.spreadsheet', [
                            'classroom_id' => $classroomId,
                            'month' => $selectedMonth,
                            'week' => $selectedWeek,
                        ]),
                    ]);
                }

                return redirect()->route('tahfidz-journals.spreadsheet', [
                    'classroom_id' => $classroomId,
                    'month' => $selectedMonth,
                    'week' => $selectedWeek,
                ])->with('success', 'Alhamdulillah, data perkembangan tahfidz kelas berhasil disimpan.');
            } catch (\Throwable $e) {
                if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal menyimpan: '.$e->getMessage(),
                    ], 422);
                }

                return back()->with('error', 'Gagal menyimpan perubahan: '.$e->getMessage());
            }
        }

        // 2. Fallback: Legacy flat `entries` array support
        $entries = $request->input('entries', []);
        if (! empty($entries) && is_array($entries)) {
            $savedCount = 0;

            foreach ($entries as $row) {
                if (empty($row['student_id']) || empty($row['type'])) {
                    continue;
                }

                $studentId = (int) $row['student_id'];
                $date = ! empty($row['date']) ? $row['date'] : now()->format('Y-m-d');
                $type = in_array($row['type'], ['ziyadah', 'murajaah', 'tahsin', 'tilawah', 'tasmi']) ? $row['type'] : 'ziyadah';
                $juz = ! empty($row['juz']) ? (int) $row['juz'] : null;
                $surah = ! empty($row['surah']) ? trim($row['surah']) : null;
                $ayahStart = ! empty($row['ayah_start']) ? (int) $row['ayah_start'] : null;
                $ayahEnd = ! empty($row['ayah_end']) ? (int) $row['ayah_end'] : null;
                $pageCount = ! empty($row['page_count']) ? (int) $row['page_count'] : null;
                $grade = ! empty($row['grade']) ? trim($row['grade']) : null;
                $notes = ! empty($row['notes']) ? trim($row['notes']) : null;

                TahfidzJournal::create([
                    'student_id' => $studentId,
                    'teacher_id' => $teacherId,
                    'date' => $date,
                    'attendance' => 'hadir',
                    'type' => $type,
                    'juz' => $juz,
                    'surah' => $surah,
                    'ayah_start' => $ayahStart,
                    'ayah_end' => $ayahEnd,
                    'page_count' => $pageCount,
                    'grade' => $grade,
                    'notes' => $notes,
                ]);

                $savedCount++;
            }

            if ($savedCount > 0) {
                return redirect()->route('tahfidz-journals.index')
                    ->with('success', "Alhamdulillah, {$savedCount} baris catatan jurnal tahfidz berhasil disimpan!");
            }
        }

        return back()->with('error', 'Tidak ada data jurnal yang dikirim untuk disimpan.');
    }

    /**
     * Map numeric score to Grade label.
     */
    private function scoreToGrade(float $score): string
    {
        if ($score >= 90) {
            return 'Mumtaz (A)';
        }
        if ($score >= 80) {
            return 'Jayyid Jiddan (B)';
        }
        if ($score >= 70) {
            return 'Jayyid (C)';
        }

        return 'Maqbul (D)';
    }

    /**
     * Display a listing of daily tahfidz journals.
     */
    public function index(Request $request): View
    {
        $classrooms = Classroom::orderBy('name')->get();
        $query = TahfidzJournal::with(['student.classroom', 'teacher'])->latest('date');

        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $journals = $query->paginate(20)->withQueryString();
        $students = Student::where('is_active', true)->orderBy('name')->get();

        return view('tahfidz_journals.index', compact('journals', 'classrooms', 'students'));
    }

    /**
     * Show the form for creating a new journal entry.
     */
    public function create(): View
    {
        $students = Student::where('is_active', true)->with('classroom')->orderBy('name')->get();

        return view('tahfidz_journals.create', compact('students'));
    }

    /**
     * Store a newly created journal entry in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'type' => 'required|in:ziyadah,murajaah,tahsin,tilawah,tasmi',
            'juz' => 'nullable|integer|min:1|max:30',
            'surah' => 'nullable|string|max:100',
            'ayah_start' => 'nullable|integer|min:1',
            'ayah_end' => 'nullable|integer|min:1',
            'page_count' => 'nullable|integer|min:1',
            'grade' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $validated['teacher_id'] = Auth::id();
        $validated['attendance'] = 'hadir';

        TahfidzJournal::create($validated);

        return redirect()->route('tahfidz-journals.index')
            ->with('success', 'Catatan jurnal tahfidz harian berhasil disimpan.');
    }

    /**
     * Show the form for editing the journal entry.
     */
    public function edit(TahfidzJournal $tahfidzJournal): View
    {
        $students = Student::where('is_active', true)->with('classroom')->orderBy('name')->get();

        return view('tahfidz_journals.edit', compact('tahfidzJournal', 'students'));
    }

    /**
     * Update the specified journal entry in storage.
     */
    public function update(Request $request, TahfidzJournal $tahfidzJournal): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'type' => 'required|in:ziyadah,murajaah,tahsin,tilawah,tasmi',
            'juz' => 'nullable|integer|min:1|max:30',
            'surah' => 'nullable|string|max:100',
            'ayah_start' => 'nullable|integer|min:1',
            'ayah_end' => 'nullable|integer|min:1',
            'page_count' => 'nullable|integer|min:1',
            'grade' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $tahfidzJournal->update($validated);

        return redirect()->route('tahfidz-journals.index')
            ->with('success', 'Catatan jurnal tahfidz berhasil diperbarui.');
    }

    /**
     * Remove the specified journal entry from storage.
     */
    public function destroy(TahfidzJournal $tahfidzJournal): RedirectResponse
    {
        $tahfidzJournal->delete();

        return redirect()->route('tahfidz-journals.index')
            ->with('success', 'Catatan jurnal tahfidz berhasil dihapus.');
    }

    /**
     * Remove multiple journal entries in bulk.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:tahfidz_journals,id',
        ]);

        $count = TahfidzJournal::whereIn('id', $validated['ids'])->delete();

        return redirect()->back()
            ->with('success', "Alhamdulillah, {$count} catatan jurnal tahfidz berhasil dihapus secara massal.");
    }

    /**
     * Synchronize and aggregate daily journals into Monthly Report Record.
     */
    public function syncToReport($reportId): RedirectResponse
    {
        $report = MonthlyReport::with('student')->findOrFail($reportId);
        $student = $report->student;

        // Fetch journals up to cut-off date (or all recent for this student)
        $journals = TahfidzJournal::where('student_id', $student->id)
            ->whereDate('date', '<=', $report->cutoff_date)
            ->whereNotNull('surah')
            ->orderBy('date', 'desc')
            ->get();

        if ($journals->isEmpty()) {
            return back()->with('error', "Belum ada catatan setoran jurnal tahfidz untuk ananda {$student->name}.");
        }

        // 1. Calculate Setoran Summary
        $ziyadahCount = $journals->where('type', 'ziyadah')->count();
        $murajaahCount = $journals->where('type', 'murajaah')->count();
        $tahsinCount = $journals->where('type', 'tahsin')->count();

        $setoranParts = [];
        if ($ziyadahCount > 0) {
            $setoranParts[] = "Ziyadah ({$ziyadahCount}x setoran)";
        }
        if ($murajaahCount > 0) {
            $setoranParts[] = "Muraja'ah ({$murajaahCount}x setoran)";
        }
        if ($tahsinCount > 0) {
            $setoranParts[] = "Tahsin ({$tahsinCount}x)";
        }
        $tahfidzSetoran = ! empty($setoranParts) ? implode(', ', $setoranParts) : 'Tahsin & Ziyadah';

        // 2. Calculate Akumulasi Tilawah
        $totalTilawahPages = $journals->whereIn('type', ['tilawah', 'ziyadah', 'murajaah'])->sum('page_count');
        if ($totalTilawahPages > 0) {
            $juzTotal = intdiv($totalTilawahPages, 20);
            $halamanSisa = $totalTilawahPages % 20;
            $tahfidzAkumulasi = "{$juzTotal} Juz {$halamanSisa} Hal";
        } else {
            $tahfidzAkumulasi = "{$journals->count()} Catatan Halaqah";
        }

        // 3. Calculate Rincian Juz
        $juzList = $journals->pluck('juz')->filter()->unique()->sort()->values();
        $surahList = $journals->pluck('surah')->filter()->unique()->take(3)->implode(', ');

        if ($juzList->isNotEmpty()) {
            $tahfidzRincian = 'Juz '.$juzList->implode(', ');
            if ($surahList) {
                $tahfidzRincian .= " ({$surahList})";
            }
        } else {
            $tahfidzRincian = $surahList ?: 'Juz 30';
        }

        // 4. Generate Aggregated Notes
        $grades = $journals->pluck('grade')->filter()->countBy();
        $topGrade = $grades->keys()->first() ?? 'Jayyid';
        $latestNote = $journals->pluck('notes')->filter()->first();

        $summaryNote = "Alhamdulillah capaian halaqah ananda berkategori {$topGrade}. ";
        if ($latestNote) {
            $summaryNote .= "Catatan ustadz: \"{$latestNote}\"";
        } else {
            $summaryNote .= "Ananda istiqomah dan disiplin dalam mengikuti halaqah Al-Qur'an.";
        }

        // Update ReportRecord
        $record = ReportRecord::firstOrCreate(['monthly_report_id' => $report->id]);
        $record->update([
            'tahfidz_setoran' => $tahfidzSetoran,
            'tahfidz_akumulasi' => $tahfidzAkumulasi,
            'tahfidz_rincian_juz' => $tahfidzRincian,
            'tahfidz_notes' => $summaryNote,
        ]);

        return back()->with('success', "Data tahfidz ananda {$student->name} berhasil ditarik otomatis dari {$journals->count()} catatan jurnal!");
    }
}
