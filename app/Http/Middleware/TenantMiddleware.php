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

        // 3. Exclude public gateway, health check, and direct login post
        if ($request->routeIs('gateway.*') || $request->is('gateway*') || $request->is('portal/*') || $request->is('up')) {
            return $next($request);
        }

        if ($request->is('login')) {
            if ($request->isMethod('GET') && ! TenantContext::hasTenant()) {
                return redirect()->route('gateway.index');
            }

            return $next($request);
        }

        // 4. If guest accesses other pages without selecting an institution token
        if (! TenantContext::hasTenant()) {
            return redirect()->route('gateway.index');
        }

        return $next($request);
    }
}
