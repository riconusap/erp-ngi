<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Kamus Data</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola tabel dan struktur data dinamis</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="goToCreate">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Tambah Tabel
        </AppButton>
      </div>
    </div>

    <el-tabs v-model="activeTab" class="kamus-list-tabs" @tab-change="handleTabChange">
      <el-tab-pane label="Tabel Dinamis" name="dynamic">
        <div class="filter-section">
          <el-input
            v-model="searchQuery"
            placeholder="Cari nama tabel..."
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

        <DataTable :data="tableData" :loading="isLoading" :empty-image="EmptyStateImage" :total="meta.total" v-model:current-page="currentPage" v-model:page-size="pageSize">
          <el-table-column prop="name" label="Nama Tabel" min-width="200" />
          <el-table-column prop="description" label="Deskripsi" min-width="250" />
          <el-table-column label="Jumlah Kolom" min-width="120">
            <template #default="scope">
              <el-tag type="info">{{ scope.row.columns?.length || 0 }} Kolom</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Action" width="80" align-center>
            <template #default="scope">
              <el-dropdown trigger="click" placement="bottom-end">
                <el-button text class="action-more-btn" aria-label="Aksi">
                  <el-icon><MoreFilled /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item @click="goToEdit(scope.row.id)"><el-icon><Edit /></el-icon> Edit Kamus</el-dropdown-item>
                    <el-dropdown-item @click="openDeleteDialog(scope.row)" style="color: var(--el-color-danger);"><el-icon><Delete /></el-icon> Hapus</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </template>
          </el-table-column>
        </DataTable>
      </el-tab-pane>

      <el-tab-pane label="Tabel Sistem (Database)" name="system">
        <div class="filter-section">
          <el-input
            v-model="searchSystemQuery"
            placeholder="Cari nama tabel sistem..."
            class="search-input"
            clearable
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>

        <DataTable :data="filteredSystemTables" :loading="isSystemLoading" :empty-image="EmptyStateImage" :total="filteredSystemTables.length" v-model:current-page="systemCurrentPage" v-model:page-size="systemPageSize">
          <el-table-column prop="name" label="Nama Tabel Sistem" min-width="200">
             <template #default="scope">
              <strong>{{ scope.row.name }}</strong>
            </template>
          </el-table-column>
          <el-table-column prop="description" label="Keterangan" min-width="300" />
          <el-table-column prop="columns_count" label="Jumlah Kolom Tersedia" width="200" align="center">
            <template #default="scope">
              <el-tag size="small" type="success">{{ scope.row.columns_count || 0 }} Kolom (Filtered)</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Action" width="120" align-center>
            <template #default="scope">
              <el-dropdown trigger="click" placement="bottom-end">
                <el-button text class="action-more-btn" aria-label="Aksi">
                  <el-icon><MoreFilled /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item @click="openSystemPreview(scope.row)"><el-icon><View /></el-icon> Preview Struktur</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </template>
          </el-table-column>
        </DataTable>
      </el-tab-pane>
    </el-tabs>

    <!-- Modal Delete -->
    <el-dialog v-model="isDeleteModalVisible" width="480px" align-center class="custom-delete-modal" title="Konfirmasi Hapus">
      <div class="delete-content" style="padding-top: 8px;">
        Apakah Anda yakin ingin menghapus tabel <strong>{{ selectedTable?.name }}</strong>? Semua data di dalamnya akan terhapus.
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="isDeleteModalVisible = false" class="btn-cancel">Batal</el-button>
          <el-button type="danger" @click="confirmDelete" :loading="saving" class="btn-delete">
            Ya, Hapus
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Modal Preview System Table -->
    <el-dialog v-model="isPreviewModalVisible" width="900px" align-center title="Preview Struktur Tabel Sistem">
      <div v-if="selectedTable" class="preview-container">
        <div style="margin-bottom: 16px;">
          <h3 style="margin: 0; color: #111827;">{{ selectedTable.name }}</h3>
          <p style="margin: 4px 0 0 0; font-size: 14px; color: #6b7280;">Menampilkan struktur kolom (kolom sensitif telah disembunyikan)</p>
        </div>
        
        <el-table :data="selectedTable.columns" border style="width: 100%" height="400">
          <el-table-column prop="name" label="Nama Kolom Database" min-width="200" />
          <el-table-column prop="type" label="Tipe Data SQL" min-width="200" />
        </el-table>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Search, MoreFilled, Edit, Delete, Plus, View } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import DataTable from '@/components/common/DataTable.vue'
import EmptyStateImage from '@/assets/empty-state.svg'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const activeTab = ref('dynamic')

// Dynamic Tables logic
const tableData = ref([])
const isLoading = ref(true)
const saving = ref(false)
const searchQuery = ref('')

const meta = reactive({
  total: 0,
  current_page: 1,
  per_page: 10
})
const currentPage = ref(1)
const pageSize = ref(10)

const isDeleteModalVisible = ref(false)
const selectedTable = ref<any>(null)

// System Tables logic
const systemTables = ref<any[]>([])
const isSystemLoading = ref(false)
const searchSystemQuery = ref('')
const systemCurrentPage = ref(1)
const systemPageSize = ref(10)
const isPreviewModalVisible = ref(false)

const filteredSystemTables = computed(() => {
  let filtered = systemTables.value
  if (searchSystemQuery.value) {
    const q = searchSystemQuery.value.toLowerCase()
    filtered = filtered.filter((t: any) => t.name.toLowerCase().includes(q))
  }
  const start = (systemCurrentPage.value - 1) * systemPageSize.value
  const end = start + systemPageSize.value
  return filtered.slice(start, end)
})

watch([currentPage, pageSize], () => {
  fetchData()
})

const fetchData = async () => {
  isLoading.value = true
  try {
    const params: any = {
      page: currentPage.value,
      limit: pageSize.value
    }
    if (searchQuery.value) params.search = searchQuery.value

    const res = await ApiService.query('kamus-data', { params })
    tableData.value = res.data.data.data
    meta.total = res.data.data.total
  } catch (error) {
    ElMessage.error('Gagal mengambil data')
  } finally {
    isLoading.value = false
  }
}

watch([searchQuery], () => {
  currentPage.value = 1
  fetchData()
})

const fetchSystemTables = async () => {
  isSystemLoading.value = true
  try {
    const res = await ApiService.get('system-tables')
    systemTables.value = res.data.data
  } catch (error) {
    ElMessage.error('Gagal mengambil data tabel sistem')
  } finally {
    isSystemLoading.value = false
  }
}

const handleTabChange = (tabName: string) => {
  if (tabName === 'system' && systemTables.value.length === 0) {
    fetchSystemTables()
  }
}

const goToCreate = () => {
  router.push({ name: 'KonfigurasiKamusDataCreate' })
}

const goToEdit = (id: number) => {
  router.push({ name: 'KonfigurasiKamusDataEdit', params: { id } })
}

const openDeleteDialog = (row: any) => {
  selectedTable.value = row
  isDeleteModalVisible.value = true
}

const openSystemPreview = (row: any) => {
  selectedTable.value = row
  isPreviewModalVisible.value = true
}

const confirmDelete = async () => {
  if (!selectedTable.value) return
  saving.value = true
  try {
    await ApiService.delete(`kamus-data/${selectedTable.value.id}`)
    ElMessage.success('Kamus data berhasil dihapus')
    isDeleteModalVisible.value = false
    fetchData()
  } catch (error) {
    ElMessage.error('Gagal menghapus data')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped lang="scss">
.page-container {
  display: flex;
  flex-direction: column;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.title-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 4px;
}

.total-badge {
  font-weight: 500;
}

.subtitle {
  color: #6B7280;
  margin: 0;
  font-size: 14px;
}

.kamus-list-tabs {
  margin-top: 8px;
  :deep(.el-tabs__item) {
    font-size: 15px;
  }
}

.filter-section {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 24px;
  margin-top: 16px;
  .search-input {
    width: 300px;
  }
}

.action-more-btn {
  padding: 8px;
  height: auto;
}
</style>
