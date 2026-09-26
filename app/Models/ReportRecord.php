<?php

namespace App\Models;

use App\Observers\ReportRecordObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ReportRecordObserver::class)]
class ReportRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_report_id',
        'tahfidz_setoran',
        'tahfidz_akumulasi',
        'tahfidz_rincian_juz',
        'tahfidz_notes',
        'adab_ibadah',
        'adab_akhlak',
        'adab_kerapian',
        'adab_kedisiplinan',
        'body_height_cm',
        'body_weight_kg',
        'is_baligh',
        'kesantrian_notes',
        'academic_notes',
        'last_spp',
        'last_laundry',
        'registration_status',
        'custom_fields',
    ];

    protected $casts = [
        'body_height_cm' => 'integer',
        'body_weight_kg' => 'integer',
        'custom_fields' => 'array',
    ];

    /**
     * Get value for a given field key (checks column first, then custom_fields).
     */
    public function getFieldValue(string $key): mixed
    {
        if (\array_key_exists($key, $this->attributes)) {
            return $this->getAttribute($key);
        }

        $custom = $this->custom_fields ?? [];

        return $custom[$key] ?? null;
    }

    /**
     * Check whether every active field of the given module has a value filled in.
     * A module with no active fields is considered complete (nothing required).
     */
    public function isModuleComplete(string $module): bool
    {
        $fields = ModuleField::getActiveFields($module);

        foreach ($fields as $field) {
            $value = $this->getFieldValue($field->key);
            if ($value === null || $value === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether every evaluation module of this record is complete,
     * i.e. the report is ready to be published.
     */
    public function isFullyComplete(): bool
    {
        foreach (array_keys(ModuleField::MODULES) as $module) {
            if (! $this->isModuleComplete($module)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the monthly report that owns the record.
     */
    public function monthlyReport(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class);
    }
}
