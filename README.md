# Panduan Instalasi Backend (Laravel)

Berikut adalah langkah-langkah untuk menjalankan backend Laravel di environment lokal.

## Prasyarat

- PHP (versi 8.2 atau lebih baru)
- Composer
- MySQL Database

---

## Langkah-Langkah Instalasi

### 1. Clone Repositori

Buka terminal dan jalankan perintah berikut untuk mengkloning repositori:

```bash
git clone https://github.com/Shifyan/skill-fit-test-backend.git
cd skill-fit-test-backend
```

2. Install Dependensi
   Jalankan perintah berikut untuk menginstal seluruh paket library PHP yang dibutuhkan:

```Bash
composer install
```

3. Konfigurasi Environment Variable (.env)
   Salin file .env.example menjadi .env, lalu buat basis data MySQL baru di lokal.

```Bash
cp .env.example .env
```

Buka file .env dan sesuaikan konfigurasi database berikut:

```Bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skill-fit-test-backend
DB_USERNAME=root
DB_PASSWORD=
```

4. Generate Application Key
   Jalankan perintah berikut untuk membuat enkripsi key aplikasi:

```Bash
php artisan key:generate
```

5. Migrasi & Seed Data
   Jalankan perintah berikut untuk membuat struktur tabel basis data dan mengisi data awal (seeder):

```Bash
php artisan migrate --seed
```

6. Jalankan Server Lokal
   Jalankan perintah berikut untuk memulai server pengembang lokal:

```Bash
php artisan serve
```

API dapat diakses melalui peramban (browser) atau API Client pada alamat:
http://127.0.0.1:8000/api
