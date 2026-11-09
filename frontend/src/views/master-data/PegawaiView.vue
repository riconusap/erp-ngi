<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Data Pegawai</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola data pegawai</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="openCreateDrawer" v-if="can('Pegawai', 'create')">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Tambah Pegawai
        </AppButton>
      </div>
    </div>

    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Cari nama, NIK, NIP, email..."
        class="search-input"
        clearable
        @clear="fetchData"
        @keyup.enter="fetchData"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
    </div>

    <DataTable
      :data="data"
      :loading="isLoading"
      :total="meta.total"
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      empty-title="Belum ada data pegawai"
      empty-subtitle="Silakan tambah pegawai baru"
      :empty-image="EmptyStateImage"
    >
      <el-table-column label="Nama Pegawai" min-width="250">
        <template #default="{ row }">
          <div class="pegawai-info">
            <el-avatar :size="40" :src="getAvatarUrl(row.avatar)">
              {{ row.nama?.charAt(0).toUpperCase() }}
            </el-avatar>
            <div class="pegawai-details">
              <span class="pegawai-name">{{ row.nama }}</span>
              <span class="pegawai-email">{{ row.email }}</span>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="nik" label="NIK" min-width="150" />
      <el-table-column prop="nip" label="NIP" min-width="150" />
      <el-table-column prop="no_telp" label="No. Telp" min-width="150">
        <template #default="{ row }">
          {{ row.no_telp || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Peran (Sistem)" min-width="140">
        <template #default="{ row }">
          <el-tag size="small" type="info">{{ row.user?.role?.name || '-' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Kontrak & Jabatan" min-width="210">
        <template #default="{ row }">
          <div v-if="row.active_kontrak" style="display: flex; flex-direction: column; gap: 2px;">
            <div style="font-weight: 600; color: #1e293b; font-size: 13px;">
              {{ row.active_kontrak.jabatan?.nama_jabatan || '-' }}
            </div>
            <div style="font-size: 12px; color: #64748b;">
              <el-tag size="small" type="success" effect="light" class="mr-1">Aktif</el-tag>
              {{ row.active_kontrak.no_kontrak }}
            </div>
          </div>
          <div v-else-if="row.kontraks_count > 0">
            <el-tag size="small" type="info">Tidak Aktif ({{ row.kontraks_count }} Riwayat)</el-tag>
          </div>
          <div v-else>
            <el-tag size="small" type="warning" effect="plain">Belum Ada Kontrak</el-tag>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Aksi" width="160" fixed="right" align="center">
        <template #default="{ row }">
          <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
            <!-- <el-button 
              type="primary" 
              link 
              size="small" 
              @click="handleAction('kontrak', row)"
            >
              <el-icon class="mr-1"><DocumentChecked /></el-icon> Lihat Kontrak
            </el-button> -->
            <el-dropdown trigger="click" @command="(cmd: string) => handleAction(cmd, row)">
              <el-button type="info" text circle size="small">
                <el-icon><MoreFilled /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="kontrak">
                    <el-icon><DocumentChecked /></el-icon> Lihat Kontrak
                  </el-dropdown-item>
                  <el-dropdown-item command="detail">
                    <el-icon><View /></el-icon> Detail Data
                  </el-dropdown-item>
                  <el-dropdown-item command="edit" v-if="can('Pegawai', 'update')">
                    <el-icon><Edit /></el-icon> Edit Data
                  </el-dropdown-item>
                  <el-dropdown-item command="delete" class="text-danger" v-if="can('Pegawai', 'delete')">
                    <el-icon><Delete /></el-icon> Hapus Data
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </template>
      </el-table-column>
    </DataTable>

    <CustomDrawer
      v-model="isDrawerOpen"
      :title="drawerTitle"
      default-size="500px"
      @closed="resetForm"
    >
      <div class="drawer-content">
        <el-form 
          ref="formRef" 
          :model="form" 
          :rules="rules" 
          label-position="top"
          class="pegawai-form"
        >
          <!-- FOTO PEGAWAI -->
          <div class="avatar-upload-section" v-if="drawerMode !== 'detail'">
            <el-upload
              class="avatar-uploader"
              action="#"
              :show-file-list="false"
              :auto-upload="false"
              :on-change="handleAvatarChange"
              accept="image/jpeg,image/png,image/webp,image/jpg"
            >
              <img v-if="form.avatarUrl" :src="form.avatarUrl" class="avatar" />
              <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
            </el-upload>
            <div class="upload-help">
              <div class="upload-title">Pass Foto Pegawai</div>
              <div class="upload-desc">Format JPG, PNG, WEBP. Maks. 2MB. (Akan disinkronisasi ke Akun Pengguna)</div>
              <el-button v-if="form.avatarUrl" type="danger" text size="small" @click="removeAvatar">Hapus Foto</el-button>
            </div>
          </div>
          <div class="detail-avatar-section" v-else>
             <el-avatar :size="100" :src="form.avatarUrl">
               <span style="font-size: 32px;">{{ form.nama?.charAt(0).toUpperCase() }}</span>
             </el-avatar>
          </div>

          <el-form-item label="Nama Lengkap" prop="nama">
            <el-input v-model="form.nama" placeholder="Masukan nama lengkap pegawai" size="large" :readonly="drawerMode === 'detail'" />
          </el-form-item>
          
          <div class="form-row">
            <el-form-item label="NIK" prop="nik" class="flex-1">
              <el-input v-model="form.nik" placeholder="Masukan NIK (16 digit)" size="large" :readonly="drawerMode === 'detail'" />
            </el-form-item>
            <el-form-item label="NIP" class="flex-1" v-if="drawerMode === 'edit' || drawerMode === 'detail'">
              <el-input v-model="form.nip" disabled size="large" />
              <div style="font-size: 12px; color: #6b7280; margin-top: 4px;" v-if="drawerMode === 'edit'">NIP dibuat otomatis oleh sistem</div>
            </el-form-item>
          </div>

          <el-form-item label="No. Telepon" prop="no_telp">
            <el-input v-model="form.no_telp" placeholder="Masukan nomor telepon" size="large" :readonly="drawerMode === 'detail'" />
          </el-form-item>

          <el-form-item label="Email Akun" prop="email">
            <el-input v-model="form.email" type="email" placeholder="Masukan email aktif" size="large" :readonly="drawerMode === 'detail'" />
          </el-form-item>

          <div class="form-row">
            <el-form-item label="Peran (Sistem)" prop="role_id" class="flex-1">
              <el-select v-model="form.role_id" placeholder="Pilih Peran Pengguna" size="large" filterable :disabled="drawerMode === 'detail'">
                <el-option
                  v-for="role in roleOptions"
                  :key="role.value"
                  :label="role.label"
                  :value="role.value"
                />
              </el-select>
            </el-form-item>
          </div>

          <el-form-item label="Password Akun" prop="password" v-if="drawerMode !== 'detail'">
            <el-input v-model="form.password" type="password" show-password placeholder="Masukan password" size="large" />
            <div v-if="drawerMode === 'edit'" style="font-size: 12px; color: #6b7280; margin-top: 4px;">Kosongkan jika tidak ingin mengubah password akun.</div>
          </el-form-item>
        </el-form>

        <div class="drawer-actions">
          <el-button @click="isDrawerOpen = false" v-if="drawerMode !== 'detail'">Batal</el-button>
          <el-button @click="isDrawerOpen = false" v-if="drawerMode === 'detail'">Tutup</el-button>
          <AppButton 
            v-if="drawerMode !== 'detail'"
            variant="primary" 
            :loading="saving" 
            @click="handleSubmit"
          >
            Simpan Data
          </AppButton>
        </div>
      </div>
    </CustomDrawer>

    <!-- DRAWER KELOLA KONTRAK PEGAWAI -->
    <PegawaiKontrakDrawer
      v-model="isKontrakDrawerOpen"
      :pegawai="selectedPegawaiForKontrak"
      @updated="fetchData"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Search, MoreFilled, Edit, Delete, Plus, View, DocumentChecked } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessageBox } from 'element-plus'

import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import PegawaiKontrakDrawer from './components/PegawaiKontrakDrawer.vue'
import { usePegawaiStore } from '@/store/pegawai'
import { useRoleStore } from '@/store/role'
import { usePermissions } from '@/composables/usePermissions'
import { ImageService } from '@/core/services/ImageService'

const { can } = usePermissions()
const pegawaiStore = usePegawaiStore()
const roleStore = useRoleStore()
const { data, meta } = storeToRefs(pegawaiStore)

const isLoading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const roleOptions = ref<{label: string, value: string}[]>([])

const isDrawerOpen = ref(false)
const drawerMode = ref<'create' | 'edit' | 'detail'>('create')
const saving = ref(false)

const isKontrakDrawerOpen = ref(false)
const selectedPegawaiForKontrak = ref<any>(null)

const formRef = ref<FormInstance>()
const form = reactive({
  id: '',
  nama: '',
  nik: '',
  nip: '',
  no_telp: '',
  email: '',
  role_id: '',
  password: '',
  avatarFile: null as File | null,
  avatarUrl: ''
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
  nama: [{ required: true, message: 'Nama lengkap wajib diisi', trigger: 'blur' }],
  nik: [{ required: true, message: 'NIK wajib diisi', trigger: 'blur' }],
  email: [
    { required: true, message: 'Email wajib diisi', trigger: 'blur' },
    { type: 'email', message: 'Format email tidak valid', trigger: 'blur' }
  ],
  role_id: [{ required: true, message: 'Peran wajib dipilih', trigger: 'change' }],
  password: drawerMode.value === 'edit' 
    ? [{ validator: validatePassword, trigger: 'change' }]
    : [
        { required: true, message: 'Password wajib diisi', trigger: 'blur' },
        { validator: validatePassword, trigger: 'change' }
      ]
}))

const drawerTitle = computed(() => {
  if (drawerMode.value === 'create') return 'Tambah Data Pegawai'
  if (drawerMode.value === 'detail') return 'Detail Data Pegawai'
  return 'Edit Data Pegawai'
})



const getAvatarUrl = (path: string | null | undefined) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  if (path.startsWith('blob:')) return path
  const baseUrl = import.meta.env.VITE_APP_API_URL?.replace('/api', '') || 'http://localhost:8000'
  return `${baseUrl}${path}`
}

const handleAvatarChange = async (file: any) => {
  try {
    const compressedFile = await ImageService.compressToWebP(file.raw)
    form.avatarFile = compressedFile
    form.avatarUrl = URL.createObjectURL(compressedFile)
  } catch (error) {
    showErrorToast('Gagal memproses gambar')
  }
}

const removeAvatar = () => {
  form.avatarFile = null
  form.avatarUrl = '' // means removed
}

const resetForm = () => {
  form.id = ''
  form.nama = ''
  form.nik = ''
  form.nip = ''
  form.no_telp = ''
  form.email = ''
  form.role_id = ''
  form.password = ''
  form.avatarFile = null
  form.avatarUrl = ''
  if (formRef.value) formRef.value.clearValidate()
}

const openCreateDrawer = () => {
  drawerMode.value = 'create'
  resetForm()
  isDrawerOpen.value = true
}

const openEditDrawer = (row: any) => {
  drawerMode.value = 'edit'
  resetForm()
  form.id = row.id
  form.nama = row.nama
  form.nik = row.nik
  form.nip = row.nip
  form.no_telp = row.no_telp || ''
  form.email = row.email
  form.role_id = row.user?.role_id || ''
  form.avatarUrl = getAvatarUrl(row.avatar)
  isDrawerOpen.value = true
}

const openDetailDrawer = (row: any) => {
  drawerMode.value = 'detail'
  resetForm()
  form.id = row.id
  form.nama = row.nama
  form.nik = row.nik
  form.nip = row.nip
  form.no_telp = row.no_telp || ''
  form.email = row.email
  form.role_id = row.user?.role_id || ''
  form.avatarUrl = getAvatarUrl(row.avatar)
  isDrawerOpen.value = true
}

const handleAction = (cmd: string, row: any) => {
  if (cmd === 'kontrak') {
    selectedPegawaiForKontrak.value = row
    isKontrakDrawerOpen.value = true
  }
  if (cmd === 'edit') openEditDrawer(row)
  if (cmd === 'detail') openDetailDrawer(row)
  if (cmd === 'delete') confirmDelete(row)
}

const confirmDelete = (row: any) => {
  ElMessageBox.confirm(
    `Apakah Anda yakin ingin menghapus data pegawai "${row.nama}"? Akun pengguna terkait juga akan dihapus.`,
    'Konfirmasi Hapus',
    { confirmButtonText: 'Hapus', cancelButtonText: 'Batal', type: 'warning' }
  ).then(async () => {
    try {
      await pegawaiStore.destroy(row.id)
      showSuccessToast('Data pegawai berhasil dihapus')
      fetchData()
    } catch (error: any) {
      showErrorToast(error.response?.data?.message || 'Gagal menghapus data')
    }
  }).catch(() => {})
}

const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      saving.value = true
      try {
        const formData = new FormData()
        formData.append('nama', form.nama)
        formData.append('nik', form.nik)
        if (form.no_telp) formData.append('no_telp', form.no_telp)
        formData.append('email', form.email)
        formData.append('role_id', form.role_id)
        if (form.password) formData.append('password', form.password)
        
        if (form.avatarFile) {
          formData.append('avatar', form.avatarFile)
        } else if (!form.avatarUrl && drawerMode.value === 'edit') {
          formData.append('avatarUrl', '') // tell backend to remove
        } else if (form.avatarUrl && drawerMode.value === 'edit') {
          formData.append('avatarUrl', 'keep') // tell backend to keep
        }

        if (drawerMode.value === 'create') {
          await pegawaiStore.create(formData)
          showSuccessToast('Pegawai berhasil ditambahkan')
        } else {
          await pegawaiStore.update(form.id, formData)
          showSuccessToast('Pegawai berhasil diperbarui')
        }
        isDrawerOpen.value = false
        fetchData()
      } catch (error: any) {
        showErrorToast(error.response?.data?.message || 'Terjadi kesalahan')
      } finally {
        saving.value = false
      }
    }
  })
}

const fetchData = async () => {
  isLoading.value = true
  try {
    await pegawaiStore.getData({ search: searchQuery.value, page: currentPage.value, limit: pageSize.value })
  } catch (error) {
    showErrorToast('Gagal memuat data pegawai')
  } finally {
    isLoading.value = false
  }
}

watch([currentPage, pageSize], () => {
  fetchData()
})

const fetchRoles = async () => {
  try {
    const res = await roleStore.getData({ status: 'Aktif' })
    roleOptions.value = res.map((r: any) => ({
      label: r.name,
      value: r.id
    }))
  } catch (e) {
    console.error('Error fetching roles', e)
  }
}

onMounted(() => {
  fetchData()
  fetchRoles()
})
</script>

<style scoped lang="scss">
.filter-section {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 24px;
  .search-input {
    width: 300px;
  }
}

.pegawai-info {
  display: flex;
  align-items: center;
  gap: 12px;

  .pegawai-details {
    display: flex;
    flex-direction: column;
    
    .pegawai-name {
      font-weight: 500;
      color: #111827;
    }
    
    .pegawai-email {
      font-size: 13px;
      color: #6B7280;
    }
  }
}

.form-row {
  display: flex;
  gap: 16px;
  .flex-1 {
    flex: 1;
  }
}

.avatar-upload-section {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 24px;
  
  .avatar-uploader {
    :deep(.el-upload) {
      border: 1px dashed #d9d9d9;
      border-radius: 8px;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: var(--el-transition-duration-fast);
      background-color: #f9fafb;
      
      &:hover {
        border-color: #14746F;
      }
    }
  }

  .avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 80px;
    height: 80px;
    text-align: center;
  }
  
  .avatar {
    width: 80px;
    height: 80px;
    display: block;
    object-fit: cover;
  }
  
  .upload-help {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    
    .upload-title {
      font-weight: 500;
      color: #374151;
    }
    
    .upload-desc {
      font-size: 12px;
      color: #6b7280;
      max-width: 200px;
      margin-bottom: 4px;
    }
  }
}

.detail-avatar-section {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.drawer-content {
  padding: 16px;
}

.drawer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
  margin-top: 24px;
}
</style>
