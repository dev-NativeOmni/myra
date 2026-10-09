<?php

namespace App\Console\Commands;

use App\Helpers\SurahHelper;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\ModuleField;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Student;
use App\Models\TahfidzJournal;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedMitqDummy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-mitq-dummy
                            {--token=MITQ : Token of the existing institution to fill}
                            {--password= : Password for every dummy account (random when omitted)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill an existing institution (default MITQ) with dummy accounts, students, tahfidz journals, and monthly reports';

    private const JOURNAL_DAYS = 28;

    /**
     * Classrooms with their tahfidz target and the surahs (ascending order of memorisation) taught there.
     *
     * @var array<string, array{juz: int, description: string, surahs: list<int>, teacher: string}>
     */
    private const CLASSROOMS = [
        'Kelas 1' => ['juz' => 30, 'description' => "Juz 30 (Juz 'Amma)", 'surahs' => [114, 113, 112, 111, 110, 109, 108, 107, 106, 105, 104, 103, 102, 101, 100, 99, 98, 97, 96, 95, 94, 93], 'teacher' => 'guru'],
        'Kelas 2' => ['juz' => 29, 'description' => 'Juz 29 (Tabarak)', 'surahs' => [67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77], 'teacher' => 'guru'],
        'Kelas 3' => ['juz' => 28, 'description' => "Juz 28 (Qad Sami'a)", 'surahs' => [58, 59, 60, 61, 62, 63, 64, 65, 66], 'teacher' => 'guru2'],
    ];

    /**
     * Dummy students. Profile drives journal scores, attendance, and report values so every
     * screen (analytics early warning, completeness, parent portal) has something to show.
     *
     * @var list<array{nis: string, name: string, gender: string, classroom: string, profile: string}>
     */
    private const STUDENTS = [
        ['nis' => 'MITQ-001', 'name' => 'Abdurrahman Al-Fatih', 'gender' => 'L', 'classroom' => 'Kelas 1', 'profile' => 'excellent'],
        ['nis' => 'MITQ-002', 'name' => 'Muhammad Rayyan', 'gender' => 'L', 'classroom' => 'Kelas 1', 'profile' => 'good'],
        ['nis' => 'MITQ-003', 'name' => 'Aisyah Humaira', 'gender' => 'P', 'classroom' => 'Kelas 2', 'profile' => 'excellent'],
        ['nis' => 'MITQ-004', 'name' => 'Fatimah Az-Zahra', 'gender' => 'P', 'classroom' => 'Kelas 2', 'profile' => 'good'],
        ['nis' => 'MITQ-005', 'name' => 'Zaid bin Tsabit', 'gender' => 'L', 'classroom' => 'Kelas 3', 'profile' => 'good'],
        ['nis' => 'MITQ-006', 'name' => 'Ali Zainal Abidin', 'gender' => 'L', 'classroom' => 'Kelas 3', 'profile' => 'excellent'],
        ['nis' => 'MITQ-007', 'name' => 'Umar Faruq Hakim', 'gender' => 'L', 'classroom' => 'Kelas 1', 'profile' => 'struggling'],
        ['nis' => 'MITQ-008', 'name' => 'Khadijah Nur Aini', 'gender' => 'P', 'classroom' => 'Kelas 1', 'profile' => 'good'],
        ['nis' => 'MITQ-009', 'name' => 'Maryam Shalihah', 'gender' => 'P', 'classroom' => 'Kelas 1', 'profile' => 'excellent'],
        ['nis' => 'MITQ-010', 'name' => 'Hamzah Asadullah', 'gender' => 'L', 'classroom' => 'Kelas 2', 'profile' => 'absent'],
        ['nis' => 'MITQ-011', 'name' => 'Bilal Rabbani', 'gender' => 'L', 'classroom' => 'Kelas 2', 'profile' => 'good'],
        ['nis' => 'MITQ-012', 'name' => 'Zainab Kamila', 'gender' => 'P', 'classroom' => 'Kelas 2', 'profile' => 'good'],
        ['nis' => 'MITQ-013', 'name' => 'Salman Al-Farisi', 'gender' => 'L', 'classroom' => 'Kelas 3', 'profile' => 'good'],
        ['nis' => 'MITQ-014', 'name' => 'Yusuf Habibullah', 'gender' => 'L', 'classroom' => 'Kelas 3', 'profile' => 'struggling'],
        ['nis' => 'MITQ-015', 'name' => 'Ruqayyah Azzahra', 'gender' => 'P', 'classroom' => 'Kelas 3', 'profile' => 'excellent'],
    ];

    /**
     * Dummy accounts keyed by username prefix (the suffix is the lower-cased token).
     *
     * @var array<string, array{name: string, role: string, classrooms?: list<string>, children?: list<string>}>
     */
    private const ACCOUNTS = [
        'admin' => ['name' => 'Ustadz Hanif (Admin Demo)', 'role' => User::ROLE_ADMIN],
        'guru' => ['name' => 'Ustadz Abdullah (Guru Tahfidz)', 'role' => User::ROLE_GURU, 'classrooms' => ['Kelas 1', 'Kelas 2']],
        'guru2' => ['name' => 'Ustadz Harun (Guru Tahfidz)', 'role' => User::ROLE_GURU, 'classrooms' => ['Kelas 3']],
        'walikelas' => ['name' => 'Ustadz Ibrahim (Wali Kelas)', 'role' => User::ROLE_WALI_KELAS, 'classrooms' => ['Kelas 1', 'Kelas 2', 'Kelas 3']],
        'kesantrian' => ['name' => 'Ustadz Salman (Kesantrian)', 'role' => User::ROLE_KESANTRIAN, 'classrooms' => ['Kelas 1', 'Kelas 2', 'Kelas 3']],
        'tu' => ['name' => 'Ustadzah Nurul (Tata Usaha)', 'role' => User::ROLE_TU],
        'walimurid' => ['name' => 'Bapak Rahmat Hidayat (Wali Santri)', 'role' => User::ROLE_WALI_MURID, 'children' => ['MITQ-001', 'MITQ-003']],
        'walimurid2' => ['name' => 'Ibu Siti Aminah (Wali Santri)', 'role' => User::ROLE_WALI_MURID, 'children' => ['MITQ-010']],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = strtoupper((string) $this->option('token'));
        $institution = Institution::where('token', $token)->first();

        if (! $institution) {
            $this->error("Lembaga dengan token {$token} tidak ditemukan. Buat lembaganya dulu lewat panel Super Admin.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: Str::password(12, symbols: false);
        $suffix = '_'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $token));

        $this->info("Mengisi data dummy untuk {$institution->name} ({$token})...");
        TenantContext::setTenant($institution);

        try {
            $accounts = DB::transaction(function () use ($institution, $password, $suffix): array {
                ModuleField::seedDefaultFields();

                $classrooms = $this->seedClassrooms($institution);
                $students = $this->seedStudents($institution, $classrooms);
                $accounts = $this->seedAccounts($institution, $password, $suffix, $classrooms, $students);

                $this->seedJournals($institution, $students, $classrooms, $accounts);
                $this->seedMonthlyReports($institution, $students);

                return $accounts;
            });
        } finally {
            TenantContext::clear();
        }

        $this->newLine();
        $this->info('Selesai. Akun dummy (semua memakai kata sandi yang sama):');
        $this->table(
            ['Peran', 'Nama', 'Username', 'Kata Sandi'],
            collect($accounts)->map(fn (User $user) => [$user->role_label, $user->name, $user->username, $password])->values()->all(),
        );
        $this->warn('Simpan kata sandi ini sekarang; tidak ditampilkan lagi. Jalankan ulang command untuk membuat kata sandi baru.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, Classroom>
     */
    private function seedClassrooms(Institution $institution): array
    {
        return collect(self::CLASSROOMS)->mapWithKeys(fn (array $data, string $name) => [
            $name => Classroom::firstOrCreate(
                ['institution_id' => $institution->id, 'name' => $name],
                [
                    'tahfizh_days' => [1, 2, 3, 4, 5, 6],
                    'target_juz' => $data['juz'],
                    'target_description' => $data['description'],
                ],
            ),
        ])->all();
    }

    /**
     * Create the dummy students, skipping any NIS already used by a real student.
     *
     * @param  array<string, Classroom>  $classrooms
     * @return array<string, array{model: Student, profile: string, classroom: string}>
     */
    private function seedStudents(Institution $institution, array $classrooms): array
    {
        $students = [];

        foreach (self::STUDENTS as $data) {
            $student = Student::firstOrCreate(
                ['institution_id' => $institution->id, 'nis' => $data['nis']],
                [
                    'name' => $data['name'],
                    'gender' => $data['gender'],
                    'classroom_id' => $classrooms[$data['classroom']]->id,
                    'is_active' => true,
                ],
            );

            if ($student->name !== $data['name']) {
                $this->warn("NIS {$data['nis']} sudah dipakai santri '{$student->name}', dilewati.");

                continue;
            }

            // Dummy students from an earlier run may sit in another class; keep them in sync.
            $student->update(['classroom_id' => $classrooms[$data['classroom']]->id, 'gender' => $data['gender'], 'is_active' => true]);

            $students[$data['nis']] = ['model' => $student, 'profile' => $data['profile'], 'classroom' => $data['classroom']];
        }

        return $students;
    }

    /**
     * @param  array<string, Classroom>  $classrooms
     * @param  array<string, array{model: Student, profile: string, classroom: string}>  $students
     * @return array<string, User>
     */
    private function seedAccounts(Institution $institution, string $password, string $suffix, array $classrooms, array $students): array
    {
        $accounts = [];
        $hashedPassword = Hash::make($password);

        foreach (self::ACCOUNTS as $prefix => $data) {
            $username = $prefix.$suffix;
            $email = $username.'@myra.id';
            $user = User::withoutGlobalScopes()->where('username', $username)->first();

            $usedElsewhere = $user
                ? $user->institution_id !== $institution->id
                : User::withoutGlobalScopes()->where('email', $email)->exists();

            if ($usedElsewhere) {
                $this->warn("Username/email {$username} sudah dipakai lembaga lain, dilewati.");

                continue;
            }

            $user ??= new User(['institution_id' => $institution->id, 'username' => $username, 'email' => $email]);
            $user->fill(['name' => $data['name'], 'role' => $data['role'], 'password' => $hashedPassword])->save();

            if (isset($data['classrooms'])) {
                $user->classrooms()->sync(collect($data['classrooms'])->map(fn (string $name) => $classrooms[$name]->id));
            }

            if (isset($data['children'])) {
                $user->children()->sync(collect($data['children'])->filter(fn (string $nis) => isset($students[$nis]))->map(fn (string $nis) => $students[$nis]['model']->id));
            }

            $accounts[$prefix] = $user;
        }

        return $accounts;
    }

    /**
     * Fill the last four weeks of halaqah days with attendance, ziyadah, and muraja'ah entries.
     *
     * @param  array<string, array{model: Student, profile: string, classroom: string}>  $students
     * @param  array<string, Classroom>  $classrooms
     * @param  array<string, User>  $accounts
     */
    private function seedJournals(Institution $institution, array $students, array $classrooms, array $accounts): void
    {
        $today = CarbonImmutable::today();
        $studentIndex = 0;

        foreach ($students as $student) {
            $classroomData = self::CLASSROOMS[$student['classroom']];
            $teacherId = ($accounts[$classroomData['teacher']] ?? null)?->id;
            $tahfizhDays = $classrooms[$student['classroom']]->tahfizh_days ?: [1, 2, 3, 4, 5, 6];
            $surahs = $classroomData['surahs'];
            $sessionIndex = 0;

            for ($daysAgo = self::JOURNAL_DAYS; $daysAgo >= 1; $daysAgo--) {
                $date = $today->subDays($daysAgo);

                if (! in_array($date->dayOfWeekIso, $tahfizhDays, true)) {
                    continue;
                }

                $attendance = $this->attendanceFor($student['profile'], $sessionIndex, $studentIndex);
                $base = ['institution_id' => $institution->id, 'teacher_id' => $teacherId, 'attendance' => $attendance];

                if ($attendance !== 'hadir') {
                    $this->saveJournal($student['model'], $date, 'ziyadah', [...$base, 'juz' => null, 'surah_id' => null, 'surah' => null, 'ayah_start' => null, 'ayah_end' => null,
                        'page_count' => null, 'score' => null, 'grade' => null, 'status' => null, 'notes' => 'Presensi: '.ucfirst($attendance)]);
                    $sessionIndex++;

                    continue;
                }

                $surah = SurahHelper::find($surahs[($studentIndex + $sessionIndex) % count($surahs)]);
                $score = $this->scoreFor($student['profile'], $sessionIndex + $studentIndex);
                $this->saveJournal($student['model'], $date, 'ziyadah', [...$base, ...$this->setoran($surah, $score, min($surah['total_ayah'], 5 + ($sessionIndex % 3) * 5))]);

                if ($sessionIndex % 3 === 2) {
                    $reviewSurah = SurahHelper::find($surahs[($studentIndex + $sessionIndex - 2) % count($surahs)]);
                    $this->saveJournal($student['model'], $date, 'murajaah', [...$base, ...$this->setoran($reviewSurah, min(100, $score + 4), $reviewSurah['total_ayah'])]);
                }

                $sessionIndex++;
            }

            $studentIndex++;
        }
    }

    /**
     * Upsert one journal entry; the date column is cast, so it must be matched with whereDate.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function saveJournal(Student $student, CarbonImmutable $date, string $type, array $attributes): void
    {
        $journal = TahfidzJournal::query()
            ->where('student_id', $student->id)
            ->whereDate('date', $date)
            ->where('type', $type)
            ->first() ?? new TahfidzJournal(['student_id' => $student->id, 'date' => $date->toDateString(), 'type' => $type]);

        $journal->fill($attributes)->save();
    }

    /**
     * @param  array{number: int, name_latin: string, total_ayah: int, juz_start: int}  $surah
     * @return array<string, mixed>
     */
    private function setoran(array $surah, int $score, int $ayahEnd): array
    {
        $needsRepeat = $score < 70;

        return [
            'juz' => $surah['juz_start'],
            'surah_id' => $surah['number'],
            'surah' => $surah['name_latin'],
            'ayah_start' => 1,
            'ayah_end' => $ayahEnd,
            'page_count' => 1,
            'score' => $score,
            'grade' => $this->gradeFor($score),
            'status' => $needsRepeat ? 'need_repeat' : 'passed',
            'notes' => $needsRepeat
                ? 'Hafalan belum lancar, perlu diulang pada pertemuan berikutnya.'
                : ($score >= 90 ? 'Lancar dengan makhraj dan tajwid yang baik.' : 'Cukup lancar, perhatikan panjang pendek bacaan.'),
        ];
    }

    private function attendanceFor(string $profile, int $sessionIndex, int $studentIndex): string
    {
        if ($profile === 'absent' && $sessionIndex % 4 === 1) {
            return ['alpa', 'izin', 'sakit'][intdiv($sessionIndex, 4) % 3];
        }

        // An occasional sick day for everyone else, staggered per student.
        return ($sessionIndex + $studentIndex) % 17 === 0 ? 'sakit' : 'hadir';
    }

    private function scoreFor(string $profile, int $seed): int
    {
        return match ($profile) {
            'excellent' => 90 + ($seed * 7) % 9,
            'struggling' => 60 + ($seed * 5) % 16,
            default => 78 + ($seed * 3) % 12,
        };
    }

    private function gradeFor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Mumtaz (A)',
            $score >= 80 => 'Jayyid Jiddan (B)',
            $score >= 70 => 'Jayyid (C)',
            default => 'Maqbul (D)',
        };
    }

    /**
     * Two published past months plus a partly filled current month (draft) for the completeness board.
     *
     * @param  array<string, array{model: Student, profile: string, classroom: string}>  $students
     */
    private function seedMonthlyReports(Institution $institution, array $students): void
    {
        $currentMonth = CarbonImmutable::today()->startOfMonth()->locale('id');
        $periods = [
            [$currentMonth->subMonths(2), 'published'],
            [$currentMonth->subMonth(), 'published'],
            [$currentMonth, 'draft'],
        ];

        $studentIndex = 0;

        foreach ($students as $student) {
            foreach ($periods as $periodIndex => [$month, $status]) {
                $report = MonthlyReport::updateOrCreate(
                    ['institution_id' => $institution->id, 'student_id' => $student['model']->id, 'period_title' => mb_strtoupper($month->translatedFormat('F Y'))],
                    ['report_date' => $month->endOfMonth()->toDateString(), 'cutoff_date' => $month->endOfMonth()->subDays(2)->toDateString(), 'status' => $status],
                );

                $isComplete = $status === 'published' || $studentIndex % 3 === 0;
                $values = $this->reportValues($student, $month, $periodIndex, $studentIndex);

                if (! $isComplete) {
                    // Current month in progress: only the tahfidz teacher has filled in some students.
                    $values = $studentIndex % 3 === 1
                        ? array_intersect_key($values, array_flip(['tahfidz_setoran', 'tahfidz_akumulasi', 'tahfidz_rincian_juz', 'tahfidz_notes', 'is_baligh']))
                        : ['is_baligh' => $values['is_baligh']];
                }

                ReportRecord::updateOrCreate(['monthly_report_id' => $report->id], $values);
            }

            $studentIndex++;
        }
    }

    /**
     * @param  array{model: Student, profile: string, classroom: string}  $student
     * @return array<string, mixed>
     */
    private function reportValues(array $student, CarbonImmutable $month, int $periodIndex, int $studentIndex): array
    {
        $classroomData = self::CLASSROOMS[$student['classroom']];
        $classNumber = (int) filter_var($student['classroom'], FILTER_SANITIZE_NUMBER_INT);
        $surahs = $classroomData['surahs'];
        $firstSurah = SurahHelper::find($surahs[0])['name_latin'];
        $lastSurah = SurahHelper::find($surahs[min(count($surahs) - 1, 3 + $periodIndex * 2 + $studentIndex % 3)])['name_latin'];
        $name = $student['model']->name;
        $profile = $student['profile'];

        $adab = fn (int $offset): string => match ($profile) {
            'excellent' => 'A (Sangat Baik)',
            'struggling', 'absent' => $offset % 2 === 0 ? 'C (Cukup)' : 'B (Baik)',
            default => $offset % 3 === 0 ? 'A (Sangat Baik)' : 'B (Baik)',
        };

        $monthLabel = $month->translatedFormat('F Y');
        $hasArrears = $profile === 'absent' && $periodIndex > 0;

        return [
            'tahfidz_setoran' => "Ziyadah {$firstSurah} s.d. {$lastSurah}",
            'tahfidz_akumulasi' => ($periodIndex + $classNumber).' Juz '.(2 + $studentIndex % 8).' Hal',
            'tahfidz_rincian_juz' => $classroomData['description'],
            'tahfidz_notes' => match ($profile) {
                'excellent' => "Alhamdulillah, ananda {$name} sangat konsisten. Hafalan lancar dengan makhraj yang jelas.",
                'struggling' => "Ananda {$name} perlu pendampingan muraja'ah di rumah; beberapa setoran masih harus diulang.",
                'absent' => "Kehadiran ananda {$name} di halaqah perlu ditingkatkan agar target bulanan tercapai.",
                default => "Ananda {$name} menunjukkan perkembangan yang baik dan stabil di halaqah.",
            },
            'adab_ibadah' => $adab(0),
            'adab_akhlak' => $adab(1),
            'adab_kerapian' => $adab(2),
            'adab_kedisiplinan' => $adab(3),
            'body_height_cm' => 118 + $classNumber * 6 + $studentIndex % 5,
            'body_weight_kg' => 22 + $classNumber * 3 + $studentIndex % 4,
            'is_baligh' => $classNumber === 3 && $student['model']->gender === 'P' ? 'Sudah' : 'Belum',
            'kesantrian_notes' => $profile === 'absent'
                ? 'Beberapa kali izin dan alpa pada kegiatan asrama; sudah dikomunikasikan dengan wali santri.'
                : 'Aktif mengikuti kegiatan asrama, menjaga kebersihan kamar, dan rajin shalat berjamaah.',
            'academic_notes' => $profile === 'excellent'
                ? 'Sangat aktif di kelas dan membantu teman yang kesulitan memahami materi.'
                : 'Mengikuti pelajaran dengan tertib; perlu meningkatkan keaktifan bertanya.',
            'last_spp' => $hasArrears ? 'Belum lunas ('.$monthLabel.')' : $monthLabel,
            'last_laundry' => $monthLabel,
            'registration_status' => $hasArrears ? 'Belum Lunas' : 'Lunas',
        ];
    }
}
