<template>
  <div class="page-container" v-loading="isLoading">
    <div v-if="!page && !isLoading" class="empty-state">
      <el-empty description="Halaman tidak ditemukan" />
    </div>

    <template v-else-if="page">
      <div class="page-header">
        <div class="header-left">
          <AppHeading level="1">
            <el-icon v-if="page.icon" class="mr-2"><component :is="page.icon" /></el-icon>
            {{ page.name }} 
            <span v-if="pageMode === 'new'" style="font-weight: 400; color: #6b7280; font-size: 16px;"> - Tambah Baru</span>
            <span v-else-if="pageMode === 'edit'" style="font-weight: 400; color: #6b7280; font-size: 16px;"> - Edit Data #{{ recordId }}</span>
          </AppHeading>
        </div>
      </div>

      <!-- Recursive Layout Renderer -->
      <div class="layout-container">
        <template v-for="block in page.schema.layout" :key="block.id">
          
          <!-- RENDER: TABS -->
          <el-tabs v-if="block.type === 'tabs'" type="border-card" class="mb-4">
            <el-tab-pane v-for="(tab, tIndex) in block.tabs" :key="tIndex" :label="tab.label">
              
              <template v-for="subBlock in tab.layout" :key="subBlock.id">
                
                <!-- RENDER SUB: TABLE -->
                <div v-if="subBlock.type === 'table'" class="mb-4">
                  <div class="table-actions mb-4">
                    <el-button 
                      v-for="(act, aIdx) in getHeaderActions(subBlock)" 
                      :key="aIdx" 
                      :type="act.type" 
                      @click="handleAction(act, null)"
                    >
                      {{ act.label }}
                    </el-button>
                  </div>
                  <el-table :data="tableData[subBlock.id] || []" border style="width: 100%" v-loading="tableLoading[subBlock.id]">
                    <el-table-column type="index" label="No" width="60" />
                    <el-table-column 
                      v-for="col in tableColumns[subBlock.id] || []" 
                      :key="col.key" 
                      :prop="subBlock.data_source_type === 'raw' ? col.key : 'data.' + col.key" 
                      :label="col.name" 
                      min-width="150" 
                      show-overflow-tooltip
                    />
                    <el-table-column label="Aksi" width="180" align="center" fixed="right" v-if="getRowActions(subBlock).length > 0">
                      <template #default="scope">
                        <el-button 
                          v-for="(act, aIdx) in getRowActions(subBlock)" 
                          :key="aIdx" 
                          :type="act.type" 
                          size="small" 
                          @click="handleAction(act, scope.row)"
                        >
                          {{ act.label }}
                        </el-button>
                      </template>
                    </el-table-column>
                  </el-table>
                </div>

                <!-- RENDER SUB: FORM -->
                <div v-if="subBlock.type === 'form'" class="mb-4 form-wrapper">
                  <el-form :model="formData[subBlock.id]" label-position="top" @submit.prevent>
                    <el-row :gutter="24">
                      <el-col :span="12" v-for="field in subBlock.fields" :key="field.key" class="mb-2">
                        <el-form-item :label="field.label">
                          <el-input v-if="field.type === 'text'" v-model="formData[subBlock.id][field.key]" />
                          <el-input v-else-if="field.type === 'textarea'" v-model="formData[subBlock.id][field.key]" type="textarea" :rows="3" />
                          <el-select v-else-if="field.type === 'select'" v-model="formData[subBlock.id][field.key]" style="width: 100%">
                            <el-option v-for="(opt, oIdx) in field.options" :key="oIdx" :label="opt" :value="opt" />
                          </el-select>
                        </el-form-item>
                      </el-col>
                    </el-row>
                    <el-button type="primary" @click="submitForm(subBlock)" :loading="formSaving[subBlock.id]">Simpan Data</el-button>
                  </el-form>
                </div>

              </template>
            </el-tab-pane>
          </el-tabs>

          <!-- RENDER ROOT: TABLE -->
          <el-card shadow="never" class="mb-4" v-if="block.type === 'table'">
            <div class="table-actions mb-4">
              <el-button 
                v-for="(act, aIdx) in getHeaderActions(block)" 
                :key="aIdx" 
                :type="act.type" 
                @click="handleAction(act, null)"
              >
                {{ act.label }}
              </el-button>
            </div>
            <el-table :data="tableData[block.id] || []" style="width: 100%" border v-loading="tableLoading[block.id]">
              <el-table-column type="index" label="No" width="60" />
              <el-table-column 
                v-for="col in tableColumns[block.id] || []" 
                :key="col.key" 
                :prop="block.data_source_type === 'raw' ? col.key : 'data.' + col.key" 
                :label="col.name" 
                min-width="150" 
                show-overflow-tooltip
              />
              <el-table-column label="Aksi" width="180" align="center" fixed="right" v-if="getRowActions(block).length > 0">
                <template #default="scope">
                  <el-button 
                    v-for="(act, aIdx) in getRowActions(block)" 
                    :key="aIdx" 
                    :type="act.type" 
                    size="small" 
                    @click="handleAction(act, scope.row)"
                  >
                    {{ act.label }}
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-card>

          <!-- RENDER ROOT: FORM -->
          <el-card shadow="never" class="mb-4" v-if="block.type === 'form'">
            <el-form :model="formData[block.id]" label-position="top" @submit.prevent>
              <el-row :gutter="24">
                <el-col :span="12" v-for="field in block.fields" :key="field.key" class="mb-2">
                  <el-form-item :label="field.label">
                    <el-input v-if="field.type === 'text'" v-model="formData[block.id][field.key]" />
                    <el-input v-else-if="field.type === 'textarea'" v-model="formData[block.id][field.key]" type="textarea" :rows="3" />
                    <el-select v-else-if="field.type === 'select'" v-model="formData[block.id][field.key]" style="width: 100%">
                      <el-option v-for="(opt, oIdx) in field.options" :key="oIdx" :label="opt" :value="opt" />
                    </el-select>
                  </el-form-item>
                </el-col>
              </el-row>
              <div class="submit-action mt-4">
                <el-button type="primary" @click="submitForm(block)" :loading="formSaving[block.id]">Simpan Data</el-button>
                <el-button @click="router.back()">Batal / Kembali</el-button>
              </div>
            </el-form>
          </el-card>

        </template>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import ApiService from '@/core/services/ApiService'

const route = useRoute()
const router = useRouter()

const slug = computed(() => route.params.slug as string)
const recordId = computed(() => route.params.id_data as string)
const pageMode = computed(() => {
  if (route.path.includes('/new/')) return 'new'
  if (route.path.includes('/edit/')) return 'edit'
  return 'view'
})

const page = ref<any>(null)
const isLoading = ref(true)

// Reactive Stores for components
const tableData = ref<Record<string, any[]>>({})
const tableColumns = ref<Record<string, any[]>>({})
const tableLoading = ref<Record<string, boolean>>({})

const formData = ref<Record<string, any>>({})
const formSaving = ref<Record<string, boolean>>({})

const tablesDict = ref<Record<number, string>>({}) // Map table_id to slug

const fetchPage = async () => {
  isLoading.value = true
  try {
    const res = await ApiService.get(`app-pages/${slug.value}/render`)
    const data = res.data.data
    data.schema = typeof data.schema === 'string' ? JSON.parse(data.schema) : (data.schema || {})
    page.value = data
    document.title = data.name

    // Setup initial data structures
    await initBlocks(data.schema.layout || [])

  } catch (error) {
    page.value = null
  } finally {
    isLoading.value = false
  }
}

const getTableSlug = async (id: number) => {
  if (tablesDict.value[id]) return tablesDict.value[id]
  try {
    const res = await ApiService.get(`kamus-data/${id}`)
    tablesDict.value[id] = res.data.data.slug
    return res.data.data.slug
  } catch (e) {
    return ''
  }
}

const initBlocks = async (blocks: any[]) => {
  for (const block of blocks) {
    if (block.type === 'table') {
      await setupTable(block)
    } else if (block.type === 'form') {
      await setupForm(block)
    } else if (block.type === 'tabs') {
      for (const tab of block.tabs) {
        await initBlocks(tab.layout || [])
      }
    }
  }
}

const setupTable = async (block: any) => {
  tableLoading.value[block.id] = true
  
  try {
    if (block.data_source_type === 'raw' && block.raw_query) {
      const resData = await ApiService.post(`app-pages/execute-query`, { query: block.raw_query })
      const rawData = resData.data.data
      tableData.value[block.id] = rawData
      if (rawData.length > 0) {
        tableColumns.value[block.id] = Object.keys(rawData[0]).map(k => ({ key: k, name: k }))
      } else {
        tableColumns.value[block.id] = []
      }
    } else if (block.dynamic_table_id) {
      const resSchema = await ApiService.get(`kamus-data/${block.dynamic_table_id}`)
      tableColumns.value[block.id] = typeof resSchema.data.data.schema === 'string' ? JSON.parse(resSchema.data.data.schema) : resSchema.data.data.schema
      const tblSlug = resSchema.data.data.slug
      tablesDict.value[block.dynamic_table_id] = tblSlug

      const reqData: any = { limit: 50 }
      if (block.filters && block.filters.length > 0) {
        reqData.filters = block.filters.filter((f: any) => f.key)
      }

      const resData = await ApiService.get(`dynamic-data/${tblSlug}`, { params: reqData })
      tableData.value[block.id] = resData.data.data.data
    }
  } catch (e) {
    console.error("Gagal memuat tabel", block.id)
  } finally {
    tableLoading.value[block.id] = false
  }
}

const setupForm = async (block: any) => {
  if (!block.dynamic_table_id) return
  
  const initData: any = {}
  ;(block.fields || []).forEach((f: any) => {
    initData[f.key] = ''
  })
  formData.value[block.id] = initData
  formSaving.value[block.id] = false

  // Hydrate if Edit Mode
  if (pageMode.value === 'edit' && recordId.value) {
    try {
      const tblSlug = await getTableSlug(block.dynamic_table_id)
      const res = await ApiService.get(`dynamic-data/${tblSlug}/${recordId.value}`)
      formData.value[block.id] = { ...initData, ...res.data.data.data }
    } catch (e) {
      console.error("Gagal menarik data lama untuk form", block.id)
    }
  }
}

const submitForm = async (block: any) => {
  formSaving.value[block.id] = true
  try {
    const tblSlug = await getTableSlug(block.dynamic_table_id)
    const payload = { data: formData.value[block.id] }
    
    if (pageMode.value === 'edit' && recordId.value) {
      await ApiService.put(`dynamic-data/${tblSlug}/${recordId.value}`, payload)
      ElMessage.success('Data berhasil diperbarui')
    } else {
      await ApiService.post(`dynamic-data/${tblSlug}`, payload)
      ElMessage.success('Data berhasil ditambahkan')
    }
    
    // Clear form if create mode
    if (pageMode.value === 'new') {
      const emptyData: any = {}
      ;(block.fields || []).forEach((f: any) => emptyData[f.key] = '')
      formData.value[block.id] = emptyData
    }
  } catch (e) {
    ElMessage.error('Gagal menyimpan data')
  } finally {
    formSaving.value[block.id] = false
  }
}

const getHeaderActions = (block: any) => {
  return (block.actions || []).filter((a: any) => a.label.toLowerCase() === 'tambah' || a.label.toLowerCase() === 'add' || a.label.toLowerCase() === 'baru')
}
const getRowActions = (block: any) => {
  return (block.actions || []).filter((a: any) => !getHeaderActions(block).includes(a))
}

const handleAction = (action: any, row: any) => {
  if (action.target_page_slug) {
    if (row && row.id) {
      router.push(`/dashboard/pages/edit/${action.target_page_slug}/${row.id}`)
    } else {
      router.push(`/dashboard/pages/new/${action.target_page_slug}`)
    }
  }
}

watch(
  () => route.fullPath,
  () => { fetchPage() }
)

onMounted(() => {
  fetchPage()
})
</script>

<style scoped lang="scss">
.page-container { display: flex; flex-direction: column; }
.page-header { display: flex; justify-content: space-between; margin-bottom: 24px; }
.mr-2 { margin-right: 8px; }
.mb-4 { margin-bottom: 16px; }
.mb-2 { margin-bottom: 8px; }
.mt-4 { margin-top: 16px; }

.layout-container { display: flex; flex-direction: column; }
.form-wrapper { padding: 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
</style>
