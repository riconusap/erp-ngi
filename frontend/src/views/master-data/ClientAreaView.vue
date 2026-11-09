<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Client & Area Penempatan</AppHeading>
        </div>
        <p class="subtitle">Kelola data mitra kerja (klien) dan cabang/area penempatan kerja pegawai</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="openCreateDrawer">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Tambah Client
        </AppButton>
      </div>
    </div>

    <!-- Data Client -->
    <div class="filter-section">
      <el-input
        v-model="clientSearch"
        placeholder="Cari kode atau nama client..."
        class="search-input"
        clearable
        @clear="fetchClientData"
        @keyup.enter="fetchClientData"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
      <div class="filter-actions">
        <el-select v-model="clientStatusFilter" placeholder="Status" clearable style="width: 140px" @change="fetchClientData">
          <el-option label="Semua Status" value="" />
          <el-option label="Aktif" value="Aktif" />
          <el-option label="Nonaktif" value="Nonaktif" />
        </el-select>
      </div>
    </div>

    <DataTable
      :data="clients"
      :loading="isClientLoading"
      :total="clientMeta.total"
      v-model:current-page="clientPage"
      v-model:page-size="clientPageSize"
      empty-title="Belum ada data client"
      empty-subtitle="Silakan tambah mitra client baru"
      :empty-image="EmptyStateImage"
    >
      <el-table-column prop="kode_client" label="Kode" width="130" />
      <el-table-column prop="nama_client" label="Nama Client" min-width="200">
        <template #default="{ row }">
          <span class="client-name-link" @click="openClientDetail(row)">{{ row.nama_client }}</span>
        </template>
      </el-table-column>
      <el-table-column label="Kontak" min-width="180">
        <template #default="{ row }">
          <div style="font-size: 13px;">
            <div v-if="row.telepon" style="color: #334155;"><el-icon class="mr-1"><Phone /></el-icon>{{ row.telepon }}</div>
            <div v-if="row.email" style="color: #64748b;"><el-icon class="mr-1"><Message /></el-icon>{{ row.email }}</div>
            <span v-if="!row.telepon && !row.email" style="color: #94a3b8;">-</span>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="alamat" label="Alamat" min-width="220" show-overflow-tooltip>
        <template #default="{ row }">
          <span style="color: #64748b;">{{ row.alamat || '-' }}</span>
        </template>
      </el-table-column>
      <el-table-column label="Penempatan" width="140" align="center">
        <template #default="{ row }">
          <el-tag
            size="small"
            type="primary"
            effect="plain"
            class="clickable-tag"
            @click="openClientDetail(row)"
          >
            <el-icon class="mr-1"><Location /></el-icon>{{ row.areas_count || 0 }} Area
          </el-tag>
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
          <el-dropdown trigger="click" @command="(cmd: string) => handleClientAction(cmd, row)">
            <el-button type="info" text circle>
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="detail">
                  <el-icon><View /></el-icon> Detail & Area
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

    <!-- Drawer Detail Client (Beserta Area Penempatan) -->
    <CustomDrawer
      v-model="isClientDetailDrawerOpen"
      :title="selectedClientDetail?.nama_client ? `Detail: ${selectedClientDetail.nama_client}` : 'Detail Client'"
      default-size="780px"
    >
      <div v-if="selectedClientDetail" class="admin-drawer-content detail-client-content">
        <!-- Client Profile Header Card -->
        <div class="client-detail-header-card">
          <div class="header-main-info">
            <div class="client-avatar-badge">
              <el-icon :size="24"><OfficeBuilding /></el-icon>
            </div>
            <div class="header-text-group">
              <div class="title-row">
                <h3 class="client-title">{{ selectedClientDetail.nama_client }}</h3>
                <el-tag size="small" type="info" effect="plain" class="code-badge">
                  {{ selectedClientDetail.kode_client }}
                </el-tag>
                <el-tag
                  :type="selectedClientDetail.status === 'Aktif' ? 'success' : 'danger'"
                  size="small"
                  class="status-tag"
                >
                  {{ selectedClientDetail.status }}
                </el-tag>
              </div>
              <p class="client-address">
                <el-icon class="mr-1"><Location /></el-icon>
                {{ selectedClientDetail.alamat || 'Alamat kantor belum diatur' }}
              </p>
            </div>
            <div class="header-action">
              <el-button size="small" @click="openEditClientFromDetail(selectedClientDetail)">
                <el-icon><Edit /></el-icon> Edit Client
              </el-button>
            </div>
          </div>

          <div class="client-meta-grid">
            <div class="meta-item">
              <span class="meta-label">No. Telepon</span>
              <span class="meta-value">
                <el-icon v-if="selectedClientDetail.telepon" class="mr-1 text-primary"><Phone /></el-icon>
                {{ selectedClientDetail.telepon || '-' }}
              </span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Alamat Email</span>
              <span class="meta-value">
                <el-icon v-if="selectedClientDetail.email" class="mr-1 text-primary"><Message /></el-icon>
                {{ selectedClientDetail.email || '-' }}
              </span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Total Penempatan</span>
              <span class="meta-value font-semibold text-primary">
                {{ clientDetailAreas.length }} Area
              </span>
            </div>
          </div>
        </div>

        <!-- Section: Area Penempatan Client -->
        <div class="detail-area-section">
          <div class="section-header">
            <div class="section-title-wrap">
              <h4 class="section-title">Area Penempatan</h4>
              <span class="section-subtitle">Daftar cabang dan lokasi penempatan kerja client ini</span>
            </div>
            <AppButton variant="primary" size="small" @click="openCreateAreaForClient">
              <template #icon>
                <el-icon><Plus /></el-icon>
              </template>
              Tambah Area
            </AppButton>
          </div>

          <div class="area-filter-bar">
            <el-input
              v-model="detailAreaSearch"
              placeholder="Cari kode atau nama area..."
              class="search-input"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
            <el-select v-model="detailAreaStatusFilter" placeholder="Status Area" clearable style="width: 140px;">
              <el-option label="Semua Status" value="" />
              <el-option label="Aktif" value="Aktif" />
              <el-option label="Nonaktif" value="Nonaktif" />
            </el-select>
          </div>

          <!-- Tabel Area di dalam Detail Client -->
          <el-table
            :data="filteredDetailAreas"
            v-loading="isDetailLoading"
            style="width: 100%; border-radius: 8px;"
            border
            stripe
          >
            <el-table-column prop="kode_area" label="Kode" width="120" />
            <el-table-column prop="nama_area" label="Nama Penempatan" min-width="170">
              <template #default="{ row }">
                <span style="font-weight: 600; color: #1e293b;">{{ row.nama_area }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="keterangan" label="Keterangan / Lokasi" min-width="160" show-overflow-tooltip>
              <template #default="{ row }">
                <span style="color: #64748b; font-size: 13px;">{{ row.keterangan || '-' }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="status" label="Status" width="100" align="center">
              <template #default="{ row }">
                <el-tag :type="row.status === 'Aktif' ? 'success' : 'danger'" size="small">
                  {{ row.status }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Aksi" width="100" align="center">
              <template #default="{ row }">
                <el-button type="primary" text circle size="small" @click="openEditArea(row)">
                  <el-icon><Edit /></el-icon>
                </el-button>
                <el-button type="danger" text circle size="small" @click="handleDeleteArea(row)">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </template>
            </el-table-column>

            <template #empty>
              <div class="detail-empty-box">
                <el-empty :image-size="60" description="Belum ada area penempatan untuk client ini">
                  <AppButton variant="primary" size="small" @click="openCreateAreaForClient">
                    <template #icon>
                      <el-icon><Plus /></el-icon>
                    </template>
                    Tambah Area Pertama
                  </AppButton>
                </el-empty>
              </div>
            </template>
          </el-table>
        </div>
      </div>
    </CustomDrawer>

    <!-- Drawer Client -->
    <CustomDrawer
      v-model="isClientDrawerOpen"
      :title="clientDrawerTitle"
      default-size="520px"
      @closed="resetClientForm"
    >
      <div class="admin-drawer-content">
        <el-form ref="clientFormRef" :model="clientForm" :rules="clientRules" label-position="top">
          <el-row :gutter="16">
            <el-col :span="10">
              <el-form-item label="Kode Client" prop="kode_client" required>
                <el-input v-model="clientForm.kode_client" placeholder="Cth: CLI-001" />
              </el-form-item>
            </el-col>
            <el-col :span="14">
              <el-form-item label="Nama Client" prop="nama_client" required>
                <el-input v-model="clientForm.nama_client" placeholder="Cth: PT Sinar Abadi" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="No. Telepon" prop="telepon">
                <el-input v-model="clientForm.telepon" placeholder="Cth: 021-5551234" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Email" prop="email">
                <el-input v-model="clientForm.email" placeholder="Cth: contact@sinarabadi.com" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Alamat Kantor" prop="alamat">
            <el-input v-model="clientForm.alamat" type="textarea" :rows="3" placeholder="Alamat lengkap client..." />
          </el-form-item>

          <el-form-item label="Status" prop="status">
            <el-radio-group v-model="clientForm.status">
              <el-radio label="Aktif">Aktif</el-radio>
              <el-radio label="Nonaktif">Nonaktif</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-form>

        <div class="drawer-actions">
          <el-button @click="isClientDrawerOpen = false">Batal</el-button>
          <AppButton variant="primary" :loading="isSaving" @click="handleClientSubmit">
            Simpan Client
          </AppButton>
        </div>
      </div>
    </CustomDrawer>

    <!-- Drawer Area -->
    <CustomDrawer
      v-model="isAreaDrawerOpen"
      :title="areaDrawerTitle"
      default-size="520px"
      @closed="resetAreaForm"
    >
      <div class="admin-drawer-content">
        <el-form ref="areaFormRef" :model="areaForm" :rules="areaRules" label-position="top">
          <el-form-item label="Client Mitra" prop="client_id" required>
            <el-select v-model="areaForm.client_id" :disabled="isAreaClientLocked" placeholder="Pilih Mitra Client" style="width: 100%;">
              <el-option 
                v-for="c in clientOptions" 
                :key="c.id" 
                :label="c.nama_client + ' (' + c.kode_client + ')'" 
                :value="c.id" 
              />
            </el-select>
          </el-form-item>

          <el-row :gutter="16">
            <el-col :span="10">
              <el-form-item label="Kode Area" prop="kode_area" required>
                <el-input v-model="areaForm.kode_area" placeholder="Cth: AR-SBY-01" />
              </el-form-item>
            </el-col>
            <el-col :span="14">
              <el-form-item label="Nama Area Penempatan" prop="nama_area" required>
                <el-input v-model="areaForm.nama_area" placeholder="Cth: Warehouse Rungkut Surabaya" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Keterangan / Lokasi" prop="keterangan">
            <el-input v-model="areaForm.keterangan" type="textarea" :rows="3" placeholder="Keterangan penempatan lokasi kerja..." />
          </el-form-item>

          <el-form-item label="Status" prop="status">
            <el-radio-group v-model="areaForm.status">
              <el-radio label="Aktif">Aktif</el-radio>
              <el-radio label="Nonaktif">Nonaktif</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-form>

        <div class="drawer-actions">
          <el-button @click="isAreaDrawerOpen = false">Batal</el-button>
          <AppButton variant="primary" :loading="isSaving" @click="handleAreaSubmit">
            Simpan Area
          </AppButton>
        </div>
      </div>
    </CustomDrawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Search, MoreFilled, Edit, Delete, Plus, Phone, Message, View, Location, OfficeBuilding } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessageBox } from 'element-plus'

import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import { useClientAreaStore, type Client, type Area } from '@/store/clientArea'

const clientAreaStore = useClientAreaStore()
const { clients, clientMeta } = storeToRefs(clientAreaStore)

const clientOptions = ref<Client[]>([])
const isSaving = ref(false)

// CLIENT STATE
const isClientLoading = ref(false)
const clientPage = ref(1)
const clientPageSize = ref(10)
const clientSearch = ref('')
const clientStatusFilter = ref('')
const isClientDrawerOpen = ref(false)
const clientDrawerMode = ref<'create' | 'edit'>('create')
const clientDrawerTitle = ref('Tambah Client Baru')
const clientFormRef = ref<FormInstance>()
const clientForm = reactive({
  id: 0,
  kode_client: '',
  nama_client: '',
  alamat: '',
  telepon: '',
  email: '',
  status: 'Aktif' as 'Aktif' | 'Nonaktif'
})

const clientRules: FormRules = {
  kode_client: [{ required: true, message: 'Kode client wajib diisi', trigger: 'blur' }],
  nama_client: [{ required: true, message: 'Nama client wajib diisi', trigger: 'blur' }],
  status: [{ required: true, message: 'Status wajib dipilih', trigger: 'change' }]
}

// CLIENT DETAIL STATE
const isClientDetailDrawerOpen = ref(false)
const selectedClientDetail = ref<Client | null>(null)
const clientDetailAreas = ref<Area[]>([])
const isDetailLoading = ref(false)
const detailAreaSearch = ref('')
const detailAreaStatusFilter = ref('')
const isAreaClientLocked = ref(false)

// AREA DRAWER STATE (dikelola lewat drawer detail client)
const isAreaDrawerOpen = ref(false)
const areaDrawerMode = ref<'create' | 'edit'>('create')
const areaDrawerTitle = ref('Tambah Area Penempatan')
const areaFormRef = ref<FormInstance>()
const areaForm = reactive({
  id: 0,
  client_id: undefined as number | undefined,
  kode_area: '',
  nama_area: '',
  keterangan: '',
  status: 'Aktif' as 'Aktif' | 'Nonaktif'
})

const areaRules: FormRules = {
  client_id: [{ required: true, message: 'Client wajib dipilih', trigger: 'change' }],
  kode_area: [{ required: true, message: 'Kode area wajib diisi', trigger: 'blur' }],
  nama_area: [{ required: true, message: 'Nama area penempatan wajib diisi', trigger: 'blur' }],
  status: [{ required: true, message: 'Status wajib dipilih', trigger: 'change' }]
}

const fetchClientData = async () => {
  isClientLoading.value = true
  try {
    await clientAreaStore.getClients({
      page: clientPage.value,
      limit: clientPageSize.value,
      search: clientSearch.value,
      status: clientStatusFilter.value
    })
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal memuat data client')
  } finally {
    isClientLoading.value = false
  }
}

watch([clientPage, clientPageSize], () => {
  fetchClientData()
})

const loadClientOptions = async () => {
  try {
    clientOptions.value = await clientAreaStore.getAllClients()
  } catch (e) {
    console.error(e)
  }
}

const openCreateDrawer = () => {
  clientDrawerMode.value = 'create'
  clientDrawerTitle.value = 'Tambah Client Baru'
  resetClientForm()
  isClientDrawerOpen.value = true
}

const openClientDetail = async (row: Client) => {
  selectedClientDetail.value = { ...row }
  clientDetailAreas.value = []
  detailAreaSearch.value = ''
  detailAreaStatusFilter.value = ''
  isClientDetailDrawerOpen.value = true
  await refreshClientDetail(row.id)
}

const refreshClientDetail = async (id: number) => {
  isDetailLoading.value = true
  try {
    const freshClient = await clientAreaStore.getClient(id)
    if (freshClient) {
      selectedClientDetail.value = freshClient
      clientDetailAreas.value = freshClient.areas || []
    }
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal memuat detail client')
  } finally {
    isDetailLoading.value = false
  }
}

const filteredDetailAreas = computed(() => {
  let list = clientDetailAreas.value || []
  if (detailAreaSearch.value) {
    const q = detailAreaSearch.value.toLowerCase()
    list = list.filter(a =>
      (a.nama_area && a.nama_area.toLowerCase().includes(q)) ||
      (a.kode_area && a.kode_area.toLowerCase().includes(q)) ||
      (a.keterangan && a.keterangan.toLowerCase().includes(q))
    )
  }
  if (detailAreaStatusFilter.value) {
    list = list.filter(a => a.status === detailAreaStatusFilter.value)
  }
  return list
})

const openCreateAreaForClient = () => {
  if (!selectedClientDetail.value) return
  areaDrawerMode.value = 'create'
  areaDrawerTitle.value = `Tambah Area - ${selectedClientDetail.value.nama_client}`
  resetAreaForm()
  areaForm.client_id = selectedClientDetail.value.id
  isAreaClientLocked.value = true
  loadClientOptions()
  isAreaDrawerOpen.value = true
}

const openEditClient = (row: Client) => {
  clientDrawerMode.value = 'edit'
  clientDrawerTitle.value = 'Edit Data Client'
  clientForm.id = row.id
  clientForm.kode_client = row.kode_client
  clientForm.nama_client = row.nama_client
  clientForm.alamat = row.alamat || ''
  clientForm.telepon = row.telepon || ''
  clientForm.email = row.email || ''
  clientForm.status = row.status
  isClientDrawerOpen.value = true
}

const openEditClientFromDetail = (client: Client) => {
  openEditClient(client)
}

const openEditArea = (row: Area) => {
  areaDrawerMode.value = 'edit'
  areaDrawerTitle.value = `Edit Area - ${row.nama_area}`
  loadClientOptions()
  areaForm.id = row.id
  areaForm.client_id = row.client_id
  areaForm.kode_area = row.kode_area
  areaForm.nama_area = row.nama_area
  areaForm.keterangan = row.keterangan || ''
  areaForm.status = row.status
  isAreaClientLocked.value = isClientDetailDrawerOpen.value
  isAreaDrawerOpen.value = true
}

const handleDeleteArea = async (row: Area) => {
  try {
    await ElMessageBox.confirm(
      `Yakin ingin menghapus area penempatan "${row.nama_area}"?`,
      'Konfirmasi Hapus',
      { confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', type: 'warning' }
    )
    await clientAreaStore.destroyArea(row.id)
    showSuccessToast('Area berhasil dihapus')
    if (selectedClientDetail.value) {
      await refreshClientDetail(selectedClientDetail.value.id)
    }
    fetchClientData()
  } catch (e: any) {
    if (e !== 'cancel') showErrorToast(e?.response?.data?.message || 'Gagal menghapus area')
  }
}

const handleClientAction = async (cmd: string, row: Client) => {
  if (cmd === 'detail') {
    openClientDetail(row)
  } else if (cmd === 'edit') {
    openEditClient(row)
  } else if (cmd === 'delete') {
    try {
      await ElMessageBox.confirm(
        `Yakin ingin menghapus client "${row.nama_client}"?`,
        'Konfirmasi Hapus',
        { confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', type: 'warning' }
      )
      await clientAreaStore.destroyClient(row.id)
      showSuccessToast('Client berhasil dihapus')
      if (selectedClientDetail.value && selectedClientDetail.value.id === row.id) {
        isClientDetailDrawerOpen.value = false
      }
      fetchClientData()
    } catch (e: any) {
      if (e !== 'cancel') showErrorToast(e?.response?.data?.message || 'Gagal menghapus client')
    }
  }
}

const resetClientForm = () => {
  clientForm.id = 0
  clientForm.kode_client = ''
  clientForm.nama_client = ''
  clientForm.alamat = ''
  clientForm.telepon = ''
  clientForm.email = ''
  clientForm.status = 'Aktif'
  clientFormRef.value?.resetFields()
}

const resetAreaForm = () => {
  areaForm.id = 0
  areaForm.client_id = undefined
  areaForm.kode_area = ''
  areaForm.nama_area = ''
  areaForm.keterangan = ''
  areaForm.status = 'Aktif'
  areaFormRef.value?.resetFields()
}

const handleClientSubmit = async () => {
  if (!clientFormRef.value) return
  await clientFormRef.value.validate(async (valid) => {
    if (!valid) return
    isSaving.value = true
    try {
      const payload = {
        kode_client: clientForm.kode_client,
        nama_client: clientForm.nama_client,
        alamat: clientForm.alamat,
        telepon: clientForm.telepon,
        email: clientForm.email,
        status: clientForm.status
      }
      if (clientDrawerMode.value === 'create') {
        await clientAreaStore.createClient(payload)
        showSuccessToast('Client berhasil ditambahkan')
      } else {
        await clientAreaStore.updateClient(clientForm.id, payload)
        showSuccessToast('Client berhasil diperbarui')
        if (selectedClientDetail.value && selectedClientDetail.value.id === clientForm.id) {
          selectedClientDetail.value = {
            ...selectedClientDetail.value,
            ...payload
          }
        }
      }
      isClientDrawerOpen.value = false
      fetchClientData()
      loadClientOptions()
    } catch (e: any) {
      showErrorToast(e?.response?.data?.message || 'Gagal menyimpan client')
    } finally {
      isSaving.value = false
    }
  })
}

const handleAreaSubmit = async () => {
  if (!areaFormRef.value) return
  await areaFormRef.value.validate(async (valid) => {
    if (!valid) return
    isSaving.value = true
    try {
      const payload = {
        client_id: areaForm.client_id,
        kode_area: areaForm.kode_area,
        nama_area: areaForm.nama_area,
        keterangan: areaForm.keterangan,
        status: areaForm.status
      }
      if (areaDrawerMode.value === 'create') {
        await clientAreaStore.createArea(payload)
        showSuccessToast('Area berhasil ditambahkan')
      } else {
        await clientAreaStore.updateArea(areaForm.id, payload)
        showSuccessToast('Area berhasil diperbarui')
      }
      isAreaDrawerOpen.value = false
      if (selectedClientDetail.value) {
        await refreshClientDetail(selectedClientDetail.value.id)
      }
      fetchClientData()
    } catch (e: any) {
      showErrorToast(e?.response?.data?.message || 'Gagal menyimpan area')
    } finally {
      isSaving.value = false
    }
  })
}

onMounted(() => {
  fetchClientData()
  loadClientOptions()
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
  margin-bottom: 20px;
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

.filter-actions {
  display: flex;
  gap: 12px;
}

.search-input {
  max-width: 320px;
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

.client-name-link {
  font-weight: 600;
  color: #1e293b;
  cursor: pointer;
  transition: color 0.15s ease;
}
.client-name-link:hover {
  color: #2563eb;
  text-decoration: underline;
}

.clickable-tag {
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}
.clickable-tag:hover {
  transform: translateY(-1px);
  filter: brightness(0.95);
}

.detail-client-content {
  padding: 8px 16px 32px 16px;
}

.client-detail-header-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 24px;
}

.header-main-info {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.client-avatar-badge {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-text-group {
  flex: 1;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.client-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.client-address {
  margin: 6px 0 0 0;
  font-size: 13px;
  color: #64748b;
  display: flex;
  align-items: center;
}

.client-meta-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.meta-label {
  font-size: 11.5px;
  font-weight: 500;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.meta-value {
  font-size: 13.5px;
  font-weight: 500;
  color: #1e293b;
  display: flex;
  align-items: center;
}

.detail-area-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-title-wrap {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.section-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
}

.section-subtitle {
  font-size: 13px;
  color: #64748b;
}

.area-filter-bar {
  display: flex;
  gap: 12px;
  align-items: center;
}

.detail-empty-box {
  padding: 24px 0;
}
</style>
