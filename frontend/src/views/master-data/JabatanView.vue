<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Master Jabatan</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola struktur jabatan, tunjangan, potongan, bonus, dan komisi default</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="openCreateDrawer">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Tambah Jabatan
        </AppButton>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Cari kode atau nama jabatan..."
        class="search-input"
        clearable
        @clear="fetchData"
        @keyup.enter="fetchData"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
      <div class="filter-actions">
        <el-select v-model="statusFilter" placeholder="Status" clearable style="width: 140px" @change="fetchData">
          <el-option label="Semua Status" value="" />
          <el-option label="Aktif" value="Aktif" />
          <el-option label="Nonaktif" value="Nonaktif" />
        </el-select>
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :data="data"
      :loading="isLoading"
      :total="meta.total"
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      empty-title="Belum ada data jabatan"
      empty-subtitle="Silakan tambah master jabatan baru"
      :empty-image="EmptyStateImage"
    >
      <el-table-column prop="kode_jabatan" label="Kode" width="130" />
      <el-table-column prop="nama_jabatan" label="Nama Jabatan" min-width="180">
        <template #default="{ row }">
          <span style="font-weight: 600; color: #1e293b;">{{ row.nama_jabatan }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="deskripsi" label="Deskripsi" min-width="200">
        <template #default="{ row }">
          <span style="color: #64748b;">{{ row.deskripsi || '-' }}</span>
        </template>
      </el-table-column>
      <el-table-column label="Komponen Default" min-width="200">
        <template #default="{ row }">
          <div class="komponen-chips" v-if="row.komponens && row.komponens.length > 0">
            <el-tag size="small" type="success" effect="plain" class="mr-1">
              {{ countKomponen(row.komponens, 'tunjangan') }} Tunjangan
            </el-tag>
            <el-tag size="small" type="danger" effect="plain" class="mr-1">
              {{ countKomponen(row.komponens, 'potongan') }} Potongan
            </el-tag>
            <el-tag size="small" type="warning" effect="plain" v-if="countKomponen(row.komponens, 'bonus') > 0" class="mr-1">
              {{ countKomponen(row.komponens, 'bonus') }} Bonus
            </el-tag>
            <el-tag size="small" type="info" effect="plain" v-if="countKomponen(row.komponens, 'komisi') > 0">
              {{ countKomponen(row.komponens, 'komisi') }} Komisi
            </el-tag>
          </div>
          <span v-else style="color: #94a3b8; font-size: 13px;">Tidak ada komponen default</span>
        </template>
      </el-table-column>
      <el-table-column label="Pegawai Terikat" width="140" align="center">
        <template #default="{ row }">
          <el-tag size="small" type="info">{{ row.kontraks_count || 0 }} Pegawai</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="status" label="Status" width="110" align="center">
        <template #default="{ row }">
          <el-tag :type="row.status === 'Aktif' ? 'success' : 'danger'" size="small">
            {{ row.status }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Aksi" width="80" fixed="right" align="center">
        <template #default="{ row }">
          <el-dropdown trigger="click" @command="(cmd: string) => handleAction(cmd, row)">
            <el-button type="info" text circle>
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="detail">
                  <el-icon><View /></el-icon> Detail Data
                </el-dropdown-item>
                <el-dropdown-item command="edit">
                  <el-icon><Edit /></el-icon> Edit Data
                </el-dropdown-item>
                <el-dropdown-item command="delete" class="text-danger">
                  <el-icon><Delete /></el-icon> Hapus Data
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </DataTable>

    <!-- Drawer Form Tambah / Edit / Detail -->
    <CustomDrawer
      v-model="isDrawerOpen"
      :title="drawerTitle"
      default-size="680px"
      @closed="resetForm"
    >
      <div class="admin-drawer-content">
        <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
          <el-row :gutter="16">
            <el-col :span="10">
              <el-form-item label="Kode Jabatan" prop="kode_jabatan" required>
                <el-input 
                  v-model="form.kode_jabatan" 
                  placeholder="Cth: JAB-MGR" 
                  :disabled="drawerMode === 'detail'" 
                />
              </el-form-item>
            </el-col>
            <el-col :span="14">
              <el-form-item label="Nama Jabatan" prop="nama_jabatan" required>
                <el-input 
                  v-model="form.nama_jabatan" 
                  placeholder="Cth: Manager Operasional" 
                  :disabled="drawerMode === 'detail'" 
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="Status" prop="status">
                <el-radio-group v-model="form.status" :disabled="drawerMode === 'detail'">
                  <el-radio label="Aktif">Aktif</el-radio>
                  <el-radio label="Nonaktif">Nonaktif</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Deskripsi Jabatan" prop="deskripsi">
            <el-input 
              v-model="form.deskripsi" 
              type="textarea" 
              :rows="2" 
              placeholder="Deskripsi tugas atau ruang lingkup jabatan..." 
              :disabled="drawerMode === 'detail'" 
            />
          </el-form-item>

          <el-divider content-position="left">
            <span style="font-weight: 600; color: #334155;">Komponen Rutin Default (Tunjangan, Potongan, Bonus, Komisi)</span>
          </el-divider>
          <p style="font-size: 13px; color: #64748b; margin-top: -12px; margin-bottom: 16px;">
            Komponen ini akan menjadi acuan default otomatis ketika pegawai dengan jabatan ini dibuatkan kontrak baru.
          </p>

          <div class="komponen-list">
            <div v-for="(komp, index) in form.komponens" :key="index" class="komponen-item-card">
              <div class="komponen-item-header">
                <span class="index-label">#{{ index + 1 }}</span>
                <el-button 
                  v-if="drawerMode !== 'detail'" 
                  type="danger" 
                  text 
                  circle 
                  size="small" 
                  @click="removeKomponen(index)"
                >
                  <el-icon><Delete /></el-icon>
                </el-button>
              </div>

              <el-row :gutter="12">
                <el-col :span="6">
                  <el-form-item label="Jenis" :prop="'komponens.' + index + '.jenis'" required>
                    <el-select v-model="komp.jenis" :disabled="drawerMode === 'detail'" style="width: 100%;">
                      <el-option label="Tunjangan" value="tunjangan" />
                      <el-option label="Potongan" value="potongan" />
                      <el-option label="Bonus" value="bonus" />
                      <el-option label="Komisi" value="komisi" />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="8">
                  <el-form-item label="Nama Komponen" :prop="'komponens.' + index + '.nama_komponen'" required>
                    <el-input v-model="komp.nama_komponen" placeholder="Cth: Tunjangan Makan" :disabled="drawerMode === 'detail'" />
                  </el-form-item>
                </el-col>
                <el-col :span="5">
                  <el-form-item label="Tipe Nilai" :prop="'komponens.' + index + '.tipe_nilai'" required>
                    <el-select v-model="komp.tipe_nilai" :disabled="drawerMode === 'detail'" style="width: 100%;">
                      <el-option label="Nominal Tetap" value="tetap" />
                      <el-option label="% Upah Bulanan" value="persentase_tetap" />
                      <el-option label="% Upah Harian" value="persentase_variabel" />
                      <el-option label="Hari x Upah Harian" value="kali_hari_variabel" />
                      <el-option label="Custom" value="custom" />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="5">
                  <el-form-item label="Nilai Default" :prop="'komponens.' + index + '.nilai_default'">
                    <el-input-number 
                      v-model="komp.nilai_default" 
                      :min="0" 
                      :step="komp.tipe_nilai === 'persentase_tetap' || komp.tipe_nilai === 'persentase_variabel' || komp.tipe_nilai === 'kali_hari_variabel' ? 1 : 10000"
                      :controls="false"
                      style="width: 100%;" 
                      :disabled="drawerMode === 'detail'" 
                    />
                  </el-form-item>
                </el-col>
              </el-row>
              <el-form-item label="Keterangan (Opsional)" style="margin-bottom: 0;">
                <el-input v-model="komp.keterangan" placeholder="Keterangan tambahan..." :disabled="drawerMode === 'detail'" />
              </el-form-item>
            </div>

            <div v-if="drawerMode !== 'detail'" style="margin-top: 12px;">
              <el-button plain @click="addKomponen">
                <el-icon class="mr-1"><Plus /></el-icon> Tambah Komponen
              </el-button>
            </div>
          </div>
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
            Simpan Jabatan
          </AppButton>
        </div>
      </div>
    </CustomDrawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Search, MoreFilled, Edit, Delete, Plus, View } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessageBox } from 'element-plus'

import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import { useJabatanStore, type Jabatan, type JabatanKomponen } from '@/store/jabatan'

const jabatanStore = useJabatanStore()
const { data, meta } = storeToRefs(jabatanStore)

const isLoading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const statusFilter = ref('')

const isDrawerOpen = ref(false)
const drawerMode = ref<'create' | 'edit' | 'detail'>('create')
const drawerTitle = ref('Tambah Jabatan')
const saving = ref(false)

const formRef = ref<FormInstance>()
const form = reactive({
  id: 0,
  kode_jabatan: '',
  nama_jabatan: '',
  deskripsi: '',
  status: 'Aktif' as 'Aktif' | 'Nonaktif',
  komponens: [] as JabatanKomponen[]
})

const rules: FormRules = {
  kode_jabatan: [{ required: true, message: 'Kode jabatan wajib diisi', trigger: 'blur' }],
  nama_jabatan: [{ required: true, message: 'Nama jabatan wajib diisi', trigger: 'blur' }],
  status: [{ required: true, message: 'Status wajib dipilih', trigger: 'change' }]
}

const fetchData = async () => {
  isLoading.value = true
  try {
    await jabatanStore.getData({
      page: currentPage.value,
      limit: pageSize.value,
      search: searchQuery.value,
      status: statusFilter.value
    })
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal memuat data jabatan')
  } finally {
    isLoading.value = false
  }
}

watch([currentPage, pageSize], () => {
  fetchData()
})

const countKomponen = (list: any[], jenis: string) => {
  return list.filter(item => item.jenis === jenis).length
}

const addKomponen = () => {
  form.komponens.push({
    jenis: 'tunjangan',
    nama_komponen: '',
    tipe_nilai: 'tetap',
    nilai_default: 0,
    keterangan: ''
  })
}

const removeKomponen = (index: number) => {
  form.komponens.splice(index, 1)
}

const openCreateDrawer = () => {
  drawerMode.value = 'create'
  drawerTitle.value = 'Tambah Jabatan Baru'
  resetForm()
  isDrawerOpen.value = true
}

const handleAction = async (cmd: string, row: Jabatan) => {
  if (cmd === 'detail' || cmd === 'edit') {
    drawerMode.value = cmd as 'detail' | 'edit'
    drawerTitle.value = cmd === 'detail' ? 'Detail Jabatan' : 'Edit Jabatan'
    
    form.id = row.id
    form.kode_jabatan = row.kode_jabatan
    form.nama_jabatan = row.nama_jabatan
    form.deskripsi = row.deskripsi || ''
    form.status = row.status
    form.komponens = (row.komponens || []).map(k => ({
      jenis: k.jenis,
      nama_komponen: k.nama_komponen,
      tipe_nilai: k.tipe_nilai,
      nilai_default: Number(k.nilai_default) || 0,
      keterangan: k.keterangan || ''
    }))

    isDrawerOpen.value = true
  } else if (cmd === 'delete') {
    try {
      await ElMessageBox.confirm(
        `Yakin ingin menghapus jabatan "${row.nama_jabatan}"? Tindakan ini tidak dapat dibatalkan.`,
        'Konfirmasi Hapus',
        {
          confirmButtonText: 'Ya, Hapus',
          cancelButtonText: 'Batal',
          type: 'warning'
        }
      )

      await jabatanStore.destroy(row.id)
      showSuccessToast('Jabatan berhasil dihapus')
      fetchData()
    } catch (e: any) {
      if (e !== 'cancel') {
        showErrorToast(e?.response?.data?.message || 'Gagal menghapus jabatan')
      }
    }
  }
}

const resetForm = () => {
  form.id = 0
  form.kode_jabatan = ''
  form.nama_jabatan = ''
  form.deskripsi = ''
  form.status = 'Aktif'
  form.komponens = []
  formRef.value?.resetFields()
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return

    saving.value = true
    try {
      const payload = {
        kode_jabatan: form.kode_jabatan,
        nama_jabatan: form.nama_jabatan,
        deskripsi: form.deskripsi,
        status: form.status,
        komponens: form.komponens
      }

      if (drawerMode.value === 'create') {
        await jabatanStore.create(payload)
        showSuccessToast('Jabatan berhasil ditambahkan')
      } else {
        await jabatanStore.update(form.id, payload)
        showSuccessToast('Jabatan berhasil diperbarui')
      }

      isDrawerOpen.value = false
      fetchData()
    } catch (error: any) {
      showErrorToast(error?.response?.data?.message || 'Gagal menyimpan data jabatan')
    } finally {
      saving.value = false
    }
  })
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.page-container {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.title-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
}

.total-badge {
  font-weight: 500;
}

.subtitle {
  color: #64748b;
  margin-top: 4px;
  font-size: 14px;
}

.filter-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  gap: 16px;
}

.search-input {
  max-width: 320px;
}

.komponen-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.admin-drawer-content {
  padding: 8px 16px 24px 16px;
}

.drawer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 32px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.komponen-item-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px 14px;
  margin-bottom: 12px;
}

.komponen-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.index-label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}
</style>
