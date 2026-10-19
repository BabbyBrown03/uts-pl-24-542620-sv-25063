# Admin Inventaris Barang

Aplikasi web untuk mengelola data inventaris barang. Dibuat sebagai Ujian Tengah
Semester mata kuliah **Konstruksi dan Evolusi Perangkat Lunak (SVPL261501)**.

| | |
|---|---|
| Nama Aplikasi | Admin Inventaris Barang |
| NIM | 24.542620 |
| Repository | `uts-pl-24-542620-sv-25063` |

---

## 1. Deskripsi & Arsitektur

Aplikasi ini terdiri dari tiga bagian yang berjalan terpisah namun saling terhubung:

```
┌──────────────┐      HTTP + JSON      ┌───────────────┐      SQL       ┌─────────┐
│   Browser    │  ───────────────────►  │    Backend    │  ───────────►  │  MySQL  │
│  (Vue.js +   │  ◄───────────────────  │   Laravel 12  │  ◄───────────  │   8.4   │
│   Axios)     │   Authorization:      │   Sanctum     │                │ (volume)│
└──────────────┘   Bearer <token>      └───────────────┘                └─────────┘
       ▲                                       ▲
       │  Nginx menyajikan hasil build          │  dilayani FrankenPHP
       │  Vue.js + proxy /api ─────────────────┘
┌──────┴───────┐
│  Service     │
│  frontend    │
└──────────────┘
```

**Alur data dari Vue.js ke database:**

1. Admin membuka halaman login dan memasukkan email + password.
2. `LoginView.vue` mengirim `POST /api/login` memakai Axios.
3. Backend memvalidasi kredensial dengan `Hash::check` dan mengembalikan **token Sanctum**.
4. Frontend menyimpan token di `localStorage` dan mengirimkannya pada setiap request
   melalui header `Authorization: Bearer <token>` (request interceptor Axios).
5. Backend menerima request, memvalidasi token, laluceased	query ke tabel `barang`.
6. Data dikirim kembali sebagai JSON dan ditampilkan pada tabel di `DashboardView.vue`.
7. Setiap penambahan/perubahan/hapusan oleh frontend langsung disimpan ke MySQL.

---

## 2. Struktur Repository

```
uts-pl-24-542620-sv-25063/
├── .github/
│   └── workflows/
│       └── ci.yml                # GitHub Actions: test, build, deploy GHCR
├── backend/                      # Laravel 12 REST API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php     # login, logout, me
│   │   │   │   └── BarangController.php   # CRUD barang
│   │   │   ├── Middleware/
│   │   │   │   └── ForceJsonResponse.php  # paksa JSON untuk route /api/*
│   │   │   ├── Requests/
│   │   │   │   └── BarangRequest.php      # validasi Laravel
│   │   │   └── Resources/
│   │   │       └── BarangResource.php     # format JSON konsisten
│   │   └── Models/
│   │       ├── User.php                   # + HasApiTokens (Sanctum)
│   │       └── Barang.php
│   ├── database/
│   │   ├── migrations/                    # tabel barang
│   │   └── seeders/                       # admin + data barang contoh
│   ├── routes/api.php                     # definisi endpoint
│   ├── tests/Feature/                     # AuthTest, BarangApiTest
│   ├── Dockerfile                         # php-fpm (CPMK4)
│   ├── Dockerfile.frankenphp              # FrankenPHP (nilai tambah)
│   ├── Caddyfile
│   ├── docker-entrypoint.sh
│   └── .dockerignore
├── frontend/                     # Vue 3 + Vite
│   ├── src/
│   │   ├── services/api.js      # instance Axios + interceptor token
│   │   ├── router/index.js      # + guard autentikasi
│   │   ├── views/
│   │   │   ├── LoginView.vue
│   │   │   └── DashboardView.vue
│   │   └── assets/main.css
│   ├── Dockerfile               # multi-stage: Node -> Nginx
│   ├── nginx.conf               # sajikan SPA + proxy /api
│   ├── .env.example
│   └── .dockerignore
├── postman/
│   ├── Admin Inventaris Barang.postman_collection.json
│   ├── Laravel Local.postman_environment.json
│   └── Docker Compose.postman_environment.json
├── docker-compose.yml           # MySQL + backend + frontend
├── .env.example                 # konfigurasi lokal (tanpa secret)
└── README.md
```

---

## 3. Teknologi yang Digunakan

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Autentikasi | Laravel Sanctum (token bearer) |
| Database | MySQL 8.4 (produksi/Compose), SQLite (dev & test) |
| Frontend | Vue 3, Vue Router 4, Vite 5 |
| HTTP client | Axios |
| Server aplikasi | FrankenPHP (nilai tambah), Nginx (frontend) |
| Container | Docker, Docker Compose |
| CI/CD | GitHub Actions |
| Registry | GitHub Container Registry (ghcr.io) |
| Pengujian | PHPUnit (feature test Laravel) |
| Dokumentasi API | Postman Collection |

---

## 4. Menjalankan Aplikasi

### 4.1 Dengan Docker Compose (cara utama)

Seluruh sistem — database, backend, dan frontend — berjalan dengan **satu perintah**
dari root repository:

```bash
# 1. Siapkan konfigurasi lokal (file .env tidak di-commit)
cp .env.example .env

# 2. Isi APP_KEY
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# 3. Jalankan semuanya
docker compose up -d --build
```

Setelah selesai:

| Layanan | Alamat |
|---|---|
| Aplikasi Vue.js (frontend) | <http://localhost:8080> |
| REST API Laravel | <http://localhost:8080/api> (lewat proxy Nginx) |
| REST API langsung | <http://localhost:8000/api> |
| MySQL | `localhost:3307` |

Akun admin (dari seeder):

```
Email    : admin@inventaris.test
Password : password123
```

Menghentikan sistem:

```bash
docker compose down        # container dihapus, DATA TETAP ADA di named volume
docker compose up -d --build
```

> **Catatan persistensi:** data MySQL disimpan pada named volume `inventaris-dbdata`.
> Menjalankan `docker compose down` lalu `docker compose up -d --build` **tidak**
> menghapus data. Untuk menghapus data juga, jalankan `docker compose down -v`.

Perintah pendukung:

```bash
docker compose ps           # status & health check
docker compose logs -f backend
docker compose exec backend php artisan migrate:status
```

### 4.2 Secara lokal tanpa Docker

**Backend:**

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

#Buat database SQLite
touch database/database.sqlite

php artisan migrate --seed
php artisan serve            # http://localhost:8000
```

**Frontend (terminal kedua):**

```bash
cd frontend
npm install
cp .env.example .env         # VITE_API_BASE_URL=/api, proxy ke 127.0.0.1:8000
npm run dev                  # http://localhost:5173
```

Pada mode ini Vite mem-proxy `/api` ke backend, sehingga tidak ada masalah CORS.

### 4.3 Konfigurasi Base URL API

Base URL API **tidak pernah ditulis langsung di dalam komponen**. Nilainya dibaca
dari environment variable (lihat `frontend/.env.example`):

| Variabel | Isi | Keterangan |
|---|---|---|
| `VITE_API_BASE_URL` | `/api` | Path relatif; Nginx/Vite yang meneruskannya |
| `VITE_API_DEV_PROXY` | `http://127.0.0.1:8000` | Target proxy saat `npm run dev` |
| `VITE_ADMIN_EMAIL` | `admin@inventaris.test` | Ditampilkan di halaman login |

---

## 5. Migration, Seeder, dan Pengujian

```bash
cd backend

php artisan migrate              # menjalankan migration
php artisan migrate --seed       # migration + seeder
php artisan migrate:fresh --seed # mulai dari nol
php artisan migrate:status       # status migration
```

Seeder membuat:

- Akun admin `admin@inventaris.test` / `password123` (password di-hash dengan
  `Hash::make`, **tidak pernah plaintext**).
- 8 data barang contoh.

Seeder bersifat *idempotent* (`updateOrCreate`), sehingga aman dijalankan ulang
pada container yang sama dan tidak menggandakan data.

Menjalankan pengujian:

```bash
cd backend
php artisan test                 # seluruh test
php artisan test --filter=AuthTest
php artisan test --filter=BarangApiTest
```

Hasil pada versi ini: **17 test passed (60 assertions)**.

---

## 6. Daftar Endpoint API

Semua prefix: `/api`. Semua endpoint kecuali `login` memerlukan header
`Authorization: Bearer <token>`.

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/api/login` | Tidak | Validasi kredensial, kembalikan token |
| POST | `/api/logout` | Ya | Cabut token yang sedang dipakai |
| GET | `/api/me` | Ya | Cek token masih valid |
| GET | `/api/barang` | Ya | Tampilkan semua barang (200) |
| GET | `/api/barang/{id}` | Ya | Detail satu barang (200 / 404) |
| POST | `/api/barang` | Ya | Tambah barang (201 / 422) |
| PUT | `/api/barang/{id}` | Ya | Ubah barang (200 / 404 / 422) |
| DELETE | `/api/barang/{id}` | Ya | Hapus barang (200 / 404) |

### Contoh Request

**Login**

```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@inventaris.test",
  "password": "password123"
}
```

```json
{
  "message": "Login berhasil.",
  "token": "1|R4k2aGAHZaBACAyo0X6SEmHEw2PBq45S2Ctdnd9N046fccc6",
  "token_type": "Bearer",
  "user": { "id": 1, "name": "Admin Inventaris", "email": "admin@inventaris.test" }
}
```

**Tambah barang**

```http
POST /api/barang
Authorization: Bearer <token>
Content-Type: application/json

{
  "kode_barang": "BRG-001",
  "nama_barang": "Laptop Asus VivoBook 14",
  "kategori": "Elektronik",
  "stok": 12,
  "harga": 8750000
}
```

```json
{
  "message": "Barang berhasil ditambahkan.",
  "data": {
    "id": 1,
    "kode_barang": "BRG-001",
    "nama_barang": "Laptop Asus VivoBook 14",
    "kategori": "Elektronik",
    "stok": 12,
    "harga": 8750000,
    "created_at": "2026-10-18T01:19:45.000000Z",
    "updated_at": "2026-10-18T01:19:45.000000Z"
  }
}
```

### Pengaturan Respons

| Status | Kapan | Contoh body |
|---|---|---|
| `200` | Berhasil (list, detail, update, delete, logout) | `{"data": ...}` atau `{"message": "..."}` |
| `201` | Data baru berhasil dibuat | `{"message": "...", "data": {...}}` |
| `401` | Token tidak ada / tidak valid | `{"message": "Unauthenticated."}` |
| `404` | Data atau endpoint tidak ditemukan | `{"message": "Data yang diminta tidak ditemukan."}` |
| `422` | Validasi gagal | `{"message": "...", "errors": {"field": ["..."]}}` |

Pesan error sengaja dibuat generik agar detail internal (nama class, stack trace)
tidak pernah terekspos ke client, apa pun nilai `APP_DEBUG`.

### Aturan Validasi Barang

| Field | Aturan |
|---|---|
| `kode_barang` | wajib, string, **unik**, max 100 |
| `nama_barang` | wajib, string, max 255 |
| `kategori` | wajib, string, max 100 |
| `stok` | wajib, integer, **tidak boleh negatif** (`min:0`) |
| `harga` | wajib, numeric, **tidak boleh negatif** (`min:0`) |

---

## 7. Postman Collection

Collection tersimpan di folder `postman/`.

**Cara mengimpor:**

1. Buka Postman → **Import** → pilih file
   `postman/Admin Inventaris Barang.postman_collection.json`.
2. Impor juga environment: tab **Environments → Import** →
   `postman/Laravel Local.postman_environment.json` (atau
   `Docker Compose.postman_environment.json`).
3. Pilih environment yang sesuai di kanan atas.
4. Buka tab **Environments**, pastikan `base_url` benar:
   - Lokal (`php artisan serve`): `http://localhost:8000/api`
   - Docker Compose: `http://localhost:8080/api`

**Cara menjalankan:**

1. Jalankan request **Login (200)** terlebih dahulu. Token otomatis tersimpan
   ke environment variable `token`.
2. Jalankan request pada grup **2. Barang - CRUD**.
3. Jalankan grup **3. Skenario Gagal** untuk membuktikan status 404 & 422.
4. Akhiri dengan **Logout (200)**.

Collection sudah berisi test script (assertions) yang otomatis memeriksa status
kode dan menyimpan token.

---

## 8. GitHub Actions, Secrets, Branch Protection, dan GHCR

### 8.1 Isi `.github/workflows/ci.yml`

| Job | Kapan jalan | Kegunaan |
|---|---|---|
| `backend-test` | push & PR | `composer install` + `php artisan test` |
| `frontend-build` | push & PR | `npm ci` + `npm run build` |
| `deploy` | **hanya push ke `main`** | Build & push image backend & frontend ke GHCR |
| `deploy-smoke` | **hanya push ke `main`** | Jalankan `docker compose up -d --build` lalu uji login & CRUD |

Job `deploy` memakai `needs: [backend-test, frontend-build]` sehingga deployment
hanya berjalan bila pengujian backend dan build frontend benar-benar lulus.
Pegawai `if: github.event_name == 'push' && github.ref == 'refs/heads/main'`
membuat job tersebut dilewati pada Pull Request maupun branch `dev`.

Tag image berbasis nomor commit:

```
ghcr.io/<user>/uts-pl-24-542620-sv-25063/backend:<short-sha>
ghcr.io/<user>/uts-pl-24-542620-sv-25063/frontend:<short-sha>
ghcr.io/<user>/uts-pl-24-542620-sv-25063/backend:latest
ghcr.io/<user>/uts-pl-24-542620-sv-25063/frontend:latest
```

Autentikasi GHCR memakai `secrets.GITHUB_TOKEN` (token bawaan repository dengan
izin `packages: write`) — **tidak ada username/password yang disimpan di repository**.

### 8.2 GitHub Secrets yang perlu dibuat

Repository → **Settings → Secrets and variables → Actions → New repository secret**:

| Nama Secret | Isi | Dipakai oleh |
|---|---|---|
| `APP_KEY` | `base64:...` (dari `php artisan key:generate --show`) | `deploy-smoke` |
| `DB_PASSWORD` | password database MySQL | `deploy-smoke` |

Nama secret harus persis `APP_KEY` dan `DB_PASSWORD` (huruf besar, tanpa spasi).

> `GITHUB_TOKEN` tidak perlu dibuat manual; otomatis tersedia.

**Perilaku bila secret belum dibuat:** job `deploy-smoke` tidak langsung gagal.
Workflow akan menampilkan peringatan di log GitHub Actions:

```
::warning title=Secret belum ada::Secret APP_KEY kosong pada repository ini...
```

dan memakai nilai acak yang hanya berlaku di runner tersebut (tidak pernah
disimpan di repository) agar pengujian tetap bisa berjalan. Secret sebenarnya tetap disarankan agar pipeline benar-benar memakai kredensial Anda.

### 8.3 Branch Protection / Ruleset

Repository → **Settings → Rules → Rulesets** (atau **Settings → Branches →
Add rule**), lalu terapkan pada `main` dan `dev`:

- **Restrict deletions** — unchecked
- **Require a pull request before merging** → aktifkan, minimal 1 approval
- **Require status checks to pass** → centang `backend-test` dan `frontend-build`
- **Require branches to be up to date** → aktifkan

Screenshot halaman pengaturan ini perlu disertakan pada laporan.

---

## 9. Dockerfile

### Backend

Terdapat dua Dockerfile:

| File | Base image | Kegunaan |
|---|---|---|
| `backend/Dockerfile` | `composer:2.8.8` + `php:8.3-fpm-bookworm` | Image Laravel standar (CPMK4) |
| `backend/Dockerfile.frankenphp` | `dunglas/frankenphp:1-php8.3-bookworm` | Nilai tambah, dipakai `docker-compose.yml` |

Keduanya multi-stage: `composer.json` dan `composer.lock` **disalin dan diinstal
terlebih dahulu**, baru seluruh kode aplikasi disalin — sehingga cache layer tetap
efektif ketika hanya kode yang berubah.

Tidak ada kredensial di dalam Dockerfile. File `.env` dikecualikan lewat
`.dockerignore`, dan konfigurasi runtime dibentuk oleh `docker-entrypoint.sh`
dari environment variable yang disuplai Docker Compose.

### Frontend

`frontend/Dockerfile` memakai **multi-stage build**:

1. **Tahap build** — `node:22.12-alpine`, menjalankan `npm ci` lalu `npm run build`.
2. **Tahap serve** — `nginx:1.27-alpine`, hanya menyalin folder `dist`.

Image akhir **tidak memuat `node_modules`**, karena yang disalin hanya
`/app/dist` ke `/usr/share/nginx/html`. Kedua tahap memakai versi image eksplisit.

---

## 10. Docker Compose

`docker-compose.yml` di root repository menjalankan tiga service:

| Service | Image | Port host | Fungsi |
|---|---|---|---|
| `mysql` | `mysql:8.4` | 3307 | Database, named volume `inventaris-dbdata` |
| `backend` | build `./backend` | 8000 | REST API Laravel (FrankenPHP) |
| `frontend` | build `./frontend` | 8080 | Nginx menyajikan Vue.js + proxy `/api` |

**Cara container saling terhubung:**

- Backend → database memakai **nama service** `mysql` (`DB_HOST=mysql`), bukan IP laptop.
- Frontend Nginx → backend memakai **nama service** `backend` (`http://backend:8000`).

**Mengapa browser tetap bisa mengakses API:**

Browser berjalan di host dan **tidak dapat me-resolve nama service internal
Docker**. Solusinya, frontend memanggil `/api` pada origin-nya sendiri
(`http://localhost:8080/api`), lalu **Nginx meneruskannya** ke `backend:8000`
di jaringan Docker Compose. Dengan begitu:

- tidak ada IP laptop yang dipakai sebagai alamat antarkontainer,
- tidak ada masalah CORS (request menjadi same-origin),
- konfigurasi CORS di backend tetap disiapkan sebagai cadangan.

Tersedia juga **health check** pada ketiga service sehingga `docker compose ps`
menampilkan status `(healthy)` dan service lain menunggu backend siap.

Kredensial database dibaca dari file `.env` lokal di root repository yang
tidak di-commit; `.env.example` tersedia sebagai contoh tanpa secret.

---

## 11. Nilai Tambah: FrankenPHP

Backend dijalankan dengan **FrankenPHP** (`dunglas/frankenphp:1-php8.3-bookworm`)
sebagai server aplikasi, menggantikan `php artisan serve` dan PHP-FPM + Nginx.
Versi image ditulis eksplisit, bukan `latest`.

Request API memang dilayani oleh FrankenPHP. Bukti yang sudah dijalankan:

**1. Proses utama di dalam container:**

```bash
docker compose exec -T backend cat /proc/1/cmdline
# frankenphp run --config /etc/caddy/Caddyfile
```

**2. Log startup:**

```
{"logger":"frankenphp","msg":"FrankenPHP started 🐘","php_version":"8.3.35","total_threads":24}
```

**3. Log request — request browser → Nginx → FrankenPHP → Laravel:**

```
INFO http.log.access.log0 handled request {
  "request": {
    "remote_ip": "172.18.0.4",          <- container frontend (Nginx)
    "method": "POST", "uri": "/api/login",
    "X-Forwarded-For": ["172.18.0.1"],   <- browser di host
    "Authorization": ["REDACTED"]
  },
  "status": 200,
  "resp_headers": {
    "Server": ["FrankenPHP Caddy"],
    "X-Powered-By": ["PHP/8.3.35"]
  }
}
```

Header `Server: FrankenPHP Caddy` dan `X-Powered-By: PHP/8.3.35` pada setiap
respons membuktikan request diproses oleh FrankenPHP, bukan oleh
`php artisan serve` maupun PHP-FPM + Nginx.

Perintah lengkap untuk mengambil bukti tersebut:

```bash
docker compose up -d --build
docker compose logs --tail=15 backend
```

Backend tetap berjalan bersama service lain di `docker-compose.yml`, dengan
ketentuan jaringan sama seperti service pada umumnya.

---

## 12. Kendala & Solusi

| No | Kendala | Solusi |
|---|---|---|
| 1 | `composer create-project` gagal saat `package:discover` (`symfony/var-dumper` gagal diekstrak) karena proses ekstraksi paralel. | Jalankan ulang `composer install` untuk memulihkan file vendor, lalu verifikasi `php artisan --version`. |
| 2 | Tabel otomatis bernama `barangs` (pluralisasi model), sedangkan migration membuat tabel `barang`. | Menambahkan `protected $table = 'barang';` pada model `Barang` agar konsisten dengan migration dan endpoint `/api/barang`. |
| 3 | Request tanpa header `Accept: application/json` (Postman/curl memakai wildcard) mendapat **500 `Route [login] not defined`**, bukan 401. | Membuat middleware `ForceJsonResponse` yang memaksa header Accept pada seluruh route `/api/*`. |
| 4 | Pesan 404 membocorkan nama class model dan stack trace saat `APP_DEBUG=true`. | Menangani `ModelNotFoundException` dan `NotFoundHttpException` di `bootstrap/app.php` agar selalu membalas pesan generik. |
| 5 | Test "token tidak berlaku setelah logout" gagal karena auth guard meng-cache user pada instance aplikasi yang sama. | Verifikasi dilakukan dengan memastikan record token terhapus dari database; skenario token ngawur sudah diuji terpisah dan menghasilkan 401. |
| 6 | Browser tidak dapat me-resolve nama service Docker (`backend`), sehingga `VITE_API_BASE_URL` tidak bisa diisi `http://backend:8000`. | Frontend memakai path relatif `/api`; Nginx meneruskan ke `backend:8000`. Alamat API tetap berasal dari environment variable. |
| 7 | PHP-FPM tidak dapat melayani HTTP secara mandiri sehingga `docker compose up` tidak cukup untuk 3 service. | Backend memakai FrankenPHP (bonus) sehingga satu service sudah dapat melayani HTTP. |
| 8 | Konversi line ending Git (CRLF) dapat merusak `docker-entrypoint.sh` di container Linux. | Menambahkan `.gitattributes` dengan `*.sh text eol=lf` dan `Dockerfile* text eol=lf`. |
| 9 | Seeder dijalankan ulang tiap container start berisiko menggandakan atau menimpa data. | Seeder memakai `updateOrCreate` sehingga idempotent, dan data pada named volume tetap utuh setelah `docker compose down` lalu `up`. |
| 10 | `npm` diblokir oleh Execution Policy PowerShell. | Gunakan `npm.cmd` sebagai pengganti `npm`. |
| 11 | Step `php artisan key:generate --force` di GitHub Actions gagal dengan error pada `file_get_contents(.env)`. | `.env` memang tidak di-commit, sehingga tidak ada di runner. Workflow diperbaiki: `cp .env.example .env` **sebelum** menjalankan `key:generate`. Diverifikasi lokal: 17 test lulus. |
| 12 | Requests ke `http://localhost:8080/api/*` dibalas **404 "Endpoint tidak ditemukan"**, padahal backend sehat. | Penyebabnya `proxy_pass` memakai variabel **beserta** komponen path (`$upstream/api/`), sehingga Nginx memotong awalan `/api` dan mengirim `/barang` ke Laravel. Diperbaiki dengan `proxy_pass http://$backend_upstream;` (tanpa path) agar URI diteruskan apa adanya. |
| 13 | Pull image `mysql:8.4` gagal dengan `TLS handshake timeout`. | Gejala jaringan sementara; pulling diulang; pulling diulang dengan percobaan berjenjang hingga berhasil. |
| 14 | Bukti "API dilayani FrankenPHP" sulit diambil karena log tidak mencatat request. | Menambahkan blok `log { output stdout; format console }` pada `backend/Caddyfile`. Sekarang setiap request API terlihat di `docker compose logs backend` lengkap dengan header `Server: FrankenPHP Caddy`. |
| 15 | Job `deploy-smoke` gagal dengan `required variable APP_KEY is missing a value` setelah PR `dev` → `main` di-merge. | Penyebabnya secret `APP_KEY`/`DB_PASSWORD` belum dibuat di repository, sehingga nilainya kosong. Workflow diperbaiki agar: (1) memberi peringatan yang jelas di log bila secret kosong, (2) memakai nilai acak sementara khusus runner agar pipeline tetap bisa jalan, dan (3) memvalidasi `.env` dengan `docker compose config --quiet` sebelum menjalankan container, sehingga pesan error tidak lagi membingungkan. |

---

## 13. Checklist Bukti Laporan

Silakan lampirkan screenshot berikut pada laporan PDF.

### Backend
- [ ] Output `php artisan test` menunjukkan seluruh test lulus
- [ ] Screenshot pengujian Postman: Login (200)
- [ ] Screenshot pengujian Postman: Tambah barang (201)
- [ ] Screenshot pengujian Postman: Ubah barang (200)
- [ ] Screenshot pengujian Postman: Hapus barang (200)
- [ ] Screenshot pengujian Postman: Akses tanpa token (401)
- [ ] Screenshot pengujian Postman: Barang tidak ada (404)
- [ ] Screenshot pengujian Postman: Validasi gagal (422)
- [ ] Tampilan Postman Collection yang sudah diimpor beserta environment

### Frontend
- [ ] Halaman login
- [ ] Dashboard menampilkan tabel barang dari database
- [ ] Form tambah barang berhasil
- [ ] Form ubah barang berhasil
- [ ] Konfirmasi hapus barang berhasil
- [ ] Pesan sukses / error tampil

### Repository & GitHub
- [ ] Tampilan repository publik `uts-pl-24-542620-sv-25063`
- [ ] Daftar branch: `main`, `dev`, dan branch fitur
- [ ] Halaman Pull Request (dev → main) beserta status check yang hijau
- [ ] Screenshot **Settings → Rules → Rules** untuk `main` dan `dev`
- [ ] GitHub Actions: workflow hijau pada `main` (4 job)
- [ ] GitHub Actions: workflow pada `dev` **tanpa** job deploy
- [ ] GitHub Actions: workflow pada Pull Request **tanpa** job deploy
- [ ] GitHub **Packages**:GHCR menampilkan `backend` dan `frontend`
- [ ] Detail tag image berdasarkan nomor commit
- [ ] GitHub **Settings → Secrets and variables → Actions** (nama secret saja)

### Docker
- [ ] Output `docker compose ps` (semua service healthy)
- [ ] Output `docker compose up -d --build` (build sukses)
- [ ] Login & CRUD berhasil dari browser
- [ ] `docker compose logs backend` menunjukkan FrankenPHP melayani request
- [ ] Bukti persistensi: `docker compose down` → `up -d --build` → data masih ada

---

## 14. Lisensi

Dibuat untuk keperluan akademik Ujian Tengah Semester SVPL261501.