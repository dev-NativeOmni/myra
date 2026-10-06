<?php

namespace App\Traits;

use App\Models\Institution;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToInstitution
{
    /**
     * Boot the trait and apply the global tenant scope and creating hook.
     */
    protected static function bootBelongsToInstitution(): void
    {
        static::addGlobalScope('institution', function (Builder $builder) {
            $tenantId = TenantContext::getTenantId();
            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable().'.institution_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->institution_id)) {
                $tenantId = TenantContext::getTenantId();
                if ($tenantId !== null) {
                    $model->institution_id = $tenantId;
                } elseif (isset($model->student_id) && $model->student) {
                    $model->institution_id = $model->student->institution_id;
                } elseif (isset($model->classroom_id) && $model->classroom) {
                    $model->institution_id = $model->classroom->institution_id;
                } elseif (isset($model->user_id) && $model->user) {
                    $model->institution_id = $model->user->institution_id;
                } elseif ($defaultInst = Institution::first()) {
                    $model->institution_id = $defaultInst->id;
                }
            }
        });
    }

    /**
     * Get the institution that owns this record.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
