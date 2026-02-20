<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kecamatan', function (Blueprint $table) {
            if (! Schema::hasColumn('kecamatan', 'alamat')) {
                $table->text('alamat')->nullable()->after('nama_kecamatan');
            }
        });

        Schema::table('desa', function (Blueprint $table) {
            if (! Schema::hasColumn('desa', 'alamat')) {
                $table->text('alamat')->nullable()->after('nama_desa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kecamatan', function (Blueprint $table) {
            if (Schema::hasColumn('kecamatan', 'alamat')) {
                $table->dropColumn('alamat');
            }
        });

        Schema::table('desa', function (Blueprint $table) {
            if (Schema::hasColumn('desa', 'alamat')) {
                $table->dropColumn('alamat');
            }
        });
    }
};
