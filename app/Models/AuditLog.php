<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use BelongsToInstitution, HasFactory, Prunable;

    public const UPDATED_AT = null;

    /** Number of days audit entries are kept before being pruned. */
    public const RETENTION_DAYS = 365;

    protected $fillable = [
        'institution_id',
        'user_id',
        'monthly_report_id',
        'student_name',
        'period_title',
        'field',
        'old_value',
        'new_value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(self::RETENTION_DAYS));
    }

    /**
     * Human readable label for the audited field (module field label, or the raw key).
     */
    public function getFieldLabelAttribute(): string
    {
        if ($this->field === 'status') {
            return 'Status Terbit';
        }

        $label = ModuleField::where('key', $this->field)->value('label');

        return $label ? preg_replace('/^\d+\.\s*/', '', $label) : $this->field;
    }
}
