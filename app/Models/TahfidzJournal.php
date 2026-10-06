<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzJournal extends Model
{
    use BelongsToInstitution, HasFactory;

    protected $fillable = [
        'institution_id',
        'student_id',
        'teacher_id',
        'date',
        'attendance',
        'type',
        'juz',
        'surah_id',
        'surah',
        'ayah_start',
        'ayah_end',
        'page_count',
        'grade',
        'status',
        'score',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'juz' => 'integer',
        'surah_id' => 'integer',
        'ayah_start' => 'integer',
        'ayah_end' => 'integer',
        'page_count' => 'integer',
        'score' => 'float',
    ];

    /**
     * Get the student that owns the journal entry.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the teacher that recorded the entry.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'ziyadah' => 'Ziyadah (Hafalan Baru)',
            'murajaah' => 'Muraja\'ah (Ulang Hafalan)',
            'tahsin' => 'Tahsin',
            'tilawah' => 'Tilawah Mandiri',
            'tasmi' => 'Tasmi\' Ujian',
            default => ucfirst($this->type),
        };
    }
}
