<?php

namespace App\Models;

use App\Observers\MonthlyReportObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(MonthlyReportObserver::class)]
class MonthlyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'period_title',
        'report_date',
        'cutoff_date',
        'status',
    ];

    protected $casts = [
        'report_date' => 'date',
        'cutoff_date' => 'date',
    ];

    /**
     * Get the student that owns the report.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the report record detail.
     */
    public function record(): HasOne
    {
        return $this->hasOne(ReportRecord::class);
    }

    /**
     * Alias for record relation.
     */
    public function reportRecord(): HasOne
    {
        return $this->hasOne(ReportRecord::class);
    }
}
