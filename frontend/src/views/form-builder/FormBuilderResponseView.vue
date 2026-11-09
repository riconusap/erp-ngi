<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">Respon: {{ form?.name || '...' }}</AppHeading>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/form-builder" class="back-link">Form Builder</router-link> 
          <span class="separator">/</span> 
          <span class="active">Respon</span>
        </div>
      </div>
      <div class="header-right">
        <el-button @click="router.push('/dashboard/form-builder')">Kembali</el-button>
        <el-button type="success" plain @click="exportData"><el-icon><Download /></el-icon> Export CSV</el-button>
      </div>
    </div>

    <el-card shadow="never" class="mt-4" v-loading="isLoading">
      <el-table :data="responses" style="width: 100%" v-if="form">
        <el-table-column type="index" label="No" width="60" />
        <el-table-column label="Waktu Submit" width="180">
          <template #default="scope">
            {{ new Date(scope.row.created_at).toLocaleString() }}
          </template>
        </el-table-column>
        
        <!-- Dinamis berdasarkan schema form -->
        <el-table-column 
          v-for="field in form.schema" 
          :key="field.key" 
          :label="field.label" 
          min-width="150" 
          show-overflow-tooltip
        >
          <template #default="scope">
            <template v-if="field.type === 'file' && scope.row.data[field.key]">
              <el-link :href="scope.row.data[field.key]" target="_blank" type="primary">Download File</el-link>
            </template>
            <template v-else>
              {{ scope.row.data[field.key] }}
            </template>
          </template>
        </el-table-column>
        
        <el-table-column label="Action" width="80" align="center" fixed="right">
          <template #default="scope">
            <el-button type="danger" link @click="deleteResponse(scope.row.id)"><el-icon><Delete /></el-icon></el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
        <el-pagination 
          layout="total, prev, pager, next" 
          :total="meta.total" 
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          @current-change="fetchResponses"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Download, Delete } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const route = useRoute()
const formId = route.params.id as string

const form = ref<any>(null)
const responses = ref<any[]>([])
const isLoading = ref(true)

const meta = reactive({ total: 0 })
const currentPage = ref(1)
const pageSize = ref(10)

const fetchFormAndResponses = async () => {
  isLoading.value = true
  try {
    const resForm = await ApiService.get(`form-builder/${formId}`)
    form.value = resForm.data.data
    form.value.schema = typeof form.value.schema === 'string' ? JSON.parse(form.value.schema) : (form.value.schema || [])

    await fetchResponses()
  } catch (error) {
    ElMessage.error('Gagal mengambil data respon')
  } finally {
    isLoading.value = false
  }
}

const fetchResponses = async () => {
  try {
    const res = await ApiService.query(`form-builder/${formId}/responses`, {
      params: { page: currentPage.value, limit: pageSize.value }
    })
    responses.value = res.data.data.data
    meta.total = res.data.data.total
  } catch (error) {
    ElMessage.error('Gagal memuat respon')
  }
}

const deleteResponse = (id: number) => {
  ElMessageBox.confirm('Yakin ingin menghapus respon ini?', 'Konfirmasi', {
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal',
    type: 'warning'
  }).then(async () => {
    try {
      await ApiService.delete(`form-responses/${id}`)
      ElMessage.success('Respon dihapus')
      fetchResponses()
    } catch {
      ElMessage.error('Gagal menghapus respon')
    }
  }).catch(() => {})
}

const exportData = () => {
  ElMessage.info('Fitur export CSV akan segera hadir!')
}

onMounted(() => {
  fetchFormAndResponses()
})
</script>

<style scoped lang="scss">
.page-container { display: flex; flex-direction: column; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.breadcrumb { font-size: 14px; color: #6B7280; .back-link { color: var(--el-color-primary); text-decoration: none; } .separator { margin: 0 8px; } .active { color: #374151; font-weight: 500; } }
</style>
