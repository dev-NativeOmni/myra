<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update institutions table with token and status
        Schema::table('institutions', function (Blueprint $table) {
            if (! Schema::hasColumn('institutions', 'token')) {
                $table->string('token', 50)->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('institutions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('token');
            }
        });

        // Ensure first institution has a default token
        $firstInstitution = DB::table('institutions')->first();
        if ($firstInstitution) {
            DB::table('institutions')->where('id', $firstInstitution->id)->update([
                'token' => $firstInstitution->token ?: 'TAQREER-DEMO',
                'is_active' => true,
            ]);
            $defaultInstitutionId = $firstInstitution->id;
        } else {
            $defaultInstitutionId = DB::table('institutions')->insertGetId([
                'name' => 'PONDOK PESANTREN CONTOH',
                'token' => 'TAQREER-DEMO',
                'is_active' => true,
                'city' => 'KOTA CONTOH',
                'director_name' => 'Ust. Fulan, S.Pd.',
                'director_title' => 'Direktur Pesantren',
                'accent_color' => '#059669',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Add institution_id to data tables
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

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (! Schema::hasColumn($tableName, 'institution_id')) {
                        $table->foreignId('institution_id')
                            ->nullable()
                            ->after('id')
                            ->constrained('institutions')
                            ->cascadeOnDelete();
                    }
                });

                // Backfill existing records with the default institution id
                DB::table($tableName)
                    ->whereNull('institution_id')
                    ->update(['institution_id' => $defaultInstitutionId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'academic_calendars',
            'class_schedules',
            'tahfidz_journals',
            'module_fields',
            'monthly_reports',
            'students',
            'classrooms',
            'users',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'institution_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign([$tableName.'_institution_id_foreign']);
                    $table->dropColumn('institution_id');
                });
            }
        }

        if (Schema::hasTable('institutions')) {
            Schema::table('institutions', function (Blueprint $table) {
                if (Schema::hasColumn('institutions', 'token')) {
                    $table->dropColumn('token');
                }
                if (Schema::hasColumn('institutions', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }
    }
};
