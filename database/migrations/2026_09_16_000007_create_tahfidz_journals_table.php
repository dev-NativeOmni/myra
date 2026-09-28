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
        Schema::create('tahfidz_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->enum('type', ['ziyadah', 'murajaah', 'tahsin', 'tilawah', 'tasmi'])->default('ziyadah');
            $table->integer('juz')->nullable();
            $table->string('surah', 100)->nullable();
            $table->integer('ayah_start')->nullable();
            $table->integer('ayah_end')->nullable();
            $table->integer('page_count')->nullable(); // Jumlah halaman (untuk tilawah / ziyadah)
            $table->string('grade', 30)->nullable(); // Mumtaz, Jayyid Jiddan, Jayyid, Maqbul, Rasib
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidz_journals');
    }
};
