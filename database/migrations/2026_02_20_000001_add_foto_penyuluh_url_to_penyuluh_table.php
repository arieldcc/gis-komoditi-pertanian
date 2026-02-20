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
        Schema::table('penyuluh', function (Blueprint $table) {
            if (! Schema::hasColumn('penyuluh', 'foto_penyuluh_url')) {
                $table->text('foto_penyuluh_url')->nullable()->after('tugas_tambahan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penyuluh', function (Blueprint $table) {
            if (Schema::hasColumn('penyuluh', 'foto_penyuluh_url')) {
                $table->dropColumn('foto_penyuluh_url');
            }
        });
    }
};
