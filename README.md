# Technical Blueprint & Engineering Workflow (Laravel 12 & Filament v3)

Dokumentasi ini merangkum seluruh perjalanan rekayasa sistem (*engineering workflow*) dan instruksi CLI (*command tracking*) dari inisialisasi awal hingga CMS & REST API siap produksi.

---

## 1. Arsitektur & Hubungan Antar Modul

Aplikasi ini berfungsi ganda sebagai:

1. **Headless Content Management System (CMS):** Panel admin Filament v3 (`/alhaiza`) untuk mengelola seluruh data portofolio secara visual.
2. **RESTful API Service:** Endpoint tunggal teragregasi (`/api/profile`) untuk menyuplai data ke frontend modern (Vue 3 / Vite).

```text
   [ Admin User ]
         │
         ▼
[ Filament Panel: /alhaiza ]
  ├── ProfileResource    ──> [ profiles table ]
  ├── ProjectResource    ──> [ projects table ]
  ├── ExperienceResource ──> [ experiences table ]
  └── SkillResource      ──> [ skills table ]
         │
         └── FileUpload (avatars) ──> [ storage/app/public/avatars ]
                                                    │
                                         (symlink public/storage)
                                                    │
   [ Vue 3 Frontend ]                               ▼
         │                             http://127.0.0.1:8000/storage/...
         ▼ (GET /api/profile)
[ Api\ProfileController ] ── Agregasi (Profile + Projects + Experiences + Skills)
```

---

## 2. End-to-End Command & Engineering Workflow

Berikut urutan perintah CLI asli yang dijalankan untuk merekayasa seluruh fitur proyek ini dari nol:

### Fase 1: Setup Framework Dasar & Panel Admin Filament v3

*Mempersiapkan runtime database awal dan antarmuka CMS Filament.*

1. Jalankan migrasi default bawaan framework:

   ```bash
   php artisan migrate
   ```

2. Pasang paket Filament Panel Builder v3:

   ```bash
   composer require filament/filament:"^3.2" -W
   ```

3. Pasang scaffolding panel Filament:

   ```bash
   php artisan filament:install --panels
   ```

4. Buat akun kredensial user admin untuk akses panel:

   ```bash
   php artisan make:filament-user
   ```

5. Atur branch git lokal dan hubungkan ke remote repository:

   ```bash
   git branch -M develop
   git remote add origin git@github-personal:Alhaiza/project-personal-website-be.git
   ```

---

### Fase 2: Modul Profile (Identitas Utama)

*Membuat entitas profil pemilik website beserta antarmuka input CMS.*

1. Generate model `Profile` sekaligus berkas migrasi database-nya:

   ```bash
   php artisan make:model Profile -m
   ```

2. Jalankan migrasi untuk membuat tabel `profiles`:

   ```bash
   php artisan migrate
   ```

3. Buat Resource Filament untuk mengelola data profil:

   ```bash
   php artisan make:filament-resource Profile
   ```

   *Penyesuaian Form & Table:*
   - Menggunakan `FileUpload::make('avatar')->disk('public')->directory('avatars')`.
   - Menggunakan `KeyValue::make('social_links')` dengan `$casts = ['social_links' => 'array']` pada model `Profile.php`.

---

### Fase 3: Instalasi API & Pembuatan Endpoint Profil

*Mengaktifkan infrastruktur API Laravel dan controller agregator.*

1. Install scaffolding REST API Laravel 12 (Sanctum & file `routes/api.php`):

   ```bash
   php artisan install:api
   ```

2. Buat controller API untuk profil:

   ```bash
   php artisan make:controller Api/ProfileController
   ```

3. Daftarkan route pada `routes/api.php`:

   ```http
   GET /api/profile
   ```

---

### Fase 4: Modul Project (Portofolio Karya)

*Membuat entitas proyek/showcase karya yang independen.*

1. Generate model `Project` dan migrasi database:

   ```bash
   php artisan make:model Project -m
   ```

2. Eksekusi migrasi tabel `projects`:

   ```bash
   php artisan migrate
   ```

3. Generate CRUD Resource Filament untuk proyek:

   ```bash
   php artisan make:filament-resource Project
   ```

   *Penyesuaian:*
   - Kolom `technologies` disimpan dalam format array casting JSON pada `Project.php`.

---

### Fase 5: Modul Experience (Pengalaman Karier)

*Membuat entitas riwayat pekerjaan profesional.*

1. Generate model `Experience` dan migrasi database:

   ```bash
   php artisan make:model Experience -m
   ```

2. Eksekusi migrasi tabel `experiences`:

   ```bash
   php artisan migrate
   ```

3. Generate Resource Filament untuk riwayat kerja:

   ```bash
   php artisan make:filament-resource Experience
   ```

---

### Fase 6: Modul Skill (Keahlian Teknis)

*Membuat entitas keahlian independen untuk diagregasikan ke controller.*

1. Generate model `Skill` dan migrasi database:

   ```bash
   php artisan make:model Skill -m
   ```

2. Eksekusi migrasi tabel `skills`:

   ```bash
   php artisan migrate
   ```

3. Generate Resource Filament untuk keahlian:

   ```bash
   php artisan make:filament-resource Skill
   ```

4. Agregasi seluruh data di `app/Http/Controllers/Api/ProfileController.php`:
   - `Profile::first()`
   - `Skill::pluck('name')->toArray()`
   - `Project::all()`
   - `Experience::all()`

---

### Fase 7: Konfigurasi Storage & Perbaikan Broken Image

*Memastikan berkas upload dapat diakses publik oleh browser dan Filament.*

1. Hubungkan direktori `storage/app/public` ke `public/storage`:

   ```bash
   php artisan storage:link
   ```

2. Penyesuaian `app/Filament/Resources/ProfileResource.php`:

   ```php
   ImageColumn::make('avatar')
       ->disk('public')
       ->circular(),
   ```

3. Penyesuaian `APP_URL` di `.env`:

   ```env
   APP_URL=http://127.0.0.1:8000
   ```

4. Bersihkan cache sistem:

   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

---

## 3. Checklist Menjalankan Proyek (Fresh Clone)

### 1. Inisialisasi Baru

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan make:filament-user
```

### 2. Jalankan Server Lokal

```bash
php artisan serve
```

### 3. URL Pengujian

- **Admin CMS:** `http://127.0.0.1:8000/alhaiza`
- **Public API:** `http://127.0.0.1:8000/api/profile`
