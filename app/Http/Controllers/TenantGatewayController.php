<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TenantGatewayController extends Controller
{
    /**
     * Display the institution token gateway page.
     */
    public function index(): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->isWaliMurid()
                ? redirect()->route('parent.dashboard')
                : redirect()->route('dashboard');
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
            return back()->withInput()->with('error', 'Database belum diinisialisasi. Silakan akses /system/setup terlebih dahulu.');
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

    /**
     * One-time direct schema & accounts initialization endpoint for production serverless.
     */
    public function setup(Request $request): JsonResponse
    {
        try {
            $log = [];

            // 0. Run database migrations to ensure all core tables exist
            Artisan::call('migrate', ['--force' => true]);
            $log[] = 'Migration: '.trim(Artisan::output());

            // 1. Ensure token and is_active columns exist on institutions
            if (! Schema::hasColumn('institutions', 'token')) {
                Schema::table('institutions', function (Blueprint $table) {
                    $table->string('token', 50)->nullable()->unique();
                });
                $log[] = 'Added institutions.token';
            }
            if (! Schema::hasColumn('institutions', 'is_active')) {
                Schema::table('institutions', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true);
                });
                $log[] = 'Added institutions.is_active';
            }

            // 2. Ensure primary institution exists
            $defaultInst = DB::table('institutions')->first();
            if (! $defaultInst) {
                $defaultId = DB::table('institutions')->insertGetId([
                    'name' => 'PONDOK PESANTREN CONTOH',
                    'token' => 'TAQREER-DEMO',
                    'is_active' => true,
                    'sub_title' => 'Islamic Boarding School',
                    'city' => 'KOTA CONTOH',
                    'director_name' => 'Ust. Fulan, S.Pd.',
                    'director_title' => 'Direktur Pesantren',
                    'accent_color' => '#059669',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $defaultInst = DB::table('institutions')->where('id', $defaultId)->first();
                $log[] = 'Created default institution';
            } elseif (empty($defaultInst->token)) {
                DB::table('institutions')->where('id', $defaultInst->id)->update([
                    'token' => 'TAQREER-DEMO',
                    'is_active' => true,
                ]);
                $log[] = 'Updated default institution token to TAQREER-DEMO';
            }

            // 3. Ensure institution_id exists on data tables
            $tables = [
                'users',
                'classrooms',
                'students',
                'monthly_reports',
                'module_fields',
                'tahfidz_journals',
                'class_schedules',
                'academic_calendars',
            ];

            foreach ($tables as $tbl) {
                if (Schema::hasTable($tbl)) {
                    if (! Schema::hasColumn($tbl, 'institution_id')) {
                        Schema::table($tbl, function (Blueprint $table) {
                            $table->unsignedBigInteger('institution_id')->nullable()->index();
                        });
                        $log[] = "Added {$tbl}.institution_id";
                    }
                    DB::table($tbl)->whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);
                }
            }

            // 4. Ensure superadmin exists with password 'password'
            $superadmin = DB::table('users')->where('username', 'superadmin')->first();
            if (! $superadmin) {
                DB::table('users')->insert([
                    'institution_id' => $defaultInst->id,
                    'name' => 'Ust. Fulan (Super Admin)',
                    'username' => 'superadmin',
                    'email' => 'superadmin@taqreer.id',
                    'password' => Hash::make('password'),
                    'role' => 'super_admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $log[] = 'Created superadmin user';
            } else {
                DB::table('users')->where('id', $superadmin->id)->update([
                    'password' => Hash::make('password'),
                    'institution_id' => $defaultInst->id,
                ]);
                $log[] = 'Updated superadmin password';
            }

            // 5. Ensure admin exists with password 'password'
            $admin = DB::table('users')->where('username', 'admin')->first();
            if (! $admin) {
                DB::table('users')->insert([
                    'institution_id' => $defaultInst->id,
                    'name' => 'Ustadzah Fatimah (Admin)',
                    'username' => 'admin',
                    'email' => 'admin@taqreer.id',
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $log[] = 'Created admin user';
            } else {
                DB::table('users')->where('id', $admin->id)->update([
                    'password' => Hash::make('password'),
                    'institution_id' => $defaultInst->id,
                ]);
                $log[] = 'Updated admin password';
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Setup completed successfully.',
                'log' => $log,
                'default_token' => 'TAQREER-DEMO',
                'accounts' => [
                    'superadmin' => 'password',
                    'admin' => 'password',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ], 500);
        }
    }
}
