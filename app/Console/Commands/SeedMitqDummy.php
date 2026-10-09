<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\ModuleField;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Student;
use App\Models\TahfidzJournal;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedMitqDummy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-mitq-dummy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed dummy users, classrooms, and students for MITQ Baitul Hikmah';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding MITQ Baitul Hikmah dummy data...');

        // 1. Ensure Institution exists
        $institution = Institution::withoutGlobalScopes()->where('token', 'MITQ')->first();

        if (! $institution) {
            $institution = Institution::withoutGlobalScopes()->where('name', 'like', '%MITQ%')->first();
        }

        if ($institution) {
            $institution->update([
                'name' => 'MITQ Baitul Hikmah',
                'token' => 'MITQ',
                'city' => 'Sukoharjo',
                'director_name' => 'Ust. Zaki Mizzanul Azhar, Lc.',
                'director_title' => 'Mudir Pesantren',
                'accent_color' => $institution->accent_color ?: '#059669',
                'is_active' => true,
            ]);
        } else {
            $institution = Institution::create([
                'name' => 'MITQ Baitul Hikmah',
                'token' => 'MITQ',
                'city' => 'Sukoharjo',
                'address' => 'Sukoharjo, Jawa Tengah',
                'phone' => '081234567891',
                'director_name' => 'Ust. Zaki Mizzanul Azhar, Lc.',
                'director_title' => 'Mudir Pesantren',
                'accent_color' => '#059669',
                'is_active' => true,
            ]);
        }

        TenantContext::setTenant($institution);

        // 2. Seed Default Module Assessment Fields if not present
        ModuleField::seedDefaultFields();

        // 3. Seed Classrooms
        $targets = [
            1 => ['juz' => 30, 'desc' => "Juz 30 (Juz 'Amma)"],
            2 => ['juz' => 29, 'desc' => 'Juz 29 (Tabarak)'],
            3 => ['juz' => 28, 'desc' => "Juz 28 (Qad Sami'a)"],
        ];

        $classrooms = [];
        for ($c = 1; $c <= 3; $c++) {
            $classrooms[$c] = Classroom::updateOrCreate(
                ['institution_id' => $institution->id, 'name' => 'Kelas '.$c],
                [
                    'tahfizh_days' => [1, 2, 3, 4, 5, 6],
                    'target_juz' => $targets[$c]['juz'],
                    'target_description' => $targets[$c]['desc'],
                ]
            );
        }

        // 4. Seed Staff Users
        $staffUsers = [
            [
                'name' => 'Ustadz Admin MITQ',
                'username' => 'admin_mitq',
                'email' => 'admin_mitq@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Ustadz Abdullah (Guru Tahfidz MITQ)',
                'username' => 'guru_mitq',
                'email' => 'guru_mitq@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_GURU,
            ],
            [
                'name' => 'Ustadz Ibrahim (Wali Kelas MITQ)',
                'username' => 'walikelas_mitq',
                'email' => 'walikelas_mitq@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_WALI_KELAS,
            ],
            [
                'name' => 'Ustadz Salman (Kesantrian MITQ)',
                'username' => 'kesantrian_mitq',
                'email' => 'kesantrian_mitq@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_KESANTRIAN,
            ],
            [
                'name' => 'Ustadzah Nurul (TU MITQ)',
                'username' => 'tu_mitq',
                'email' => 'tu_mitq@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TU,
            ],
            [
                'name' => 'Bapak Rahmat (Wali Santri MITQ)',
                'username' => 'walimurid_mitq',
                'email' => 'walimurid_mitq@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_WALI_MURID,
            ],
        ];

        foreach ($staffUsers as $sData) {
            $user = User::withoutGlobalScopes()->where('username', $sData['username'])->first();
            if ($user) {
                $user->update([
                    'institution_id' => $institution->id,
                    'name' => $sData['name'],
                    'password' => $sData['password'],
                    'role' => $sData['role'],
                ]);
            } else {
                User::create([
                    'institution_id' => $institution->id,
                    ...$sData,
                ]);
            }
        }

        // Link classroom-scoped staff to all MITQ classrooms
        $classroomIds = collect($classrooms)->pluck('id')->all();
        User::withoutGlobalScopes()
            ->where('institution_id', $institution->id)
            ->whereIn('role', User::CLASSROOM_SCOPED_ROLES)
            ->get()
            ->each(fn (User $u) => $u->classrooms()->sync($classroomIds));

        // 5. Seed Students
        $studentsData = [
            ['nis' => 'MITQ-001', 'name' => 'Abdurrahman Al-Fatih', 'gender' => 'L', 'class_key' => 1],
            ['nis' => 'MITQ-002', 'name' => 'Muhammad Rayyan', 'gender' => 'L', 'class_key' => 1],
            ['nis' => 'MITQ-003', 'name' => 'Aisyah Humaira', 'gender' => 'P', 'class_key' => 2],
            ['nis' => 'MITQ-004', 'name' => 'Fatimah Az-Zahra', 'gender' => 'P', 'class_key' => 2],
            ['nis' => 'MITQ-005', 'name' => 'Zaid bin Tsabit', 'gender' => 'L', 'class_key' => 3],
            ['nis' => 'MITQ-006', 'name' => 'Ali Zainal Abidin', 'gender' => 'L', 'class_key' => 3],
        ];

        $createdStudents = [];
        foreach ($studentsData as $st) {
            $createdStudents[] = Student::updateOrCreate(
                ['institution_id' => $institution->id, 'nis' => $st['nis']],
                [
                    'classroom_id' => $classrooms[$st['class_key']]->id,
                    'name' => $st['name'],
                    'gender' => $st['gender'],
                    'status' => 'aktif',
                ]
            );
        }

        // 6. Link Parent account to first student
        $parentUser = User::withoutGlobalScopes()->where('username', 'walimurid_mitq')->first();
        if ($parentUser && count($createdStudents) > 0) {
            $parentUser->children()->sync([$createdStudents[0]->id]);
        }

        // 7. Seed Sample Monthly Report & Tahfidz Journal for Student 1
        $guruUser = User::withoutGlobalScopes()->where('username', 'guru_mitq')->first();
        if ($guruUser && count($createdStudents) > 0) {
            $firstStudent = $createdStudents[0];

            TahfidzJournal::updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'student_id' => $firstStudent->id,
                    'date' => Carbon::now()->subDays(2)->toDateString(),
                ],
                [
                    'user_id' => $guruUser->id,
                    'session' => 'ziyadah',
                    'surah_name' => 'An-Naba',
                    'surah_number' => 78,
                    'ayah_start' => 1,
                    'ayah_end' => 20,
                    'juz' => 30,
                    'fluency_level' => 'A',
                    'mistakes_count' => 0,
                    'notes' => 'Hafalan sangat lancar dan tajwid tepat.',
                ]
            );

            $periodTitle = Carbon::now()->translatedFormat('F Y');
            $report = MonthlyReport::updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'student_id' => $firstStudent->id,
                    'period_title' => $periodTitle,
                ],
                [
                    'report_date' => Carbon::now()->toDateString(),
                    'cutoff_date' => Carbon::now()->endOfMonth()->toDateString(),
                    'status' => 'published',
                ]
            );

            ReportRecord::updateOrCreate(
                ['monthly_report_id' => $report->id],
                [
                    'institution_id' => $institution->id,
                    'values' => [
                        'tahfidz_setoran' => 'Surah An-Naba: 1-20',
                        'tahfidz_akumulasi' => '1 Juz',
                        'tahfidz_rincian_juz' => 'Juz 30 (Juz \'Amma)',
                        'tahfidz_kelancaran' => 'A',
                        'tahfidz_tajwid' => 'A',
                        'adab_ibadah' => '4.0 (A)',
                        'adab_akhlak' => '4.0 (A)',
                        'adab_kerapian' => '3.5 (A-)',
                        'adab_disiplin' => '4.0 (A)',
                        'body_height_cm' => '135',
                        'body_weight_kg' => '32',
                        'is_baligh' => 'Belum',
                        'academic_notes' => 'Santri menunjukkan kesungguhan yang sangat baik dalam KBM dan halaqah tahfidz.',
                        'last_spp' => 'Lunas',
                        'last_laundry' => 'Lunas',
                        'registration_status' => 'Terdaftar',
                    ],
                ]
            );
        }

        $this->info('Dummy users & data for MITQ Baitul Hikmah successfully seeded!');
        $this->table(
            ['Role', 'Nama', 'Username', 'Password'],
            [
                ['Admin', 'Ustadz Admin MITQ', 'admin_mitq', 'password'],
                ['Guru Tahfidz', 'Ustadz Abdullah (Guru Tahfidz MITQ)', 'guru_mitq', 'password'],
                ['Wali Kelas', 'Ustadz Ibrahim (Wali Kelas MITQ)', 'walikelas_mitq', 'password'],
                ['Kesantrian', 'Ustadz Salman (Kesantrian MITQ)', 'kesantrian_mitq', 'password'],
                ['Tata Usaha (TU)', 'Ustadzah Nurul (TU MITQ)', 'tu_mitq', 'password'],
                ['Wali Murid', 'Bapak Rahmat (Wali Santri MITQ)', 'walimurid_mitq', 'password'],
            ]
        );

        return Command::SUCCESS;
    }
}
