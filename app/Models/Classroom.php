<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tahfizh_days',
        'target_juz',
        'target_description',
    ];

    protected $casts = [
        'tahfizh_days' => 'array',
        'target_juz' => 'integer',
    ];

    /**
     * Get active tahfizh days (default: 1-6 / Senin-Sabtu).
     */
    public function getTahfizhDaysAttribute($value): array
    {
        if (is_null($value)) {
            return [1, 2, 3, 4, 5, 6];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? $decoded : [1, 2, 3, 4, 5, 6];
    }

    /**
     * Get default target juz according to standard elementary curriculum.
     */
    public function getEffectiveTargetJuzAttribute(): int
    {
        if ($this->target_juz) {
            return $this->target_juz;
        }

        return match ((string) $this->name) {
            '1' => 30,
            '2' => 29,
            '3' => 28,
            '4' => 1,
            '5' => 2,
            '6' => 3,
            default => 30,
        };
    }

    /**
     * Get formatted label for target juz.
     */
    public function getTargetLabelAttribute(): string
    {
        if ($this->target_description) {
            return $this->target_description;
        }

        $juz = $this->effective_target_juz;

        return match ($juz) {
            30 => "Juz 30 (Juz 'Amma)",
            29 => 'Juz 29 (Tabarak)',
            28 => "Juz 28 (Qad Sami'a)",
            default => "Juz {$juz}",
        };
    }

    /**
     * Get the students for the classroom.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Staff (guru, wali kelas, kesantrian) assigned as responsible for this classroom.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
