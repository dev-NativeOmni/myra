<?php

namespace App\Providers;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');

        // Ensure database migrations and default tenant token are applied in production serverless
        if ($this->app->environment('production') && ! $this->app->runningInConsole()) {
            $lockFile = '/tmp/migration_v4.lock';
            if (! file_exists($lockFile)) {
                try {
                    Artisan::call('migrate', ['--force' => true]);

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

                    // Ensure superadmin account exists and password is valid
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
                    }

                    // Ensure admin account exists
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
                    }

                    // Backfill institution_id for existing records if null
                    User::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);
                    Classroom::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);
                    Student::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);

                    // Seed full dataset if empty
                    if (User::withoutGlobalScopes()->count() <= 2) {
                        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\SampleDataSeeder', '--force' => true]);
                    }

                    @touch($lockFile);
                } catch (\Throwable $e) {
                    Log::error('Auto-migration error: '.$e->getMessage());
                }
            }
        }
    }
}
