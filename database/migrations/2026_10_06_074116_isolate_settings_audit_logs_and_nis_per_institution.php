<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separates the remaining shared data per institution:
 * - settings (academic calendar / holidays) were global for every institution,
 * - audit logs were visible to every institution's admin,
 * - student NIS had to be unique across the whole platform.
 *
 * Existing settings belong to the first institution (the only one before
 * multi-institution support); audit logs take the institution of their report.
 */
return new class extends Migration
{
    public function up(): void
    {
        $firstInstitutionId = DB::table('institutions')->orderBy('id')->value('id');

        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['key']);
        });
        DB::table('settings')->whereNull('institution_id')->update(['institution_id' => $firstInstitutionId]);
        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['institution_id', 'key']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        DB::table('audit_logs')->whereNull('institution_id')->update([
            'institution_id' => DB::table('monthly_reports')
                ->select('institution_id')
                ->whereColumn('monthly_reports.id', 'audit_logs.monthly_report_id')
                ->limit(1),
        ]);
        DB::table('audit_logs')->whereNull('institution_id')->update(['institution_id' => $firstInstitutionId]);

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['nis']);
            $table->unique(['institution_id', 'nis']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['institution_id', 'nis']);
            $table->unique('nis');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_id');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['institution_id', 'key']);
            $table->dropConstrainedForeignId('institution_id');
            $table->unique('key');
        });
    }
};
