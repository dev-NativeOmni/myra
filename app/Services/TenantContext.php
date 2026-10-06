<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantContext
{
    protected static bool $ignoreScope = false;

    /**
     * True while the tenant is being resolved. Resolving it loads the authenticated
     * user, and User is itself tenant-scoped, so the scope would call back into this
     * class and recurse until PHP runs out of memory.
     */
    protected static bool $resolving = false;

    /**
     * Get the currently active institution instance.
     */
    public static function getTenant(): ?Institution
    {
        if (static::$resolving) {
            return null;
        }

        static::$resolving = true;

        try {
            // 1. Check logged-in user's institution
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->institution_id) {
                    return Institution::find($user->institution_id);
                }
            }

            // 2. Check session
            $sessionTenantId = Session::get('active_institution_id');
            if ($sessionTenantId) {
                return Institution::where('is_active', true)->find($sessionTenantId);
            }

            return null;
        } finally {
            static::$resolving = false;
        }
    }

    /**
     * Get the active institution ID.
     */
    public static function getTenantId(): ?int
    {
        if (static::$ignoreScope) {
            return null;
        }

        $tenant = static::getTenant();

        return $tenant?->id;
    }

    /**
     * Set the active institution context.
     */
    public static function setTenant(?Institution $institution): void
    {
        if (! $institution) {
            static::clear();

            return;
        }

        Session::put('active_institution_id', $institution->id);
        Session::put('active_institution_token', $institution->token);
        Session::put('active_institution_name', $institution->name);
    }

    /**
     * Set the active institution by its token.
     */
    public static function setTenantByToken(string $token): ?Institution
    {
        $institution = Institution::where('token', strtoupper(trim($token)))
            ->where('is_active', true)
            ->first();

        if ($institution) {
            static::setTenant($institution);
        }

        return $institution;
    }

    /**
     * Check if a valid tenant is selected.
     */
    public static function hasTenant(): bool
    {
        return static::getTenant() !== null;
    }

    /**
     * Clear the tenant session.
     */
    public static function clear(): void
    {
        Session::forget(['active_institution_id', 'active_institution_token', 'active_institution_name']);
    }

    /**
     * Execute a callback ignoring the institution global scope.
     */
    public static function withoutScope(callable $callback): mixed
    {
        $previous = static::$ignoreScope;
        static::$ignoreScope = true;

        try {
            return $callback();
        } finally {
            static::$ignoreScope = $previous;
        }
    }
}
