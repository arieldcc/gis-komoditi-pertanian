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
        Schema::create('usulan_perubahan_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->constrained('penugasan_penyuluh')->cascadeOnDelete();
            $table->foreignId('kunjungan_id')->nullable()->constrained('kunjungan_monitoring')->nullOnDelete();
            $table->foreignId('diajukan_oleh_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('diproses_oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('target_tipe', ['petani', 'lahan', 'lahan_komoditas']);
            $table->unsignedBigInteger('target_id');
            $table->string('field_name', 80);
            $table->text('nilai_lama')->nullable();
            $table->text('nilai_usulan')->nullable();
            $table->text('alasan')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamps();

            $table->index(['penugasan_id', 'status']);
            $table->index(['target_tipe', 'target_id']);
            $table->index(['diajukan_oleh_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usulan_perubahan_data');
    }
};
