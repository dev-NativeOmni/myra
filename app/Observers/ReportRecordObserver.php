<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ReportRecord;

class ReportRecordObserver
{
    /**
     * Record every changed evaluation field (system columns and custom fields).
     */
    public function updated(ReportRecord $record): void
    {
        $report = $record->monthlyReport()->with('student')->first();

        $context = [
            'institution_id' => $report?->institution_id,
            'user_id' => auth()->id(),
            'monthly_report_id' => $report?->id,
            'student_name' => $report?->student?->name,
            'period_title' => $report?->period_title,
        ];

        foreach (array_keys($record->getChanges()) as $field) {
            if (in_array($field, ['updated_at', 'custom_fields'], true)) {
                continue;
            }

            $this->log($context, $field, $record->getOriginal($field), $record->getAttribute($field));
        }

        if ($record->wasChanged('custom_fields')) {
            $old = $record->getOriginal('custom_fields') ?? [];
            $new = $record->custom_fields ?? [];

            foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
                if (($old[$key] ?? null) !== ($new[$key] ?? null)) {
                    $this->log($context, $key, $old[$key] ?? null, $new[$key] ?? null);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(array $context, string $field, mixed $old, mixed $new): void
    {
        AuditLog::create($context + [
            'field' => $field,
            'old_value' => $old === null ? null : (string) $old,
            'new_value' => $new === null ? null : (string) $new,
        ]);
    }
}
