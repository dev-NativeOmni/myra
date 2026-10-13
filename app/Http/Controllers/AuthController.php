<?php

namespace App\Http\Controllers;

use App\Console\Commands\SeedDemoInstitution;
use App\Models\ImpersonationLog;
use App\Models\Institution;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Show the login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $tenant = TenantContext::getTenant();
        $demoAccounts = $tenant?->isDemo() ? $this->demoAccounts($tenant) : collect();

        return view('auth.login', compact('tenant', 'demoAccounts'));
    }

    /**
     * One-click login for the seeded accounts of the fictional DEMO institution, so demo
     * credentials never have to be shared. Every other account still needs its password.
     */
    public function demoLogin(Request $request, string $username): RedirectResponse
    {
        $demo = Institution::where('token', Institution::DEMO_TOKEN)->where('is_active', true)->first();
        $user = $demo ? $this->demoAccounts($demo)->firstWhere('username', $username) : null;

        abort_if($user === null, 404);

        $request->session()->regenerate();
        Auth::login($user);
        TenantContext::setTenant($demo);

        return $this->redirectBasedOnRole($user)
            ->with('success', "Anda masuk ke lembaga demo sebagai {$user->name} ({$user->role_label}).");
    }

    /**
     * Handle authentication attempt using username.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');
        $throttleKey = Str::transliterate(Str::lower($credentials['username']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return back()->withErrors([
                'username' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$minutes} menit.",
            ])->onlyInput('username');
        }

        $tenantId = TenantContext::getTenantId();
        $cleanUsername = strtolower(trim($credentials['username']));

        // Find user ignoring scope to inspect their institution
        $user = TenantContext::withoutScope(function () use ($cleanUsername) {
            return User::whereRaw('LOWER(username) = ?', [$cleanUsername])->first();
        });

        if ($user) {
            // If user has an institution and a different tenant is selected, ensure isolation
            if ($tenantId !== null && $user->institution_id !== null && $user->institution_id !== $tenantId && ! $user->isSuperAdmin()) {
                return back()->withErrors([
                    'username' => 'Akun ini tidak terdaftar pada lembaga yang sedang dipilih.',
                ])->onlyInput('username');
            }

            if ($user->institution && ! $user->institution->is_active && Hash::check($credentials['password'], $user->password)) {
                return back()->withErrors([
                    'username' => 'Lembaga Anda sedang dinonaktifkan. Hubungi pengelola platform.',
                ])->onlyInput('username');
            }

            if (Hash::check($credentials['password'], $user->password)) {
                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();
                Auth::login($user, $remember);

                if ($user->institution) {
                    TenantContext::setTenant($user->institution);
                } else {
                    TenantContext::clear();
                }

                return $this->redirectBasedOnRole($user)
                    ->with('success', "Selamat datang kembali, {$user->name} ({$user->role_label})!");
            }
        }

        RateLimiter::hit($throttleKey, 300);

        return back()->withErrors([
            'username' => 'Username atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('username');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $institution = $request->user()?->institution;

        // Logging out in the middle of a support session still closes its log entry.
        if ($logId = $request->session()->get(ImpersonationController::LOG_KEY)) {
            ImpersonationLog::whereKey($logId)->whereNull('ended_at')->update(['ended_at' => now()]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($institution) {
            TenantContext::setTenant($institution);
        }

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Seeded demo accounts of the given demo institution, in the seeder's display order.
     *
     * @return Collection<int, User>
     */
    private function demoAccounts(Institution $demo): Collection
    {
        $usernames = SeedDemoInstitution::accountUsernames();

        return TenantContext::withoutScope(fn () => User::query()
            ->where('institution_id', $demo->id)
            ->whereIn('username', $usernames)
            ->get())
            ->sortBy(fn (User $user) => array_search($user->username, $usernames, true))
            ->values();
    }

    /**
     * Helper redirect based on role.
     */
    private function redirectBasedOnRole(User $user): RedirectResponse
    {
        return redirect()->route($user->homeRouteName());
    }
}
