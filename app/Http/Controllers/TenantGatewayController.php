<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantGatewayController extends Controller
{
    /**
     * Display the institution token gateway page.
     */
    public function index(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->homeRouteName());
        }

        $currentTenant = null;
        $sampleInstitutions = collect();

        try {
            $currentTenant = TenantContext::getTenant();
            $sampleInstitutions = Institution::where('is_active', true)->take(6)->get();
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        return view('gateway.index', compact('currentTenant', 'sampleInstitutions'));
    }

    /**
     * Verify the entered institution token.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string|max:50',
        ], [
            'token.required' => 'Silakan masukkan kode / token akses lembaga.',
        ]);

        $cleanToken = strtoupper(trim($request->token));
        $institution = null;

        try {
            $institution = Institution::where('token', $cleanToken)
                ->where('is_active', true)
                ->first();
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Sistem sedang tidak dapat diakses. Silakan coba beberapa saat lagi atau hubungi administrator.');
        }

        if (! $institution) {
            return back()->withInput()->with('error', "Token akses lembaga '{$cleanToken}' tidak ditemukan atau sedang tidak aktif.");
        }

        TenantContext::setTenant($institution);

        return redirect()->route('login')->with('success', "Akses lembaga '{$institution->name}' berhasil diverifikasi. Silakan masuk dengan akun Anda.");
    }

    /**
     * Direct link access via token (e.g. /portal/{token}).
     */
    public function direct(string $token): RedirectResponse
    {
        $institution = TenantContext::setTenantByToken($token);

        if (! $institution) {
            return redirect()->route('gateway.index')->with('error', "Lembaga dengan token '{$token}' tidak ditemukan.");
        }

        return redirect()->route('login')->with('success', "Selamat datang di portal {$institution->name}. Silakan masuk.");
    }

    /**
     * Reset / Switch institution context.
     */
    public function reset(): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        TenantContext::clear();

        return redirect()->route('gateway.index')->with('info', 'Anda telah keluar dari akses lembaga.');
    }
}
