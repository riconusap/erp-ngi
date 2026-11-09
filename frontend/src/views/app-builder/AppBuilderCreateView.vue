<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">{{ isEdit ? 'Edit Halaman' : 'Buat Halaman Baru' }}</AppHeading>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/app-builder" class="back-link">App Builder</router-link> 
          <span class="separator">/</span> 
          <span class="active">{{ isEdit ? 'Edit' : 'Buat' }}</span>
        </div>
      </div>
      <div class="header-right">
        <el-button @click="router.push('/dashboard/app-builder')">Batal</el-button>
        <el-button type="primary" @click="submitPage" :loading="saving">Simpan Halaman</el-button>
      </div>
    </div>

    <el-row :gutter="24" v-loading="isLoading">
      <!-- PANEL PENGATURAN -->
      <el-col :span="6">
        <el-card shadow="never" class="settings-card mb-4">
          <template #header><div class="card-header">Pengaturan Menu</div></template>
          <el-form label-position="top">
            <el-form-item label="Nama Halaman (Menu)" required>
              <el-input v-model="page.name" placeholder="Misal: Data Klien" />
            </el-form-item>
            <el-form-item label="Icon (Element Plus)">
              <el-input v-model="page.icon" placeholder="Misal: User, Document, dll" />
            </el-form-item>
            <el-form-item label="Status">
              <el-switch v-model="page.is_active" active-text="Aktif" inactive-text="Nonaktif" />
            </el-form-item>
          </el-form>
        </el-card>

        <el-card shadow="never" class="settings-card">
          <template #header><div class="card-header">Tambah Komponen</div></template>
          <div class="field-buttons">
            <el-button plain @click="addBlock(page.schema.layout, 'table')"><el-icon><Grid /></el-icon> Canvas Tabel</el-button>
            <el-button plain @click="addBlock(page.schema.layout, 'form')"><el-icon><Document /></el-icon> Canvas Form</el-button>
            <el-button plain @click="addBlock(page.schema.layout, 'tabs')"><el-icon><Folder /></el-icon> Tab Container</el-button>
          </div>
        </el-card>
      </el-col>

      <!-- AREA DESAIN LAYOUT -->
      <el-col :span="18">
        <el-card shadow="never" class="preview-card" style="min-height: 500px">
          <template #header>
            <div class="card-header">
              <span>Desain Layout Halaman</span>
            </div>
          </template>

          <div v-if="page.schema.layout.length === 0" class="empty-layout">
            <el-empty description="Halaman masih kosong. Tambahkan komponen dari panel kiri." />
          </div>

          <div class="layout-builder">
            <div v-for="(block, bIndex) in page.schema.layout" :key="block.id" class="layout-block">
              <div class="block-header">
                <div class="block-title">
                  <el-icon><component :is="getBlockIcon(block.type)" /></el-icon> 
                  {{ getBlockTitle(block.type) }}
                </div>
                <el-button type="danger" link @click="page.schema.layout.splice(bIndex, 1)"><el-icon><Delete /></el-icon></el-button>
              </div>

              <div v-if="block.type === 'table'" class="block-body">
                <div style="margin-bottom: 16px;">
                  <span style="font-weight: 500; font-size: 14px; margin-right: 12px;">Tipe Sumber Data:</span>
                  <el-radio-group v-model="block.data_source_type">
                    <el-radio label="dynamic">Kamus Data</el-radio>
                    <el-radio label="raw">Raw SQL Query</el-radio>
                  </el-radio-group>
                </div>

                <el-form-item v-if="block.data_source_type !== 'raw'" label="Sumber Data Tabel">
                  <el-select v-model="block.dynamic_table_id" placeholder="Pilih Kamus Data" style="width: 100%;">
                    <el-option v-for="t in tables" :key="t.id" :label="t.name" :value="t.id" />
                  </el-select>
                </el-form-item>

                <el-form-item v-if="block.data_source_type === 'raw'" label="Raw SQL Query (Hanya SELECT)">
                  <el-input v-model="block.raw_query" type="textarea" :rows="3" placeholder="SELECT * FROM users WHERE status = 'aktif'" />
                  <el-button type="success" size="small" plain class="mt-2" @click="testRawQuery(block.raw_query)">
                    Cek Query
                  </el-button>
                </el-form-item>

                <!-- Filter Data Section (Only for Kamus Data) -->
                <template v-if="block.data_source_type !== 'raw'">
                  <p class="section-subtitle">Custom Query / Filter (Opsional)</p>
                  <div v-for="(filter, fIdx) in block.filters" :key="fIdx" class="action-item">
                    <el-row :gutter="8">
                      <el-col :span="8">
                        <el-input v-model="filter.key" placeholder="Key (DB Column)" size="small" />
                      </el-col>
                      <el-col :span="6">
                        <el-select v-model="filter.operator" placeholder="Operator" size="small" style="width: 100%">
                          <el-option label="Sama Dengan (=)" value="=" />
                          <el-option label="Tidak Sama (!=)" value="!=" />
                          <el-option label="Lebih Besar (>)" value=">" />
                          <el-option label="Lebih Kecil (<)" value="<" />
                          <el-option label="Mengandung (LIKE)" value="ilike" />
                        </el-select>
                      </el-col>
                      <el-col :span="8">
                        <el-input v-model="filter.value" placeholder="Nilai (Value)" size="small" />
                      </el-col>
                      <el-col :span="2">
                        <el-button type="danger" link @click="block.filters.splice(fIdx, 1)"><el-icon><Delete /></el-icon></el-button>
                      </el-col>
                    </el-row>
                  </div>
                  <el-button type="primary" plain size="small" class="mt-2" @click="block.filters.push({key:'', operator:'=', value:''})">
                    Tambah Filter
                  </el-button>
                </template>
                
                <p class="section-subtitle mt-4">Tombol Aksi (Misal: Tambah, Edit)</p>
                <div v-for="(action, aIndex) in block.actions" :key="aIndex" class="action-item">
                  <el-row :gutter="8">
                    <el-col :span="6"><el-input v-model="action.label" placeholder="Label" size="small" /></el-col>
                    <el-col :span="6">
                      <el-select v-model="action.type" placeholder="Tipe" size="small">
                        <el-option label="Primary" value="primary" />
                        <el-option label="Success" value="success" />
                        <el-option label="Warning" value="warning" />
                      </el-select>
                    </el-col>
                    <el-col :span="10">
                      <el-select v-model="action.target_page_slug" placeholder="Target Halaman (Slug)" size="small" style="width: 100%;">
                        <el-option v-for="p in allPages" :key="p.slug" :label="p.name" :value="p.slug" />
                      </el-select>
                    </el-col>
                    <el-col :span="2">
                      <el-button type="danger" link @click="block.actions.splice(aIndex, 1)"><el-icon><Delete /></el-icon></el-button>
                    </el-col>
                  </el-row>
                </div>
                <el-button type="primary" plain size="small" class="mt-2" @click="block.actions.push({label:'Aksi', type:'primary', target_page_slug:''})">
                  Tambah Aksi
                </el-button>
              </div>

              <!-- BLOCK: FORM -->
              <div v-else-if="block.type === 'form'" class="block-body">
                <el-form-item label="Sumber Data Form">
                  <el-select v-model="block.dynamic_table_id" placeholder="Pilih Kamus Data" style="width: 100%;">
                    <el-option v-for="t in tables" :key="t.id" :label="t.name" :value="t.id" />
                  </el-select>
                </el-form-item>
                
                <p class="section-subtitle">Desain Input Form</p>
                <div class="field-buttons" style="flex-direction: row; margin-bottom: 12px;">
                  <el-button size="small" @click="addFormField(block, 'text')">Teks Singkat</el-button>
                  <el-button size="small" @click="addFormField(block, 'textarea')">Teks Panjang</el-button>
                  <el-button size="small" @click="addFormField(block, 'select')">Select Option</el-button>
                  <el-button size="small" @click="addFormField(block, 'file')">File Upload</el-button>
                </div>

                <div v-for="(field, fIndex) in block.fields" :key="fIndex" class="form-field-item">
                  <el-row :gutter="12" align="middle">
                    <el-col :span="8">
                      <el-input v-model="field.label" placeholder="Label Pertanyaan" size="small" />
                    </el-col>
                    <el-col :span="6">
                      <el-input v-model="field.key" placeholder="Key (DB)" size="small" />
                    </el-col>
                    <el-col :span="6">
                      <el-checkbox v-model="field.required" size="small">Wajib Diisi</el-checkbox>
                    </el-col>
                    <el-col :span="4" style="text-align: right">
                      <el-tag size="small">{{ field.type }}</el-tag>
                      <el-button type="danger" link @click="block.fields.splice(fIndex, 1)"><el-icon><Delete /></el-icon></el-button>
                    </el-col>
                  </el-row>
                  <!-- Select options configuration -->
                  <div v-if="field.type === 'select'" style="margin-top: 8px">
                    <el-input v-model="field.optionsStr" size="small" placeholder="Pisahkan opsi dengan koma. Misal: Laki-laki, Perempuan" @change="updateOptions(field)" />
                  </div>
                </div>
              </div>

              <!-- BLOCK: TABS -->
              <div v-else-if="block.type === 'tabs'" class="block-body">
                <el-tabs type="border-card">
                  <el-tab-pane v-for="(tab, tIndex) in block.tabs" :key="tIndex" :label="tab.label">
                    <template #label>
                      <div style="display: flex; align-items: center; gap: 8px;">
                        <el-input v-model="tab.label" size="small" style="width: 120px;" @click.stop />
                        <el-button type="danger" link style="padding: 0" @click="block.tabs.splice(tIndex, 1)"><el-icon><Close /></el-icon></el-button>
                      </div>
                    </template>
                    
                    <div class="field-buttons" style="flex-direction: row; margin-bottom: 16px;">
                      <span style="font-size: 13px; color: #6b7280; margin-right: 8px;">Isi Tab ini dengan:</span>
                      <el-button size="small" plain @click="addBlock(tab.layout, 'table')">Tabel</el-button>
                      <el-button size="small" plain @click="addBlock(tab.layout, 'form')">Form</el-button>
                    </div>

                    <!-- RECURSIVE RENDER (Simplified for Tab level) -->
                    <div v-for="(subBlock, sbIndex) in tab.layout" :key="subBlock.id" class="layout-block sub-block">
                      <div class="block-header">
                        <div class="block-title"><el-icon><component :is="getBlockIcon(subBlock.type)" /></el-icon> {{ getBlockTitle(subBlock.type) }}</div>
                        <el-button type="danger" link @click="tab.layout.splice(sbIndex, 1)"><el-icon><Delete /></el-icon></el-button>
                      </div>
                      
                      <!-- SUB BLOCK: TABLE -->
                      <div v-if="subBlock.type === 'table'" class="block-body">
                        <div style="margin-bottom: 16px;">
                          <span style="font-weight: 500; font-size: 13px; margin-right: 12px;">Sumber:</span>
                          <el-radio-group v-model="subBlock.data_source_type" size="small">
                            <el-radio label="dynamic">Kamus Data</el-radio>
                            <el-radio label="raw">Raw SQL Query</el-radio>
                          </el-radio-group>
                        </div>

                        <el-form-item v-if="subBlock.data_source_type !== 'raw'" label="Sumber Data Tabel">
                          <el-select v-model="subBlock.dynamic_table_id" placeholder="Pilih Kamus Data" size="small" style="width: 100%;">
                            <el-option v-for="t in tables" :key="t.id" :label="t.name" :value="t.id" />
                          </el-select>
                        </el-form-item>

                        <el-form-item v-if="subBlock.data_source_type === 'raw'" label="Raw SQL Query (SELECT)">
                          <el-input v-model="subBlock.raw_query" type="textarea" :rows="2" placeholder="SELECT * FROM table" />
                          <el-button type="success" size="small" plain class="mt-2" @click="testRawQuery(subBlock.raw_query)">
                            Cek Query
                          </el-button>
                        </el-form-item>

                        <!-- Filter Data Section for subBlock -->
                        <template v-if="subBlock.data_source_type !== 'raw'">
                          <p class="section-subtitle mt-2">Custom Query / Filter</p>
                          <div v-for="(filter, fIdx) in subBlock.filters" :key="fIdx" class="action-item">
                            <el-row :gutter="8">
                              <el-col :span="8">
                                <el-input v-model="filter.key" placeholder="Key (DB Column)" size="small" />
                              </el-col>
                              <el-col :span="6">
                                <el-select v-model="filter.operator" placeholder="Operator" size="small" style="width: 100%">
                                  <el-option label="(=)" value="=" />
                                  <el-option label="(!=)" value="!=" />
                                  <el-option label="(>)" value=">" />
                                  <el-option label="(<)" value="<" />
                                  <el-option label="(LIKE)" value="ilike" />
                                </el-select>
                              </el-col>
                              <el-col :span="8">
                                <el-input v-model="filter.value" placeholder="Nilai (Value)" size="small" />
                              </el-col>
                              <el-col :span="2">
                                <el-button type="danger" link @click="subBlock.filters.splice(fIdx, 1)"><el-icon><Delete /></el-icon></el-button>
                              </el-col>
                            </el-row>
                          </div>
                          <el-button type="primary" plain size="small" class="mt-2" @click="subBlock.filters.push({key:'', operator:'=', value:''})">
                            Tambah Filter
                          </el-button>
                        </template>
                      </div>
                      
                      <!-- SUB BLOCK: FORM -->
                      <div v-else-if="subBlock.type === 'form'" class="block-body">
                        <el-form-item label="Sumber Data Form">
                          <el-select v-model="subBlock.dynamic_table_id" placeholder="Pilih Kamus Data" size="small" style="width: 100%;">
                            <el-option v-for="t in tables" :key="t.id" :label="t.name" :value="t.id" />
                          </el-select>
                        </el-form-item>
                        <div class="field-buttons" style="flex-direction: row; margin-bottom: 8px;">
                          <el-button size="small" @click="addFormField(subBlock, 'text')">Teks</el-button>
                          <el-button size="small" @click="addFormField(subBlock, 'textarea')">Teks Panjang</el-button>
                        </div>
                        <div v-for="(field, fIndex) in subBlock.fields" :key="fIndex" class="form-field-item">
                          <el-row :gutter="12">
                            <el-col :span="10"><el-input v-model="field.label" placeholder="Label" size="small" /></el-col>
                            <el-col :span="10"><el-input v-model="field.key" placeholder="Key" size="small" /></el-col>
                            <el-col :span="4"><el-button type="danger" link @click="subBlock.fields.splice(fIndex, 1)"><el-icon><Delete /></el-icon></el-button></el-col>
                          </el-row>
                        </div>
                      </div>
                    </div>

                  </el-tab-pane>
                  <el-button size="small" @click="block.tabs.push({label: 'Tab Baru', layout: []})" style="margin: 8px">
                    + Tambah Tab
                  </el-button>
                </el-tabs>
              </div>

            </div>
          </div>

        </el-card>
      </el-col>
    </el-row>

    <!-- Query Test Dialog -->
    <el-dialog v-model="queryTestDialogVisible" title="Hasil Test Query" width="70%">
      <div v-if="queryTestResults.length === 0" class="empty-layout">
        <el-empty description="Tidak ada data atau hasil kosong" />
      </div>
      <el-table v-else :data="queryTestResults" border height="400">
        <el-table-column type="index" width="60" />
        <el-table-column 
          v-for="col in queryTestColumns" 
          :key="col" 
          :prop="col" 
          :label="col" 
          min-width="120"
          show-overflow-tooltip
        />
      </el-table>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="queryTestDialogVisible = false">Tutup</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Grid, Document, Folder, EditPen, Select, Delete, Plus, Remove, Upload, Rank, Close } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const route = useRoute()
const pageId = route.params.id as string
const isEdit = computed(() => !!pageId)

const isLoading = ref(false)
const saving = ref(false)

const queryTestDialogVisible = ref(false)
const queryTestResults = ref<any[]>([])
const queryTestColumns = ref<string[]>([])

const testRawQuery = async (query: string) => {
  if (!query) {
    ElMessage.warning('Query tidak boleh kosong')
    return
  }
  
  try {
    const res = await ApiService.post('app-pages/execute-query', { query })
    const data = res.data.data
    queryTestResults.value = data
    if (data.length > 0) {
      queryTestColumns.value = Object.keys(data[0])
    } else {
      queryTestColumns.value = []
    }
    queryTestDialogVisible.value = true
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || 'Gagal mengeksekusi query')
  }
}

const page = ref({
  name: '',
  icon: 'Document',
  is_active: true,
  schema: {
    layout: [] as any[]
  } as any
})

const tables = ref<any[]>([])
const allPages = ref<any[]>([])

const fetchDependencies = async () => {
  try {
    const res = await ApiService.query('kamus-data', { params: { limit: 100 } })
    tables.value = res.data.data.data

    const resPages = await ApiService.query('app-pages', { params: { limit: 100 } })
    allPages.value = resPages.data.data.data
  } catch (error) {
    console.error('Gagal mengambil referensi')
  }
}

const fetchDetail = async () => {
  isLoading.value = true
  try {
    const res = await ApiService.get(`app-pages/${pageId}`)
    const data = res.data.data
    page.value.name = data.name
    page.value.icon = data.icon
    page.value.is_active = data.is_active
    
    let schemaObj = typeof data.schema === 'string' ? JSON.parse(data.schema) : (data.schema || {})
    if (!schemaObj.layout) schemaObj.layout = []
    
    // Convert options array to string for edit binding
    const processFields = (fields: any[]) => {
      fields.forEach(f => {
        if (f.type === 'select' && Array.isArray(f.options)) {
          f.optionsStr = f.options.join(', ')
        }
      })
    }
    
    schemaObj.layout.forEach((block: any) => {
      if (block.type === 'form') processFields(block.fields || [])
      if (block.type === 'tabs') {
        block.tabs.forEach((t: any) => {
          t.layout.forEach((sb: any) => {
            if (sb.type === 'form') processFields(sb.fields || [])
          })
        })
      }
    })

    page.value.schema = schemaObj
  } catch (error) {
    ElMessage.error('Gagal mengambil data halaman')
    router.push('/dashboard/app-builder')
  } finally {
    isLoading.value = false
  }
}

const getBlockIcon = (type: string) => {
  if (type === 'table') return 'Grid'
  if (type === 'form') return 'Document'
  return 'Folder'
}

const getBlockTitle = (type: string) => {
  if (type === 'table') return 'Canvas Tabel Data'
  if (type === 'form') return 'Canvas Form Input'
  return 'Tab Container'
}

const addBlock = (targetArray: any[], type: string) => {
  const block: any = {
    id: `block_${Date.now()}`,
    type
  }
  if (type === 'table') {
    block.data_source_type = 'dynamic'
    block.dynamic_table_id = null
    block.raw_query = ''
    block.actions = []
    block.filters = []
  } else if (type === 'form') {
    block.dynamic_table_id = null
    block.fields = []
  } else if (type === 'tabs') {
    block.tabs = [
      { label: 'Tab 1', layout: [] }
    ]
  }
  targetArray.push(block)
}

const addFormField = (block: any, type: string) => {
  if (!block.fields) block.fields = []
  const newField: any = {
    type,
    label: '',
    key: `key_${Date.now()}`,
    required: false
  }
  if (type === 'select') {
    newField.optionsStr = 'Opsi 1, Opsi 2'
    newField.options = ['Opsi 1', 'Opsi 2']
  }
  block.fields.push(newField)
}

const updateOptions = (field: any) => {
  if (field.optionsStr) {
    field.options = field.optionsStr.split(',').map((s: string) => s.trim())
  }
}

const submitPage = async () => {
  if (!page.value.name) {
    ElMessage.warning('Nama Halaman wajib diisi')
    return
  }
  
  saving.value = true
  try {
    if (isEdit.value) {
      await ApiService.put(`app-pages/${pageId}`, page.value)
      ElMessage.success('Halaman berhasil diupdate')
    } else {
      await ApiService.post('app-pages', page.value)
      ElMessage.success('Halaman berhasil dibuat. Silakan Muat Ulang (Refresh) Browser untuk melihat menu di sidebar.')
    }
    router.push('/dashboard/app-builder')
  } catch (error) {
    ElMessage.error('Gagal menyimpan halaman')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchDependencies()
  if (isEdit.value) {
    fetchDetail()
  }
})
</script>

<style scoped lang="scss">
.page-container { display: flex; flex-direction: column; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.breadcrumb { font-size: 14px; color: #6B7280; .back-link { color: var(--el-color-primary); text-decoration: none; } .separator { margin: 0 8px; } .active { color: #374151; font-weight: 500; } }
.card-header { font-weight: 600; font-size: 16px; }
.mb-4 { margin-bottom: 16px; }
.mt-2 { margin-top: 8px; }

.field-buttons { display: flex; flex-direction: column; gap: 8px; .el-button { margin: 0; justify-content: flex-start; } }

.empty-layout { display: flex; justify-content: center; padding: 40px; background: #f9fafb; border-radius: 8px; border: 1px dashed #d1d5db; }

.layout-builder { display: flex; flex-direction: column; gap: 16px; }
.layout-block {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.sub-block {
  margin-bottom: 12px;
  box-shadow: none;
}
.block-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  border-radius: 8px 8px 0 0;
  
  .block-title {
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
}
.block-body { padding: 16px; }

.section-subtitle { font-size: 13px; font-weight: 600; color: #4b5563; margin-top: 16px; margin-bottom: 8px; border-bottom: 1px solid #f3f4f6; padding-bottom: 4px; }
.action-item { border: 1px solid #e5e7eb; padding: 8px; border-radius: 4px; margin-bottom: 8px; background: #fafafa; }
.form-field-item { border: 1px solid #e5e7eb; padding: 8px; border-radius: 4px; margin-bottom: 8px; background: #fafafa; }
</style>
