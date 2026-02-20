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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('kode_role', 30)->unique();
            $table->string('nama_role', 100);
            $table->unsignedTinyInteger('level_akses')->unique();
            $table->timestamps();
        });

        if (! Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('roles')
                    ->nullOnDelete();
            });
        }

        Schema::create('kecamatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kecamatan', 20)->unique();
            $table->string('nama_kecamatan', 120)->unique();
            $table->decimal('centroid_lat', 10, 7)->nullable();
            $table->decimal('centroid_lng', 10, 7)->nullable();
            $table->longText('geom_polygon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_wilayah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kecamatan_id')->constrained('kecamatan')->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'kecamatan_id']);
        });

        Schema::create('desa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained('kecamatan')->restrictOnDelete();
            $table->string('kode_desa', 20)->nullable();
            $table->string('nama_desa', 120);
            $table->decimal('centroid_lat', 10, 7)->nullable();
            $table->decimal('centroid_lng', 10, 7)->nullable();
            $table->longText('geom_polygon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['kecamatan_id', 'nama_desa']);
            $table->unique(['kecamatan_id', 'kode_desa']);
        });

        Schema::create('balai_penyuluh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained('kecamatan')->restrictOnDelete();
            $table->string('nama_balai', 180);
            $table->text('alamat_balai')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('foto_balai_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['kecamatan_id', 'nama_balai']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('penyuluh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('balai_id')->constrained('balai_penyuluh')->restrictOnDelete();
            $table->string('nip', 40)->nullable()->unique();
            $table->string('jabatan', 120)->nullable();
            $table->string('lokasi_penugasan', 255)->nullable();
            $table->string('tugas_tambahan', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('komoditas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_komoditas', 30)->unique();
            $table->string('nama_komoditas', 120)->unique();
            $table->string('satuan_default', 30)->default('kg');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('petani', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desa')->restrictOnDelete();
            $table->string('nik', 30)->nullable()->unique();
            $table->string('nama_petani', 150);
            $table->string('no_hp', 30)->nullable();
            $table->text('alamat_domisili')->nullable();
            $table->string('kelompok_tani', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petani_id')->constrained('petani')->cascadeOnDelete();
            $table->foreignId('desa_id')->constrained('desa')->restrictOnDelete();
            $table->text('alamat_lahan')->nullable();
            $table->decimal('luas_ha', 10, 2)->nullable();
            $table->enum('kondisi_lahan', ['baik', 'sedang', 'rusak', 'kritis'])->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->longText('geom_polygon')->nullable();
            $table->text('foto_lahan_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('lahan_komoditas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lahan_id')->constrained('lahan')->cascadeOnDelete();
            $table->foreignId('komoditas_id')->constrained('komoditas')->restrictOnDelete();
            $table->integer('tahun_tanam')->nullable();
            $table->decimal('luas_tanam_ha', 10, 2)->nullable();
            $table->enum('status_tanam', ['rencana', 'tanam', 'panen', 'bera', 'gagal'])->default('tanam');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['lahan_id', 'komoditas_id', 'tahun_tanam'], 'uk_lahan_komoditas_tahun');
        });

        Schema::create('penugasan_penyuluh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyuluh_id')->constrained('penyuluh')->restrictOnDelete();
            $table->foreignId('lahan_id')->constrained('lahan')->restrictOnDelete();
            $table->foreignId('dibuat_oleh_user_id')->constrained('users')->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status_penugasan', ['aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->timestamps();
        });

        Schema::create('kunjungan_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->constrained('penugasan_penyuluh')->cascadeOnDelete();
            $table->dateTime('tanggal_kunjungan');
            $table->text('kondisi_tanaman')->nullable();
            $table->text('catatan_umum')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('status_verifikasi', ['draft', 'menunggu', 'revisi', 'ditolak', 'disetujui'])->default('draft');
            $table->timestamps();
        });

        Schema::create('kategori_kendala', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori', 100)->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('kendala_kunjungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('kunjungan_monitoring')->cascadeOnDelete();
            $table->foreignId('kategori_kendala_id')->constrained('kategori_kendala')->restrictOnDelete();
            $table->text('deskripsi_kendala');
            $table->enum('tingkat_keparahan', ['rendah', 'sedang', 'tinggi', 'kritis'])->default('sedang');
            $table->boolean('perlu_tindak_lanjut')->default(true);
            $table->timestamps();
        });

        Schema::create('kategori_kebutuhan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori', 100)->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('kebutuhan_kunjungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('kunjungan_monitoring')->cascadeOnDelete();
            $table->foreignId('kategori_kebutuhan_id')->constrained('kategori_kebutuhan')->restrictOnDelete();
            $table->text('deskripsi_kebutuhan');
            $table->decimal('jumlah', 14, 2)->nullable();
            $table->string('satuan', 30)->nullable();
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->timestamps();
        });

        Schema::create('periode_laporan', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status_periode', ['terbuka', 'ditutup', 'arsip'])->default('terbuka');
            $table->timestamps();
            $table->unique(['bulan', 'tahun']);
        });

        Schema::create('produksi_panen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lahan_komoditas_id')->constrained('lahan_komoditas')->restrictOnDelete();
            $table->foreignId('periode_id')->constrained('periode_laporan')->restrictOnDelete();
            $table->foreignId('kunjungan_id')->nullable()->constrained('kunjungan_monitoring')->nullOnDelete();
            $table->date('tanggal_panen');
            $table->decimal('jumlah_produksi', 14, 2)->default(0);
            $table->decimal('produktivitas_kg_ha', 14, 2)->nullable();
            $table->decimal('harga_jual', 14, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('verifikasi_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('kunjungan_monitoring')->cascadeOnDelete();
            $table->foreignId('diverifikasi_oleh_user_id')->constrained('users')->restrictOnDelete();
            $table->enum('status_verifikasi', ['menunggu', 'revisi', 'ditolak', 'disetujui']);
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamp('diverifikasi_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('laporan_pimpinan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('periode_laporan')->restrictOnDelete();
            $table->foreignId('generated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('jenis_laporan', 50);
            $table->text('file_url');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('laporan_pimpinan_kecamatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_pimpinan')->cascadeOnDelete();
            $table->foreignId('kecamatan_id')->constrained('kecamatan')->restrictOnDelete();
            $table->decimal('total_produksi', 14, 2)->default(0);
            $table->decimal('total_luas', 14, 2)->default(0);
            $table->unsignedInteger('total_petani')->default(0);
            $table->unsignedInteger('total_lahan')->default(0);
            $table->timestamps();
            $table->unique(['laporan_id', 'kecamatan_id']);
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 160);
            $table->text('pesan');
            $table->string('ref_tipe', 50)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_read']);
        });

        Schema::create('lampiran_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->nullable()->constrained('kunjungan_monitoring')->nullOnDelete();
            $table->foreignId('lahan_id')->nullable()->constrained('lahan')->nullOnDelete();
            $table->foreignId('balai_id')->nullable()->constrained('balai_penyuluh')->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('file_url');
            $table->enum('file_type', ['image', 'video', 'document', 'other'])->default('image');
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 50);
            $table->string('entity_name', 80);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamps();
            $table->index(['entity_name', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('lampiran_media');
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('laporan_pimpinan_kecamatan');
        Schema::dropIfExists('laporan_pimpinan');
        Schema::dropIfExists('verifikasi_log');
        Schema::dropIfExists('produksi_panen');
        Schema::dropIfExists('periode_laporan');
        Schema::dropIfExists('kebutuhan_kunjungan');
        Schema::dropIfExists('kategori_kebutuhan');
        Schema::dropIfExists('kendala_kunjungan');
        Schema::dropIfExists('kategori_kendala');
        Schema::dropIfExists('kunjungan_monitoring');
        Schema::dropIfExists('penugasan_penyuluh');
        Schema::dropIfExists('lahan_komoditas');
        Schema::dropIfExists('lahan');
        Schema::dropIfExists('petani');
        Schema::dropIfExists('komoditas');
        Schema::dropIfExists('penyuluh');
        Schema::dropIfExists('balai_penyuluh');
        Schema::dropIfExists('desa');
        Schema::dropIfExists('user_wilayah');
        Schema::dropIfExists('kecamatan');

        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }

        Schema::dropIfExists('roles');
    }
};
