<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">{{ isEdit ? 'Edit Form' : 'Buat Form Baru' }}</AppHeading>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/form-builder" class="back-link">Form Builder</router-link> 
          <span class="separator">/</span> 
          <span class="active">{{ isEdit ? 'Edit' : 'Buat' }}</span>
        </div>
      </div>
      <div class="header-right">
        <el-button @click="router.push('/dashboard/form-builder')">Batal</el-button>
        <el-button type="primary" @click="submitForm" :loading="saving">Simpan Form</el-button>
      </div>
    </div>

    <el-row :gutter="24" v-loading="isLoading">
      <!-- Pengaturan Form Utama -->
      <el-col :span="8">
        <el-card shadow="never" class="settings-card">
          <template #header><div class="card-header">Pengaturan Form</div></template>
          <el-form label-position="top">
            <el-form-item label="Nama Form" required>
              <el-input v-model="form.name" placeholder="Misal: Survei Kepuasan" />
            </el-form-item>
            <el-form-item label="Target Tabel Penyimpanan">
              <el-select v-model="form.dynamic_table_id" placeholder="Pilih Tabel (Opsional)" clearable style="width: 100%;">
                <el-option v-for="table in tables" :key="table.id" :label="table.name" :value="table.id" />
              </el-select>
              <div class="hint-text" style="margin-top: 4px; font-size: 12px; color: #6b7280;">Jika dipilih, respon akan otomatis tersimpan ke tabel ini di Data Dinamis.</div>
            </el-form-item>
            <el-form-item label="Deskripsi">
              <el-input type="textarea" :rows="3" v-model="form.description" placeholder="Deskripsi form..." />
            </el-form-item>
            <el-form-item label="Status">
              <el-switch v-model="form.is_active" active-text="Aktif" inactive-text="Nonaktif" />
            </el-form-item>
          </el-form>
        </el-card>

        <el-card shadow="never" class="mt-4 settings-card">
          <template #header><div class="card-header">Tambah Element Baru</div></template>
          <div class="field-buttons">
            <el-button plain @click="addElement('text')"><el-icon><EditPen /></el-icon> Teks Singkat</el-button>
            <el-button plain @click="addElement('textarea')"><el-icon><Document /></el-icon> Teks Panjang</el-button>
            <el-button plain @click="addElement('select')"><el-icon><Select /></el-icon> Select Option</el-button>
            <el-button plain @click="addElement('file')"><el-icon><Upload /></el-icon> File Upload</el-button>
          </div>
        </el-card>
      </el-col>

      <!-- Area Desain Form -->
      <el-col :span="16">
        <el-card shadow="never" class="preview-card">
          <template #header>
            <div class="card-header">
              <span>Desain Form ({{ form.schema.length }} Element)</span>
            </div>
          </template>
          
          <div v-if="form.schema.length === 0" class="empty-schema">
            <el-empty description="Silakan tambah element dari panel kiri" :image-size="100" />
          </div>

          <div class="schema-list" v-else>
            <div 
              v-for="(element, index) in form.schema" 
              :key="element.key" 
              class="field-item"
              draggable="true"
              @dragstart="onDragStart($event, index)"
              @dragover.prevent="onDragOver($event, index)"
              @drop="onDrop($event, index)"
              @dragenter="dragOverIndex = index"
              @dragleave="dragOverIndex = null"
              :class="{ 'drag-over': dragOverIndex === index }"
            >
              <div class="field-header">
                <div class="drag-handle">
                  <el-icon class="drag-icon"><Rank /></el-icon>
                  <span class="field-type-badge">{{ getElementTypeLabel(element.type) }}</span>
                </div>
                <el-button type="danger" link @click="removeElement(index)"><el-icon><Delete /></el-icon> Hapus</el-button>
              </div>
              
              <el-row :gutter="16">
                <el-col :span="12">
                  <el-form-item label="Label Pertanyaan">
                    <el-input v-model="element.label" placeholder="Pertanyaan..." @input="generateKey(element)" />
                  </el-form-item>
                </el-col>
                <el-col :span="6">
                  <el-form-item label="Variabel (Key)">
                    <el-input v-model="element.key" placeholder="key_variabel" />
                  </el-form-item>
                </el-col>
                <el-col :span="6" class="flex-bottom">
                  <el-checkbox v-model="element.required" label="Wajib Diisi" border />
                </el-col>
              </el-row>

              <!-- Opsi Tambahan untuk Select Option -->
              <div v-if="element.type === 'select'" class="options-container">
                <p style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">Pilihan Dropdown:</p>
                <div v-for="(opt, optIdx) in element.options" :key="optIdx" class="option-row">
                  <el-input v-model="element.options[optIdx]" size="small" placeholder="Opsi..." />
                  <el-button type="danger" link @click="element.options.splice(optIdx, 1)"><el-icon><Remove /></el-icon></el-button>
                </div>
                <el-button type="primary" link size="small" @click="element.options.push(`Pilihan ${element.options.length + 1}`)"><el-icon><Plus /></el-icon> Tambah Pilihan</el-button>
              </div>

              <!-- Opsi Tambahan untuk File Upload -->
              <div v-if="element.type === 'file'" class="options-container">
                <el-row :gutter="16">
                  <el-col :span="12">
                    <el-form-item label="Tipe File Diterima">
                      <el-select v-model="element.allowedTypes" multiple placeholder="Pilih tipe file" style="width: 100%;">
                        <el-option label="Gambar (JPG, PNG)" value="image/*" />
                        <el-option label="Dokumen PDF" value="application/pdf" />
                        <el-option label="Word/Excel" value="application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />
                        <el-option label="Semua File" value="*" />
                      </el-select>
                    </el-form-item>
                  </el-col>
                  <el-col :span="12">
                    <el-form-item label="Ukuran Maksimal (MB)">
                      <el-input-number v-model="element.maxSize" :min="1" :max="50" style="width: 100%;" />
                      <div class="hint-text">Maksimal 50 MB</div>
                    </el-form-item>
                  </el-col>
                </el-row>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { EditPen, Document, Select, Delete, Plus, Remove, Upload, Rank } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const route = useRoute()
const formId = route.params.id as string
const isEdit = computed(() => !!formId)

const isLoading = ref(false)
const saving = ref(false)

const form = ref({
  name: '',
  description: '',
  is_active: true,
  dynamic_table_id: null as number | null,
  schema: [] as any[]
})

const tables = ref<any[]>([])

const fetchTables = async () => {
  try {
    const res = await ApiService.query('kamus-data', { params: { limit: 100 } })
    tables.value = res.data.data.data
  } catch (error) {
    console.error('Gagal mengambil daftar tabel dinamis')
  }
}

const fetchDetail = async () => {
  isLoading.value = true
  try {
    const res = await ApiService.get(`form-builder/${formId}`)
    const data = res.data.data
    form.value.name = data.name
    form.value.description = data.description
    form.value.is_active = data.is_active
    form.value.dynamic_table_id = data.dynamic_table_id
    form.value.schema = typeof data.schema === 'string' ? JSON.parse(data.schema) : (data.schema || [])
  } catch (error) {
    ElMessage.error('Gagal mengambil data form')
    router.push('/dashboard/form-builder')
  } finally {
    isLoading.value = false
  }
}

const addElement = (type: string) => {
  const newElement: any = {
    type,
    label: 'Pertanyaan Baru',
    key: `element_${Date.now()}`,
    required: false
  }
  if (type === 'select') {
    newElement.options = ['Pilihan 1', 'Pilihan 2']
  } else if (type === 'file') {
    newElement.allowedTypes = ['*']
    newElement.maxSize = 10 // default 10MB
  }
  form.value.schema.push(newElement)
}

const removeElement = (index: number) => {
  form.value.schema.splice(index, 1)
}

const generateKey = (element: any) => {
  if (element.label && !isEdit.value) { // Don't auto-generate on edit to avoid losing data map
    let key = element.label.toLowerCase().replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_')
    if (key.endsWith('_')) key = key.slice(0, -1)
    element.key = key
  }
}

const getElementTypeLabel = (type: string) => {
  const map: any = {
    text: 'Teks Singkat',
    textarea: 'Teks Panjang',
    select: 'Select Option',
    file: 'File Upload'
  }
  return map[type] || type
}

// Drag and Drop Logic
const draggedIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)

const onDragStart = (e: DragEvent, index: number) => {
  draggedIndex.value = index
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.dropEffect = 'move'
  }
}

const onDragOver = (e: DragEvent, index: number) => {
  e.preventDefault()
  if (draggedIndex.value === null || draggedIndex.value === index) return
  dragOverIndex.value = index
}

const onDrop = (e: DragEvent, dropIndex: number) => {
  if (draggedIndex.value !== null && draggedIndex.value !== dropIndex) {
    const itemToMove = form.value.schema.splice(draggedIndex.value, 1)[0]
    form.value.schema.splice(dropIndex, 0, itemToMove)
  }
  draggedIndex.value = null
  dragOverIndex.value = null
}

const submitForm = async () => {
  if (!form.value.name) {
    ElMessage.warning('Nama form wajib diisi')
    return
  }
  
  saving.value = true
  try {
    if (isEdit.value) {
      await ApiService.put(`form-builder/${formId}`, form.value)
      ElMessage.success('Form berhasil diupdate')
    } else {
      await ApiService.post('form-builder', form.value)
      ElMessage.success('Form berhasil dibuat')
    }
    router.push('/dashboard/form-builder')
  } catch (error) {
    ElMessage.error('Gagal menyimpan form')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchTables()
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
.field-buttons { display: flex; flex-direction: column; gap: 8px; .el-button { margin: 0; justify-content: flex-start; } }
.schema-list { display: flex; flex-direction: column; gap: 16px; }
.field-item { 
  border: 1px solid #e5e7eb; 
  border-radius: 8px; 
  padding: 16px; 
  background: #fafafa; 
  transition: all 0.2s; 
  cursor: grab;
  &:active { cursor: grabbing; }
  &:hover { border-color: var(--el-color-primary); }
  &.drag-over { border: 2px dashed var(--el-color-primary); opacity: 0.8; }
}
.field-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #e5e7eb; }
.drag-handle { display: flex; align-items: center; gap: 8px; }
.drag-icon { cursor: grab; color: #9ca3af; font-size: 18px; }
.field-type-badge { background: #e0e7ff; color: #4338ca; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.flex-bottom { display: flex; align-items: flex-end; padding-bottom: 18px; }
.options-container { margin-top: 8px; padding: 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; }
.option-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.hint-text { font-size: 11px; color: #6b7280; margin-top: 4px; }
</style>
