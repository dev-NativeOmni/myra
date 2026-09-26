<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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

    /**
     * Default interface terminology, used until an institution row overrides it.
     */
    public const DEFAULT_TERMS = [
        'student' => 'Santri',
        'teacher' => 'Guru',
        'class' => 'Kelas',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('institution:current:attributes'));
    }

    /**
     * Get the single institution profile row, cached for the request lifecycle
     * and beyond since it changes rarely but is read on nearly every page.
     */
    public static function current(): self
    {
        $attributes = Cache::rememberForever(
            'institution:current:attributes',
            fn () => static::first()?->getAttributes() ?? []
        );

        return (new static)->forceFill($attributes);
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
