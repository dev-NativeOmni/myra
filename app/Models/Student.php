<?php

namespace App\Models;

use App\Services\StudentProgressService;
use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use BelongsToInstitution, HasFactory;

    protected $fillable = [
        'institution_id',
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
     * Parent (Wali Murid) accounts linked to this student.
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student')
            ->where('role', User::ROLE_WALI_MURID)
            ->withTimestamps();
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
