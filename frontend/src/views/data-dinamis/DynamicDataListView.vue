<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Data: {{ tableSchema?.name || 'Memuat...' }}</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/data-dinamis" class="back-link">Data Dinamis</router-link> 
          <span class="separator">/</span> 
          <span class="active">{{ tableSchema?.name || '...' }}</span>
        </div>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="goToCreate" :disabled="!tableSchema">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Tambah Data
        </AppButton>
      </div>
    </div>

    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Pencarian data..."
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
      v-if="tableSchema"
      :data="tableData" 
      :loading="isLoading" 
      :empty-image="EmptyStateImage" 
      :total="meta.total" 
      v-model:current-page="currentPage" 
      v-model:page-size="pageSize"
    >
      <el-table-column 
        v-for="col in displayColumns" 
        :key="col.key" 
        :prop="'data.' + col.key" 
        :label="col.name" 
        min-width="150" 
      >
        <template #default="scope">
          <!-- Special handling for boolean or other types if needed -->
          <span v-if="col.type === 'boolean'">
            <el-tag :type="scope.row.data[col.key] ? 'success' : 'danger'" size="small">
              {{ scope.row.data[col.key] ? 'Ya' : 'Tidak' }}
            </el-tag>
          </span>
          <span v-else>{{ scope.row.data[col.key] }}</span>
        </template>
      </el-table-column>

      <el-table-column label="Action" width="80" align="center" fixed="right">
        <template #default="scope">
          <el-dropdown trigger="click" placement="bottom-end">
            <el-button text class="action-more-btn" aria-label="Aksi">
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="goToEdit(scope.row.id)"><el-icon><Edit /></el-icon> Edit</el-dropdown-item>
                <el-dropdown-item @click="openDeleteDialog(scope.row)" style="color: var(--el-color-danger);"><el-icon><Delete /></el-icon> Hapus</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </DataTable>

    <!-- Modal Delete -->
    <el-dialog v-model="isDeleteModalVisible" width="480px" align-center class="custom-delete-modal" title="Konfirmasi Hapus">
      <div class="delete-content" style="padding-top: 8px;">
        Apakah Anda yakin ingin menghapus data ini?
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Search, MoreFilled, Edit, Delete, Plus } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import DataTable from '@/components/common/DataTable.vue'
import EmptyStateImage from '@/assets/empty-state.svg'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const route = useRoute()
const slug = route.params.slug as string

const tableSchema = ref<any>(null)
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
const selectedRecord = ref<any>(null)

// Compute columns that are NOT sensitive to display in the list view
const displayColumns = computed(() => {
  if (!tableSchema.value || !tableSchema.value.columns) return []
  return tableSchema.value.columns.filter((c: any) => !c.is_sensitive)
})

watch([currentPage, pageSize], () => {
  fetchData()
})

const fetchSchema = async () => {
  try {
    const res = await ApiService.get(`dynamic-data/${slug}/schema`)
    tableSchema.value = res.data.data
  } catch (error) {
    ElMessage.error('Gagal mengambil struktur tabel')
    router.push('/dashboard/data-dinamis')
  }
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params: any = {
      page: currentPage.value,
      limit: pageSize.value
    }
    if (searchQuery.value) params.search = searchQuery.value

    const res = await ApiService.query(`dynamic-data/${slug}`, { params })
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

const goToCreate = () => {
  router.push(`/dashboard/data-dinamis/${slug}/create`)
}

const goToEdit = (id: number) => {
  router.push(`/dashboard/data-dinamis/${slug}/${id}/edit`)
}

const openDeleteDialog = (row: any) => {
  selectedRecord.value = row
  isDeleteModalVisible.value = true
}

const confirmDelete = async () => {
  if (!selectedRecord.value) return
  saving.value = true
  try {
    await ApiService.delete(`dynamic-data/${slug}/${selectedRecord.value.id}`)
    ElMessage.success('Data berhasil dihapus')
    isDeleteModalVisible.value = false
    fetchData()
  } catch (error) {
    ElMessage.error('Gagal menghapus data')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await fetchSchema()
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
}

.breadcrumb {
  font-size: 14px;
  color: #6B7280;
  .back-link {
    color: var(--el-color-primary);
    text-decoration: none;
    &:hover {
      text-decoration: underline;
    }
  }
  .separator {
    margin: 0 8px;
  }
  .active {
    color: #374151;
    font-weight: 500;
  }
}

.total-badge {
  font-weight: 500;
}

.filter-section {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 24px;
  .search-input {
    width: 300px;
  }
}

.action-more-btn {
  padding: 8px;
  height: auto;
}
</style>
