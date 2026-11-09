<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Daftar Peran</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola peran (roles) dan hak akses (permissions) aplikasi</p>
      </div>
      <div class="header-right">
        <AppButton v-if="can('Peran', 'create')" variant="primary" @click="router.push('/dashboard/konfigurasi/peran/create')">Tambah peran</AppButton>
      </div>
    </div>

    <div class="filter-section">
      <el-input aria-label="Kotak pencarian" v-model="searchQuery" placeholder="Cari peran" :prefix-icon="Search" class="search-input" @input="fetchData" />
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
      <el-table-column prop="name" label="Nama Peran" min-width="260">
        <template #default="scope">
          <strong>{{ scope.row.name }}</strong>
        </template>
      </el-table-column>
      <el-table-column prop="can_access_admin" label="Akses Admin" min-width="150" align="center">
        <template #default="scope">
          <el-tag :type="scope.row.can_access_admin ? 'success' : 'info'" effect="plain">
            {{ scope.row.can_access_admin ? 'Akses Admin' : 'Khusus Absensi' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="status" label="Status" min-width="120" align="center">
        <template #default="scope">
          <el-tag :type="scope.row.status === 'Aktif' ? 'success' : 'danger'" effect="light" class="status-badge">
            {{ scope.row.status }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column v-if="can('Peran', 'update') || can('Peran', 'delete')" label="Action" width="80" align="center">
        <template #default="scope">
          <el-dropdown trigger="click" placement="bottom-end">
            <el-button text class="action-more-btn">
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-if="can('Peran', 'update')" @click="router.push(`/dashboard/konfigurasi/peran/${scope.row.id}/edit`)">
                  <el-icon><Edit /></el-icon> Edit & Hak Akses
                </el-dropdown-item>
                <el-dropdown-item v-if="can('Peran', 'delete')" @click="openDeleteModal(scope.row)">
                  <el-icon><Delete /></el-icon> Hapus
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </DataTable>

    <!-- Modal Delete -->
    <el-dialog v-model="isDeleteModalVisible" width="480px" align-center class="custom-delete-modal" title="Konfirmasi Hapus">
      <div class="delete-content" style="padding-top: 8px;">
        Apakah Anda yakin ingin menghapus peran <strong>{{ selectedRole?.name }}</strong>?
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { Search, MoreFilled, Edit, Delete } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'

import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import { useRoleStore } from '@/store/role'
import { usePermissions } from '@/composables/usePermissions'

const router = useRouter()
const roleStore = useRoleStore()
const { can } = usePermissions()
const { data, meta } = storeToRefs(roleStore)

const isLoading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const activeFilter = ref('')

const saving = ref(false)

const isDeleteModalVisible = ref(false)
const selectedRole = ref<any>(null)

const fetchData = async () => {
  isLoading.value = true
  await roleStore.getData({ search: searchQuery.value, status: activeFilter.value, page: currentPage.value, limit: pageSize.value })
  isLoading.value = false
}

onMounted(() => {
  fetchData()
})

const openDeleteModal = (row: any) => {
  selectedRole.value = row
  isDeleteModalVisible.value = true
}

const confirmDelete = async () => {
  saving.value = true
  try {
    await roleStore.remove(selectedRole.value.id)
    showSuccessToast('Peran berhasil dihapus')
    isDeleteModalVisible.value = false
    fetchData()
  } catch (error) {
    showErrorToast('Gagal menghapus peran')
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
</style>
