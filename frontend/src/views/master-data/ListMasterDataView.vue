<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Master Data</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola referensi data master sistem</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="openAddDrawer">Tambah data</AppButton>
      </div>
    </div>

    <div class="filter-section">
      <el-input aria-label="Kotak pencarian" v-model="searchQuery" placeholder="Cari nama atau kode" :prefix-icon="Search" class="search-input" />
      <div class="filter-actions">
        <el-select v-model="activeFilter" placeholder="Filter" clearable class="custom-select">
          <el-option label="Aktif" value="Aktif" />
          <el-option label="Nonaktif" value="Nonaktif" />
        </el-select>
      </div>
    </div>

    <DataTable :data="data" :loading="isLoading" :empty-image="EmptyStateImage" :total="meta.total" v-model:current-page="currentPage" v-model:page-size="pageSize">
      <el-table-column prop="code" label="Kode" min-width="120" />
      <el-table-column prop="name" label="Nama Referensi" min-width="200" />
      <el-table-column prop="description" label="Deskripsi" min-width="250" />
      <el-table-column prop="createdAt" label="Dibuat Pada" min-width="150" />
      <el-table-column prop="status" label="Status" min-width="100">
        <template #default="scope">
          <el-tag :type="scope.row.status.toLowerCase() === 'aktif' ? 'success' : 'danger'" effect="light" class="status-badge">
            {{ scope.row.status }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Action" width="80" align="center">
        <template #default="scope">
          <el-dropdown trigger="click" placement="bottom-end">
            <el-button text class="action-more-btn" aria-label="Aksi">
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="openEditDrawer(scope.row)"><el-icon><Edit /></el-icon> Edit</el-dropdown-item>
                <el-dropdown-item @click="deleteItem(scope.row.id)" style="color: var(--el-color-danger);"><el-icon><Delete /></el-icon> Hapus</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </DataTable>

    <CustomDrawer v-model="isDrawerOpen">
      <div class="admin-drawer-content">
        <h2>{{ drawerMode === 'add' ? 'Tambah Data' : 'Edit Data' }}</h2>
        <el-form ref="formRef" :model="form" label-position="top" class="mt-4">
          <el-form-item label="Kode Referensi">
            <el-input v-model="form.code" placeholder="Cth: CAT-001" size="large" />
          </el-form-item>
          <el-form-item label="Nama Referensi">
            <el-input v-model="form.name" placeholder="Cth: Kategori Umum" size="large" />
          </el-form-item>
          <el-form-item label="Deskripsi">
            <el-input v-model="form.description" type="textarea" placeholder="Deskripsi data" size="large" />
          </el-form-item>
          <el-form-item label="Status">
            <el-switch v-model="form.isActive" style="--el-switch-on-color: #1967D2;" />
          </el-form-item>
        </el-form>

        <div class="drawer-footer mt-4">
          <el-button text @click="isDrawerOpen = false">Batal</el-button>
          <el-button type="danger" color="#ef4444" @click="saveData" :loading="saving">Simpan</el-button>
        </div>
      </div>
    </CustomDrawer>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { ref, reactive, onMounted, watch } from 'vue'
import { Search, MoreFilled, Edit, Delete } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import { useMasterDataStore } from '@/store/masterData'
import { ElMessageBox } from 'element-plus'

const store = useMasterDataStore()
const { data, meta } = storeToRefs(store)

const isLoading = ref(false)
const saving = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const activeFilter = ref('')

const isDrawerOpen = ref(false)
const drawerMode = ref<'add'|'edit'>('add')

const form = reactive({
  id: '',
  code: '',
  name: '',
  description: '',
  isActive: true
})

const fetchList = async () => {
  isLoading.value = true
  await store.getData({ page: currentPage.value, limit: pageSize.value, search: searchQuery.value, status: activeFilter.value })
  isLoading.value = false
}

onMounted(() => {
  fetchList()
})

watch([currentPage, pageSize, searchQuery, activeFilter], () => {
  fetchList()
})

const openAddDrawer = () => {
  drawerMode.value = 'add'
  form.id = ''
  form.code = ''
  form.name = ''
  form.description = ''
  form.isActive = true
  isDrawerOpen.value = true
}

const openEditDrawer = (row: any) => {
  drawerMode.value = 'edit'
  form.id = row.id
  form.code = row.code
  form.name = row.name
  form.description = row.description
  form.isActive = row.status === 'Aktif'
  isDrawerOpen.value = true
}

const saveData = async () => {
  saving.value = true
  try {
    const payload = {
      code: form.code,
      name: form.name,
      description: form.description,
      status: form.isActive ? 'Aktif' : 'Nonaktif'
    }
    
    if (drawerMode.value === 'add') {
      await store.create(payload)
      showSuccessToast('Data berhasil ditambahkan')
    } else {
      await store.update(form.id, payload)
      showSuccessToast('Data berhasil diubah')
    }
    
    isDrawerOpen.value = false
    fetchList()
  } catch (error) {
    showErrorToast('Terjadi kesalahan')
  } finally {
    saving.value = false
  }
}

const deleteItem = async (id: string) => {
  ElMessageBox.confirm('Yakin ingin menghapus data ini?', 'Konfirmasi', {
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal',
    type: 'warning',
    confirmButtonClass: 'el-button--danger'
  }).then(async () => {
    await store.delete(id)
    showSuccessToast('Data berhasil dihapus')
    fetchList()
  }).catch(() => {})
}
</script>

<style scoped>
.mt-4 { margin-top: 1rem; }
</style>
