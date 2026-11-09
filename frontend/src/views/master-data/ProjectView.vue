<template>
  <div class="page-container">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Master Project</AppHeading>
        </div>
        <p class="subtitle">
          Kelola proyek kerja sama klien, penempatan area spesifik, maupun kontrak langsung tanpa area
        </p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="openCreateDrawer">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Tambah Project
        </AppButton>
      </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-primary-soft">
          <el-icon :size="20"><FolderOpened /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Total Project</span>
          <span class="stat-value">{{ projectMeta.total }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-success-soft">
          <el-icon :size="20"><Clock /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Sedang Berjalan</span>
          <span class="stat-value text-success">{{ runningCount }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-info-soft">
          <el-icon :size="20"><Document /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Kontrak Langsung</span>
          <span class="stat-value text-primary">{{ directContractCount }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-warning-soft">
          <el-icon :size="20"><Location /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Berbasis Area</span>
          <span class="stat-value text-warning">{{ areaBasedCount }}</span>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Cari kode, nama proyek, atau lingkup kerja..."
        class="search-input"
        clearable
        @clear="fetchProjects"
        @keyup.enter="fetchProjects"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>

      <div class="filter-actions">
        <!-- Filter Client -->
        <el-select
          v-model="clientFilter"
          placeholder="Pilih Client"
          clearable
          filterable
          class="filter-select"
          style="width: 220px;"
          @change="handleClientFilterChange"
        >
          <el-option label="Semua Client" value="" />
          <el-option
            v-for="c in clientOptions"
            :key="c.id"
            :label="`${c.kode_client} - ${c.nama_client}`"
            :value="c.id"
          />
        </el-select>

        <!-- Filter Area / Penempatan -->
        <el-select
          v-model="areaFilter"
          placeholder="Tipe Penempatan"
          clearable
          filterable
          class="filter-select"
          style="width: 210px;"
          @change="fetchProjects"
        >
          <el-option label="Semua Penempatan" value="" />
          <el-option label="Kontrak Langsung (Tanpa Area)" value="direct" />
          <el-option
            v-for="a in filterAreaOptions"
            :key="a.id"
            :label="`${a.kode_area} - ${a.nama_area}`"
            :value="a.id"
          />
        </el-select>

        <!-- Filter Status -->
        <el-select
          v-model="statusFilter"
          placeholder="Status"
          clearable
          class="filter-select"
          style="width: 150px;"
          @change="fetchProjects"
        >
          <el-option label="Semua Status" value="" />
          <el-option label="Perencanaan" value="Perencanaan" />
          <el-option label="Berjalan" value="Berjalan" />
          <el-option label="Selesai" value="Selesai" />
          <el-option label="Ditunda" value="Ditunda" />
          <el-option label="Batal" value="Batal" />
        </el-select>
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :data="projects"
      :loading="isLoading"
      :total="projectMeta.total"
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      empty-title="Belum ada data project"
      empty-subtitle="Silakan tambah proyek baru atau sesuaikan filter pencarian"
      :empty-image="EmptyStateImage"
    >
      <el-table-column prop="kode_project" label="Kode" width="130" />

      <el-table-column label="Nama Project" min-width="240">
        <template #default="{ row }">
          <div class="project-title-cell">
            <span class="project-name-link" @click="openProjectDetail(row)">{{ row.nama_project }}</span>
            <span v-if="row.deskripsi" class="project-desc-sub text-truncate">{{ row.deskripsi }}</span>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Client / Mitra" min-width="200">
        <template #default="{ row }">
          <div v-if="row.client" class="client-cell">
            <span class="client-name">{{ row.client.nama_client }}</span>
            <span class="client-code">{{ row.client.kode_client }}</span>
          </div>
          <span v-else class="text-muted">-</span>
        </template>
      </el-table-column>

      <el-table-column label="Penempatan / Tipe" min-width="210">
        <template #default="{ row }">
          <div v-if="row.area" class="placement-cell">
            <div class="placement-main">
              <el-icon class="placement-icon area-icon"><Location /></el-icon>
              <span class="placement-name" :title="row.area.nama_area">{{ row.area.nama_area }}</span>
            </div>
            <div class="placement-sub">
              <span class="placement-badge area">Area</span>
              <span v-if="row.area.kode_area" class="placement-code">{{ row.area.kode_area }}</span>
            </div>
          </div>
          <div v-else class="placement-cell">
            <div class="placement-main">
              <el-icon class="placement-icon direct-icon"><Document /></el-icon>
              <span class="placement-name">Kontrak Langsung</span>
            </div>
            <div class="placement-sub">
              <span class="placement-badge direct">Langsung</span>
              <span class="placement-hint">Tanpa area spesifik</span>
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Periode" min-width="190">
        <template #default="{ row }">
          <div class="date-range-cell">
            <span v-if="row.tanggal_mulai || row.tanggal_selesai">
              {{ row.tanggal_mulai || '?' }} s/d {{ row.tanggal_selesai || '?' }}
            </span>
            <span v-else class="text-muted">Fleksibel / Belum diatur</span>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Nilai Kontrak" min-width="160" align="right">
        <template #default="{ row }">
          <span class="currency-text">{{ formatRupiah(row.nilai_kontrak) }}</span>
        </template>
      </el-table-column>

      <el-table-column label="Pegawai" width="130" align="center">
        <template #default="{ row }">
          <el-tag
            size="small"
            :type="(row.pegawai_kontraks_count || 0) > 0 ? 'success' : 'info'"
            effect="plain"
          >
            <el-icon class="mr-1"><User /></el-icon>
            {{ row.pegawai_kontraks_count || 0 }} Orang
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="status" label="Status" width="120" align="center">
        <template #default="{ row }">
          <el-tag :type="getStatusTagType(row.status)" size="small">
            {{ row.status }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Aksi" width="80" fixed="right" align="center">
        <template #default="{ row }">
          <el-dropdown trigger="click" @command="(cmd: string) => handleActionCommand(cmd, row)">
            <el-button type="info" text circle>
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="detail">
                  <el-icon><View /></el-icon> Detail Proyek
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

    <!-- Drawer: Form Tambah / Edit Project -->
    <CustomDrawer
      v-model="isFormDrawerOpen"
      :title="drawerMode === 'create' ? 'Tambah Project Baru' : 'Edit Data Project'"
      default-size="640px"
    >
      <div class="admin-drawer-content">
        <el-form
          ref="formRef"
          :model="form"
          :rules="formRules"
          label-position="top"
          class="project-form"
          @submit.prevent="handleSubmit"
        >
          <!-- Section 1: Mitra & Tipe Penempatan -->
          <div class="form-section-card">
            <h4 class="form-section-title">
              <el-icon class="mr-1 text-primary"><OfficeBuilding /></el-icon>
              Mitra Kerja & Penempatan
            </h4>

            <el-form-item label="Client / Perusahaan Mitra" prop="client_id">
              <el-select
                v-model="form.client_id"
                placeholder="Pilih mitra klien"
                filterable
                style="width: 100%"
                size="large"
                @change="handleFormClientChange"
              >
                <el-option
                  v-for="c in clientOptions"
                  :key="c.id"
                  :label="`${c.kode_client} - ${c.nama_client}`"
                  :value="c.id"
                />
              </el-select>
            </el-form-item>

            <el-form-item label="Area Penempatan (Opsional)" prop="area_id">
              <el-select
                v-model="form.area_id"
                placeholder="Pilih area penempatan spesifik (opsional)"
                clearable
                filterable
                :disabled="!form.client_id"
                style="width: 100%"
                size="large"
              >
                <el-option
                  v-for="a in formAreaOptions"
                  :key="a.id"
                  :label="`${a.kode_area} - ${a.nama_area}`"
                  :value="a.id"
                />
              </el-select>
              <div class="field-hint-box">
                <el-icon class="hint-icon"><Document /></el-icon>
                <span>
                  <strong>Kasus Kontrak Langsung:</strong> Biarkan opsi ini kosong jika proyek berupa kontrak kerja sama langsung dengan klien tanpa penempatan area spesifik.
                </span>
              </div>
            </el-form-item>
          </div>

          <!-- Section 2: Identitas Proyek -->
          <div class="form-section-card">
            <h4 class="form-section-title">
              <el-icon class="mr-1 text-primary"><FolderOpened /></el-icon>
              Identitas Proyek
            </h4>

            <div class="form-grid-2">
              <el-form-item label="Kode Project" prop="kode_project">
                <el-input
                  v-model="form.kode_project"
                  placeholder="Cth: PRJ-2026-001"
                  size="large"
                />
              </el-form-item>

              <el-form-item label="Status Proyek" prop="status">
                <el-select v-model="form.status" style="width: 100%" size="large">
                  <el-option label="Perencanaan" value="Perencanaan" />
                  <el-option label="Berjalan" value="Berjalan" />
                  <el-option label="Selesai" value="Selesai" />
                  <el-option label="Ditunda" value="Ditunda" />
                  <el-option label="Batal" value="Batal" />
                </el-select>
              </el-form-item>
            </div>

            <el-form-item label="Nama Project" prop="nama_project">
              <el-input
                v-model="form.nama_project"
                placeholder="Cth: Maintenance Server DC & Cloud Infra"
                size="large"
              />
            </el-form-item>

            <el-form-item label="Deskripsi / Lingkup Kerja" prop="deskripsi">
              <el-input
                v-model="form.deskripsi"
                type="textarea"
                :rows="3"
                placeholder="Jelaskan ruang lingkup pekerjaan atau catatan kontrak proyek..."
              />
            </el-form-item>
          </div>

          <!-- Section 3: Jadwal & Nilai Kontrak -->
          <div class="form-section-card">
            <h4 class="form-section-title">
              <el-icon class="mr-1 text-primary"><Money /></el-icon>
              Periode & Nilai Kontrak
            </h4>

            <div class="form-grid-2">
              <el-form-item label="Tanggal Mulai" prop="tanggal_mulai">
                <el-date-picker
                  v-model="form.tanggal_mulai"
                  type="date"
                  placeholder="Pilih tanggal mulai"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                  size="large"
                />
              </el-form-item>

              <el-form-item label="Tanggal Selesai" prop="tanggal_selesai">
                <el-date-picker
                  v-model="form.tanggal_selesai"
                  type="date"
                  placeholder="Pilih tanggal selesai"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                  size="large"
                />
              </el-form-item>
            </div>

            <el-form-item label="Nilai Kontrak (Rp)" prop="nilai_kontrak">
              <el-input-number
                v-model="form.nilai_kontrak"
                :min="0"
                :step="1000000"
                placeholder="0"
                style="width: 100%"
                size="large"
                :controls="false"
              />
              <span v-if="form.nilai_kontrak" class="currency-preview">
                Terbaca: <strong>{{ formatRupiah(form.nilai_kontrak) }}</strong>
              </span>
            </el-form-item>
          </div>

          <div class="drawer-footer-actions">
            <el-button @click="isFormDrawerOpen = false">Batal</el-button>
            <AppButton variant="primary" :loading="isSaving" @click="handleSubmit">
              {{ drawerMode === 'create' ? 'Simpan Project' : 'Perbarui Project' }}
            </AppButton>
          </div>
        </el-form>
      </div>
    </CustomDrawer>

    <!-- Drawer: Detail Project -->
    <CustomDrawer
      v-model="isDetailDrawerOpen"
      :title="selectedProject?.nama_project ? `Detail: ${selectedProject.nama_project}` : 'Detail Project'"
      default-size="680px"
    >
      <div v-if="selectedProject" class="admin-drawer-content detail-project-wrapper">
        <!-- Header Profile Card -->
        <div class="detail-header-card">
          <div class="detail-header-main">
            <div class="project-avatar-badge">
              <el-icon :size="26"><FolderOpened /></el-icon>
            </div>
            <div class="detail-title-col">
              <div class="detail-title-row">
                <h3 class="detail-name">{{ selectedProject.nama_project }}</h3>
                <el-tag :type="getStatusTagType(selectedProject.status)" size="small">
                  {{ selectedProject.status }}
                </el-tag>
              </div>
              <div class="detail-sub-row">
                <span class="detail-code">{{ selectedProject.kode_project }}</span>
                <span class="dot-separator">•</span>
                <span v-if="selectedProject.area" class="type-indicator text-primary">
                  <el-icon class="mr-1"><Location /></el-icon>
                  Penempatan: {{ selectedProject.area.nama_area }}
                </span>
                <span v-else class="type-indicator text-primary">
                  <el-icon class="mr-1"><Document /></el-icon>
                  Kontrak Langsung Klien
                </span>
              </div>
            </div>
          </div>

          <div class="detail-quick-actions">
            <el-button size="small" @click="openEditProject(selectedProject)">
              <el-icon class="mr-1"><Edit /></el-icon> Edit Data
            </el-button>
          </div>
        </div>

        <!-- Detail Cards Grid -->
        <div class="detail-body-grid">
          <!-- Card Klien & Penempatan -->
          <div class="info-block">
            <h4 class="info-block-title">
              <el-icon class="mr-1 text-primary"><OfficeBuilding /></el-icon> Mitra Klien & Penempatan
            </h4>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Klien Mitra</span>
                <span class="info-value font-semibold">
                  {{ selectedProject.client?.nama_client || '-' }} ({{ selectedProject.client?.kode_client || '-' }})
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Tipe Penempatan</span>
                <span class="info-value">
                  <template v-if="selectedProject.area">
                    <el-tag size="small" type="primary" effect="plain">
                      <el-icon class="mr-1"><Location /></el-icon>
                      {{ selectedProject.area.nama_area }} ({{ selectedProject.area.kode_area }})
                    </el-tag>
                  </template>
                  <template v-else>
                    <el-tag size="small" type="info" effect="plain">
                      <el-icon class="mr-1"><Document /></el-icon>
                      Kontrak Langsung (Tanpa Area Spesifik)
                    </el-tag>
                  </template>
                </span>
              </div>
              <div v-if="selectedProject.client?.alamat" class="info-item">
                <span class="info-label">Alamat Klien</span>
                <span class="info-value text-muted">{{ selectedProject.client.alamat }}</span>
              </div>
            </div>
          </div>

          <!-- Card Finansial & Jadwal -->
          <div class="info-block">
            <h4 class="info-block-title">
              <el-icon class="mr-1 text-primary"><Money /></el-icon> Nilai Kontrak & Timeline
            </h4>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Nilai Kontrak</span>
                <span class="info-value text-primary font-semibold currency-lg">
                  {{ formatRupiah(selectedProject.nilai_kontrak) }}
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Periode Proyek</span>
                <span class="info-value">
                  <span v-if="selectedProject.tanggal_mulai || selectedProject.tanggal_selesai">
                    {{ selectedProject.tanggal_mulai || '?' }} s/d {{ selectedProject.tanggal_selesai || '?' }}
                  </span>
                  <span v-else class="text-muted">Belum ditentukan</span>
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Status Pelaksanaan</span>
                <span class="info-value">
                  <el-tag :type="getStatusTagType(selectedProject.status)" size="small">
                    {{ selectedProject.status }}
                  </el-tag>
                </span>
              </div>
            </div>
          </div>

          <!-- Card Deskripsi -->
          <div class="info-block full-width">
            <h4 class="info-block-title">
              <el-icon class="mr-1 text-primary"><Document /></el-icon> Lingkup Kerja / Deskripsi
            </h4>
            <p class="description-text">
              {{ selectedProject.deskripsi || 'Tidak ada catatan deskripsi atau rincian lingkup kerja untuk proyek ini.' }}
            </p>
          </div>

          <!-- Card Pegawai Ditugaskan -->
          <div class="info-block full-width">
            <h4 class="info-block-title">
              <el-icon class="mr-1 text-primary"><User /></el-icon>
              Pegawai Ditugaskan ({{ selectedProject.pegawai_kontraks?.length || selectedProject.pegawai_kontraks_count || 0 }} Orang)
            </h4>
            <div v-if="selectedProject.pegawai_kontraks && selectedProject.pegawai_kontraks.length" class="assigned-pegawai-grid">
              <div
                v-for="k in selectedProject.pegawai_kontraks"
                :key="k.id"
                class="assigned-pegawai-card"
              >
                <div class="peg-avatar">
                  <el-icon :size="16"><User /></el-icon>
                </div>
                <div class="peg-info">
                  <span class="peg-name">{{ k.pegawai?.nama || 'Pegawai' }}</span>
                  <span class="peg-sub">
                    {{ k.jabatan?.nama_jabatan || '-' }} • No: {{ k.no_kontrak }}
                  </span>
                </div>
              </div>
            </div>
            <span v-else class="text-muted" style="font-size: 13px;">Belum ada pegawai yang ditugaskan ke proyek ini.</span>
          </div>

          <!-- Meta Audit -->
          <div class="meta-audit-footer full-width">
            <span>Dibuat oleh: <strong>{{ selectedProject.creator?.username || 'Sistem' }}</strong></span>
            <span class="dot-separator">•</span>
            <span>Terakhir diubah: {{ selectedProject.updated_at ? formatDate(selectedProject.updated_at) : '-' }}</span>
          </div>
        </div>
      </div>
    </CustomDrawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import {
  Search,
  Plus,
  Edit,
  Delete,
  Location,
  OfficeBuilding,
  Document,
  MoreFilled,
  View,
  Clock,
  Money,
  FolderOpened
} from '@element-plus/icons-vue'
import { ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import { formatRupiah, formatDate } from '@/utils/formatters'
import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import { useProjectStore, type Project, type ProjectStatus } from '@/store/project'
import { useClientAreaStore, type Client, type Area } from '@/store/clientArea'

const projectStore = useProjectStore()
const clientAreaStore = useClientAreaStore()
const { projects, projectMeta } = storeToRefs(projectStore)

const isLoading = ref(false)
const isSaving = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const clientFilter = ref<number | ''>('')
const areaFilter = ref<string>('')
const statusFilter = ref<string>('')

const clientOptions = ref<Client[]>([])
const allAreas = ref<Area[]>([])

// Drawers
const isFormDrawerOpen = ref(false)
const isDetailDrawerOpen = ref(false)
const drawerMode = ref<'create' | 'edit'>('create')
const selectedProject = ref<Project | null>(null)
const editingId = ref<number | null>(null)

// Form
const formRef = ref<FormInstance>()
const form = reactive({
  client_id: undefined as number | undefined,
  area_id: undefined as number | null | undefined,
  kode_project: '',
  nama_project: '',
  deskripsi: '',
  tanggal_mulai: '',
  tanggal_selesai: '',
  nilai_kontrak: undefined as number | undefined,
  status: 'Perencanaan' as ProjectStatus,
})

const formRules: FormRules = {
  client_id: [
    { required: true, message: 'Mitra klien wajib dipilih', trigger: 'change' }
  ],
  kode_project: [
    { required: true, message: 'Kode proyek wajib diisi', trigger: 'blur' },
    { max: 50, message: 'Maksimal 50 karakter', trigger: 'blur' }
  ],
  nama_project: [
    { required: true, message: 'Nama proyek wajib diisi', trigger: 'blur' },
    { max: 255, message: 'Maksimal 255 karakter', trigger: 'blur' }
  ],
  status: [
    { required: true, message: 'Status proyek wajib dipilih', trigger: 'change' }
  ]
}

// Computed stats
const runningCount = computed(() => {
  return projects.value.filter(p => p.status === 'Berjalan').length
})

const directContractCount = computed(() => {
  return projects.value.filter(p => !p.area_id).length
})

const areaBasedCount = computed(() => {
  return projects.value.filter(p => !!p.area_id).length
})

// Filter Area options (filtered by selected client if any)
const filterAreaOptions = computed(() => {
  if (clientFilter.value) {
    return allAreas.value.filter(a => a.client_id === clientFilter.value)
  }
  return allAreas.value
})

// Form Area options (filtered by currently selected client in form)
const formAreaOptions = computed(() => {
  if (form.client_id) {
    return allAreas.value.filter(a => a.client_id === form.client_id)
  }
  return []
})

const getStatusTagType = (status: ProjectStatus): '' | 'primary' | 'success' | 'warning' | 'info' | 'danger' => {
  switch (status) {
    case 'Perencanaan':
      return 'info'
    case 'Berjalan':
      return 'primary'
    case 'Selesai':
      return 'success'
    case 'Ditunda':
      return 'warning'
    case 'Batal':
      return 'danger'
    default:
      return 'info'
  }
}

const fetchClientsAndAreas = async () => {
  try {
    const [clientsRes, areasRes] = await Promise.all([
      clientAreaStore.getAllClients(),
      clientAreaStore.getAllAreas()
    ])
    clientOptions.value = clientsRes
    allAreas.value = areasRes
  } catch (error) {
    console.error('Failed to load clients/areas', error)
  }
}

const fetchProjects = async () => {
  isLoading.value = true
  try {
    await projectStore.getProjects({
      page: currentPage.value,
      limit: pageSize.value,
      search: searchQuery.value,
      client_id: clientFilter.value || undefined,
      area_id: areaFilter.value || undefined,
      status: statusFilter.value || undefined
    })
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal memuat data project')
  } finally {
    isLoading.value = false
  }
}

const handleClientFilterChange = () => {
  // If chosen client does not own the selected area filter, reset area filter
  if (areaFilter.value && areaFilter.value !== 'direct') {
    const areaIdNum = Number(areaFilter.value)
    const exists = filterAreaOptions.value.some(a => a.id === areaIdNum)
    if (!exists) {
      areaFilter.value = ''
    }
  }
  fetchProjects()
}

const handleFormClientChange = () => {
  // If newly selected client doesn't contain current area_id, clear it
  if (form.area_id) {
    const exists = formAreaOptions.value.some(a => a.id === form.area_id)
    if (!exists) {
      form.area_id = null
    }
  }
}

const openCreateDrawer = () => {
  drawerMode.value = 'create'
  editingId.value = null
  form.client_id = undefined
  form.area_id = null
  form.kode_project = `PRJ-${new Date().getFullYear()}-${String(projectMeta.value.total + 1).padStart(3, '0')}`
  form.nama_project = ''
  form.deskripsi = ''
  form.tanggal_mulai = ''
  form.tanggal_selesai = ''
  form.nilai_kontrak = undefined
  form.status = 'Perencanaan'
  isFormDrawerOpen.value = true
}

const openEditProject = (row: Project) => {
  drawerMode.value = 'edit'
  editingId.value = row.id
  form.client_id = row.client_id
  form.area_id = row.area_id || null
  form.kode_project = row.kode_project
  form.nama_project = row.nama_project
  form.deskripsi = row.deskripsi || ''
  form.tanggal_mulai = row.tanggal_mulai || ''
  form.tanggal_selesai = row.tanggal_selesai || ''
  form.nilai_kontrak = row.nilai_kontrak ? Number(row.nilai_kontrak) : undefined
  form.status = row.status
  isFormDrawerOpen.value = true
}

const openProjectDetail = async (row: Project) => {
  selectedProject.value = row
  isDetailDrawerOpen.value = true
  try {
    const full = await projectStore.getProject(row.id)
    if (full) {
      selectedProject.value = full
    }
  } catch (e) {
    console.error('Failed to load project detail', e)
  }
}

const handleActionCommand = (cmd: string, row: Project) => {
  if (cmd === 'detail') {
    openProjectDetail(row)
  } else if (cmd === 'edit') {
    openEditProject(row)
  } else if (cmd === 'delete') {
    handleDeleteProject(row)
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return

    isSaving.value = true
    try {
      const payload: any = {
        client_id: form.client_id,
        area_id: form.area_id || null,
        kode_project: form.kode_project,
        nama_project: form.nama_project,
        deskripsi: form.deskripsi || null,
        tanggal_mulai: form.tanggal_mulai || null,
        tanggal_selesai: form.tanggal_selesai || null,
        nilai_kontrak: form.nilai_kontrak ?? null,
        status: form.status
      }

      if (drawerMode.value === 'create') {
        await projectStore.createProject(payload)
        showSuccessToast('Proyek baru berhasil ditambahkan')
      } else if (editingId.value) {
        await projectStore.updateProject(editingId.value, payload)
        showSuccessToast('Data proyek berhasil diperbarui')
        if (selectedProject.value && selectedProject.value.id === editingId.value) {
          const updated = await projectStore.getProject(editingId.value)
          selectedProject.value = updated
        }
      }

      isFormDrawerOpen.value = false
      fetchProjects()
    } catch (error: any) {
      const msg = error?.response?.data?.message || 'Gagal menyimpan data project'
      showErrorToast(msg)
    } finally {
      isSaving.value = false
    }
  })
}

const handleDeleteProject = (row: Project) => {
  ElMessageBox.confirm(
    `Apakah Anda yakin ingin menghapus proyek "${row.nama_project}" (${row.kode_project})?`,
    'Konfirmasi Hapus',
    {
      confirmButtonText: 'Hapus Proyek',
      cancelButtonText: 'Batal',
      type: 'warning',
      confirmButtonClass: 'el-button--danger'
    }
  ).then(async () => {
    try {
      await projectStore.destroyProject(row.id)
      showSuccessToast('Proyek berhasil dihapus')
      if (selectedProject.value?.id === row.id) {
        isDetailDrawerOpen.value = false
      }
      fetchProjects()
    } catch (error: any) {
      showErrorToast(error?.response?.data?.message || 'Gagal menghapus proyek')
    }
  }).catch(() => {})
}

watch([currentPage, pageSize], () => {
  fetchProjects()
})

onMounted(async () => {
  await fetchClientsAndAreas()
  await fetchProjects()
})
</script>

<style scoped lang="scss">
.page-container {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  gap: 16px;

  .header-left {
    .title-wrapper {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .subtitle {
      margin: 4px 0 0;
      color: #64748B;
      font-size: 14px;
    }
  }
}

/* Stats Row */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;

  .stat-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);

    .stat-icon-wrapper {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;

      &.bg-primary-soft {
        background: #F0FDF4;
        color: #14746F;
      }
      &.bg-success-soft {
        background: #ECFDF5;
        color: #059669;
      }
      &.bg-info-soft {
        background: #F0F9FF;
        color: #0284C7;
      }
      &.bg-warning-soft {
        background: #FFFBEB;
        color: #D97706;
      }
    }

    .stat-content {
      display: flex;
      flex-direction: column;

      .stat-label {
        font-size: 12px;
        color: #64748B;
        font-weight: 500;
      }
      .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #0F172A;
      }
    }
  }
}

/* Filter Toolbar */
.filter-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;

  .search-input {
    width: 320px;
  }

  .filter-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }
}

/* Table Cells */
.project-title-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;

  .project-name-link {
    font-weight: 600;
    color: #14746F;
    cursor: pointer;
    font-size: 14px;
    transition: color 0.15s ease;

    &:hover {
      color: #0F5B57;
      text-decoration: underline;
    }
  }

  .project-desc-sub {
    font-size: 12px;
    color: #64748B;
  }
}

.client-cell {
  display: flex;
  flex-direction: column;

  .client-name {
    font-size: 13px;
    font-weight: 500;
    color: #1E293B;
  }
  .client-code {
    font-size: 11px;
    color: #94A3B8;
  }
}

.placement-cell {
  display: flex;
  flex-direction: column;
  gap: 3px;

  .placement-main {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #1E293B;
    line-height: 1.3;

    .placement-icon {
      font-size: 14px;
      flex-shrink: 0;

      &.area-icon {
        color: #14746F;
      }
      &.direct-icon {
        color: #64748B;
      }
    }

    .placement-name {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      max-width: 220px;
    }
  }

  .placement-sub {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    line-height: 1.2;

    .placement-badge {
      display: inline-flex;
      align-items: center;
      padding: 1px 6px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.2px;

      &.area {
        background: #E6F4F2;
        color: #0F5B57;
        border: 1px solid #B8D5D3;
      }

      &.direct {
        background: #F1F5F9;
        color: #475569;
        border: 1px solid #E2E8F0;
      }
    }

    .placement-code {
      color: #64748B;
      font-weight: 500;
    }

    .placement-hint {
      color: #94A3B8;
    }
  }
}

.area-badge, .direct-badge {
  font-weight: 500;
  display: inline-flex;
  align-items: center;
}

.currency-text {
  font-weight: 600;
  color: #1E293B;
  font-size: 13px;
}

.date-range-cell {
  font-size: 13px;
  color: #475569;
}

/* Form Styles */
.project-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section-card {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 16px;

  .form-section-title {
    margin: 0 0 16px;
    font-size: 14px;
    font-weight: 600;
    color: #1E293B;
    display: flex;
    align-items: center;
  }
}

.form-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.field-hint-box {
  margin-top: 8px;
  background: #F1F5F9;
  border-left: 3px solid #14746F;
  border-radius: 4px;
  padding: 8px 12px;
  font-size: 12px;
  color: #475569;
  line-height: 1.4;
  display: flex;
  align-items: flex-start;
  gap: 8px;

  .hint-icon {
    font-size: 15px;
    color: #14746F;
    margin-top: 1px;
    flex-shrink: 0;
  }
}

.currency-preview {
  display: block;
  font-size: 12px;
  color: #14746F;
  margin-top: 4px;
}

.drawer-footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #E2E8F0;
}

/* Detail Drawer Styles */
.detail-project-wrapper {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-header-card {
  background: #FFFFFF;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  padding: 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;

  .detail-header-main {
    display: flex;
    align-items: center;
    gap: 16px;

    .project-avatar-badge {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      background: #F0FDF4;
      color: #14746F;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .detail-title-col {
      display: flex;
      flex-direction: column;
      gap: 4px;

      .detail-title-row {
        display: flex;
        align-items: center;
        gap: 10px;

        .detail-name {
          margin: 0;
          font-size: 16px;
          font-weight: 700;
          color: #0F172A;
        }
      }

      .detail-sub-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;

        .detail-code {
          font-weight: 600;
          color: #64748B;
        }

        .type-indicator {
          font-weight: 500;
          display: inline-flex;
          align-items: center;
        }
      }
    }
  }
}

.detail-body-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;

  .info-block {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 16px;

    &.full-width {
      grid-column: span 2;
    }

    .info-block-title {
      margin: 0 0 14px;
      font-size: 13px;
      font-weight: 600;
      color: #334155;
      display: flex;
      align-items: center;
    }

    .info-list {
      display: flex;
      flex-direction: column;
      gap: 10px;

      .info-item {
        display: flex;
        flex-direction: column;
        gap: 2px;

        .info-label {
          font-size: 11px;
          color: #94A3B8;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .info-value {
          font-size: 13px;
          color: #1E293B;

          &.currency-lg {
            font-size: 16px;
          }
        }
      }
    }

    .description-text {
      margin: 0;
      font-size: 13px;
      color: #475569;
      line-height: 1.6;
      white-space: pre-line;
    }
  }

  .meta-audit-footer {
    grid-column: span 2;
    padding: 12px;
    background: #F8FAFC;
    border-radius: 8px;
    border: 1px dashed #CBD5E1;
    font-size: 12px;
    color: #64748B;
    display: flex;
    align-items: center;
    gap: 8px;
  }
}

.dot-separator {
  color: #CBD5E1;
}

.assigned-pegawai-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;

  .assigned-pegawai-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 10px;

    .peg-avatar {
      width: 32px;
      height: 32px;
      border-radius: 6px;
      background: #E2E8F0;
      color: #475569;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .peg-info {
      display: flex;
      flex-direction: column;
      overflow: hidden;

      .peg-name {
        font-size: 13px;
        font-weight: 600;
        color: #1E293B;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .peg-sub {
        font-size: 11px;
        color: #64748B;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }
  }
}

.text-muted {
  color: #94A3B8;
}

.text-danger {
  color: #EF4444;
}

.text-success {
  color: #059669;
}

.text-warning {
  color: #D97706;
}

.text-primary {
  color: #14746F;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mr-1 {
  margin-right: 4px;
}

@media (max-width: 768px) {
  .form-grid-2,
  .detail-body-grid {
    grid-template-columns: 1fr;
  }

  .detail-body-grid .info-block.full-width {
    grid-column: span 1;
  }

  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }

  .filter-section .search-input {
    width: 100%;
  }
}
</style>
