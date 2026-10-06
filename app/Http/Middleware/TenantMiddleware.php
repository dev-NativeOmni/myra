<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
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
        if ($request->user()) {
            if ($request->user()->institution_id) {
                TenantContext::setTenant($request->user()->institution);
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
