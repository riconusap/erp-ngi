<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Daftar Pengguna</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola daftar pengguna (users) dan peran mereka</p>
      </div>
      <div class="header-right">
        <AppButton v-if="can('Pengguna', 'create')" variant="primary" @click="openCreateDrawer">Tambah Pengguna</AppButton>
      </div>
    </div>

    <div class="filter-section">
      <el-input aria-label="Kotak pencarian" v-model="searchQuery" placeholder="Cari nama atau email" :prefix-icon="Search" class="search-input" @input="fetchData" />
      <div class="filter-actions">
        <el-select v-model="activeFilter" placeholder="Status" clearable class="custom-select" @change="fetchData">
          <el-option label="Aktif" value="Aktif" />
          <el-option label="Nonaktif" value="Nonaktif" />
        </el-select>
      </div>
    </div>

    <DataTable 
      :data="data" 
      :loading="isLoading" 
      :empty-image="EmptyStateImage" 
      :total="meta.total" 
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      @update:current-page="fetchData"
      @update:page-size="fetchData"
    >
      <el-table-column label="Username" min-width="250">
        <template #default="scope">
          <div style="display: flex; align-items: center; gap: 12px;">
            <el-avatar v-if="scope.row.avatar" :size="36" :src="getAvatarUrl(scope.row.avatar)"></el-avatar>
            <el-avatar v-else :size="36" style="background-color: var(--el-color-primary); color: white; font-weight: bold;">
              {{ getInitials(scope.row.username) }}
            </el-avatar>
            <div style="display: flex; flex-direction: column;">
              <span style="font-weight: 600; color: #111827;">{{ scope.row.username }}</span>
              <span style="font-size: 12px; color: #6B7280;">{{ scope.row.email }}</span>
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Pegawai Terkait" min-width="200">
        <template #default="scope">
          <div style="display: flex; flex-direction: column;">
             <span v-if="scope.row.pegawai" style="font-weight: 500;">{{ scope.row.pegawai.nama }}</span>
             <span v-if="scope.row.pegawai" style="font-size: 12px; color: #6B7280;">NIP: {{ scope.row.pegawai.nip }}</span>
             <span v-else style="color: #9CA3AF; font-style: italic;">Tidak terkait pegawai</span>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Peran (Role)" min-width="150">
        <template #default="scope">
          <el-tag size="small" type="info" effect="plain">{{ getRoleName(scope.row.role_id) }}</el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="status" label="Status" min-width="120">
        <template #default="scope">
          <el-tag :type="scope.row.status === 'Aktif' ? 'success' : 'danger'" effect="light" class="status-badge">
            {{ scope.row.status }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Action" width="80" align="center">
        <template #default="scope">
          <el-dropdown trigger="click" placement="bottom-end">
            <el-button text class="action-more-btn">
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="openDetailDrawer(scope.row)">
                  <el-icon><View /></el-icon> Detail
                </el-dropdown-item>
                <el-dropdown-item v-if="can('Pengguna', 'update')" @click="openEditDrawer(scope.row)">
                  <el-icon><Edit /></el-icon> Edit
                </el-dropdown-item>
                <el-dropdown-item v-if="can('Pengguna', 'delete')" @click="openDeleteModal(scope.row)">
                  <el-icon><Delete /></el-icon> Hapus
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </DataTable>

    <!-- Drawer for Form / Detail -->
    <CustomDrawer v-model="isDrawerOpen">
      <div class="admin-drawer-content">
        <div class="drawer-breadcrumb">
          <span class="back-link" @click="isDrawerOpen = false">List Pengguna</span> / 
          <span class="active">
            {{ drawerMode === 'detail' ? 'Detail Pengguna' : (drawerMode === 'edit' ? 'Edit Pengguna' : 'Tambah Pengguna') }}
          </span>
        </div>

        <h2>
          {{ drawerMode === 'detail' ? 'Detail Pengguna' : (drawerMode === 'edit' ? 'Edit Pengguna' : 'Tambah Pengguna') }}
        </h2>

        <!-- Mode Detail -->
        <div v-if="drawerMode === 'detail'" class="detail-container mt-4">
          <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
            <el-avatar v-if="form.avatarUrl" :size="64" :src="form.avatarUrl"></el-avatar>
            <el-avatar v-else :size="64" style="background-color: var(--el-color-primary); color: white; font-weight: bold; font-size: 24px;">
              {{ getInitials(form.username) }}
            </el-avatar>
            <div>
              <h3 style="margin: 0; font-size: 18px;">{{ form.username }}</h3>
              <p style="margin: 4px 0 0 0; color: #6B7280;">{{ form.email }}</p>
            </div>
          </div>
          
          <el-descriptions border :column="1" direction="vertical">
            <el-descriptions-item label="Username">{{ form.username }}</el-descriptions-item>
            <el-descriptions-item label="Alamat Email">{{ form.email }}</el-descriptions-item>
            <el-descriptions-item label="Pegawai Terkait">
              {{ form.pegawai ? form.pegawai.nama : 'Tidak terkait pegawai' }}
            </el-descriptions-item>
            <el-descriptions-item label="Peran (Role)">
              <el-tag size="small" type="info" effect="plain">{{ getRoleName(form.role_id) }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="Status">
              <el-tag :type="form.isActive ? 'success' : 'danger'" effect="light" class="status-badge">
                {{ form.isActive ? 'Aktif' : 'Nonaktif' }}
              </el-tag>
            </el-descriptions-item>
          </el-descriptions>

          <div class="drawer-footer mt-5">
            <el-button @click="isDrawerOpen = false">Tutup</el-button>
            <el-button type="danger" v-if="can('pengguna', 'edit')" color="#ef4444" @click="openEditDrawer(form)">Edit Pengguna</el-button>
          </div>
        </div>

        <!-- Mode Create / Edit -->
        <el-form v-else ref="formRef" :model="form" :rules="rules" label-position="top" class="mt-4">
          <el-form-item label="Foto Profil (Avatar)">
            <el-upload
              class="avatar-uploader"
              :show-file-list="false"
              :auto-upload="false"
              :on-change="handleAvatarChange"
              accept="image/jpeg,image/png,image/webp"
            >
              <img v-if="form.avatarUrl" :src="form.avatarUrl" class="avatar-preview" />
              <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
            </el-upload>
          </el-form-item>

          <el-form-item label="Username" prop="username">
            <el-input v-model="form.username" placeholder="Masukan username pengguna" size="large" />
          </el-form-item>
          
          <el-form-item label="Email" prop="email">
            <el-input v-model="form.email" placeholder="contoh: user@domain.com" type="email" size="large" />
          </el-form-item>

          <el-form-item label="Peran (Role)" prop="role_id">
            <el-select v-model="form.role_id" placeholder="Pilih peran pengguna" size="large" style="width: 100%;">
              <el-option v-for="role in roleStore.data" :key="role.id" :label="role.name" :value="role.id" />
            </el-select>
          </el-form-item>

          <el-form-item label="Status">
            <div style="display: flex; align-items: center; gap: 12px; height: 40px;">
              <span style="font-size: 14px;">Aktif</span>
              <el-switch v-model="form.isActive" />
            </div>
          </el-form-item>

          <el-divider content-position="left" class="mt-4 mb-4">Keamanan</el-divider>
          
          <el-form-item label="Password" prop="password">
            <el-input v-model="form.password" type="password" show-password placeholder="Masukan kata sandi" size="large" />
            <p v-if="drawerMode === 'edit'" class="help-text">Kosongkan jika tidak ingin mengubah password.</p>
          </el-form-item>

          <div class="drawer-footer mt-5">
            <el-button text @click="isDrawerOpen = false">Batal</el-button>
            <el-button type="danger" color="#ef4444" @click="saveForm" :loading="saving">Simpan</el-button>
          </div>
        </el-form>
      </div>
    </CustomDrawer>

    <!-- Modal Delete -->
    <el-dialog v-model="isDeleteModalVisible" width="480px" align-center class="custom-delete-modal" title="Konfirmasi Hapus">
      <div class="delete-content" style="padding-top: 8px;">
        Apakah Anda yakin ingin menghapus pengguna <strong>{{ selectedUser?.username }}</strong>?
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button text @click="isDeleteModalVisible = false">Batal</el-button>
          <el-button type="danger" color="#ef4444" @click="confirmDelete" :loading="saving">Hapus</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { Search, MoreFilled, Edit, Delete, View, Plus } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'

import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import { useUserStore } from '@/store/user'
import { useRoleStore } from '@/store/role'
import { usePermissions } from '@/composables/usePermissions'
import { ImageService } from '@/core/services/ImageService'

const router = useRouter()
const { can } = usePermissions()
const userStore = useUserStore()
const roleStore = useRoleStore()
const { data, meta } = storeToRefs(userStore)

const isLoading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const activeFilter = ref('')

// === Drawer Logic ===
const isDrawerOpen = ref(false)
const drawerMode = ref<'create' | 'edit' | 'detail'>('create')
const saving = ref(false)

const formRef = ref<FormInstance>()
const form = reactive({
  id: '',
  username: '',
  email: '',
  role_id: '',
  password: '',
  isActive: true,
  avatarFile: null as File | null,
  avatarUrl: '',
  pegawai: null as any
})

const validatePassword = (rule: any, value: any, callback: any) => {
  if (value) {
    if (value.length < 8) {
      callback(new Error('Password minimal 8 karakter'))
    } else if (!/(?=.*[a-z])/.test(value)) {
      callback(new Error('Password harus mengandung huruf kecil'))
    } else if (!/(?=.*[A-Z])/.test(value)) {
      callback(new Error('Password harus mengandung huruf besar'))
    } else if (!/(?=.*[0-9])/.test(value)) {
      callback(new Error('Password harus mengandung angka'))
    } else if (!/(?=.*[!@#$%^&*_=+-])/.test(value)) {
      callback(new Error('Password harus mengandung simbol (!@#$%^&*_=+-)' ))
    } else {
      callback()
    }
  } else {
    callback()
  }
}

const rules = computed<FormRules>(() => ({
  username: [
    { required: true, message: 'Username wajib diisi', trigger: 'change' },
    { pattern: /^\\S*$/, message: 'Username tidak boleh mengandung spasi', trigger: 'change' }
  ],
  email: [
    { required: true, message: 'Email wajib diisi', trigger: 'change' },
    { type: 'email', message: 'Format email tidak valid', trigger: 'change' }
  ],
  role_id: [{ required: true, message: 'Peran wajib dipilih', trigger: 'change' }],
  password: drawerMode.value === 'edit' 
    ? [{ validator: validatePassword, trigger: 'change' }]
    : [
        { required: true, message: 'Password wajib diisi', trigger: 'change' },
        { validator: validatePassword, trigger: 'change' }
      ]
}))

const resetForm = () => {
  form.id = ''
  form.username = ''
  form.email = ''
  form.role_id = ''
  form.password = ''
  form.isActive = true
  form.avatarFile = null
  form.avatarUrl = ''
  form.pegawai = null
  if (formRef.value) formRef.value.clearValidate()
}

const openCreateDrawer = () => {
  drawerMode.value = 'create'
  resetForm()
  isDrawerOpen.value = true
}

const getAvatarUrl = (path: string | null | undefined) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const baseUrl = import.meta.env.VITE_APP_API_URL?.replace('/api', '') || 'http://localhost:8000'
  return `${baseUrl}${path}`
}

const openEditDrawer = (row: any) => {
  drawerMode.value = 'edit'
  resetForm()
  form.id = row.id
  form.username = row.username
  form.email = row.email
  form.role_id = row.role_id
  form.isActive = (row.status === 'Aktif' || row.isActive)
  form.avatarUrl = getAvatarUrl(row.avatar)
  form.pegawai = row.pegawai
  isDrawerOpen.value = true
}

const openDetailDrawer = (row: any) => {
  drawerMode.value = 'detail'
  form.id = row.id
  form.username = row.username
  form.email = row.email
  form.role_id = row.role_id
  form.isActive = row.status === 'Aktif'
  form.avatarUrl = getAvatarUrl(row.avatar)
  form.pegawai = row.pegawai
  isDrawerOpen.value = true
}

const handleAvatarChange = async (file: any) => {
  const rawFile = file.raw
  if (rawFile.type !== 'image/jpeg' && rawFile.type !== 'image/png' && rawFile.type !== 'image/webp') {
    showErrorToast('Avatar harus berupa format JPG, PNG, atau WebP!')
    return false
  }
  if (rawFile.size / 1024 / 1024 > 5) { // Allow up to 5MB initially, it will be compressed
    showErrorToast('Ukuran avatar awal tidak boleh lebih dari 5MB!')
    return false
  }
  
  try {
    const webpFile = await ImageService.compressToWebP(rawFile)
    form.avatarFile = webpFile
    form.avatarUrl = URL.createObjectURL(webpFile)
  } catch (error) {
    console.error(error)
    showErrorToast('Gagal memproses gambar avatar')
  }
}

const saveForm = async () => {
  if (!formRef.value || saving.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    const payload = new FormData()
    payload.append('username', form.username)
    payload.append('email', form.email)
    payload.append('role_id', form.role_id)
    payload.append('status', form.isActive ? 'Aktif' : 'Nonaktif')
    
    if (form.password) {
      payload.append('password', form.password)
    }
    
    if (form.avatarFile) {
      payload.append('avatar', form.avatarFile)
    }

    if (drawerMode.value === 'edit') {
      await userStore.update(form.id, payload)
      showSuccessToast('Data pengguna berhasil diperbarui')
    } else {
      await userStore.create(payload)
      showSuccessToast('Pengguna berhasil ditambahkan')
    }
    isDrawerOpen.value = false
    fetchData()
  } catch (error) {
    showErrorToast('Gagal menyimpan pengguna')
  } finally {
    saving.value = false
  }
}
// ====================

const isDeleteModalVisible = ref(false)
const selectedUser = ref<any>(null)

const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}

const getRoleName = (roleId: string) => {
  const role = roleStore.data.find(r => r.id === roleId)
  return role ? role.name : 'Unknown'
}

const fetchData = async () => {
  isLoading.value = true
  if (roleStore.data.length === 0) {
    await roleStore.getData({})
  }
  await userStore.getData({ search: searchQuery.value, status: activeFilter.value, page: currentPage.value, limit: pageSize.value })
  isLoading.value = false
}

onMounted(() => {
  fetchData()
})

const openDeleteModal = (row: any) => {
  selectedUser.value = row
  isDeleteModalVisible.value = true
}

const confirmDelete = async () => {
  saving.value = true
  try {
    await userStore.remove(selectedUser.value.id)
    showSuccessToast('Pengguna berhasil dihapus')
    isDeleteModalVisible.value = false
    fetchData()
  } catch (error) {
    showErrorToast('Gagal menghapus pengguna')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.title-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
}
.subtitle {
  color: #6B7280;
  margin: 4px 0 0 0;
  font-size: 0.9rem;
}
.filter-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}
.search-input {
  width: 300px;
}
.status-badge {
  text-transform: capitalize;
}
.help-text {
  margin: 4px 0 0 0;
  font-size: 12px;
  color: #6B7280;
}
.drawer-breadcrumb {
  margin-bottom: 24px;
  font-size: 14px;
  color: #6B7280;
}
.drawer-breadcrumb .back-link {
  color: #111827;
  font-weight: 500;
  cursor: pointer;
}
.drawer-breadcrumb .back-link:hover {
  text-decoration: underline;
}
.drawer-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 24px;
  border-top: 1px solid #E5E7EB;
}

/* Avatar Uploader */
.avatar-uploader :deep(.el-upload) {
  border: 1px dashed var(--el-border-color);
  border-radius: 50%;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: var(--el-transition-duration-fast);
  width: 100px;
  height: 100px;
  background-color: #f9fafb;
}

.avatar-uploader :deep(.el-upload:hover) {
  border-color: var(--el-color-primary);
}

.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.avatar-preview {
  width: 100px;
  height: 100px;
  display: block;
  object-fit: cover;
}
</style>
