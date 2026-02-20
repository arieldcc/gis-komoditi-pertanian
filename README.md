# GIS Komoditi Pertanian

Panduan ini menjelaskan langkah dari **mengambil project dari GitHub ke local** sampai **aplikasi berhasil dijalankan**.

Repository:
`https://github.com/arieldcc/gis-komoditi-pertanian.git`

## 1. Prasyarat
Pastikan di komputer sudah terpasang:
- Git
- PHP 8.2+
- Composer
- Node.js 18+ dan npm
- MySQL/MariaDB

## 2. Clone Project dari GitHub
Jalankan di terminal:

```bash
git clone https://github.com/arieldcc/gis-komoditi-pertanian.git
cd gis-komoditi-pertanian
```

## 3. Install Dependency Backend (Laravel)

```bash
composer install
```

## 4. Install Dependency Frontend

```bash
npm install
```

## 5. Siapkan File Environment
Salin file contoh environment:

```bash
cp .env.example .env
```

## 6. Konfigurasi Database di `.env`
Buka file `.env`, lalu sesuaikan bagian ini:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sig_komoditas_pertanian
DB_USERNAME=root
DB_PASSWORD=
```

## 7. Buat Database di MySQL
Masuk ke MySQL lalu jalankan:

```sql
CREATE DATABASE sig_komoditas_pertanian;
```

## 8. Generate APP Key + Link Storage

```bash
php artisan key:generate
php artisan storage:link
```

## 9. Jalankan Migrasi dan Seeder

Untuk instalasi pertama (database baru), jalankan:

```bash
php artisan migrate
php artisan db:seed
```

Seeder akan membuat data awal termasuk akun default.

## 10. Build Asset Frontend
Pilih salah satu:

Mode development (disarankan saat coding):
```bash
npm run dev
```

Mode production build:
```bash
npm run build
```

## 11. Jalankan Aplikasi
Di terminal lain jalankan:

```bash
php artisan serve
```

Buka browser:
`http://127.0.0.1:8000`

## 12. Akun Login Default
Hasil dari seeder:

- Admin Dinas
  - Email: `admin@sigkomoditas.id`
  - Password: `Admin12345!`
- Admin Kecamatan
  - Email: `admin.kecamatan@sigkomoditas.id`
  - Password: `password123`
- Penyuluh
  - Email: `penyuluh@sigkomoditas.id`
  - Password: `password123`
- Pimpinan Dinas
  - Email: `pimpinan@sigkomoditas.id`
  - Password: `password123`

## 13. Menjalankan Semua Service Sekaligus (Opsional)
Jika ingin sekaligus server + queue + log + vite:

```bash
composer run dev
```

## 14. Troubleshooting Singkat

### A. `Class ... not found` atau autoload error
```bash
composer dump-autoload
```

### B. Perubahan frontend tidak muncul
- Pastikan `npm run dev` sedang berjalan, atau jalankan ulang `npm run build`.

### C. Gagal koneksi database
- Cek nilai `DB_*` di `.env`.
- Pastikan service MySQL aktif.
- Pastikan nama database sudah dibuat.

### D. File upload/foto tidak tampil
```bash
php artisan storage:link
```

## 15. Catatan Penting
- Jangan commit file `.env` ke GitHub.
- Jika sudah ada data manual di database, **jangan** gunakan `migrate:fresh` karena akan menghapus semua data.
