<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ImpersonationController;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request and ensure tenant context is established.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If route has tenant token parameter (e.g. /portal/{token})
        if ($token = $request->route('token')) {
            TenantContext::setTenantByToken($token);
        }

        // 2. If user is logged in, ensure tenant context is synced
        if ($user = $request->user()) {
            $institution = $user->institution;

            // A deactivated institution locks out its users; Super Admin support sessions stay open.
            if ($institution && ! $institution->is_active && ! $request->session()->has(ImpersonationController::IMPERSONATOR_KEY)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['username' => 'Lembaga Anda sedang dinonaktifkan. Hubungi pengelola platform.']);
            }

            if ($institution) {
                TenantContext::setTenant($institution);
            }

            return $next($request);
        }

        // 3. Exclude public gateway, login, logout and health check
        if ($request->routeIs('gateway.*') || $request->routeIs('login*') || $request->routeIs('logout') ||
            $request->is('gateway*') || $request->is('portal/*') || $request->is('login*') || $request->is('logout') ||
            $request->is('up')) {
            return $next($request);
        }

        // 4. If guest accesses protected pages without tenant context, redirect to gateway
        if (! TenantContext::hasTenant()) {
            return redirect()->route('gateway.index');
        }

        return $next($request);
    }
}
