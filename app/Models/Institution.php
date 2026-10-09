<?php

namespace App\Models;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'token',
        'is_active',
        'sub_title',
        'city',
        'address',
        'phone',
        'logo_path',
        'stamp_path',
        'director_name',
        'director_title',
        'signature_path',
        'accent_color',
        'term_student',
        'term_teacher',
        'term_class',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Default interface terminology, used until an institution row overrides it.
     */
    public const DEFAULT_TERMS = [
        'student' => 'Santri',
        'teacher' => 'Guru',
        'class' => 'Kelas',
    ];

    /**
     * Data URIs already fetched from storage, keyed by file path.
     *
     * @var array<string, string|null>
     */
    private array $imageDataUris = [];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('institution:current:attributes'));
    }

    /**
     * Relationships with scoped models.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Admin accounts that run this institution (used by the platform for support sessions).
     */
    public function admins(): HasMany
    {
        return $this->hasMany(User::class)->withoutGlobalScope('institution')->where('role', User::ROLE_ADMIN)->orderBy('name');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    public function moduleFields(): HasMany
    {
        return $this->hasMany(ModuleField::class);
    }

    public function tahfidzJournals(): HasMany
    {
        return $this->hasMany(TahfidzJournal::class);
    }

    /**
     * Public URL of an uploaded branding image, or null when none is set.
     *
     * @param  'logo_path'|'stamp_path'|'signature_path'  $attribute
     */
    public function imageUrl(string $attribute): ?string
    {
        $path = $this->{$attribute};

        return $path ? Storage::disk(config('filesystems.uploads'))->url($path) : null;
    }

    /**
     * Uploaded branding image as a data URI, so dompdf can embed it
     * regardless of which disk (local or Supabase Storage) holds the file.
     *
     * @param  'logo_path'|'stamp_path'|'signature_path'  $attribute
     */
    public function imageDataUri(string $attribute): ?string
    {
        $path = $this->{$attribute};

        if (! $path) {
            return null;
        }

        // Batch exports render one PDF per student; fetch each image from storage only once.
        if (! array_key_exists($path, $this->imageDataUris)) {
            $disk = Storage::disk(config('filesystems.uploads'));

            $this->imageDataUris[$path] = $disk->exists($path)
                ? 'data:'.$disk->mimeType($path).';base64,'.base64_encode($disk->get($path))
                : null;
        }

        return $this->imageDataUris[$path];
    }

    /**
     * Get the active institution profile row from TenantContext or fallback to first.
     */
    public static function current(): self
    {
        $tenant = TenantContext::getTenant();
        if ($tenant) {
            return $tenant;
        }

        return static::first() ?? new static([
            'name' => 'PONDOK PESANTREN CONTOH',
            'city' => 'KOTA CONTOH',
            'director_name' => 'Ust. Fulan, S.Pd.',
            'director_title' => 'Direktur Pesantren',
            'accent_color' => '#059669',
            'term_student' => self::DEFAULT_TERMS['student'],
            'term_teacher' => self::DEFAULT_TERMS['teacher'],
            'term_class' => self::DEFAULT_TERMS['class'],
        ]);
    }

    /**
     * Seed dummy staff, parent accounts, classrooms, and students for this institution.
     *
     * @return array<string, string>
     */
    public function seedDemoData(): array
    {
        $tokenSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->token ?: 'inst'.$this->id));

        // 1. Ensure default assessment fields exist
        TenantContext::setTenant($this);
        ModuleField::seedDefaultFields();

        // 2. Ensure at least 3 classrooms
        $targets = [
            1 => ['juz' => 30, 'desc' => "Juz 30 (Juz 'Amma)"],
            2 => ['juz' => 29, 'desc' => 'Juz 29 (Tabarak)'],
            3 => ['juz' => 28, 'desc' => "Juz 28 (Qad Sami'a)"],
        ];

        $classrooms = [];
        for ($c = 1; $c <= 3; $c++) {
            $classrooms[$c] = Classroom::updateOrCreate(
                ['institution_id' => $this->id, 'name' => 'Kelas '.$c],
                [
                    'tahfizh_days' => [1, 2, 3, 4, 5, 6],
                    'target_juz' => $targets[$c]['juz'],
                    'target_description' => $targets[$c]['desc'],
                ]
            );
        }

        // 3. Dummy Staff & Parent accounts
        $dummyUsers = [
            [
                'name' => 'Ustadz Abdullah (Guru Tahfidz)',
                'username' => 'guru_'.$tokenSlug,
                'email' => 'guru_'.$tokenSlug.'@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_GURU,
            ],
            [
                'name' => 'Ustadz Ibrahim (Wali Kelas)',
                'username' => 'walikelas_'.$tokenSlug,
                'email' => 'walikelas_'.$tokenSlug.'@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_WALI_KELAS,
            ],
            [
                'name' => 'Ustadz Salman (Kesantrian)',
                'username' => 'kesantrian_'.$tokenSlug,
                'email' => 'kesantrian_'.$tokenSlug.'@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_KESANTRIAN,
            ],
            [
                'name' => 'Ustadzah Nurul (Tata Usaha)',
                'username' => 'tu_'.$tokenSlug,
                'email' => 'tu_'.$tokenSlug.'@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TU,
            ],
            [
                'name' => 'Bapak Rahmat (Wali Santri)',
                'username' => 'walimurid_'.$tokenSlug,
                'email' => 'walimurid_'.$tokenSlug.'@myra.id',
                'password' => Hash::make('password'),
                'role' => User::ROLE_WALI_MURID,
            ],
        ];

        foreach ($dummyUsers as $uData) {
            $user = User::withoutGlobalScopes()->where('username', $uData['username'])->first();
            $belongsToAnotherInstitution = $user
                ? $user->institution_id !== $this->id
                : User::withoutGlobalScopes()->where('email', $uData['email'])->exists();

            // Different tokens can share a slug (e.g. "MIT-Q" and "MITQ"); never hijack another tenant's account.
            if ($belongsToAnotherInstitution) {
                continue;
            }

            if ($user) {
                $user->update([
                    'name' => $uData['name'],
                    'password' => $uData['password'],
                    'role' => $uData['role'],
                ]);
            } else {
                User::create([
                    'institution_id' => $this->id,
                    ...$uData,
                ]);
            }
        }

        // 4. Link classroom-scoped staff to all classrooms
        $classroomIds = collect($classrooms)->pluck('id')->all();
        User::withoutGlobalScopes()
            ->where('institution_id', $this->id)
            ->whereIn('role', User::CLASSROOM_SCOPED_ROLES)
            ->get()
            ->each(fn (User $u) => $u->classrooms()->sync($classroomIds));

        // 5. Seed sample students if less than 3 students exist
        if ($this->students()->count() < 3) {
            $prefixNis = strtoupper(substr($tokenSlug, 0, 4));
            $sampleStudents = [
                ['nis' => $prefixNis.'-001', 'name' => 'Abdurrahman Al-Fatih', 'gender' => 'L', 'class_key' => 1],
                ['nis' => $prefixNis.'-002', 'name' => 'Muhammad Rayyan', 'gender' => 'L', 'class_key' => 1],
                ['nis' => $prefixNis.'-003', 'name' => 'Aisyah Humaira', 'gender' => 'P', 'class_key' => 2],
                ['nis' => $prefixNis.'-004', 'name' => 'Fatimah Az-Zahra', 'gender' => 'P', 'class_key' => 2],
                ['nis' => $prefixNis.'-005', 'name' => 'Zaid bin Tsabit', 'gender' => 'L', 'class_key' => 3],
                ['nis' => $prefixNis.'-006', 'name' => 'Ali Zainal Abidin', 'gender' => 'L', 'class_key' => 3],
            ];

            foreach ($sampleStudents as $st) {
                Student::updateOrCreate(
                    ['institution_id' => $this->id, 'nis' => $st['nis']],
                    [
                        'classroom_id' => $classrooms[$st['class_key']]->id,
                        'name' => $st['name'],
                        'gender' => $st['gender'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // 6. Link parent to first student
        $parent = User::withoutGlobalScopes()
            ->where('institution_id', $this->id)
            ->where('username', 'walimurid_'.$tokenSlug)
            ->first();
        $firstChild = $this->students()->first();
        if ($parent && $firstChild) {
            $parent->children()->sync([$firstChild->id]);
        }

        return [
            'guru' => 'guru_'.$tokenSlug,
            'walikelas' => 'walikelas_'.$tokenSlug,
            'kesantrian' => 'kesantrian_'.$tokenSlug,
            'tu' => 'tu_'.$tokenSlug,
            'walimurid' => 'walimurid_'.$tokenSlug,
        ];
    }

    /**
     * Get the configured interface term for "student", "teacher", or "class"
     * (e.g. Santri/Siswa, Guru/Musyrif, Kelas/Halaqah), falling back to the default.
     */
    public static function term(string $key): string
    {
        $column = "term_{$key}";
        $value = static::current()->{$column} ?? null;

        return $value ?: (self::DEFAULT_TERMS[$key] ?? ucfirst($key));
    }
}
