<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">Form Builder</AppHeading>
        <p class="subtitle">Buat dan kelola form dinamis untuk publik</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="goToCreate">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Buat Form
        </AppButton>
      </div>
    </div>

    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Cari form..."
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
      <el-col :span="8" v-for="form in formList" :key="form.id" style="margin-bottom: 24px;">
        <el-card class="form-card" shadow="hover">
          <div class="card-content">
            <h3 class="form-title">{{ form.name }}</h3>
            <p class="form-desc">{{ form.description || 'Tidak ada deskripsi' }}</p>
            <div class="form-meta">
              <el-tag size="small" :type="form.is_active ? 'success' : 'danger'">
                {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
              </el-tag>
              <el-tag size="small" type="info">{{ form.schema?.length || 0 }} Field</el-tag>
            </div>
            <div class="card-actions">
              <el-tooltip content="Edit Form" placement="top">
                <el-button circle @click="goToEdit(form.id)"><el-icon><Edit /></el-icon></el-button>
              </el-tooltip>
              <el-tooltip content="Lihat Respon" placement="top">
                <el-button circle type="primary" plain @click="goToResponses(form.id)"><el-icon><DataLine /></el-icon></el-button>
              </el-tooltip>
              <el-tooltip content="Bagikan Link" placement="top">
                <el-button circle type="success" plain @click="copyLink(form.slug)"><el-icon><Link /></el-icon></el-button>
              </el-tooltip>
              <el-tooltip content="Hapus" placement="top">
                <el-button circle type="danger" plain @click="openDeleteDialog(form)"><el-icon><Delete /></el-icon></el-button>
              </el-tooltip>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    
    <div v-if="!isLoading && formList.length === 0" class="empty-state">
      <el-empty description="Belum ada form yang dibuat" />
    </div>

    <!-- Modal Delete -->
    <el-dialog v-model="isDeleteModalVisible" width="480px" align-center title="Konfirmasi Hapus">
      <div class="delete-content">
        Apakah Anda yakin ingin menghapus form <strong>{{ selectedForm?.name }}</strong>?
        Semua respon yang sudah masuk juga akan ikut terhapus.
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
import { Search, Plus, Edit, DataLine, Link, Delete } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const formList = ref<any[]>([])
const isLoading = ref(true)
const saving = ref(false)
const searchQuery = ref('')

const isDeleteModalVisible = ref(false)
const selectedForm = ref<any>(null)

const fetchData = async () => {
  isLoading.value = true
  try {
    const params: any = { limit: 100 }
    if (searchQuery.value) params.search = searchQuery.value

    const res = await ApiService.query('form-builder', { params })
    formList.value = res.data.data.data
  } catch (error) {
    ElMessage.error('Gagal mengambil daftar form')
  } finally {
    isLoading.value = false
  }
}

watch([searchQuery], () => fetchData())

const goToCreate = () => router.push('/dashboard/form-builder/create')
const goToEdit = (id: number) => router.push(`/dashboard/form-builder/${id}/edit`)
const goToResponses = (id: number) => router.push(`/dashboard/form-builder/${id}/responses`)

const copyLink = (slug: string) => {
  const url = `${window.location.origin}/form/${slug}`
  navigator.clipboard.writeText(url)
  ElMessage.success('Link form berhasil disalin!')
}

const openDeleteDialog = (form: any) => {
  selectedForm.value = form
  isDeleteModalVisible.value = true
}

const confirmDelete = async () => {
  if (!selectedForm.value) return
  saving.value = true
  try {
    await ApiService.delete(`form-builder/${selectedForm.value.id}`)
    ElMessage.success('Form berhasil dihapus')
    isDeleteModalVisible.value = false
    fetchData()
  } catch (error) {
    ElMessage.error('Gagal menghapus form')
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
  .form-title { margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #111827; }
  .form-desc { margin: 0 0 16px 0; font-size: 14px; color: #6B7280; flex-grow: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .form-meta { display: flex; gap: 8px; margin-bottom: 16px; }
  .card-actions { display: flex; gap: 8px; border-top: 1px solid #f3f4f6; padding-top: 16px; justify-content: flex-end; }
}
.empty-state { display: flex; justify-content: center; padding: 40px; }
</style>
