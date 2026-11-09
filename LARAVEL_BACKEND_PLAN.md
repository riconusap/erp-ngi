# Panduan Skema Backend Laravel (Boilerplate Admin)

Dokumen ini berisi panduan rancangan database (Migrations, Models) dan arsitektur REST API untuk backend berbasis **Laravel**. Rancangan ini disesuaikan sepenuhnya dengan struktur data (Store Pinia) yang telah dibangun di sisi *Frontend* (Vue 3).

**⚠️ Instruksi untuk AI Agent (Antigravity):**
Baca dokumen ini secara menyeluruh, lalu buatkan *Migration*, *Model*, *Controller*, dan *Route* (di `api.php`) yang merepresentasikan arsitektur di bawah ini.

---

## 1. Skema Database

### Tabel `roles` (Peran)
Menyimpan data master dari tingkatan peran (contoh: Super Admin, Staff).
- `id` (bigIncrements)
- `name` (string, max: 100)
- `description` (text, nullable)
- `status` (enum: `['Aktif', 'Nonaktif']`, default: `'Aktif'`)
- `created_at`, `updated_at`

### Tabel `users` (Pengguna)
Modifikasi tabel bawaan Laravel untuk mendukung *Role-Based Access Control* (RBAC).
- `id` (bigIncrements)
- `name` (string)
- `email` (string, unique)
- `password` (string)
- `role_id` (unsignedBigInteger, Foreign Key ke `roles.id`, nullable/restrict on delete)
- `status` (enum: `['Aktif', 'Nonaktif']`, default: `'Aktif'`)
- `remember_token`
- `created_at`, `updated_at`

### Tabel `menus` (Manajemen Menu)
Menyimpan hierarki menu (Folder/Parent dan Sub-menu).
- `id` (bigIncrements)
- `name` (string)
- `path` (string, nullable) -> *Nullable karena Parent Menu biasanya tidak punya path.*
- `icon` (string, nullable) -> *Menyimpan nama icon Element Plus (contoh: 'User', 'Setting').*
- `sort_order` (integer, default: 0)
- `status` (enum: `['Aktif', 'Nonaktif']`, default: `'Aktif'`)
- `parent_id` (unsignedBigInteger, nullable, Foreign Key ke `menus.id` untuk *self-referencing / child menu*)
- `created_at`, `updated_at`

### Tabel `menu_actions` (Aksi Menu)
Menyimpan daftar aksi spesifik yang diizinkan untuk suatu menu.
- `id` (bigIncrements)
- `menu_id` (unsignedBigInteger, Foreign Key ke `menus.id`, cascade on delete)
- `code` (string) -> *contoh: 'read', 'create', 'update', 'delete', 'approve'*
- `name` (string) -> *contoh: 'Lihat / Akses', 'Tambah', 'Ubah'*
- `created_at`, `updated_at`
> **Aturan Migrasi:** Tambahkan `unique` index gabungan pada `(menu_id, code)` agar tidak ada kode aksi ganda di dalam satu menu.

### Tabel `role_permissions` (Hak Akses)
Tabel pivot (penghubung) yang mendefinisikan Role mana memiliki akses ke Aksi Menu apa.
- `role_id` (unsignedBigInteger, Foreign Key ke `roles.id`, cascade on delete)
- `menu_action_id` (unsignedBigInteger, Foreign Key ke `menu_actions.id`, cascade on delete)
> **Aturan Migrasi:** Tambahkan `unique` index gabungan pada `(role_id, menu_action_id)`.

---

## 2. Struktur REST API (Endpoints)

Pastikan semua Endpoint dibungkus oleh Middleware autentikasi (seperti **Laravel Sanctum**). Kembalikan JSON dengan format standar: `{ "status": true/false, "message": "...", "data": ... }`.

### 🔐 Autentikasi (`AuthController`)
- `POST /api/login` (Menerima `email`, `password`)
- `POST /api/logout` (Memerlukan token auth)
- `GET /api/user` (Mengembalikan data user login saat ini berserta Role dan struktur menunya)

### 👥 Pengguna (`UserController`)
- `GET /api/users` (Mendukung parameter `?search=...` dan `?status=...`)
- `POST /api/users` 
- `GET /api/users/{id}`
- `PUT /api/users/{id}` (Password opsional. Jika dikosongkan, jangan diupdate)
- `DELETE /api/users/{id}`

### 🎭 Peran & Hak Akses (`RoleController`)
- `GET /api/roles`
- `POST /api/roles`
  - **Ekspektasi Payload:** Menerima detail role dan array hak akses.
  - *Format frontend:* `{ "name": "...", "permissions": { "menu_id_1": ["read", "create"], "menu_id_2": ["read"] } }`
  - *Tugas Controller:* Harus memetakan kombinasi `menu_id` dan `code` tersebut untuk mencari `menu_action_id` yang tepat, lalu di-sync ke tabel `role_permissions`.
- `GET /api/roles/{id}`
- `PUT /api/roles/{id}` (Update data dan *sync* pivot `permissions`)
- `DELETE /api/roles/{id}`

### 📁 Menu (`MenuController`)
- `GET /api/menus` 
  - *Logika:* Mengembalikan struktur nested/tree (Parent beserta *children*-nya).
- `POST /api/menus`
  - **Ekspektasi Payload:** Menyertakan array `actions` seperti `[{ code: 'create', name: 'Tambah' }, ...]`.
  - **Aturan Khusus (PENTING):** Secara default, *Backend* WAJIB memasukkan aksi `{code: 'read', name: 'Lihat / Akses'}` ke tabel `menu_actions` untuk setiap menu baru yang dibuat. Jika payload tidak mengirimkannya, controller harus menyisipkannya.
- `GET /api/menus/{id}`
- `PUT /api/menus/{id}` (Sinkronisasi perubahan array `actions`)
- `DELETE /api/menus/{id}`

---

## 3. Catatan Implementasi Model (Eloquent)
- **Menu Model**:
  - Punya relasi `children()` -> `hasMany(Menu::class, 'parent_id')`
  - Punya relasi `actions()` -> `hasMany(MenuAction::class, 'menu_id')`
  - *(Opsional disarankan)*: Gunakan *Eager Loading* `with(['children.actions', 'actions'])` secara default pada index.
- **Role Model**:
  - Punya relasi `permissions()` -> `belongsToMany(MenuAction::class, 'role_permissions')`
- **User Model**:
  - Punya relasi `role()` -> `belongsTo(Role::class)`
