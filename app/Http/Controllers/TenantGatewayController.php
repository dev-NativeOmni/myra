<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantGatewayController extends Controller
{
    /**
     * Display the institution token gateway page.
     */
    public function index(): View|RedirectResponse
    {
        // If already logged in, redirect to appropriate home
        if (Auth::check()) {
            return Auth::user()->isWaliMurid()
                ? redirect()->route('parent.dashboard')
                : redirect()->route('dashboard');
        }

        $currentTenant = TenantContext::getTenant();
        $sampleInstitutions = Institution::where('is_active', true)->take(6)->get();

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
        $institution = Institution::where('token', $cleanToken)
            ->where('is_active', true)
            ->first();

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

    /**
     * One-time system initialization and database sync endpoint for production serverless.
     */
    public function setup(Request $request): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = Artisan::output();

            // Ensure primary institution exists
            $defaultInst = Institution::first();
            if (! $defaultInst) {
                $defaultInst = Institution::create([
                    'name' => 'PONDOK PESANTREN CONTOH',
                    'token' => 'TAQREER-DEMO',
                    'is_active' => true,
                    'sub_title' => 'Islamic Boarding School',
                    'city' => 'KOTA CONTOH',
                    'director_name' => 'Ust. Fulan, S.Pd.',
                    'director_title' => 'Direktur Pesantren',
                    'accent_color' => '#059669',
                ]);
            } elseif (empty($defaultInst->token)) {
                $defaultInst->update([
                    'token' => 'TAQREER-DEMO',
                    'is_active' => true,
                ]);
            }

            // Ensure superadmin exists with password 'password'
            $superadmin = User::withoutGlobalScopes()->where('username', 'superadmin')->first();
            if (! $superadmin) {
                User::create([
                    'institution_id' => $defaultInst->id,
                    'name' => 'Ust. Fulan (Super Admin)',
                    'username' => 'superadmin',
                    'email' => 'superadmin@taqreer.id',
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_SUPER_ADMIN,
                ]);
            } else {
                $superadmin->update([
                    'password' => Hash::make('password'),
                    'institution_id' => $defaultInst->id,
                ]);
            }

            // Ensure admin exists with password 'password'
            $admin = User::withoutGlobalScopes()->where('username', 'admin')->first();
            if (! $admin) {
                User::create([
                    'institution_id' => $defaultInst->id,
                    'name' => 'Ustadzah Fatimah (Admin)',
                    'username' => 'admin',
                    'email' => 'admin@taqreer.id',
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_ADMIN,
                ]);
            } else {
                $admin->update([
                    'password' => Hash::make('password'),
                    'institution_id' => $defaultInst->id,
                ]);
            }

            // Backfill records
            User::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);
            Classroom::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);
            Student::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Database migration and default accounts setup completed successfully.',
                'migrate_output' => $migrateOutput,
                'default_token' => $defaultInst->token,
                'accounts' => [
                    'superadmin' => 'password',
                    'admin' => 'password',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
