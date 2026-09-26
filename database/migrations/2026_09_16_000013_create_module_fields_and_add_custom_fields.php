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
        Schema::create('module_fields', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50); // tahfidz, kesantrian, akademik, administrasi
            $table->string('key', 100);    // column name or custom key
            $table->string('label', 150);
            $table->string('type', 30)->default('text'); // text, select, number, textarea
            $table->json('options')->nullable(); // For select type options
            $table->string('placeholder')->nullable();
            $table->string('suffix', 30)->nullable(); // e.g. cm, kg
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['module', 'order_index']);
        });

        Schema::table('report_records', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('registration_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_records', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::dropIfExists('module_fields');
    }
};

