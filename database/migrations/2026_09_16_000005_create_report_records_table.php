<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->unique()->constrained('monthly_reports')->cascadeOnDelete();

            // Tahfidz
            $table->string('tahfidz_setoran', 100)->nullable();
            $table->string('tahfidz_akumulasi', 100)->nullable();
            $table->string('tahfidz_rincian_juz', 100)->nullable();
            $table->text('tahfidz_notes')->nullable();

            // Kesantrian
            $table->string('adab_ibadah', 20)->nullable();
            $table->string('adab_akhlak', 20)->nullable();
            $table->string('adab_kerapian', 20)->nullable();
            $table->string('adab_kedisiplinan', 20)->nullable();
            $table->integer('body_height_cm')->nullable();
            $table->integer('body_weight_kg')->nullable();
            $table->enum('is_baligh', ['Belum', 'Sudah'])->default('Belum');
            $table->text('kesantrian_notes')->nullable();

            // Akademik
            $table->text('academic_notes')->nullable();

            // Administrasi
            $table->string('last_spp', 50)->nullable();
            $table->string('last_laundry', 50)->nullable();
            $table->string('registration_status', 50)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_records');
    }
};
