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
        Schema::table('usulan_perubahan_data', function (Blueprint $table) {
            if (! Schema::hasColumn('usulan_perubahan_data', 'waktu_pengajuan')) {
                $table->timestamp('waktu_pengajuan')->nullable()->after('alasan');
                $table->index('waktu_pengajuan');
            }
        });

        DB::table('usulan_perubahan_data')
            ->whereNull('waktu_pengajuan')
            ->update([
                'waktu_pengajuan' => DB::raw('created_at'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usulan_perubahan_data', function (Blueprint $table) {
            if (Schema::hasColumn('usulan_perubahan_data', 'waktu_pengajuan')) {
                $table->dropIndex(['waktu_pengajuan']);
                $table->dropColumn('waktu_pengajuan');
            }
        });
    }
};
