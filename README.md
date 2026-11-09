# GreenlyConnect

> **Sistem ERP & Manajemen Kepegawaian (HRMS)**  
> **PT Naturally Green Indonesia**

---

## 📌 Tentang GreenlyConnect

**GreenlyConnect** adalah platform ERP dan Human Resource Management System (HRMS) terpadu yang dirancang khusus untuk operasional **PT Naturally Green Indonesia**. Sistem ini mengelola seluruh siklus SDM mulai dari data induk karyawan, riwayat kontrak kerja, presensi harian berbasis GPS/jadwal shift, pengajuan dan persetujuan cuti berjenjang, penugasan proyek & task management, notifikasi realtime via Server-Sent Events (SSE), hingga kustomisasi data dinamis dan form builder.

---

## 🛠️ Tech Stack

### Frontend
- **Framework**: Vue 3 (Composition API with `<script setup lang="ts">`)
- **Build Tool**: Vite & TypeScript
- **State Management**: Pinia Store
- **UI Framework**: Element Plus & FontAwesome
- **Styling**: SCSS, Custom Theme Tokens, BEM-inspired modular styles
- **HTTP Client**: Axios with Bearer token interceptor

### Backend
- **Framework**: Laravel 13 (PHP 8.3+)
- **Database**: PostgreSQL 16
- **Autentikasi**: Laravel Sanctum (Bearer Token)
- **Dokumentasi API**: L5-Swagger (OpenAPI 3.0)
- **Realtime**: Server-Sent Events (SSE) Stream

### Infrastruktur & Containerization
- **Docker & Docker Compose**: Multi-container orchestration (PostgreSQL, Backend API, Frontend Nginx)
- **Web Server Production**: Nginx Alpine with built-in API reverse-proxy

---

## 🚀 Fitur Utama

1. **Autentikasi & Dynamic RBAC (Role-Based Access Control)**
   - Manajemen peran (role), menu navigasi dinamis, dan matriks hak akses aksi (`read`, `create`, `update`, `delete`, `reset-password`).
   - Format response izin akses terstruktur: `menus -> list_menu -> list Action`.

2. **Manajemen Pegawai & Kontrak Kerja**
   - Pendataan profil lengkap pegawai, NIK, NIP, kontak, dan riwayat dokumen.
   - Manajemen kontrak kerja pegawai, jabatan, client & area penempatan, komponen gaji tetap/variabel, serta tracking masa berlaku kontrak.

3. **Presensi & Absensi Karyawan**
   - Clock In & Clock Out presensi harian dengan verifikasi jadwal shift, toleransi keterlambatan, dan riwayat presensi.
   - Perhitungan otomatis status kehadiran (Tepat Waktu, Terlambat, dsb).

4. **Pengajuan & Persetujuan Cuti**
   - Pengajuan cuti terintegrasi kuota, kalkulasi hari kerja otomatis, filter weekend/libur, dan alur approval berjenjang.

5. **Task & Project Management**
   - Penugasan pekerjaan (task) per project, prioritas, deadline, status tracking (Todo, In Progress, In Review, Done), dan penilaian rating task.

6. **Notifikasi Realtime (SSE)**
   - Streaming notifikasi penugasan task, review, dan update status via Server-Sent Events tanpa perlu me-refresh halaman.

7. **Dynamic Data & Form Builder**
   - Pembuatan tabel dinamis kustom, field generator, form builder publik, dan app renderer otomatis.

8. **Dokumentasi API Terintegrasi**
   - Swagger / OpenAPI UI interaktif untuk 74+ endpoint backend.

---

## 🐳 Cara Menjalankan dengan Docker

### 1. Menjalankan Seluruh Stack (Production-Ready)
Jalankan perintah berikut pada root folder project:

```bash
docker compose up --build -d
```

Layanan yang akan berjalan:
- **Aplikasi GreenlyConnect (Frontend)**: [http://localhost:3000](http://localhost:3000)
- **Backend REST API**: [http://localhost:8000](http://localhost:8000)
- **Dokumentasi Swagger API**: [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)
- **Database PostgreSQL**: `localhost:5432` (`POSTGRES_DB=greenly_connect`, User/Password: `postgres`/`postgres`)

### 2. Mode Pengembangan (Live Hot-Reload)
Untuk kebutuhan pengembangan kode secara langsung dengan Vite HMR:

```bash
docker compose -f docker-compose.dev.yml up --build
```
- **Frontend (Vite Dev)**: [http://localhost:5173](http://localhost:5173)
- **Backend API**: [http://localhost:8000](http://localhost:8000)

### 3. Menghentikan Layanan
```bash
docker compose down
# atau jika menggunakan compose dev:
docker compose -f docker-compose.dev.yml down
```

---

## 💻 Cara Menjalankan Manual (Lokal Tanpa Docker)

### Prasyarat
- PHP >= 8.3 & Composer
- Node.js >= 20 & npm
- PostgreSQL >= 15

### 1. Backend Setup
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan l5-swagger:generate
php artisan serve --port=8000
```

### 2. Frontend Setup
```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

---

## 📚 Akses Dokumentasi API

Dokumentasi lengkap seluruh endpoint API GreenlyConnect dapat diakses melalui Swagger UI di:
```
http://localhost:8000/api/documentation
```

---

## 🏢 Copyright

© 2026 **PT Naturally Green Indonesia**. All rights reserved.
