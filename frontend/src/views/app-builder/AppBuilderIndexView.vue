<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">App Builder</AppHeading>
        <p class="subtitle">Buat dan kelola halaman aplikasi khusus (Canvas)</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="goToCreate">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Buat Halaman Baru
        </AppButton>
      </div>
    </div>

    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Cari halaman..."
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

    <el-row :gutter="24" v-loading="isLoading" class="cards-container">
      <el-col :span="8" v-for="page in pageList" :key="page.id" style="margin-bottom: 24px;">
        <el-card class="form-card" shadow="hover">
          <div class="card-content">
            <h3 class="form-title">
              <el-icon v-if="page.icon"><component :is="page.icon" /></el-icon>
              {{ page.name }}
            </h3>
            <p class="form-desc">
              Tipe: <strong>{{ page.type === 'table' ? 'Tabel Data' : 'Form Dinamis' }}</strong>
              <br/>
              Sumber Data: {{ page.dynamic_table ? page.dynamic_table.name : 'Tidak Ada' }}
            </p>
            <div class="form-meta">
              <el-tag size="small" :type="page.is_active ? 'success' : 'danger'">
                {{ page.is_active ? 'Aktif' : 'Nonaktif' }}
              </el-tag>
              <el-tag size="small" type="info">{{ page.slug }}</el-tag>
            </div>
            <div class="card-actions">
              <el-tooltip content="Edit Halaman" placement="top">
                <el-button circle @click="goToEdit(page.id)"><el-icon><Edit /></el-icon></el-button>
              </el-tooltip>
              <el-tooltip content="Hapus" placement="top">
                <el-button circle type="danger" plain @click="openDeleteDialog(page)"><el-icon><Delete /></el-icon></el-button>
              </el-tooltip>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    
    <div v-if="!isLoading && pageList.length === 0" class="empty-state">
      <el-empty description="Belum ada halaman yang dibuat" />
    </div>

    <!-- Modal Delete -->
    <el-dialog v-model="isDeleteModalVisible" width="480px" align-center title="Konfirmasi Hapus">
      <div class="delete-content">
        Apakah Anda yakin ingin menghapus halaman <strong>{{ selectedPage?.name }}</strong>?
        Menu ini akan hilang dari Sidebar.
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="isDeleteModalVisible = false">Batal</el-button>
          <el-button type="danger" @click="confirmDelete" :loading="saving">Ya, Hapus</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Search, Plus, Edit, Delete } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const pageList = ref<any[]>([])
const isLoading = ref(true)
const saving = ref(false)
const searchQuery = ref('')

const isDeleteModalVisible = ref(false)
const selectedPage = ref<any>(null)

const fetchData = async () => {
  isLoading.value = true
  try {
    const params: any = { limit: 100 }
    if (searchQuery.value) params.search = searchQuery.value

    const res = await ApiService.query('app-pages', { params })
    pageList.value = res.data.data.data
  } catch (error) {
    ElMessage.error('Gagal mengambil daftar halaman')
  } finally {
    isLoading.value = false
  }
}

watch([searchQuery], () => fetchData())

const goToCreate = () => router.push('/dashboard/app-builder/create')
const goToEdit = (id: number) => router.push(`/dashboard/app-builder/${id}/edit`)

const openDeleteDialog = (page: any) => {
  selectedPage.value = page
  isDeleteModalVisible.value = true
}

const confirmDelete = async () => {
  if (!selectedPage.value) return
  saving.value = true
  try {
    await ApiService.delete(`app-pages/${selectedPage.value.id}`)
    ElMessage.success('Halaman berhasil dihapus')
    isDeleteModalVisible.value = false
    fetchData()
  } catch (error) {
    ElMessage.error('Gagal menghapus halaman')
  } finally {
    saving.value = false
  }
}

onMounted(() => fetchData())
</script>

<style scoped lang="scss">
.page-container { display: flex; flex-direction: column; }
.page-header { display: flex; justify-content: space-between; margin-bottom: 24px; }
.subtitle { color: #6B7280; margin: 4px 0 0 0; font-size: 14px; }
.filter-section { display: flex; justify-content: flex-end; margin-bottom: 24px; .search-input { width: 300px; } }
.form-card {
  height: 100%;
  .card-content { display: flex; flex-direction: column; height: 100%; }
  .form-title { display: flex; align-items: center; gap: 8px; margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #111827; }
  .form-desc { margin: 0 0 16px 0; font-size: 14px; color: #6B7280; flex-grow: 1; }
  .form-meta { display: flex; gap: 8px; margin-bottom: 16px; }
  .card-actions { display: flex; gap: 8px; border-top: 1px solid #f3f4f6; padding-top: 16px; justify-content: flex-end; }
}
.empty-state { display: flex; justify-content: center; padding: 40px; }
</style>
