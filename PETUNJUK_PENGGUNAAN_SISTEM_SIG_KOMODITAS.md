# PETUNJUK PENGGUNAAN SISTEM
## Sistem Informasi Geografis Komoditas Pertanian

Dokumen ini berisi panduan operasional sistem SIG Komoditas Pertanian berbasis Laravel untuk 4 level user:
1. Admin Dinas (pusat)
2. Admin Kecamatan
3. Penyuluh
4. Pimpinan Dinas

---

## 1. Tujuan Sistem
Sistem ini digunakan untuk:
- Mengelola data master wilayah, komoditas, balai, penyuluh, kelompok tani, petani, lahan, dan titik komoditas.
- Mencatat kunjungan penyuluh secara digital beserta foto, kendala, kebutuhan, dan data produksi.
- Menjalankan verifikasi laporan berjenjang (penyuluh -> admin kecamatan -> pimpinan).
- Menyajikan peta dan dashboard realtime untuk pengambilan keputusan.

---

## 2. Persiapan Sebelum Menggunakan Sistem

### 2.1 Menjalankan aplikasi
1. Pastikan MySQL lokal sudah aktif.
2. Masuk ke folder project `sig-komoditas-pertanian`.
3. Jalankan web server Laravel.
4. Jalankan Vite jika menggunakan mode development frontend.
5. Akses aplikasi melalui browser: `http://127.0.0.1:8000`.

### 2.2 Akun default (hasil seeder)
Gunakan akun awal berikut untuk login pertama:

| Role | Email | Password |
|---|---|---|
| Admin Dinas | admin@sigkomoditas.id | Admin12345! |
| Admin Kecamatan | admin.kecamatan@sigkomoditas.id | password123 |
| Penyuluh | penyuluh@sigkomoditas.id | password123 |
| Pimpinan Dinas | pimpinan@sigkomoditas.id | password123 |

Catatan keamanan:
- Segera ubah password setelah login pertama.
- Nonaktifkan user yang sudah tidak bertugas.

### 2.3 Komponen antarmuka yang dipakai di hampir semua menu
- DataTable server-side untuk daftar data.
- Tombol aksi standar: View, Edit, Delete.
- Notifikasi menggunakan SweetAlert2.
- Input titik spasial lewat peta (klik peta -> latitude, longitude, alamat otomatis/manual).

---

## 3. Alur Data Utama Sistem
1. Admin Dinas menyiapkan master data dan akun awal.
2. Admin Kecamatan mengelola penyuluh, petani, lahan, komoditas lahan, serta penugasan penyuluh ke petani.
3. Penyuluh melakukan kunjungan terpadu, menginput laporan lapangan + foto.
4. Admin Kecamatan memverifikasi laporan dan memproses usulan perubahan data.
5. Pimpinan Dinas melihat ringkasan, peta, analitik, dan mengunduh laporan CSV/PDF.

---

## 4. Panduan Step-by-Step Per Role

## 4.1 Admin Dinas (Full Access)

### Menu 1: Dashboard
1. Login sebagai admin dinas.
2. Buka menu `Dashboard`.
3. Periksa KPI, grafik pie/bar/candlestick, dan feed aktivitas realtime.
4. Data dashboard akan update otomatis tanpa refresh halaman.

### Menu 2: Manajemen User
1. Buka `Manajemen User`.
2. Klik `Tambah User`.
3. Isi nama, email, role, status aktif, password.
4. Simpan data.
5. Gunakan Edit untuk perbaikan data user.
6. Gunakan Delete untuk menghapus user yang tidak dipakai.

Catatan:
- Akun Admin Kecamatan sebaiknya dibuat dari menu Balai.
- Akun Penyuluh sebaiknya dibuat dari menu Data Balai & Penyuluh.

### Menu 3: Master Wilayah
1. Buka `Master Wilayah`.
2. Tambah Kecamatan:
   1. Isi nama kecamatan.
   2. Klik peta untuk mengisi koordinat centroid.
   3. Alamat dapat terisi otomatis; jika gagal geocoding, isi manual.
   4. Simpan.
3. Tambah Desa:
   1. Pilih kecamatan.
   2. Isi nama desa.
   3. Klik peta untuk titik desa.
   4. Simpan.
4. Untuk data yang sudah ada, gunakan tombol View/Edit/Delete di tabel.

### Menu 4: Master Komoditas
1. Buka `Master Komoditas`.
2. Isi nama komoditas dan satuan default.
3. Kode komoditas dibuat otomatis oleh sistem.
4. Simpan dan lanjutkan untuk komoditas lain.

### Menu 5: Master Kelompok Tani
1. Buka `Master Kelompok Tani`.
2. Input nama kelompok tani.
3. Kode kelompok dibuat otomatis.
4. Simpan.

### Menu 6: Style Ikon Peta
1. Buka `Style Ikon Peta`.
2. Atur icon symbol, warna icon, warna latar, ukuran untuk entity peta:
   - Kecamatan
   - Desa
   - Balai
   - Komoditas default
3. Atur style khusus per komoditas jika diperlukan.
4. Simpan agar ikon berubah dinamis di peta sistem.

### Menu 7: Data Balai & Penyuluh
#### A. Tambah Balai + akun Admin Kecamatan
1. Buka `Data Balai & Penyuluh`.
2. Pada form `Tambah Balai Penyuluh`, isi:
   - Kecamatan
   - Nama dan alamat balai
   - Titik peta (latitude/longitude)
   - Foto balai (maksimal 5MB)
   - Profil akun admin kecamatan (nama, email, password)
3. Simpan.

Ketentuan:
- 1 kecamatan hanya boleh 1 balai.
- Balai tersebut menjadi basis akun admin kecamatan.

#### B. Tambah Penyuluh + akun Penyuluh
1. Masih di menu yang sama, gunakan form `Tambah Penyuluh`.
2. Isi profil lengkap penyuluh, pilih balai.
3. Upload foto penyuluh (maksimal 5MB).
4. Isi email dan password akun penyuluh.
5. Simpan.

### Menu 8: Monitoring Laporan
1. Buka `Monitoring Laporan`.
2. Gunakan filter status bila perlu.
3. Klik View untuk melihat detail laporan penyuluh.
4. Isi respon verifikasi jika perlu tindak lanjut.
5. Status dapat diubah ke menunggu/revisi/ditolak/disetujui sesuai kebijakan.

### Menu 9: Laporan Pimpinan
1. Buka `Laporan Pimpinan`.
2. Buat periode laporan (bulan, tahun, rentang tanggal).
3. Buat laporan (jenis laporan + file referensi).
4. Klik `Generate Detail` untuk membuat ringkasan per kecamatan dari data produksi.
5. Pastikan detail terisi sebelum digunakan di level pimpinan.

---

## 4.2 Admin Kecamatan

### Menu 1: Dashboard
1. Login sebagai admin kecamatan.
2. Cek kartu ringkasan (penyuluh aktif, laporan menunggu, usulan perubahan).
3. Pantau grafik realtime status laporan dan aktivitas kunjungan.

### Menu 2: Data Penyuluh
#### A. Tambah Penyuluh
1. Buka `Data Penyuluh`.
2. Isi data akun dan profil penyuluh.
3. Pilih balai di kecamatan Anda.
4. Upload foto penyuluh (maks 5MB).
5. Simpan.

#### B. Penugasan Penyuluh ke Petani/Lahan
1. Pada bagian `Penugasan Penyuluh ke Petani`, pilih:
   - Penyuluh
   - Lahan petani
   - Tanggal mulai/selesai
   - Status penugasan
2. Simpan.

Catatan:
- 1 penyuluh dapat ditugaskan ke lebih dari 1 petani/lahan.
- Kombinasi penugasan aktif duplikat akan ditolak sistem.

### Menu 3: Master Kelompok Tani
1. Buka `Master Kelompok Tani`.
2. Tambah atau ubah kelompok tani aktif.
3. Data ini dipakai pada profil petani.

### Menu 4: Data Petani & Lahan
#### A. Tambah Petani
1. Pilih desa.
2. Isi nama petani, no HP, alamat domisili, kelompok tani.
3. Upload foto petani (wajib, maksimal 5MB).
4. Simpan.

#### B. Tambah Lahan
1. Pilih petani dan desa.
2. Isi luas, kondisi, alamat lahan.
3. Klik peta untuk titik latitude/longitude.
4. Upload foto lahan (wajib, maksimal 5MB).
5. Simpan.

#### C. Tambah Lahan Komoditas
1. Pilih lahan dan komoditas.
2. Isi tahun tanam, luas tanam, status tanam, catatan.
3. Klik peta untuk titik komoditas (wajib latitude/longitude).
4. Simpan.

#### D. Peta Sebaran Komoditas
1. Gunakan filter komoditas.
2. Klik marker untuk melihat ringkasan data dasar dan foto.

### Menu 5: Verifikasi Laporan
#### A. Verifikasi laporan kunjungan penyuluh
1. Buka `Verifikasi Laporan`.
2. Pada tabel kunjungan, klik View.
3. Cek detail lengkap:
   - Biodata petani/lahan/komoditas target
   - Kondisi tanaman
   - Kendala dan kebutuhan
   - Produksi
   - Foto kunjungan/foto kendala
4. Isi respon:
   - `Disetujui`, atau
   - `Revisi` + keterangan revisi.
5. Simpan respon.

#### B. Proses usulan perubahan data lapangan
1. Pada tabel usulan perubahan, cek field yang diajukan.
2. Periksa alasan perubahan dan waktu pengajuan.
3. Pilih `Disetujui` atau `Ditolak`.
4. Tambahkan catatan admin bila diperlukan.

Catatan:
- Jika disetujui, perubahan data target akan diterapkan sistem.

### Menu 6: Rekap Kecamatan
1. Buka `Rekap Kecamatan`.
2. Pantau total petani, total lahan, total luas, dan rekap komoditas.
3. Gunakan hasil ini untuk evaluasi mingguan/bulanan kecamatan.

---

## 4.3 Penyuluh

### Menu 1: Dashboard
1. Login sebagai penyuluh.
2. Periksa:
   - Petani binaan aktif
   - Kunjungan minggu ini
   - Status laporan
3. Pantau grafik dan aktivitas kunjungan realtime.

### Menu 2: Kunjungan Terpadu
Menu ini adalah menu utama kerja penyuluh.

#### A. Input laporan kunjungan
1. Buka `Kunjungan Terpadu`.
2. Pilih `Penugasan Petani`.
3. Sistem otomatis menampilkan:
   - Profil petani
   - Profil lahan
   - Komoditas lahan
4. Isi data kunjungan:
   - Tanggal kunjungan
   - Status laporan (draft/menunggu/revisi)
   - Kondisi tanaman, catatan umum, rekomendasi
5. Upload `Foto Kunjungan` (wajib, maksimal 5MB).
6. Jika ada, isi data produksi:
   - Komoditas lahan
   - Periode
   - Tanggal panen
   - Jumlah produksi
   - Harga jual, produktivitas, catatan
7. Jika ada, isi kendala dan kebutuhan.
8. Jika melaporkan kendala, upload foto kendala (maksimal 5MB).
9. Simpan kunjungan.

Catatan validasi:
- Jika input gagal, data yang sudah diisi tidak dibersihkan total.
- Sistem menampilkan pesan field yang kurang/salah.

#### B. Pengajuan Perbaikan Data Lapangan (terpisah)
1. Masih di halaman kunjungan, buka bagian usulan perbaikan.
2. Pilih penugasan petani.
3. Isi field yang ingin dikoreksi (misal titik lokasi, luas lahan, status tanam).
4. Isi alasan perubahan.
5. Simpan usulan.

Catatan:
- Usulan tidak langsung mengubah data utama.
- Perubahan menunggu persetujuan admin kecamatan.

### Menu 3: Riwayat Laporan
1. Buka `Riwayat Laporan`.
2. Lihat status setiap laporan yang telah dikirim.
3. Baca catatan verifikasi dari admin kecamatan.
4. Lakukan perbaikan jika status revisi.

---

## 4.4 Pimpinan Dinas

### Menu 1: Dashboard
1. Login sebagai pimpinan.
2. Pantau indikator strategis realtime:
   - Kecamatan terlapor
   - Laporan terverifikasi
   - Total produksi
   - Komoditas dominan
3. Analisis cepat melalui pie/bar/candlestick.

### Menu 2: Peta Komoditas
1. Buka `Peta Komoditas`.
2. Lihat sebaran marker:
   - Kecamatan
   - Desa
   - Balai penyuluh
   - Titik komoditas
3. Klik marker untuk melihat data dasar dan foto (jika tersedia).

### Menu 3: Laporan Terverifikasi
1. Buka `Laporan Terverifikasi`.
2. Tinjau daftar laporan dan detail agregat per kecamatan.
3. Gunakan data ini sebagai dasar pengambilan keputusan.

### Menu 4: Analitik Tren
1. Buka `Analitik Tren`.
2. Pelajari tren produksi komoditas dan rata-rata harga.
3. Gunakan hasil analitik untuk evaluasi program dan prioritas kebijakan.

### Menu 5: Unduh Dokumen
1. Buka `Unduh Dokumen`.
2. Unduh data produksi dalam CSV.
3. Pilih laporan tertentu lalu unduh:
   - Detail kecamatan (CSV)
   - Laporan lengkap (PDF)

Isi PDF mencakup:
- Ringkasan total produksi/luas/petani/lahan.
- Detail kecamatan.
- Sumber laporan penyuluh yang sudah disetujui admin kecamatan.

---

## 5. Panduan Penggunaan Data Spasial
1. Saat ada field latitude/longitude, gunakan fitur klik peta.
2. Setelah klik peta:
   - Latitude dan longitude terisi otomatis.
   - Alamat dicoba diambil otomatis (reverse geocoding).
3. Jika alamat tidak muncul otomatis, isi alamat secara manual.
4. Pastikan titik sesuai lokasi nyata sebelum simpan.

---

## 6. Aturan Upload File
- Foto balai: maksimal 5MB.
- Foto penyuluh: maksimal 5MB.
- Foto petani: maksimal 5MB.
- Foto lahan: maksimal 5MB.
- Foto kunjungan: maksimal 5MB (wajib pada laporan kunjungan).
- Foto kendala: maksimal 5MB (wajib jika mengisi data kendala).
- Format yang didukung: `jpg`, `jpeg`, `png`, `webp`.

---

## 7. Tips Operasional Harian
1. Isi data master terlebih dahulu sebelum operasional lapangan.
2. Gunakan status `draft` untuk laporan yang belum final.
3. Gunakan status `menunggu` saat laporan siap diverifikasi.
4. Admin kecamatan sebaiknya mengecek menu verifikasi setiap hari.
5. Pimpinan sebaiknya menggunakan unduh PDF per periode sebagai arsip resmi.

---

## 8. Troubleshooting Singkat

### 8.1 DataTable menampilkan Ajax error
Langkah cek:
1. Pastikan login masih aktif (session belum expired).
2. Pastikan route endpoint tersedia dan role sesuai.
3. Cek apakah ada error SQL pada log aplikasi.
4. Refresh halaman setelah memastikan data relasi sudah valid.

### 8.2 Marker peta tidak tampil sesuai jenis
Langkah cek:
1. Periksa pengaturan di menu `Style Ikon Peta`.
2. Pastikan style untuk kecamatan, desa, dan komoditas sudah berbeda.
3. Simpan style lalu reload halaman.

### 8.3 Upload foto gagal
Langkah cek:
1. Ukuran file harus <= 5MB.
2. Format file harus gambar yang didukung.
3. Pastikan storage publik sudah bisa diakses.

### 8.4 Alamat dari klik peta tidak otomatis
- Isi alamat secara manual pada field alamat.
- Koordinat tetap harus terisi agar data spasial valid.

---

## 9. Penutup
Dengan mengikuti alur pada dokumen ini, proses kerja tiap level user akan sinkron dari lapangan sampai pimpinan. Gunakan data yang akurat, disiplin verifikasi, dan konsisten memperbarui master data agar dashboard dan laporan tetap valid.
