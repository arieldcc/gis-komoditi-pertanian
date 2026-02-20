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
        Schema::table('lahan_komoditas', function (Blueprint $table) {
            if (! Schema::hasColumn('lahan_komoditas', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('catatan');
            }
            if (! Schema::hasColumn('lahan_komoditas', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('lahan_komoditas', 'alamat_titik')) {
                $table->text('alamat_titik')->nullable()->after('longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lahan_komoditas', function (Blueprint $table) {
            if (Schema::hasColumn('lahan_komoditas', 'alamat_titik')) {
                $table->dropColumn('alamat_titik');
            }
            if (Schema::hasColumn('lahan_komoditas', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('lahan_komoditas', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};

