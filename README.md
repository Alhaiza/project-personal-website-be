# Technical Blueprint & Engineering Workflow (Laravel 12 & Filament v3)

Dokumentasi ini merangkum evolusi arsitektur dan alur pengembangan kode (flow) dari kondisi awal (*fresh installation*) hingga status terkini (*production-ready API & CMS*) berdasarkan analisis perubahan kode nyata (*diff*).

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

## 2. End-to-End Engineering Flow

Alur berikut memetakan perjalanan kode dari nol hingga selesai, menjelaskan file yang lahir di setiap fase beserta keputusan teknisnya.

### Fase 1: Setup Dasar & Testing Framework

*Fokus: Mempersiapkan runtime framework dan test harness.*

- **Langkah Kerja:**
  1. Inisialisasi basis Laravel dan konfigurasi `.env`.
  2. Integrasi **Pest PHP** sebagai testing engine modern pengganti PHPUnit bawaan.
- **Perubahan Struktur File:**
  - Tambah konfigurasi tes: `tests/Pest.php`
  - Refaktor unit & feature test ke syntax deklaratif Pest (`it('returns a successful response', ...)`).

---

### Fase 2: Pemasangan Headless CMS (Filament v3)

*Fokus: Mengaktifkan panel admin kustom tanpa mencampuri route default.*

- **Langkah Kerja:**
  1. Install paket Filament Panel Builder (`filament/filament`).
  2. Mendaftarkan custom provider panel `alhaiza` di `bootstrap/providers.php`.
- **Perubahan Struktur File:**
  - `app/Providers/Filament/AlhaizaPanelProvider.php`: Panel Provider independen dengan path `/alhaiza`, brand color Amber, dan middleware autentikasi bawaan.
  - Aset publik Filament terpublikasi di `public/css/filament/` dan `public/js/filament/`.

---

### Fase 3: Desain Skema Database & Model Decoupled

*Fokus: Memisahkan domain data ke tabel independen berelasi flat.*

Daripada menyimpan semua riwayat dalam satu entitas raksasa, arsitektur dibagi menjadi 4 entitas independen:

1. **Modul Profile**
   - Migrasi: `database/migrations/*_create_profiles_table.php` (`name`, `headline`, `bio`, `avatar`, `social_links`).
   - Model: `app/Models/Profile.php` dengan `$casts = ['social_links' => 'array']`.
2. **Modul Project**
   - Migrasi: `database/migrations/*_create_projects_table.php` (`title`, `description`, `technologies`, `repository_url`, `demo_url`).
   - Model: `app/Models/Project.php` dengan `$casts = ['technologies' => 'array']`.
3. **Modul Experience**
   - Migrasi: `database/migrations/*_create_experiences_table.php` (`company`, `role`, `start_date`, `end_date`, `description`).
   - Model: `app/Models/Experience.php`.
4. **Modul Skill**
   - Migrasi: `database/migrations/*_create_skills_table.php` (`name`, `category`).
   - Model: `app/Models/Skill.php`.

---

### Fase 4: Pembangunan CRUD Panel Admin (Filament Resources)

*Fokus: Menyediakan antarmuka input data untuk tiap entitas database.*

Setiap entitas mendapatkan class Resource dan sub-pages (`Create`, `Edit`, `List`):

- **`app/Filament/Resources/ProfileResource.php`**
  - Form: `TextInput` (name, headline), `Textarea` (bio), `FileUpload` (avatar), `KeyValue` (social_links).
  - Table: `ImageColumn` (avatar), `TextColumn` (name, headline, updated_at).
- **`app/Filament/Resources/ProjectResource.php`**
  - Form: `TextInput`, `TagsInput` (technologies), `Textarea`.
- **`app/Filament/Resources/ExperienceResource.php`**
  - Form: `TextInput` (company, role), `DatePicker` (start_date, end_date), `Textarea`.
- **`app/Filament/Resources/SkillResource.php`**
  - Form: `TextInput` (name), `Select` (category).

---

### Fase 5: Agregasi Endpoint API Publik

*Fokus: Menggabungkan data multi-tabel menjadi satu respons JSON efisien.*

Alih-alih frontend melakukan 4 kali HTTP round-trip, backend menyediakan satu endpoint agregasi:

- **Routing:** Diaktifkan di `routes/api.php` dan didaftarkan pada routing pipeline `bootstrap/app.php`.

  ```http
  GET /api/profile
  ```

- **Controller:** `app/Http/Controllers/Api/ProfileController.php`
  - Mengambil profile utama: `Profile::first()`
  - Mengekstrak skill dalam format flat array: `Skill::pluck('name')->toArray()`
  - Mengambil koleksi project & experience: `Project::all()`, `Experience::all()`
  - Memetakan avatar ke URL absolut: `asset('storage/' . $profile->avatar)`
- **Format Response JSON:**

  ```json
  {
    "success": true,
    "message": "Profile data retrieved successfully",
    "data": {
      "name": "...",
      "role": "...",
      "bio": "...",
      "avatar": "http://127.0.0.1:8000/storage/avatars/...",
      "skills": ["PHP", "Laravel", "TypeScript"],
      "projects": [...],
      "experiences": [...]
    }
  }
  ```

---

### Fase 6: Diagnostik & Perbaikan Storage Preview

*Fokus: Menyelesaikan issue broken image pada Filament Admin Table.*

- **Gejala Masalah:** Gambar avatar sukses di frontend via API, tetapi rusak (broken icon) di Filament Admin Table.
- **Akar Masalah:**
  1. `ImageColumn::make('avatar')` di `ProfileResource.php` secara default menggunakan disk `local` (`storage/app/private`), bukan disk `public`.
  2. Nilai `APP_URL` di `.env` belum cocok dengan port server aktif (`http://127.0.0.1:8000`), sehingga URL resolver Filament gagal dimuat oleh browser.
- **Diff Solusi:**
  1. Pada `app/Filament/Resources/ProfileResource.php`:

     ```php
     ImageColumn::make('avatar')
         ->disk('public')
         ->circular(),
     ```

  2. Pada `.env`:

     ```env
     APP_URL=http://127.0.0.1:8000
     ```

---

## 3. Checklist Menjalankan & Menguji Proyek

### 1. Inisialisasi Baru (Clone ke Device Baru)

```bash
composer install
cp .env.example .env
php artisan key:generate
# Sesuaikan konfigurasi database di .env
php artisan migrate
php artisan storage:link
php artisan make:filament-user # Buat user admin
```

### 2. Runtime Server

```bash
php artisan serve
```

### 3. Pembersihan Cache (Setiap Ubah .env / View / Config)

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 4. URL Verifikasi

- **Admin Panel:** `http://127.0.0.1:8000/alhaiza`
- **Public API:** `http://127.0.0.1:8000/api/profile`
