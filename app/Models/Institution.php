<?php

namespace App\Models;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
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
