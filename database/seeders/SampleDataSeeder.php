<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\MonthlyReport;
use App\Models\ReportRecord;
use App\Models\Student;
use App\Models\TahfidzJournal;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Institution Profile (Default Primary Tenant)
        $institution = Institution::firstOrCreate(
            ['token' => 'TAQREER-DEMO'],
            [
                'name' => 'PONDOK PESANTREN CONTOH',
                'sub_title' => 'Islamic Boarding School',
                'city' => 'KOTA CONTOH',
                'address' => 'Kota Contoh, Jawa Tengah',
                'phone' => '081234567890',
                'director_name' => 'Ust. Fulan, S.Pd.',
                'director_title' => 'Direktur Pesantren',
                'accent_color' => '#059669',
                'is_active' => true,
            ]
        );

        TenantContext::setTenant($institution);

        // Seed second sample institution for multi-tenant testing
        $secondInstitution = Institution::firstOrCreate(
            ['token' => 'ALHIKMAH-DEMO'],
            [
                'name' => 'PESANTREN TAHFIDZ AL-HIKMAH',
                'sub_title' => 'Tahfidzul Qur\'an & Sains',
                'city' => 'MALANG',
                'address' => 'Jl. Pesantren No. 12, Malang',
                'phone' => '082198765432',
                'director_name' => 'Dr. KH. Ahmad Zaki, M.A.',
                'director_title' => 'Pengasuh Pesantren',
                'accent_color' => '#2563EB',
                'is_active' => true,
            ]
        );

        // 2. Seed 6 Classrooms (Kelas 1 - Kelas 6) with Curriculum Targets
        $targets = [
            1 => ['juz' => 30, 'desc' => "Juz 30 (Juz 'Amma)"],
            2 => ['juz' => 29, 'desc' => 'Juz 29 (Tabarak)'],
            3 => ['juz' => 28, 'desc' => "Juz 28 (Qad Sami'a)"],
            4 => ['juz' => 1, 'desc' => 'Juz 1 (Al-Baqarah 1-141)'],
            5 => ['juz' => 2, 'desc' => 'Juz 2 (Al-Baqarah 142-252)'],
            6 => ['juz' => 3, 'desc' => "Juz 3 (Al-Baqarah 253 - Ali 'Imran 92)"],
        ];

        $classrooms = [];
        for ($c = 1; $c <= 6; $c++) {
            $classrooms[$c] = Classroom::updateOrCreate(
                ['name' => (string) $c],
                [
                    'tahfizh_days' => [1, 2, 3, 4, 5, 6],
                    'target_juz' => $targets[$c]['juz'],
                    'target_description' => $targets[$c]['desc'],
                ]
            );
        }

        // 3. Seed Staff & Admin Users
        $staffUsers = [
            [
                'name' => 'Ust. Fulan (Super Admin)',
                'username' => 'superadmin',
                'email' => 'superadmin@taqreer.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPER_ADMIN,
            ],
            [
                'name' => 'Ustadzah Fatimah (Admin Operasional)',
                'username' => 'admin',
                'email' => 'admin@taqreer.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Ustadz Abdullah (Guru Tahfidz)',
                'username' => 'guru',
                'email' => 'guru@taqreer.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_GURU,
            ],
            [
                'name' => 'Ustadz Ibrahim (Wali Kelas)',
                'username' => 'walikelas',
                'email' => 'walikelas@taqreer.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_WALI_KELAS,
            ],
            [
                'name' => 'Ustadz Yusuf (Wali Asrama / Kesantrian)',
                'username' => 'kesantrian',
                'email' => 'kesantrian@taqreer.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_KESANTRIAN,
            ],
            [
                'name' => 'Ustadzah Maryam (Staf TU & Keuangan)',
                'username' => 'tu',
                'email' => 'tu@taqreer.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TU,
            ],
        ];

        foreach ($staffUsers as $sUser) {
            User::updateOrCreate(
                ['username' => $sUser['username']],
                $sUser
            );
        }

        // Assign classroom-scoped staff (guru, wali kelas, kesantrian) to every
        // classroom by default so sample/demo accounts can edit everything out of the box.
        $allClassroomIds = collect($classrooms)->pluck('id');
        User::whereIn('role', User::CLASSROOM_SCOPED_ROLES)->get()->each(
            fn (User $staff) => $staff->classrooms()->sync($allClassroomIds)
        );

        $guruUser = User::where('role', User::ROLE_GURU)->first();

        // 4. Data Pool for 6 Classes (10 Students each -> 60 Total)
        $classesStudents = [
            1 => [
                ['name' => 'Abdurrahman Faiz Al Hafidz', 'gender' => 'L', 'parent' => 'Bapak Hafidzan Pratama', 'surah' => 'An-Nas', 'surah_id' => 114, 'surah2' => 'Al-Falaq', 'surah2_id' => 113, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Tahsin & Ziyadah Juz 30'],
                ['name' => 'Muhammad Salman Al Farisi', 'gender' => 'L', 'parent' => 'Bapak Faris Hidayat', 'surah' => 'Al-Ikhlas', 'surah_id' => 112, 'surah2' => 'Al-Lahab', 'surah2_id' => 111, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Ahmad Zaki Mubarak', 'gender' => 'L', 'parent' => 'Ibu Siti Fatimah', 'surah' => 'An-Nasr', 'surah_id' => 110, 'surah2' => 'Al-Kafirun', 'surah2_id' => 109, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Bilal Syakir Rabbani', 'gender' => 'L', 'parent' => 'Bapak Rabbani Yusuf', 'surah' => 'Al-Kautsar', 'surah_id' => 108, 'surah2' => 'Al-Ma\'un', 'surah2_id' => 107, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Ibrahim Khalilullah', 'gender' => 'L', 'parent' => 'Bapak Khalil Rahman', 'surah' => 'Quraisy', 'surah_id' => 106, 'surah2' => 'Al-Fil', 'surah2_id' => 105, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Fathir Rayyan Al-Ghifari', 'gender' => 'L', 'parent' => 'Ibu Nurul Aini', 'surah' => 'Al-Humazah', 'surah_id' => 104, 'surah2' => 'Al-\'Asr', 'surah2_id' => 103, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Hamzah Asadullah', 'gender' => 'L', 'parent' => 'Bapak Joko Susilo', 'surah' => 'At-Takatsur', 'surah_id' => 102, 'surah2' => 'Al-Qari\'ah', 'surah2_id' => 101, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Umar Mukhtar Al-Farouq', 'gender' => 'L', 'parent' => 'Bapak Mukhtar Ali', 'surah' => 'Al-\'Adiyat', 'surah_id' => 100, 'surah2' => 'Az-Zalzalah', 'surah2_id' => 99, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Yusuf Manshur Robbani', 'gender' => 'L', 'parent' => 'Ibu Khadijah Rahma', 'surah' => 'Al-Bayyinah', 'surah_id' => 98, 'surah2' => 'Al-Qadr', 'surah2_id' => 97, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Tahsin & Ziyadah'],
                ['name' => 'Zaid bin Tsabit Al-Anshari', 'gender' => 'L', 'parent' => 'Bapak Anshori Wijaya', 'surah' => 'Al-\'Alaq', 'surah_id' => 96, 'surah2' => 'At-Tin', 'surah2_id' => 95, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
            ],
            2 => [
                ['name' => 'Ali Zainal Abidin', 'gender' => 'L', 'parent' => 'Bapak Zainal Arifin', 'surah' => 'Asy-Syarh', 'surah_id' => 94, 'surah2' => 'Ad-Dhuha', 'surah2_id' => 93, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Hasan Al-Bashri', 'gender' => 'L', 'parent' => 'Ibu Halimah Tusya\'diah', 'surah' => 'Al-Lail', 'surah_id' => 92, 'surah2' => 'Asy-Syams', 'surah2_id' => 91, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Husain Syahid', 'gender' => 'L', 'parent' => 'Bapak Syahid Abdullah', 'surah' => 'Al-Balad', 'surah_id' => 90, 'surah2' => 'Al-Fajr', 'surah2_id' => 89, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Mu\'adz bin Jabal', 'gender' => 'L', 'parent' => 'Bapak Jabal Nur', 'surah' => 'Al-Ghasyiyah', 'surah_id' => 88, 'surah2' => 'Al-A\'la', 'surah2_id' => 87, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Mus\'ab bin Umair', 'gender' => 'L', 'parent' => 'Ibu Aminah', 'surah' => 'Ath-Thariq', 'surah_id' => 86, 'surah2' => 'Al-Buruj', 'surah2_id' => 85, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Sa\'ad bin Abi Waqqash', 'gender' => 'L', 'parent' => 'Bapak Waqqash Rasyid', 'surah' => 'Al-Insyiqaq', 'surah_id' => 84, 'surah2' => 'Al-Muthaffifin', 'surah2_id' => 83, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Sa\'id bin Zaid', 'gender' => 'L', 'parent' => 'Bapak Zaidan Ilham', 'surah' => 'Al-Infitar', 'surah_id' => 82, 'surah2' => 'At-Takwir', 'surah2_id' => 81, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Thalhah bin Ubaidillah', 'gender' => 'L', 'parent' => 'Ibu Fatimah Zahra', 'surah' => '\'Abasa', 'surah_id' => 80, 'surah2' => 'An-Nazi\'at', 'surah2_id' => 79, 'juz' => 'Juz 30', 'juz_num' => 30, 'target' => 'Ziyadah Juz 30'],
                ['name' => 'Zubair bin Awwam', 'gender' => 'L', 'parent' => 'Bapak Awwam Hakim', 'surah' => 'An-Naba\'', 'surah_id' => 78, 'surah2' => 'Al-Mulk', 'surah2_id' => 67, 'juz' => 'Juz 30, 29', 'juz_num' => 29, 'target' => 'Ziyadah Juz 29'],
                ['name' => 'Abu Ubaidah Al-Jarrah', 'gender' => 'L', 'parent' => 'Bapak Jarrah Munawwar', 'surah' => 'Al-Mulk', 'surah_id' => 67, 'surah2' => 'Al-Qalam', 'surah2_id' => 68, 'juz' => 'Juz 29', 'juz_num' => 29, 'target' => 'Ziyadah Juz 29'],
            ],
            3 => [
                ['name' => 'Abdullah bin Mas\'ud', 'gender' => 'L', 'parent' => 'Bapak Mas\'ud Riyadi', 'surah' => 'Al-Haqqah', 'surah_id' => 69, 'surah2' => 'Al-Ma\'arij', 'surah2_id' => 70, 'juz' => 'Juz 29', 'juz_num' => 29, 'target' => 'Ziyadah Juz 29'],
                ['name' => 'Abu Dzar Al-Ghifari', 'gender' => 'L', 'parent' => 'Bapak Ghifar Hamdan', 'surah' => 'Nuh', 'surah_id' => 71, 'surah2' => 'Al-Jinn', 'surah2_id' => 72, 'juz' => 'Juz 29', 'juz_num' => 29, 'target' => 'Ziyadah Juz 29'],
                ['name' => 'Abu Hurairah Ad-Dausi', 'gender' => 'L', 'parent' => 'Ibu Maryam Hasan', 'surah' => 'Al-Muzzammil', 'surah_id' => 73, 'surah2' => 'Al-Muddatstsir', 'surah2_id' => 74, 'juz' => 'Juz 29', 'juz_num' => 29, 'target' => 'Ziyadah Juz 29'],
                ['name' => 'Ammar bin Yasir', 'gender' => 'L', 'parent' => 'Bapak Yasir Arafat', 'surah' => 'Al-Qiyamah', 'surah_id' => 75, 'surah2' => 'Al-Insan', 'surah2_id' => 76, 'juz' => 'Juz 29', 'juz_num' => 29, 'target' => 'Ziyadah Juz 29'],
                ['name' => 'Anas bin Malik', 'gender' => 'L', 'parent' => 'Bapak Malik Ibrahim', 'surah' => 'Al-Mursalat', 'surah_id' => 77, 'surah2' => 'Al-Insan', 'surah2_id' => 76, 'juz' => 'Juz 29', 'juz_num' => 29, 'target' => 'Muraja\'ah Juz 29'],
                ['name' => 'Ja\'far bin Abi Thalib', 'gender' => 'L', 'parent' => 'Bapak Thalib Husein', 'surah' => 'At-Tahrim', 'surah_id' => 66, 'surah2' => 'Ath-Thalaq', 'surah2_id' => 65, 'juz' => 'Juz 28', 'juz_num' => 28, 'target' => 'Ziyadah Juz 28'],
                ['name' => 'Khalid bin Walid', 'gender' => 'L', 'parent' => 'Bapak Walid Setiawan', 'surah' => 'At-Taghabun', 'surah_id' => 64, 'surah2' => 'Al-Munafiqun', 'surah2_id' => 63, 'juz' => 'Juz 28', 'juz_num' => 28, 'target' => 'Ziyadah Juz 28'],
                ['name' => 'Miqdad bin Amr', 'gender' => 'L', 'parent' => 'Ibu Aisyah Wardani', 'surah' => 'Al-Jumu\'ah', 'surah_id' => 62, 'surah2' => 'Ash-Shaff', 'surah2_id' => 61, 'juz' => 'Juz 28', 'juz_num' => 28, 'target' => 'Ziyadah Juz 28'],
                ['name' => 'Usamah bin Zaid', 'gender' => 'L', 'parent' => 'Bapak Zaidan Fikri', 'surah' => 'Al-Mumtahanah', 'surah_id' => 60, 'surah2' => 'Al-Hasyr', 'surah2_id' => 59, 'juz' => 'Juz 28', 'juz_num' => 28, 'target' => 'Ziyadah Juz 28'],
                ['name' => 'Salman Al-Khair', 'gender' => 'L', 'parent' => 'Bapak Khairul Anam', 'surah' => 'Al-Mujadilah', 'surah_id' => 58, 'surah2' => 'Al-Hadid', 'surah2_id' => 57, 'juz' => 'Juz 28', 'juz_num' => 28, 'target' => 'Ziyadah Juz 28'],
            ],
            4 => [
                ['name' => 'Muhammad Al-Fatih', 'gender' => 'L', 'parent' => 'Bapak Murad Syah', 'surah' => 'Al-Baqarah', 'surah_id' => 2, 'surah2' => 'Al-Fatihah', 'surah2_id' => 1, 'juz' => 'Juz 1', 'juz_num' => 1, 'target' => 'Ziyadah Juz 1'],
                ['name' => 'Salahuddin Al-Ayyubi', 'gender' => 'L', 'parent' => 'Bapak Ayyub Najmuddin', 'surah' => 'Al-Baqarah', 'surah_id' => 2, 'surah2' => 'Al-Baqarah', 'surah2_id' => 2, 'juz' => 'Juz 1 (Ayat 1-141)', 'juz_num' => 1, 'target' => 'Ziyadah Juz 1'],
                ['name' => 'Tariq bin Ziyad', 'gender' => 'L', 'parent' => 'Bapak Ziyad Marwan', 'surah' => 'Al-Baqarah', 'surah_id' => 2, 'surah2' => 'Al-Baqarah', 'surah2_id' => 2, 'juz' => 'Juz 1 (Ayat 142-252)', 'juz_num' => 2, 'target' => 'Ziyadah Juz 2'],
                ['name' => 'Qutuz Saifuddin', 'gender' => 'L', 'parent' => 'Ibu Rahmawati', 'surah' => 'Al-Baqarah', 'surah_id' => 2, 'surah2' => 'Al-Baqarah', 'surah2_id' => 2, 'juz' => 'Juz 2', 'juz_num' => 2, 'target' => 'Ziyadah Juz 2'],
                ['name' => 'Nuruddin Zanki', 'gender' => 'L', 'parent' => 'Bapak Imaduddin Zanki', 'surah' => 'Ali \'Imran', 'surah_id' => 3, 'surah2' => 'Al-Baqarah', 'surah2_id' => 2, 'juz' => 'Juz 3', 'juz_num' => 3, 'target' => 'Ziyadah Juz 3'],
                ['name' => 'Imam Bukhari', 'gender' => 'L', 'parent' => 'Bapak Ismail Mughirah', 'surah' => 'Ali \'Imran', 'surah_id' => 3, 'surah2' => 'Ali \'Imran', 'surah2_id' => 3, 'juz' => 'Juz 3, 4', 'juz_num' => 3, 'target' => 'Ziyadah Juz 3'],
                ['name' => 'Imam Muslim', 'gender' => 'L', 'parent' => 'Bapak Hajjaj Qusyairi', 'surah' => 'An-Nisa\'', 'surah_id' => 4, 'surah2' => 'Ali \'Imran', 'surah2_id' => 3, 'juz' => 'Juz 4', 'juz_num' => 4, 'target' => 'Ziyadah Juz 4'],
                ['name' => 'Imam Syafi\'i', 'gender' => 'L', 'parent' => 'Ibu Fatimah Az-Zahra', 'surah' => 'An-Nisa\'', 'surah_id' => 4, 'surah2' => 'An-Nisa\'', 'surah2_id' => 4, 'juz' => 'Juz 5', 'juz_num' => 5, 'target' => 'Ziyadah Juz 5'],
                ['name' => 'Imam Abu Hanifah', 'gender' => 'L', 'parent' => 'Bapak Tsabit Nu\'man', 'surah' => 'Al-Ma\'idah', 'surah_id' => 5, 'surah2' => 'An-Nisa\'', 'surah2_id' => 4, 'juz' => 'Juz 6', 'juz_num' => 6, 'target' => 'Ziyadah Juz 6'],
                ['name' => 'Imam Malik', 'gender' => 'L', 'parent' => 'Bapak Anas Malik', 'surah' => 'Al-Ma\'idah', 'surah_id' => 5, 'surah2' => 'Al-An\'am', 'surah2_id' => 6, 'juz' => 'Juz 6, 7', 'juz_num' => 6, 'target' => 'Ziyadah Juz 6'],
            ],
            5 => [
                ['name' => 'Ahmad Dahlan', 'gender' => 'L', 'parent' => 'Bapak Abu Bakar', 'surah' => 'Al-An\'am', 'surah_id' => 6, 'surah2' => 'Al-A\'raf', 'surah2_id' => 7, 'juz' => 'Juz 7, 8', 'juz_num' => 7, 'target' => 'Ziyadah Juz 7'],
                ['name' => 'Hasyim Asy\'ari', 'gender' => 'L', 'parent' => 'Bapak Asy\'ari Halim', 'surah' => 'Al-A\'raf', 'surah_id' => 7, 'surah2' => 'Al-Anfal', 'surah2_id' => 8, 'juz' => 'Juz 8, 9', 'juz_num' => 8, 'target' => 'Ziyadah Juz 8'],
                ['name' => 'Mohammad Natsir', 'gender' => 'L', 'parent' => 'Bapak Idris Sutan Saripado', 'surah' => 'At-Taubah', 'surah_id' => 9, 'surah2' => 'Al-Anfal', 'surah2_id' => 8, 'juz' => 'Juz 10', 'juz_num' => 10, 'target' => 'Ziyadah Juz 10'],
                ['name' => 'Buya Hamka', 'gender' => 'L', 'parent' => 'Bapak Abdul Karim Amrullah', 'surah' => 'Yunus', 'surah_id' => 10, 'surah2' => 'Hud', 'surah2_id' => 11, 'juz' => 'Juz 11', 'juz_num' => 11, 'target' => 'Ziyadah Juz 11'],
                ['name' => 'Rasuna Said', 'gender' => 'P', 'parent' => 'Bapak Muhammad Said', 'surah' => 'Yusuf', 'surah_id' => 12, 'surah2' => 'Ar-Ra\'d', 'surah2_id' => 13, 'juz' => 'Juz 12, 13', 'juz_num' => 12, 'target' => 'Ziyadah Juz 12'],
                ['name' => 'Rohana Kudus', 'gender' => 'P', 'parent' => 'Bapak Moehammad Rasjad', 'surah' => 'Ibrahim', 'surah_id' => 14, 'surah2' => 'Al-Hijr', 'surah2_id' => 15, 'juz' => 'Juz 13, 14', 'juz_num' => 13, 'target' => 'Ziyadah Juz 13'],
                ['name' => 'Dewi Sartika', 'gender' => 'P', 'parent' => 'Bapak Raden Rangga Somanegara', 'surah' => 'An-Nahl', 'surah_id' => 16, 'surah2' => 'Al-Isra\'', 'surah2_id' => 17, 'juz' => 'Juz 14, 15', 'juz_num' => 14, 'target' => 'Ziyadah Juz 14'],
                ['name' => 'Cut Nyak Dien', 'gender' => 'P', 'parent' => 'Bapak Teuku Nanta Seutia', 'surah' => 'Al-Kahf', 'surah_id' => 18, 'surah2' => 'Maryam', 'surah2_id' => 19, 'juz' => 'Juz 15, 16', 'juz_num' => 15, 'target' => 'Ziyadah Juz 15'],
                ['name' => 'Nyai Ahmad Dahlan', 'gender' => 'P', 'parent' => 'Bapak Kyai Haji Fadhil', 'surah' => 'Thaha', 'surah_id' => 20, 'surah2' => 'Al-Anbiya\'', 'surah2_id' => 21, 'juz' => 'Juz 16, 17', 'juz_num' => 16, 'target' => 'Ziyadah Juz 16'],
                ['name' => 'Fatmawati Sukarno', 'gender' => 'P', 'parent' => 'Bapak Hassan Din', 'surah' => 'Al-Hajj', 'surah_id' => 22, 'surah2' => 'Al-Mu\'minun', 'surah2_id' => 23, 'juz' => 'Juz 17, 18', 'juz_num' => 17, 'target' => 'Ziyadah Juz 17'],
            ],
            6 => [
                ['name' => 'Fatih Robbani', 'gender' => 'L', 'parent' => 'Bapak Robbani Subarkah', 'surah' => 'An-Nur', 'surah_id' => 24, 'surah2' => 'Al-Furqan', 'surah2_id' => 25, 'juz' => 'Juz 18, 19', 'juz_num' => 18, 'target' => 'Tasmi\' Akbar 5 Juz'],
                ['name' => 'Ghazi Al-Kautsar', 'gender' => 'L', 'parent' => 'Bapak Kautsar Hadi', 'surah' => 'Asy-Syu\'ara\'', 'surah_id' => 26, 'surah2' => 'An-Naml', 'surah2_id' => 27, 'juz' => 'Juz 19, 20', 'juz_num' => 19, 'target' => 'Tasmi\' Akbar 5 Juz'],
                ['name' => 'Haidar Ali Murtadha', 'gender' => 'L', 'parent' => 'Bapak Murtadha Hasyim', 'surah' => 'Al-Qashash', 'surah_id' => 28, 'surah2' => 'Al-\'Ankabut', 'surah2_id' => 29, 'juz' => 'Juz 20, 21', 'juz_num' => 20, 'target' => 'Mutqin 5 Juz'],
                ['name' => 'Izzuddin Al-Qassam', 'gender' => 'L', 'parent' => 'Bapak Abdul Qadir', 'surah' => 'Ar-Rum', 'surah_id' => 30, 'surah2' => 'Luqman', 'surah2_id' => 31, 'juz' => 'Juz 21', 'juz_num' => 21, 'target' => 'Mutqin 5 Juz'],
                ['name' => 'Jihad Akbar Fisabilillah', 'gender' => 'L', 'parent' => 'Bapak Akbar Nugraha', 'surah' => 'As-Sajdah', 'surah_id' => 32, 'surah2' => 'Al-Ahzab', 'surah2_id' => 33, 'juz' => 'Juz 21, 22', 'juz_num' => 21, 'target' => 'Mutqin 5 Juz'],
                ['name' => 'Kenan Al-Manshur', 'gender' => 'L', 'parent' => 'Bapak Manshur Effendi', 'surah' => 'Saba\'', 'surah_id' => 34, 'surah2' => 'Fathir', 'surah2_id' => 35, 'juz' => 'Juz 22', 'juz_num' => 22, 'target' => 'Mutqin 6 Juz'],
                ['name' => 'Luqman Hakim An-Nafi', 'gender' => 'L', 'parent' => 'Bapak Nafi Suherman', 'surah' => 'Yasin', 'surah_id' => 36, 'surah2' => 'Ash-Shaffat', 'surah2_id' => 37, 'juz' => 'Juz 22, 23', 'juz_num' => 22, 'target' => 'Mutqin 6 Juz'],
                ['name' => 'Muzammil Hasballah', 'gender' => 'L', 'parent' => 'Bapak Hasballah Usman', 'surah' => 'Shad', 'surah_id' => 38, 'surah2' => 'Az-Zumar', 'surah2_id' => 39, 'juz' => 'Juz 23, 24', 'juz_num' => 23, 'target' => 'Tasmi\' Akbar 7 Juz'],
                ['name' => 'Naufal Syarafuddin', 'gender' => 'L', 'parent' => 'Bapak Syarafuddin Malik', 'surah' => 'Ghafir', 'surah_id' => 40, 'surah2' => 'Fushshilat', 'surah2_id' => 41, 'juz' => 'Juz 24', 'juz_num' => 24, 'target' => 'Tasmi\' Akbar 7 Juz'],
                ['name' => 'Qaisar Al-Farabi', 'gender' => 'L', 'parent' => 'Bapak Farabi Kurniawan', 'surah' => 'Asy-Syura', 'surah_id' => 42, 'surah2' => 'Az-Zukhruf', 'surah2_id' => 43, 'juz' => 'Juz 25', 'juz_num' => 25, 'target' => 'Tasmi\' Akbar 10 Juz'],
            ],
        ];

        $globalStudentIndex = 1;

        // 5. Seed Students, Parents, Monthly Reports, and Journals for all 6 Classes
        foreach ($classesStudents as $classNumber => $studentsList) {
            $classroom = $classrooms[$classNumber];

            foreach ($studentsList as $indexInClass => $data) {
                $nis = sprintf('%02d%02d', $classNumber, $indexInClass + 1);
                $parentUsername = 'walimurid'.$globalStudentIndex;
                $parentEmail = 'walimurid'.$globalStudentIndex.'@taqreer.id';

                // A. Create Student
                $student = Student::updateOrCreate(
                    ['nis' => $nis],
                    [
                        'name' => $data['name'],
                        'classroom_id' => $classroom->id,
                        'gender' => $data['gender'],
                        'is_active' => true,
                    ]
                );

                // B. Create Monthly Report
                $report = MonthlyReport::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'period_title' => 'JULI-AGUSTUS 2026',
                    ],
                    [
                        'report_date' => '2026-09-01',
                        'cutoff_date' => '2026-08-30',
                        'status' => 'published',
                    ]
                );

                // C. Create Monthly Report Record
                ReportRecord::updateOrCreate(
                    ['monthly_report_id' => $report->id],
                    [
                        'tahfidz_setoran' => $data['target'],
                        'tahfidz_akumulasi' => ($classNumber).' Juz '.($indexInClass * 2).' Hal',
                        'tahfidz_rincian_juz' => $data['juz'].' ('.$data['surah'].')',
                        'tahfidz_notes' => "Alhamdulillah ananda {$data['name']} menunjukkan perkembangan tahfidz yang sangat baik, makhraj jelas dan konsisten dalam halaqah.",

                        'adab_ibadah' => ($indexInClass % 2 === 0) ? 'A (Sangat Baik)' : 'B (Baik)',
                        'adab_akhlak' => 'A (Sangat Baik)',
                        'adab_kerapian' => ($indexInClass % 3 === 0) ? 'A (Sangat Baik)' : 'B (Baik)',
                        'adab_kedisiplinan' => 'A (Sangat Baik)',
                        'body_height_cm' => 125 + ($classNumber * 4) + ($indexInClass % 5),
                        'body_weight_kg' => 24 + ($classNumber * 3) + ($indexInClass % 4),
                        'is_baligh' => ($classNumber >= 5) ? 'Sudah' : 'Belum',
                        'kesantrian_notes' => 'Ananda aktif mengikuti seluruh rangkaian kegiatan halaqah dan asrama dengan penuh antusias.',

                        'academic_notes' => 'Mengikuti kegiatan belajar di kelas dengan tertib dan menunjukkan pemahaman materi yang sangat baik.',

                        'last_spp' => 'Agustus 2026',
                        'last_laundry' => 'Agustus 2026',
                        'registration_status' => 'Lunas',
                    ]
                );

                // D. Create Parent User (Wali Murid)
                User::updateOrCreate(
                    ['username' => $parentUsername],
                    [
                        'name' => $data['parent'],
                        'username' => $parentUsername,
                        'email' => $parentEmail,
                        'password' => Hash::make('password'),
                        'role' => User::ROLE_WALI_MURID,
                    ]
                )->children()->syncWithoutDetaching([$student->id]);

                // E. Demo account 'walimurid' linked to students 1 and 2, to show a parent with several children
                if ($globalStudentIndex === 1) {
                    User::updateOrCreate(
                        ['username' => 'walimurid'],
                        [
                            'name' => $data['parent'].' (Demo)',
                            'username' => 'walimurid',
                            'email' => 'walimurid@taqreer.id',
                            'password' => Hash::make('password'),
                            'role' => User::ROLE_WALI_MURID,
                        ]
                    );
                }

                if (in_array($globalStudentIndex, [1, 2], true)) {
                    User::where('username', 'walimurid')->firstOrFail()->children()->syncWithoutDetaching([$student->id]);
                }

                // F. Sample Daily Tahfidz Journals
                TahfidzJournal::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'date' => '2026-08-15',
                        'type' => 'ziyadah',
                    ],
                    [
                        'teacher_id' => $guruUser?->id,
                        'attendance' => 'hadir',
                        'juz' => $data['juz_num'],
                        'surah_id' => $data['surah_id'],
                        'surah' => $data['surah'],
                        'ayah_start' => 1,
                        'ayah_end' => 10,
                        'page_count' => 1,
                        'score' => 95,
                        'grade' => 'Mumtaz (A)',
                        'status' => 'passed',
                        'notes' => 'Bacaan makhraj huruf sangat baik dan lancar.',
                    ]
                );

                TahfidzJournal::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'date' => '2026-08-22',
                        'type' => 'murajaah',
                    ],
                    [
                        'teacher_id' => $guruUser?->id,
                        'attendance' => 'hadir',
                        'juz' => $data['juz_num'],
                        'surah_id' => $data['surah2_id'],
                        'surah' => $data['surah2'],
                        'ayah_start' => 1,
                        'ayah_end' => 15,
                        'page_count' => 1,
                        'score' => 88,
                        'grade' => 'Jayyid Jiddan (B+)',
                        'status' => 'passed',
                        'notes' => 'Muraja\'ah lancar dalam satu kali duduk.',
                    ]
                );

                $globalStudentIndex++;
            }
        }

        TenantContext::clear();
    }
}
