<template>
  <div class="page-container" v-loading="loading">
    <!-- Profile Hero Card -->
    <div class="profile-hero-card" v-if="profile">
      <div
        class="hero-cover"
        :style="userCoverStyle"
        :class="{ 'has-custom-cover': !!userCoverUrl }"
        v-loading="uploadingCover"
      >
        <div class="cover-overlay" v-if="userCoverUrl"></div>
        <input
          ref="directCoverInputRef"
          type="file"
          accept="image/jpeg,image/png,image/jpg,image/webp"
          style="display: none;"
          @change="handleDirectCoverUpload"
        />
        <div class="cover-actions">
          <button
            type="button"
            class="cover-action-btn"
            @click="triggerDirectCoverUpload"
            :disabled="uploadingCover"
            :title="userCoverUrl ? 'Ubah Foto Sampul' : 'Tambahkan Foto Sampul'"
          >
            <el-icon v-if="!uploadingCover" class="me-1"><PictureFilled /></el-icon>
            <el-icon v-else class="is-loading me-1"><Loading /></el-icon>
            <span>{{ userCoverUrl ? 'Ubah Cover' : 'Tambah Cover' }}</span>
          </button>
          <button
            v-if="userCoverUrl"
            type="button"
            class="cover-action-btn btn-remove-cover"
            @click="removeCover"
            :disabled="uploadingCover"
            title="Hapus Cover (Gunakan Default)"
          >
            <el-icon><Delete /></el-icon>
          </button>
        </div>
      </div>
      <div class="hero-body">
        <div class="hero-avatar-wrapper">
          <el-avatar :size="96" :src="userAvatar" class="hero-avatar" v-loading="uploadingAvatar">
            {{ (profile.pegawai?.nama || profile.user.username)?.charAt(0)?.toUpperCase() }}
          </el-avatar>
          <input
            ref="directAvatarInputRef"
            type="file"
            accept="image/jpeg,image/png,image/jpg,image/webp"
            style="display: none;"
            @change="handleDirectAvatarUpload"
          />
          <div
            class="avatar-edit-badge"
            :class="{ 'is-uploading': uploadingAvatar }"
            @click="triggerDirectAvatarUpload"
            title="Klik untuk langsung ganti foto profil"
          >
            <el-icon v-if="!uploadingAvatar"><Camera /></el-icon>
            <el-icon v-else class="is-loading"><Loading /></el-icon>
          </div>
        </div>

        <div class="hero-meta">
          <div class="hero-title-row">
            <div class="hero-title-group">
              <h1 class="hero-name">{{ profile.pegawai?.nama || profile.user.username }}</h1>
              <div class="hero-tags">
                <el-tag size="small" type="danger" effect="dark" class="role-badge">
                  {{ profile.user.role?.name || 'User' }}
                </el-tag>
                <el-tag size="small" :type="profile.user.status === 'Aktif' ? 'success' : 'danger'" effect="light">
                  {{ profile.user.status }}
                </el-tag>
                <el-tag v-if="profile.kontrak?.jabatan" size="small" type="info" effect="plain">
                  {{ profile.kontrak.jabatan.nama_jabatan }}
                </el-tag>
              </div>
            </div>

            <div class="hero-actions">
              <AppButton variant="primary" @click="openEditModal">
                <el-icon class="me-1"><Edit /></el-icon> Edit Profil
              </AppButton>
            </div>
          </div>

          <div class="hero-pills">
            <div class="info-pill" v-if="profile.pegawai?.nip">
              <el-icon class="pill-icon"><CreditCard /></el-icon>
              <span>NIP: <strong>{{ profile.pegawai.nip }}</strong></span>
            </div>
            <div class="info-pill" v-if="profile.pegawai?.nik">
              <el-icon class="pill-icon"><Postcard /></el-icon>
              <span>NIK: <strong>{{ profile.pegawai.nik }}</strong></span>
            </div>
            <div class="info-pill">
              <el-icon class="pill-icon"><Message /></el-icon>
              <span>{{ profile.user.email }}</span>
            </div>
            <div class="info-pill" v-if="profile.pegawai?.no_telp">
              <el-icon class="pill-icon"><Phone /></el-icon>
              <span>{{ profile.pegawai.no_telp }}</span>
            </div>
            <div class="info-pill" v-if="profile.kontrak?.area">
              <el-icon class="pill-icon"><Location /></el-icon>
              <span>{{ profile.kontrak.area.nama_area }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="stats-grid" v-if="profile">
      <div class="stat-card">
        <div class="stat-icon icon-blue">
          <el-icon><OfficeBuilding /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">Project Terhubung</span>
          <span class="stat-value">{{ profile.stats.total_projects }} Project</span>
          <span class="stat-sub">Aktif & Penugasan</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-amber">
          <el-icon><Finished /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">Tugas Dikerjakan</span>
          <span class="stat-value">{{ profile.stats.total_tasks }} Tugas</span>
          <span class="stat-sub text-success">{{ profile.stats.completed_tasks }} Tugas Selesai</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-green">
          <el-icon><Calendar /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">Pengajuan Cuti</span>
          <span class="stat-value">{{ profile.stats.total_cuti }} Cuti</span>
          <span class="stat-sub">Riwayat diajukan</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-purple">
          <el-icon><Document /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">Status Kontrak</span>
          <span class="stat-value">
            {{ profile.kontrak ? profile.kontrak.tipe_kontrak?.toUpperCase() : 'Non-Kontrak' }}
          </span>
          <span class="stat-sub" v-if="profile.kontrak?.terhitung_sampai_tanggal">
            s/d {{ formatDateOnly(profile.kontrak.terhitung_sampai_tanggal) }}
          </span>
          <span class="stat-sub" v-else>Pengguna Sistem</span>
        </div>
      </div>
    </div>

    <!-- Detail Pegawai & Detail Akun Pengguna -->
    <el-row :gutter="20" v-if="profile">
      <!-- 1. Detail Pegawai -->
      <el-col :xs="24" :lg="12">
        <el-card class="detail-card h-100" shadow="never">
          <template #header>
            <div class="card-header">
              <div class="header-left-title">
                <el-icon class="section-icon text-primary"><UserFilled /></el-icon>
                <span>Detail Pegawai</span>
              </div>
              <el-tag v-if="profile.pegawai" size="small" type="success" effect="light">Terhubung Pegawai</el-tag>
              <el-tag v-else size="small" type="warning" effect="light">Belum Terhubung</el-tag>
            </div>
          </template>

          <div v-if="profile.pegawai" class="detail-list">
            <div class="detail-item">
              <span class="detail-label">Nama Lengkap</span>
              <span class="detail-val font-semibold">{{ profile.pegawai.nama }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">NIK (No. KTP)</span>
              <span class="detail-val">{{ profile.pegawai.nik || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">NIP (Nomor Induk Pegawai)</span>
              <span class="detail-val">
                <el-tag size="small" type="info">{{ profile.pegawai.nip || '-' }}</el-tag>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email Pegawai</span>
              <span class="detail-val">{{ profile.pegawai.email || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">No. Telepon / WhatsApp</span>
              <span class="detail-val">{{ profile.pegawai.no_telp || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Jabatan Aktif</span>
              <span class="detail-val font-medium text-primary">
                {{ profile.kontrak?.jabatan?.nama_jabatan || '-' }}
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Area Penempatan</span>
              <span class="detail-val">
                {{ profile.kontrak?.area?.nama_area || '-' }}
                <small v-if="profile.kontrak?.area?.client" class="text-muted d-block">
                  ({{ profile.kontrak.area.client.nama_client }})
                </small>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Terdaftar Sejak</span>
              <span class="detail-val">{{ formatDateTime(profile.pegawai.created_at) }}</span>
            </div>
          </div>
          <el-empty v-else description="Akun ini belum ditautkan ke data pegawai." :image-size="80" />
        </el-card>
      </el-col>

      <!-- 2. Detail Pengguna (Akun Sistem) -->
      <el-col :xs="24" :lg="12">
        <el-card class="detail-card h-100" shadow="never">
          <template #header>
            <div class="card-header">
              <div class="header-left-title">
                <el-icon class="section-icon text-danger"><Key /></el-icon>
                <span>Detail Akun Pengguna</span>
              </div>
              <el-tag size="small" type="danger" effect="plain">Akun Sistem</el-tag>
            </div>
          </template>

          <div class="detail-list">
            <div class="detail-item">
              <span class="detail-label">Username</span>
              <span class="detail-val font-semibold">{{ profile.user.username }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email Login</span>
              <span class="detail-val">{{ profile.user.email }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Peran Sistem (Role)</span>
              <span class="detail-val">
                <el-tag size="small" type="danger" effect="dark">{{ profile.user.role?.name || '-' }}</el-tag>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Hak Akses Dashboard Admin</span>
              <span class="detail-val">
                <el-tag size="small" :type="profile.user.role?.can_access_admin ? 'success' : 'info'">
                  {{ profile.user.role?.can_access_admin ? 'Ya (Diizinkan)' : 'Tidak' }}
                </el-tag>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Status Akun</span>
              <span class="detail-val">
                <el-tag size="small" :type="profile.user.status === 'Aktif' ? 'success' : 'danger'">
                  {{ profile.user.status }}
                </el-tag>
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">ID Akun Pengguna</span>
              <span class="detail-val font-mono">#{{ profile.user.id }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Waktu Pendaftaran</span>
              <span class="detail-val">{{ formatDateTime(profile.user.created_at) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Keamanan Akun</span>
              <span class="detail-val text-success font-medium">
                <el-icon class="me-1"><Lock /></el-icon> Kata Sandi Terenkripsi
              </span>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 3. Detail Kontrak Kerja -->
    <el-card class="detail-card mt-4" shadow="never" v-if="profile">
      <template #header>
        <div class="card-header">
          <div class="header-left-title">
            <el-icon class="section-icon text-warning"><Document /></el-icon>
            <span>Detail Kontrak Kerja</span>
          </div>
          <div v-if="profile.kontrak">
            <el-tag size="small" :type="profile.kontrak.status === 'aktif' ? 'success' : 'danger'" effect="dark">
              Status: {{ profile.kontrak.status?.toUpperCase() }}
            </el-tag>
          </div>
        </div>
      </template>

      <div v-if="profile.kontrak">
        <el-descriptions :column="3" border class="contract-descriptions">
          <el-descriptions-item label="Nomor Kontrak">
            <strong>{{ profile.kontrak.no_kontrak }}</strong>
          </el-descriptions-item>
          <el-descriptions-item label="Tipe Kontrak">
            <el-tag size="small" type="primary">{{ profile.kontrak.tipe_kontrak?.toUpperCase() }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Jabatan">
            {{ profile.kontrak.jabatan?.nama_jabatan || '-' }}
          </el-descriptions-item>

          <el-descriptions-item label="Terhitung Mulai (TMT)">
            {{ formatDateOnly(profile.kontrak.terhitung_mulai_tanggal) }}
          </el-descriptions-item>
          <el-descriptions-item label="Terhitung Selesai (TST)">
            {{ formatDateOnly(profile.kontrak.terhitung_sampai_tanggal) }}
          </el-descriptions-item>
          <el-descriptions-item label="Area Penempatan">
            {{ profile.kontrak.area?.nama_area || '-' }}
          </el-descriptions-item>

          <el-descriptions-item label="Klien Perusahaan">
            {{ profile.kontrak.area?.client?.nama_client || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="Gaji Pokok / Elemen Tetap">
            <strong class="text-success">{{ formatCurrency(profile.kontrak.element_tetap) }}</strong>
          </el-descriptions-item>
          <el-descriptions-item label="Tunjangan / Elemen Variabel">
            <strong class="text-primary">{{ formatCurrency(profile.kontrak.element_variabel) }}</strong>
          </el-descriptions-item>

          <el-descriptions-item label="Keterangan Kontrak" :span="3">
            {{ profile.kontrak.keterangan || 'Kontrak kerja berlaku sesuai ketentuan periode berjalan.' }}
          </el-descriptions-item>
        </el-descriptions>

        <!-- Komponen Gaji Tambahan -->
        <div v-if="profile.kontrak.komponens && profile.kontrak.komponens.length > 0" class="contract-sub-section mt-4">
          <h4 class="sub-section-title">
            <el-icon class="me-1"><Money /></el-icon> Komponen Gaji & Tunjangan Terdaftar:
          </h4>
          <div class="komponen-chips mt-2">
            <div v-for="komp in profile.kontrak.komponens" :key="komp.id" class="komponen-chip">
              <span class="chip-name">{{ komp.nama_komponen }}</span>
              <span class="chip-val" :class="komp.jenis === 'potongan' ? 'text-danger' : 'text-success'">
                {{ komp.jenis === 'potongan' ? '-' : '+' }} {{ formatCurrency(komp.nilai) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Riwayat Kontrak Lainnya -->
        <div v-if="profile.kontrak.riwayat_kontrak && profile.kontrak.riwayat_kontrak.length > 1" class="contract-history mt-4">
          <el-collapse>
            <el-collapse-item title="Lihat Riwayat Kontrak Lainnya" name="history">
              <el-table :data="profile.kontrak.riwayat_kontrak" size="small" stripe>
                <el-table-column prop="no_kontrak" label="No. Kontrak" min-width="160" />
                <el-table-column prop="tipe_kontrak" label="Tipe" min-width="100" />
                <el-table-column prop="jabatan" label="Jabatan" min-width="160" />
                <el-table-column prop="area" label="Area" min-width="160" />
                <el-table-column label="Periode" min-width="180">
                  <template #default="{ row }">
                    {{ formatDateOnly(row.tmt) }} s/d {{ formatDateOnly(row.tst) }}
                  </template>
                </el-table-column>
                <el-table-column prop="status" label="Status" width="100" align="center">
                  <template #default="{ row }">
                    <el-tag size="small" :type="row.status === 'aktif' ? 'success' : 'info'">{{ row.status }}</el-tag>
                  </template>
                </el-table-column>
              </el-table>
            </el-collapse-item>
          </el-collapse>
        </div>
      </div>
      <el-empty v-else description="Belum ada data kontrak kerja aktif untuk pengguna ini." :image-size="80" />
    </el-card>

    <!-- 4. List Project Terhubung -->
    <el-card class="detail-card mt-4" shadow="never" v-if="profile">
      <template #header>
        <div class="card-header">
          <div class="header-left-title">
            <el-icon class="section-icon text-success"><OfficeBuilding /></el-icon>
            <span>Daftar Project Terhubung</span>
          </div>
          <el-tag type="info" size="small">{{ profile.projects.length }} Project Terhubung</el-tag>
        </div>
      </template>

      <div v-if="profile.projects && profile.projects.length > 0">
        <el-table :data="profile.projects" stripe style="width: 100%;">
          <el-table-column prop="kode_project" label="Kode" width="140">
            <template #default="{ row }">
              <el-tag size="small" type="info">{{ row.kode_project }}</el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="nama_project" label="Nama Project" min-width="220">
            <template #default="{ row }">
              <div class="project-title-cell">
                <span class="project-name">{{ row.nama_project }}</span>
                <span v-if="row.deskripsi" class="project-desc">{{ row.deskripsi }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="Klien & Area" min-width="200">
            <template #default="{ row }">
              <div class="client-cell">
                <span class="client-name">{{ row.client?.nama_client || '-' }}</span>
                <span class="area-name">{{ row.area?.nama_area || '-' }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="Periode Project" min-width="180">
            <template #default="{ row }">
              <span class="period-text">
                {{ formatDateOnly(row.tanggal_mulai) }} s/d {{ formatDateOnly(row.tanggal_selesai) }}
              </span>
            </template>
          </el-table-column>

          <el-table-column label="Sumber Keterhubungan" min-width="200">
            <template #default="{ row }">
              <el-tag size="small" :type="row.source.includes('Kontrak') ? 'primary' : 'warning'" effect="light">
                {{ row.source }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="tasks_count" label="Tugas Terkait" width="120" align="center">
            <template #default="{ row }">
              <el-badge :value="row.tasks_count" :type="row.tasks_count > 0 ? 'primary' : 'info'">
                <span class="tasks-badge-text">{{ row.tasks_count }} Task</span>
              </el-badge>
            </template>
          </el-table-column>

          <el-table-column prop="status" label="Status" width="120" align="center">
            <template #default="{ row }">
              <el-tag size="small" :type="getProjectStatusTag(row.status)">
                {{ row.status }}
              </el-tag>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <el-empty
        v-else
        description="Belum ada project yang terhubung dengan akun / kontrak kerja pegawai ini."
        :image-size="80"
      />
    </el-card>

    <!-- Modal Edit Profile (Redesigned) -->
    <el-dialog
      v-model="isEditModalVisible"
      class="redesigned-profile-modal"
      width="600px"
      align-center
      destroy-on-close
    >
      <template #header>
        <div class="custom-modal-header">
          <div class="modal-header-icon">
            <el-icon><UserFilled /></el-icon>
          </div>
          <div class="modal-header-text">
            <h3 class="modal-title">Edit Profil Pengguna</h3>
            <p class="modal-subtitle">Perbarui informasi identitas, kontak, dan kredensial akun Anda</p>
          </div>
        </div>
      </template>

      <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-position="top">
        <!-- 1. Avatar Upload Card -->
        <div class="edit-avatar-section">
          <div class="avatar-preview-box">
            <el-avatar :size="80" :src="avatarPreviewUrl || userAvatar" class="modal-avatar">
              {{ (editForm.nama || editForm.username)?.charAt(0)?.toUpperCase() }}
            </el-avatar>
            <div class="avatar-status-pill" v-if="avatarPreviewUrl">
              <el-icon><Check /></el-icon>
            </div>
          </div>

          <div class="avatar-controls">
            <div class="avatar-text-info">
              <span class="avatar-title">Foto Profil Pengguna</span>
              <span class="avatar-hint">Format JPG, PNG, atau WebP (Maksimal 2MB).</span>
            </div>

            <div class="avatar-btn-row">
              <el-upload
                action="#"
                :auto-upload="false"
                :show-file-list="false"
                :on-change="handleAvatarChange"
                accept="image/jpeg,image/png,image/jpg,image/webp"
              >
                <el-button size="default" class="upload-btn">
                  <el-icon class="me-1"><Upload /></el-icon> {{ avatarPreviewUrl ? 'Ganti Foto' : 'Unggah Foto Baru' }}
                </el-button>
              </el-upload>

              <el-button
                v-if="avatarPreviewUrl"
                size="default"
                text
                type="danger"
                @click="cancelAvatarPreview"
                class="ms-2"
              >
                Batalkan
              </el-button>
            </div>
          </div>
        </div>

        <!-- 2. Section: Data Identitas & Kontak -->
        <div class="modal-section-divider">
          <div class="section-title">
            <el-icon class="section-icon"><User /></el-icon>
            <span>Informasi Identitas & Kontak</span>
          </div>
        </div>

        <el-row :gutter="14">
          <el-col :xs="24" :sm="24">
            <el-form-item label="Nama Lengkap" prop="nama">
              <el-input
                v-model="editForm.nama"
                placeholder="Masukkan nama lengkap"
                size="large"
                clearable
              >
                <template #prefix>
                  <el-icon class="input-icon"><UserFilled /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="12">
            <el-form-item label="Username" prop="username">
              <el-input
                v-model="editForm.username"
                placeholder="Masukkan username"
                size="large"
                clearable
              >
                <template #prefix>
                  <el-icon class="input-icon"><User /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="12">
            <el-form-item label="No. Telepon / WhatsApp" prop="no_telp">
              <el-input
                v-model="editForm.no_telp"
                placeholder="contoh: 08123456789"
                size="large"
                clearable
              >
                <template #prefix>
                  <el-icon class="input-icon"><Phone /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="24">
            <el-form-item label="Email Akun" prop="email">
              <el-input
                v-model="editForm.email"
                placeholder="contoh: user@domain.com"
                type="email"
                size="large"
                clearable
              >
                <template #prefix>
                  <el-icon class="input-icon"><Message /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 3. Section: Keamanan Akun -->
        <div class="modal-section-divider mt-2">
          <div class="section-title">
            <el-icon class="section-icon"><Lock /></el-icon>
            <span>Keamanan & Kata Sandi</span>
          </div>
        </div>

        <div class="security-card">
          <el-form-item label="Password Baru" prop="password" class="mb-1">
            <el-input
              v-model="editForm.password"
              type="password"
              show-password
              placeholder="Masukkan password baru jika ingin diganti"
              size="large"
            >
              <template #prefix>
                <el-icon class="input-icon"><Key /></el-icon>
              </template>
            </el-input>
          </el-form-item>
          <div class="security-tip">
            <el-icon class="me-1"><InfoFilled /></el-icon>
            <span>Biarkan kolom password kosong jika tidak berniat mengubah kata sandi Anda.</span>
          </div>
        </div>
      </el-form>

      <template #footer>
        <div class="custom-modal-footer">
          <span class="footer-note text-muted">
            <el-icon class="me-1"><Check /></el-icon> Sinkronisasi otomatis ke data pegawai
          </span>
          <div class="footer-actions">
            <el-button size="large" @click="isEditModalVisible = false" class="btn-cancel">
              Batal
            </el-button>
            <el-button
              type="primary"
              size="large"
              :loading="savingProfile"
              @click="saveProfile"
              class="btn-save"
            >
              <el-icon class="me-1" v-if="!savingProfile"><Check /></el-icon> Simpan Perubahan
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import {
  Camera,
  Edit,
  CreditCard,
  Postcard,
  Message,
  Phone,
  Location,
  OfficeBuilding,
  Finished,
  Calendar,
  Document,
  UserFilled,
  Key,
  Lock,
  Money,
  Upload,
  Loading,
  PictureFilled,
  Delete,
  User,
  Check,
  InfoFilled
} from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'
import AppButton from '@/components/common/AppButton.vue'
import { useAuthStore } from '@/store/auth'

const authStore = useAuthStore()
const { detailedProfile: profile, loadingProfile: loading } = storeToRefs(authStore)

const isEditModalVisible = ref(false)
const savingProfile = ref(false)
const editFormRef = ref<FormInstance>()
const avatarFile = ref<File | null>(null)
const avatarPreviewUrl = ref<string>('')
const directAvatarInputRef = ref<HTMLInputElement | null>(null)
const uploadingAvatar = ref(false)
const directCoverInputRef = ref<HTMLInputElement | null>(null)
const uploadingCover = ref(false)

const editForm = reactive({
  nama: '',
  username: '',
  email: '',
  no_telp: '',
  password: ''
})

const editRules = reactive<FormRules>({
  username: [
    { required: true, message: 'Username wajib diisi', trigger: 'blur' },
    { pattern: /^\S*$/, message: 'Username tidak boleh mengandung spasi', trigger: 'change' }
  ],
  email: [
    { required: true, message: 'Email wajib diisi', trigger: 'blur' },
    { type: 'email', message: 'Format email tidak valid', trigger: 'change' }
  ]
})

const userAvatar = computed(() => {
  const av = profile.value?.user?.avatar || profile.value?.pegawai?.avatar
  if (!av) return ''
  if (av.startsWith('http')) return av
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
  const cleanBase = baseUrl.replace(/\/api\/?$/, '')
  return `${cleanBase}${pathStart(av)}`
})

const userCoverUrl = computed(() => {
  const cov = profile.value?.user?.cover || profile.value?.pegawai?.cover
  if (!cov) return ''
  if (cov.startsWith('http')) return cov
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
  const cleanBase = baseUrl.replace(/\/api\/?$/, '')
  return `${cleanBase}${pathStart(cov)}`
})

const userCoverStyle = computed(() => {
  if (userCoverUrl.value) {
    return {
      backgroundImage: `url("${userCoverUrl.value}")`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
      backgroundRepeat: 'no-repeat'
    }
  }
  return {}
})

const pathStart = (path: string) => path.startsWith('/') ? path : `/${path}`

const formatDateOnly = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatDateTime = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (val?: number | null) => {
  if (val === null || val === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(val)
}

const getProjectStatusTag = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'aktif':
    case 'active':
      return 'success'
    case 'selesai':
    case 'completed':
      return 'info'
    case 'tertunda':
    case 'on hold':
      return 'warning'
    default:
      return 'primary'
  }
}

onMounted(async () => {
  await authStore.fetchDetailedProfile()
})

const triggerDirectAvatarUpload = () => {
  if (uploadingAvatar.value) return
  directAvatarInputRef.value?.click()
}

const handleDirectAvatarUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']
  if (!validTypes.includes(file.type)) {
    showErrorToast('Format foto harus berupa JPG, PNG, atau WebP.')
    target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    showErrorToast('Ukuran foto maksimal 2MB.')
    target.value = ''
    return
  }

  uploadingAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('avatar', file)
    const res = await authStore.updateProfileData(formData)
    showSuccessToast(res?.message || 'Foto profil berhasil diperbarui.')
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal mengunggah foto profil baru.')
  } finally {
    uploadingAvatar.value = false
    target.value = ''
  }
}

const triggerDirectCoverUpload = () => {
  if (uploadingCover.value) return
  directCoverInputRef.value?.click()
}

const handleDirectCoverUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']
  if (!validTypes.includes(file.type)) {
    showErrorToast('Format cover harus berupa JPG, PNG, atau WebP.')
    target.value = ''
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    showErrorToast('Ukuran foto cover maksimal 5MB.')
    target.value = ''
    return
  }

  uploadingCover.value = true
  try {
    const formData = new FormData()
    formData.append('cover', file)
    const res = await authStore.updateProfileData(formData)
    showSuccessToast(res?.message || 'Foto cover berhasil diperbarui.')
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal mengunggah foto cover baru.')
  } finally {
    uploadingCover.value = false
    target.value = ''
  }
}

const removeCover = async () => {
  uploadingCover.value = true
  try {
    const formData = new FormData()
    formData.append('remove_cover', '1')
    const res = await authStore.updateProfileData(formData)
    showSuccessToast(res?.message || 'Foto cover berhasil dihapus.')
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal menghapus foto cover.')
  } finally {
    uploadingCover.value = false
  }
}

const openEditModal = () => {
  if (!profile.value) return
  editForm.nama = profile.value.pegawai?.nama || profile.value.user.username || ''
  editForm.username = profile.value.user.username || ''
  editForm.email = profile.value.user.email || ''
  editForm.no_telp = profile.value.pegawai?.no_telp || ''
  editForm.password = ''
  avatarFile.value = null
  avatarPreviewUrl.value = ''
  isEditModalVisible.value = true
}

const handleAvatarChange = (file: any) => {
  if (file.raw) {
    avatarFile.value = file.raw
    avatarPreviewUrl.value = URL.createObjectURL(file.raw)
  }
}

const cancelAvatarPreview = () => {
  avatarFile.value = null
  avatarPreviewUrl.value = ''
}

const saveProfile = async () => {
  if (!editFormRef.value || savingProfile.value) return
  const valid = await editFormRef.value.validate().catch(() => false)
  if (!valid) return

  savingProfile.value = true
  try {
    const formData = new FormData()
    formData.append('username', editForm.username)
    formData.append('email', editForm.email)
    if (editForm.nama) {
      formData.append('nama', editForm.nama)
    }
    if (editForm.no_telp) {
      formData.append('no_telp', editForm.no_telp)
    }
    if (editForm.password) {
      formData.append('password', editForm.password)
    }
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }

    const res = await authStore.updateProfileData(formData)
    showSuccessToast(res?.message || 'Profil berhasil diperbarui.')
    isEditModalVisible.value = false
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal memperbarui profil.')
  } finally {
    savingProfile.value = false
  }
}
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Hero Card */
.profile-hero-card {
  background-color: #ffffff;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.hero-cover {
  position: relative;
  height: 160px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  transition: background-image 0.3s ease;
}
.hero-cover.has-custom-cover {
  background-color: #0f172a;
}
.cover-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.15) 0%, rgba(0, 0, 0, 0.45) 100%);
  pointer-events: none;
}
.cover-actions {
  position: absolute;
  top: 14px;
  right: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
  z-index: 3;
}
.cover-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  font-size: 13px;
  font-weight: 500;
  color: #ffffff;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}
.cover-action-btn:hover:not(:disabled) {
  background: rgba(15, 23, 42, 0.85);
  border-color: rgba(255, 255, 255, 0.45);
  transform: translateY(-1px);
}
.cover-action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.cover-action-btn.btn-remove-cover {
  padding: 6px 10px;
  background: rgba(239, 68, 68, 0.7);
  border-color: rgba(239, 68, 68, 0.4);
}
.cover-action-btn.btn-remove-cover:hover:not(:disabled) {
  background: rgba(220, 38, 38, 0.9);
  border-color: rgba(255, 255, 255, 0.45);
}
.hero-body {
  position: relative;
  padding: 0 28px 24px 28px;
  display: flex;
  gap: 24px;
}
.hero-avatar-wrapper {
  position: relative;
  width: 96px;
  height: 96px;
  margin-top: -48px;
  flex-shrink: 0;
  align-self: flex-start;
  display: inline-block;
}
.hero-avatar {
  width: 96px !important;
  height: 96px !important;
  border: 4px solid #ffffff;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  background-color: #ef4444;
  color: #ffffff;
  font-weight: 700;
  font-size: 32px;
}
.avatar-edit-badge {
  position: absolute;
  bottom: 0px;
  right: 0px;
  width: 30px;
  height: 30px;
  background-color: #ef4444;
  color: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s ease, background-color 0.2s ease;
  z-index: 2;
}
.avatar-edit-badge .el-icon {
  font-size: 15px;
}
.avatar-edit-badge:hover {
  transform: scale(1.1);
  background-color: #dc2626;
}
.avatar-edit-badge.is-uploading {
  cursor: not-allowed;
  background-color: #64748b;
  transform: none !important;
}

.hero-meta {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 12px;
}
.hero-title-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 12px;
}
.hero-title-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.hero-name {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}
.hero-tags {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.role-badge {
  font-weight: 600;
}

.hero-pills {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 4px;
}
.info-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #475569;
}
.pill-icon {
  color: #64748b;
  font-size: 15px;
}

/* Quick Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background-color: #ffffff;
  border-radius: 12px;
  padding: 18px 20px;
  border: 1px solid #e5e7eb;
}
.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 10px;
  font-size: 24px;
}
.icon-blue { background-color: #eff6ff; color: #3b82f6; }
.icon-amber { background-color: #fffbeb; color: #f59e0b; }
.icon-green { background-color: #f0fdf4; color: #10b981; }
.icon-purple { background-color: #faf5ff; color: #a855f7; }

.stat-info {
  display: flex;
  flex-direction: column;
}
.stat-label {
  font-size: 12.5px;
  color: #64748b;
}
.stat-value {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}
.stat-sub {
  font-size: 11.5px;
  color: #94a3b8;
  margin-top: 2px;
}

/* Detail Cards */
.detail-card {
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.header-left-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 15px;
  color: #0f172a;
}
.section-icon {
  font-size: 18px;
}

/* Detail List (2-column key-value) */
.detail-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f1f5f9;
}
.detail-item:last-child {
  border-bottom: none;
}
.detail-label {
  font-size: 13px;
  color: #64748b;
}
.detail-val {
  font-size: 13.5px;
  color: #1e293b;
  text-align: right;
}
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.font-mono { font-family: monospace; }

/* Contract Section */
.contract-sub-section {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 14px 18px;
}
.sub-section-title {
  display: flex;
  align-items: center;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin: 0;
}
.komponen-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.komponen-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12.5px;
}
.chip-name {
  color: #334155;
  font-weight: 500;
}
.chip-val {
  font-weight: 700;
}

/* Projects Table */
.project-title-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.project-name {
  font-weight: 600;
  color: #0f172a;
  font-size: 13.5px;
}
.project-desc {
  font-size: 11.5px;
  color: #64748b;
}
.client-cell {
  display: flex;
  flex-direction: column;
}
.client-name {
  font-weight: 500;
  color: #1e293b;
  font-size: 13px;
}
.area-name {
  font-size: 11.5px;
  color: #64748b;
}
.period-text {
  font-size: 12.5px;
  color: #334155;
}
.tasks-badge-text {
  font-size: 12.5px;
  font-weight: 500;
}

/* Redesigned Modal Styling */
:deep(.redesigned-profile-modal.el-dialog) {
  border-radius: 16px;
  overflow: hidden;
  padding: 0;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}

:deep(.redesigned-profile-modal .el-dialog__header) {
  margin: 0;
  padding: 20px 24px 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  background-color: #ffffff;
}

:deep(.redesigned-profile-modal .el-dialog__body) {
  padding: 20px 24px;
  max-height: 75vh;
  overflow-y: auto;
}

:deep(.redesigned-profile-modal .el-dialog__footer) {
  margin: 0;
  padding: 16px 24px;
  border-top: 1px solid #f1f5f9;
  background-color: #f8fafc;
}

.custom-modal-header {
  display: flex;
  align-items: center;
  gap: 14px;
}
.modal-header-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}
.modal-header-text {
  display: flex;
  flex-direction: column;
}
.modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.3;
}
.modal-subtitle {
  margin: 2px 0 0 0;
  font-size: 13px;
  color: #64748b;
}

/* 1. Avatar Section */
.edit-avatar-section {
  display: flex;
  align-items: center;
  gap: 20px;
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 20px;
}
.avatar-preview-box {
  position: relative;
  flex-shrink: 0;
}
.modal-avatar {
  border: 3px solid #ffffff;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  background-color: #ef4444;
  color: #ffffff;
  font-weight: 700;
  font-size: 28px;
}
.avatar-status-pill {
  position: absolute;
  bottom: 0px;
  right: 0px;
  width: 22px;
  height: 22px;
  background-color: #10b981;
  color: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  border: 2px solid #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}
.avatar-controls {
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
}
.avatar-text-info {
  display: flex;
  flex-direction: column;
}
.avatar-title {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
}
.avatar-hint {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}
.avatar-btn-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}
.upload-btn {
  font-weight: 500;
  border-radius: 8px;
}

/* Section Dividers */
.modal-section-divider {
  margin: 16px 0 12px 0;
  padding-bottom: 8px;
  border-bottom: 1px solid #f1f5f9;
}
.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
}
.section-icon {
  color: #3b82f6;
  font-size: 16px;
}

/* Security Box */
.security-card {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  margin-top: 4px;
}
.security-tip {
  display: flex;
  align-items: center;
  font-size: 12px;
  color: #64748b;
  margin-top: 6px;
}

/* Form items & input styling */
:deep(.el-form-item__label) {
  font-weight: 600 !important;
  color: #334155 !important;
  font-size: 13px !important;
  margin-bottom: 6px !important;
}
.input-icon {
  color: #94a3b8;
  font-size: 16px;
}

/* Footer Styling */
.custom-modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}
.footer-note {
  font-size: 12.5px;
  display: flex;
  align-items: center;
}
.footer-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}
.btn-cancel {
  border-radius: 8px;
  font-weight: 500;
}
.btn-save {
  border-radius: 8px;
  font-weight: 600;
}

@media (max-width: 768px) {
  .hero-cover {
    height: 130px;
  }
  .cover-actions {
    top: 10px;
    right: 12px;
  }
  .cover-action-btn {
    padding: 5px 10px;
    font-size: 12px;
  }
  .hero-body {
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0 16px 20px 16px;
  }
  .hero-avatar-wrapper {
    align-self: center;
  }
  .hero-title-row {
    flex-direction: column;
    align-items: center;
  }
  .hero-pills {
    justify-content: center;
  }
  .custom-modal-footer {
    flex-direction: column-reverse;
    gap: 12px;
    align-items: stretch;
  }
  .footer-actions {
    width: 100%;
    justify-content: flex-end;
  }
  .footer-note {
    justify-content: center;
  }
  .edit-avatar-section {
    flex-direction: column;
    text-align: center;
  }
  .avatar-btn-row {
    justify-content: center;
  }
}
</style>
