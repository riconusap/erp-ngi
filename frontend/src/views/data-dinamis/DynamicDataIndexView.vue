<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Data Dinamis</AppHeading>
        </div>
        <p class="subtitle">Pilih tabel dinamis untuk mengelola datanya</p>
      </div>
    </div>

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

    <el-row :gutter="24" v-loading="isLoading" class="cards-container">
      <el-col :span="8" v-for="table in tableData" :key="table.id" style="margin-bottom: 24px;">
        <el-card class="table-card" shadow="hover" @click="goToTable(table.slug)">
          <div class="card-content">
            <h3 class="table-title">{{ table.name }}</h3>
            <p class="table-desc">{{ table.description || 'Tidak ada deskripsi' }}</p>
            <div class="table-meta">
              <el-tag size="small" type="info">{{ table.columns?.length || 0 }} Kolom</el-tag>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    
    <div v-if="!isLoading && tableData.length === 0" class="empty-state">
      <el-empty description="Belum ada tabel dinamis" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Search } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const tableData = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')

const fetchData = async () => {
  isLoading.value = true
  try {
    const params: any = {
      limit: 100
    }
    if (searchQuery.value) params.search = searchQuery.value

    const res = await ApiService.query('kamus-data', { params })
    tableData.value = res.data.data.data
  } catch (error) {
    ElMessage.error('Gagal mengambil daftar tabel')
  } finally {
    isLoading.value = false
  }
}

watch([searchQuery], () => {
  fetchData()
})

const goToTable = (slug: string) => {
  router.push(`/dashboard/data-dinamis/${slug}`)
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

.subtitle {
  color: #6B7280;
  margin: 0;
  font-size: 14px;
}

.filter-section {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 24px;
  .search-input {
    width: 300px;
  }
}

.table-card {
  cursor: pointer;
  transition: all 0.2s ease;
  height: 100%;
  
  &:hover {
    transform: translateY(-4px);
    border-color: var(--el-color-primary);
  }
  
  .card-content {
    display: flex;
    flex-direction: column;
    height: 100%;
  }
  
  .table-title {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: #111827;
  }
  
  .table-desc {
    margin: 0 0 16px 0;
    font-size: 14px;
    color: #6B7280;
    flex-grow: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
}

.empty-state {
  display: flex;
  justify-content: center;
  padding: 40px;
}
</style>
