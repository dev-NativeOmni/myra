<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('auth.login', compact('tenant'));
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

        // Find user ignoring scope to inspect their institution
        $user = TenantContext::withoutScope(function () use ($credentials) {
            return User::where('username', $credentials['username'])->first();
        });

        if ($user) {
            // If user has an institution and a different tenant is selected, ensure isolation
            if ($tenantId !== null && $user->institution_id !== null && $user->institution_id !== $tenantId && ! $user->isSuperAdmin()) {
                return back()->withErrors([
                    'username' => 'Akun ini tidak terdaftar pada lembaga yang sedang dipilih.',
                ])->onlyInput('username');
            }

            if (TenantContext::withoutScope(fn () => Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $remember))) {
                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();
                $authenticatedUser = Auth::user();

                if ($authenticatedUser->institution) {
                    TenantContext::setTenant($authenticatedUser->institution);
                }

                return $this->redirectBasedOnRole($authenticatedUser)
                    ->with('success', "Selamat datang kembali, {$authenticatedUser->name} ({$authenticatedUser->role_label})!");
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
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Helper redirect based on role.
     */
    private function redirectBasedOnRole(User $user): RedirectResponse
    {
        return redirect()->route($user->homeRouteName());
    }
}
