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
        Schema::create('map_marker_styles', function (Blueprint $table) {
            $table->id();
            $table->string('style_code', 80)->unique();
            $table->enum('scope', ['entity', 'komoditas']);
            $table->foreignId('komoditas_id')->nullable()->constrained('komoditas')->nullOnDelete();
            $table->string('label', 120);
            $table->string('icon_symbol', 12)->default('📍');
            $table->string('icon_color', 20)->default('#1e7b4f');
            $table->string('bg_color', 20)->default('#ffffff');
            $table->unsignedTinyInteger('size')->default(28);
            $table->timestamps();
            $table->unique('komoditas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_marker_styles');
    }
};
