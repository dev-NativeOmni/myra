<?php

namespace App\Http\Controllers;

use App\Models\ImpersonationLog;
use App\Models\Institution;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets the platform Super Admin act as an institution's Admin to help with
 * support issues, and return to the platform afterwards. Every session is logged.
 */
class ImpersonationController extends Controller
{
    /** Session key holding the Super Admin's user id while impersonating. */
    public const IMPERSONATOR_KEY = 'impersonator_id';

    /** Session key holding the ImpersonationLog id of the running session. */
    public const LOG_KEY = 'impersonation_log_id';

    public function start(Request $request, Institution $institution, string $user): RedirectResponse
    {
        $superAdmin = $request->user();

        $admin = User::withoutGlobalScopes()
            ->where('id', $user)
            ->where('institution_id', $institution->id)
            ->where('role', User::ROLE_ADMIN)
            ->firstOrFail();

        $log = ImpersonationLog::create([
            'impersonator_id' => $superAdmin->id,
            'impersonated_user_id' => $admin->id,
            'institution_id' => $institution->id,
            'ip_address' => $request->ip(),
            'started_at' => now(),
        ]);

        Auth::login($admin);
        $request->session()->regenerate();
        $request->session()->put(self::IMPERSONATOR_KEY, $superAdmin->id);
        $request->session()->put(self::LOG_KEY, $log->id);
        TenantContext::setTenant($institution);

        return redirect()->route($admin->homeRouteName())
            ->with('success', "Anda sekarang membantu {$institution->name} sebagai {$admin->name} (@{$admin->username}).");
    }

    public function stop(Request $request): RedirectResponse
    {
        $superAdminId = $request->session()->pull(self::IMPERSONATOR_KEY);
        $logId = $request->session()->pull(self::LOG_KEY);

        $superAdmin = $superAdminId
            ? User::withoutGlobalScopes()->where('id', $superAdminId)->where('role', User::ROLE_SUPER_ADMIN)->first()
            : null;

        abort_unless($superAdmin, 403, 'Anda tidak sedang dalam sesi bantuan Super Admin.');

        ImpersonationLog::whereKey($logId)->whereNull('ended_at')->update(['ended_at' => now()]);

        TenantContext::clear();
        Auth::login($superAdmin);
        $request->session()->regenerate();

        return redirect()->route('platform.institutions.index')->with('success', 'Sesi bantuan selesai. Anda kembali ke Platform.');
    }
}
