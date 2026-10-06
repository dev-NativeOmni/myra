<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A support session in which the platform Super Admin acted as an institution's Admin.
 */
class ImpersonationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'impersonator_id',
        'impersonated_user_id',
        'institution_id',
        'ip_address',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id')->withoutGlobalScopes();
    }

    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_user_id')->withoutGlobalScopes();
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
