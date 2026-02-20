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
        Schema::table('petani', function (Blueprint $table) {
            if (! Schema::hasColumn('petani', 'foto_petani_url')) {
                $table->text('foto_petani_url')->nullable()->after('kelompok_tani');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petani', function (Blueprint $table) {
            if (Schema::hasColumn('petani', 'foto_petani_url')) {
                $table->dropColumn('foto_petani_url');
            }
        });
    }
};
