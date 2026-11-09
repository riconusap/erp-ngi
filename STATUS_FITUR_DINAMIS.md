# Dokumentasi Status: Kamus Data, Form Dinamis, & Page Builder

Dokumen ini mencatat ringkasan status teknis, arsitektur, berkas terkait, serta catatan pengembangan terakhir sebelum ketiga fitur dihentikan sementara (*paused*).

---

## 1. Ringkasan Ekosistem & Keterkaitan

Ketiga fitur dirancang untuk bekerja sebagai ekosistem modular:
1. **Kamus Data**: Fondasi struktur data (schema dinamis) dan pembuatan PostgreSQL View otomatis.
2. **Form Dinamis**: Antarmuka pembuat form mandiri untuk pengumpulan data publik atau internal.
3. **Page / App Builder**: Antarmuka pembuat halaman kustom (Canvas Layout: Tabel, Form, Tab) yang diintegrasikan otomatis ke sistem navigasi/menu dashboard.

```
┌────────────────────────────────────────────────────────┐
│                      Kamus Data                        │
│   (dynamic_tables, dynamic_columns, dynamic_records)   │
│             PostgreSQL View: v_<slug>                  │
└──────────────┬──────────────────────────┬──────────────┘
               │                          │
               ▼                          ▼
┌──────────────────────────────┐  ┌──────────────────────────────────┐
│         Form Dinamis         │  │       Page / App Builder         │
│ (dynamic_forms, responses)   │  │ (app_pages, layout, menus, RBAC) │
│ - Target input ke Kamus Data │  │ - Canvas: Table, Form, Tabs      │
│ - Tautan publik /form/:slug  │  │ - Data: Kamus Data / Raw SQL     │
└──────────────────────────────┘  └──────────────────────────────────┘
```

---

## 2. Kamus Data (Data Dictionary & Dynamic Data)

### Deskripsi & Konsep
Modul untuk mendefinisikan entitas data fleksibel tanpa membuat migrasi database manual setiap kali ada penambahan tabel. 
- Data disimpan dalam tabel `dynamic_records` berbasis kolom **JSONB**.
- Setiap tabel dinamis yang dibuat akan otomatis meng-generate **PostgreSQL View** (`v_<slug>`) menggunakan sintaks `data->>'key' AS "key"` untuk kemudahan query SQL langsung.
- Mendukung pemindaian tabel sistem PostgreSQL asli (`getSystemTables`) dengan proteksi kolom sensitif (password, NIK, dsb).

### Berkas Terkait
- **Backend:**
  - Migrasi:
    - [2026_10_10_092617_create_dynamic_tables_table.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_092617_create_dynamic_tables_table.php)
    - [2026_10_10_092618_create_dynamic_columns_table.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_092618_create_dynamic_columns_table.php)
    - [2026_10_10_092619_create_dynamic_records_table.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_092619_create_dynamic_records_table.php)
    - [2026_10_10_100206_add_is_sensitive_to_dynamic_columns_table.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_100206_add_is_sensitive_to_dynamic_columns_table.php)
  - Models:
    - [DynamicTable.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Models/DynamicTable.php)
    - [DynamicColumn.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Models/DynamicColumn.php)
    - [DynamicRecord.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Models/DynamicRecord.php)
  - Controllers:
    - [DynamicTableController.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Http/Controllers/DynamicTableController.php)
    - [DynamicRecordController.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Http/Controllers/DynamicRecordController.php)
- **Frontend:**
  - Manajemen Kamus Data:
    - [KamusDataView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/konfigurasi/KamusDataView.vue)
    - [KamusDataFormView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/konfigurasi/KamusDataFormView.vue)
  - Pengelolaan Isi Data Dinamis:
    - [DynamicDataIndexView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/data-dinamis/DynamicDataIndexView.vue)
    - [DynamicDataListView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/data-dinamis/DynamicDataListView.vue)
    - [DynamicDataFormView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/data-dinamis/DynamicDataFormView.vue)

### Endpoint REST API
- `GET /api/system-tables` - Mendapatkan daftar tabel fisik bawaan database (dengan sensor kolom sensitif).
- `apiResource('kamus-data')`:
  - `GET /api/kamus-data` - Daftar definisi kamus data (pagination & search).
  - `POST /api/kamus-data` - Buat tabel kamus data baru + generate view.
  - `GET /api/kamus-data/{id}` - Detail skema tabel.
  - `PUT /api/kamus-data/{id}` - Update tabel & kolom + recreate view.
  - `DELETE /api/kamus-data/{id}` - Hapus tabel + drop view.
- CRUD Data Dinamis:
  - `GET /api/dynamic-data/{slug}/schema` - Mengambil metadata struktur kolom.
  - `GET /api/dynamic-data/{slug}` - Daftar record (search JSONB & filter fleksibel).
  - `POST /api/dynamic-data/{slug}` - Tambah record data JSONB.
  - `GET /api/dynamic-data/{slug}/{id}` - Detail satu baris record.
  - `PUT /api/dynamic-data/{slug}/{id}` - Perbarui record JSONB.
  - `DELETE /api/dynamic-data/{slug}/{id}` - Hapus record.

### Status & Catatan Saat Ini
- **Status:** Berfungsi (*fully working*). CRUD kamus data, trigger pembuatan view `v_<slug>`, serta CRUD isi data dinamis sudah berjalan.
- **Hal yang perlu diperhatikan saat resume:**
  - Pengeditan kolom pada `DynamicTableController::update()` saat ini memakai metode `delete()` dan `create()` ulang kolom. Jika nama kolom diubah drastis, data JSONB lama tidak otomatis termigrasi.
  - Dukungan relasi (lookup/foreign key) antar tabel dinamis belum diimplementasikan.

---

## 3. Form Dinamis (Dynamic Form Builder)

### Deskripsi & Konsep
Modul untuk membuat formulir dinamis via visual editor. Setiap form dapat berdiri sendiri (menyimpan jawaban ke tabel respon) atau dihubungkan ke salah satu tabel di **Kamus Data**.

### Berkas Terkait
- **Backend:**
  - Migrasi:
    - [2026_10_10_164427_create_dynamic_forms_table.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_164427_create_dynamic_forms_table.php)
    - [2026_10_10_164428_create_dynamic_form_responses_table.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_164428_create_dynamic_form_responses_table.php)
    - [2026_10_10_170015_add_dynamic_table_id_to_dynamic_forms.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_170015_add_dynamic_table_id_to_dynamic_forms.php)
  - Models:
    - [DynamicForm.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Models/DynamicForm.php)
    - [DynamicFormResponse.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Models/DynamicFormResponse.php)
  - Controllers:
    - [DynamicFormController.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Http/Controllers/DynamicFormController.php)
    - [DynamicFormResponseController.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Http/Controllers/DynamicFormResponseController.php)
- **Frontend:**
  - [FormBuilderIndexView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/form-builder/FormBuilderIndexView.vue)
  - [FormBuilderCreateView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/form-builder/FormBuilderCreateView.vue)
  - [FormBuilderResponseView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/form-builder/FormBuilderResponseView.vue)
  - [PublicFormView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/form-builder/PublicFormView.vue) (rute `/form/:slug`)

### Endpoint REST API
- `GET /api/form-builder/{form_id}/responses` - Rekap respon form.
- `GET /api/form-responses/{id}` - Detail respon.
- `DELETE /api/form-responses/{id}` - Hapus respon.
- Catatan: Sebagian submit form dinamis diarahkan menggunakan handler di `app-pages/{slug}/submit` dan upload berkas via `app-pages/{slug}/upload`.

### Status & Catatan Saat Ini
- **Status:** Fungsionalitas dasar selesai (tipe elemen: Teks Singkat, Teks Panjang, Select Option, File Upload).
- **Hal yang perlu diperhatikan saat resume:**
  - Penggabungan logic antara `DynamicFormController` mandiri dan `AppPageController` (pada App Builder) masih memiliki beberapa overlap pada endpoint submit.
  - Perlu penambahan tipe input lebih lanjut jika dilanjutkan (Date Picker, Checkbox, Radio, Rich Text).

---

## 4. Page Builder / App Builder

### Deskripsi & Konsep
Modul pembuatan halaman dashboard instan tanpa *coding* (*no-code/low-code page generator*).
- Menyediakan layout canvas yang dapat diisi blok:
  1. **Canvas Tabel**: Menampilkan data dari Kamus Data atau **Raw SQL Query** (`SELECT ...`).
  2. **Canvas Form**: Formulir input/edit data.
  3. **Tab Container**: Pengelompokan beberapa blok tabel/form dalam tabulasi.
- **Otomatisasi Menu & RBAC:**
  - Setiap halaman yang dibuat otomatis membuat entri di tabel `menus` (di bawah folder `App Kustom`).
  - Otomatis membuat aksi izin di `menu_actions` (`read`, `create`, `update`, `delete`).
  - Otomatis disinkronkan ke role Super Admin (`role_id: 1`).
- Renderer dinamis (`AppRendererView.vue`) memproses skema JSON untuk menampilkan halaman di rute:
  - `/dashboard/pages/view/:slug` (Mode Baca/Tabel)
  - `/dashboard/pages/new/:slug` (Mode Buat Data)
  - `/dashboard/pages/edit/:slug/:id_data` (Mode Ubah Data)

### Berkas Terkait
- **Backend:**
  - Migrasi:
    - [2026_10_10_171348_create_app_pages_table.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_171348_create_app_pages_table.php)
    - [2026_10_10_173243_modify_app_pages_for_complex_schema.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/database/migrations/2026_10_10_173243_modify_app_pages_for_complex_schema.php)
  - Models:
    - [AppPage.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Models/AppPage.php)
  - Controller:
    - [AppPageController.php](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend/app/Http/Controllers/AppPageController.php)
- **Frontend:**
  - [AppBuilderIndexView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/app-builder/AppBuilderIndexView.vue)
  - [AppBuilderCreateView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/app-builder/AppBuilderCreateView.vue)
  - [AppRendererView.vue](file:///Users/laptop/Documents/KBYN/boilerplate-admin-vue/frontend/src/views/app-builder/AppRendererView.vue)

### Endpoint REST API
- `POST /api/app-pages/execute-query` - Menjalankan pengujian query Raw SQL (proteksi hanya `SELECT`).
- `apiResource('app-pages')`:
  - `GET /api/app-pages` - Daftar halaman app builder.
  - `POST /api/app-pages` - Simpan halaman baru + generate Menu & Permissions.
  - `GET /api/app-pages/{id}` - Ambil detail skema halaman.
  - `PUT /api/app-pages/{id}` - Update skema halaman + sinkronisasi menu.
  - `DELETE /api/app-pages/{id}` - Hapus halaman + hapus menu & action terkait.
- Runtime View:
  - `GET /api/app-pages/{slug}/render` - Mengambil definisi halaman aktif berdasarkan slug.

### Status & Catatan Saat Ini
- **Status:** Berfungsi untuk skenario pembuatan halaman tabel, tab, dan form dasar.
- **Hal yang perlu diperhatikan saat resume:**
  - Fitur eksekusi Raw SQL Query (`executeQuery`) saat ini baru memvalidasi awalan kata `select`. Perlu sanitasi parameter lanjutan untuk mencegah SQL Injection jika query menerima input dinamis dari user.
  - Aksi tombol pada baris tabel (Action buttons) saat ini baru mendukung aksi bawaan (Navigasi Create/Edit/Delete sederhana).
  - Mekanisme drag-and-drop antar elemen layout belum ditambahkan (masih menggunakan tombol tambah blok).

---

## 5. Ringkasan Status Database

| Tabel Database | Status | Keterangan |
| :--- | :---: | :--- |
| `dynamic_tables` | ✅ Siap | Menyimpan definisi kamus data & slug |
| `dynamic_columns` | ✅ Siap | Definisi atribut kolom, tipe, required, is_sensitive |
| `dynamic_records` | ✅ Siap | Tempat penyimpanan nilai data (JSONB) |
| `dynamic_forms` | ✅ Siap | Definisi form dinamis mandiri |
| `dynamic_form_responses` | ✅ Siap | Penampung respon submit form mandiri |
| `app_pages` | ✅ Siap | Definisi canvas halaman custom (schema JSON) |
| `v_<slug>` (PostgreSQL Views) | ✅ Siap | Terbuat otomatis dari `dynamic_tables` |

---

## 6. Checklist & Panduan Melanjutkan (Next Steps Saat Resume)

Bila di kemudian hari fitur ini ingin dilanjutkan atau disempurnakan:
1. **Pembersihan & Unifikasi Route:** Putuskan apakah Form Dinamis mandiri (`/form-builder`) tetap dipisah atau dilebur sepenuhnya ke dalam Page Builder (`/app-builder`).
2. **Keamanan Query Raw SQL:** Tingkatkan validasi pada `AppPageController::executeQuery` dengan AST parser atau query bindings jika user diizinkan memasukkan filter dinamis.
3. **Data Relasi:** Tambahkan tipe kolom `relation` pada Kamus Data agar form builder bisa otomatis memuat *dropdown* dari tabel dinamis lain atau tabel master pegawai/pengguna.
4. **Export / Import:** Menambahkan fitur download data dinamis ke format Excel/CSV berdasarkan view `v_<slug>`.
