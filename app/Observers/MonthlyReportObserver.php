<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\MonthlyReport;

class MonthlyReportObserver
{
    /**
     * Record who changed the publish status of a report.
     */
    public function updated(MonthlyReport $report): void
    {
        if (! $report->wasChanged('status')) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'monthly_report_id' => $report->id,
            'student_name' => $report->student?->name,
            'period_title' => $report->period_title,
            'field' => 'status',
            'old_value' => $report->getOriginal('status'),
            'new_value' => $report->status,
        ]);
    }
}
