<?php

namespace App\Models;

use App\Services\StudentProgressService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'name',
        'classroom_id',
        'gender',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the classroom that owns the student.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the monthly reports for the student.
     */
    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    /**
     * Get the parent user associated with the student.
     */
    public function parentUser(): HasOne
    {
        return $this->hasOne(User::class, 'student_id')->where('role', User::ROLE_WALI_MURID);
    }

    /**
     * Get the tahfidz journals for the student.
     */
    public function tahfidzJournals(): HasMany
    {
        return $this->hasMany(TahfidzJournal::class);
    }

    /**
     * Get structured monthly progress trend data.
     */
    public function getProgressTrends(): array
    {
        return StudentProgressService::getStudentTrends($this);
    }
}
