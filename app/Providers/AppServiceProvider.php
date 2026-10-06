<?php

namespace App\Providers;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
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
            $lockFile = '/tmp/migration_v2.lock';
            if (! file_exists($lockFile)) {
                try {
                    Artisan::call('migrate', ['--force' => true]);

                    // Ensure primary institution has token TAQREER-DEMO
                    $defaultInst = Institution::first();
                    if ($defaultInst && empty($defaultInst->token)) {
                        $defaultInst->update([
                            'token' => 'TAQREER-DEMO',
                            'is_active' => true,
                        ]);
                    }

                    // Backfill institution_id for existing records if null
                    if ($defaultInst) {
                        User::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);
                        Classroom::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);
                        Student::whereNull('institution_id')->update(['institution_id' => $defaultInst->id]);
                    }

                    @touch($lockFile);
                } catch (\Throwable $e) {
                    Log::error('Auto-migration error: '.$e->getMessage());
                }
            }
        }
    }
}
