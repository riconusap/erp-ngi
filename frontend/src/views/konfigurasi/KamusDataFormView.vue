<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">{{ isEdit ? 'Edit Kamus Data' : 'Tambah Kamus Data' }}</AppHeading>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/konfigurasi/kamus-data" class="back-link">List Kamus Data</router-link> 
          <span class="separator">/</span> 
          <span class="active">{{ isEdit ? 'Edit' : 'Tambah' }}</span>
        </div>
      </div>
    </div>

    <el-card class="box-card mt-4">
      <el-form :model="form" :rules="rules" ref="formRef" label-position="top" @submit.prevent>
        <el-form-item label="Nama Tabel" prop="name">
          <el-input v-model="form.name" placeholder="Cth: Data Pegawai" size="large" />
        </el-form-item>
        
        <el-form-item label="Deskripsi" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="Deskripsi tabel ini" />
        </el-form-item>

        <div style="margin-top: 32px;">
          <el-tabs v-model="activeTab" class="kamus-tabs">
            <el-tab-pane label="Struktur Kolom" name="struktur">
              <div style="margin-bottom: 16px; display: flex; justify-content: flex-end;">
                <el-button type="danger" plain size="small" @click="addColumn">
                  <el-icon><Plus /></el-icon> Tambah Kolom
                </el-button>
              </div>

              <div v-for="(col, index) in form.columns" :key="index" class="column-card">
                <div class="column-header">
                  <span class="column-title">Kolom {{ index + 1 }}</span>
                  <el-button type="danger" text @click="removeColumn(index)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </div>
                <el-row :gutter="16">
                  <el-col :span="12">
                    <el-form-item :label="'Nama Kolom'" :prop="'columns.' + index + '.name'" :rules="{ required: true, message: 'Nama kolom wajib diisi', trigger: 'blur' }">
                      <el-input v-model="col.name" placeholder="Cth: Nama Lengkap" />
                    </el-form-item>
                  </el-col>
                  <el-col :span="12">
                    <el-form-item :label="'Tipe Data'" :prop="'columns.' + index + '.type'" :rules="{ required: true, message: 'Tipe data wajib diisi', trigger: 'change' }">
                      <el-select v-model="col.type" placeholder="Pilih Tipe">
                        <el-option label="Teks Pendek (String)" value="string" />
                        <el-option label="Teks Panjang (Text)" value="text" />
                        <el-option label="Angka (Integer)" value="integer" />
                        <el-option label="Tanggal (Date)" value="date" />
                        <el-option label="Ya/Tidak (Boolean)" value="boolean" />
                      </el-select>
                    </el-form-item>
                  </el-col>
                </el-row>
                <el-form-item>
                  <el-checkbox v-model="col.is_required">Wajib diisi (Required)</el-checkbox>
                  <el-checkbox v-model="col.is_sensitive" style="margin-left: 16px;">Data Sensitif (Hidden from public views)</el-checkbox>
                </el-form-item>
              </div>
            </el-tab-pane>

            <el-tab-pane label="Preview Tabel" name="preview">
              <div class="preview-container">
                <p class="preview-help">Ini adalah pratinjau bentuk tabel berdasarkan konfigurasi kolom di tab sebelumnya.</p>
                <el-table :data="[]" border style="width: 100%" empty-text="Belum ada data (Mode Preview)">
                  <el-table-column 
                    v-for="(col, index) in form.columns.filter(c => c.name.trim() !== '')" 
                    :key="index" 
                    :label="col.name" 
                    min-width="150" 
                  />
                </el-table>
              </div>
            </el-tab-pane>
          </el-tabs>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px;">
          <el-button @click="router.push('/dashboard/konfigurasi/kamus-data')" size="large">Batal</el-button>
          <el-button type="danger" color="#ef4444" @click="submitForm" :loading="saving" size="large">Simpan</el-button>
        </div>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Plus, Delete } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const route = useRoute()

const isEdit = computed(() => route.name === 'KonfigurasiKamusDataEdit')
const saving = ref(false)
const formRef = ref<FormInstance>()
const activeTab = ref('struktur')

interface DynamicColumn {
  name: string
  key?: string
  type: string
  is_required: boolean
  is_sensitive: boolean
}

const form = reactive({
  id: '',
  name: '',
  description: '',
  columns: [] as DynamicColumn[]
})

const rules = computed<FormRules>(() => ({
  name: [{ required: true, message: 'Nama tabel wajib diisi', trigger: 'change' }]
}))

const addColumn = () => {
  form.columns.push({
    name: '',
    type: 'string',
    is_required: false,
    is_sensitive: false
  })
}

const removeColumn = (index: number) => {
  form.columns.splice(index, 1)
}

const fetchDetail = async () => {
  try {
    const res = await ApiService.get(`kamus-data/${route.params.id}`)
    const data = res.data.data
    form.id = data.id
    form.name = data.name
    form.description = data.description
    form.columns = data.columns.map((c: any) => ({
      name: c.name,
      key: c.key,
      type: c.type,
      is_required: c.is_required,
      is_sensitive: c.is_sensitive
    }))
  } catch (error) {
    ElMessage.error('Gagal mengambil data')
    router.back()
  }
}

const submitForm = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (valid) {
      if (form.columns.length === 0) {
        ElMessage.warning('Tabel minimal harus memiliki 1 kolom')
        return
      }

      saving.value = true
      try {
        if (isEdit.value) {
          await ApiService.put(`kamus-data/${form.id}`, form)
          ElMessage.success('Kamus data berhasil diperbarui')
        } else {
          await ApiService.post('kamus-data', form)
          ElMessage.success('Kamus data berhasil ditambahkan')
        }
        router.push({ name: 'KonfigurasiKamusData' })
      } catch (error: any) {
        ElMessage.error(error.response?.data?.message || 'Terjadi kesalahan')
      } finally {
        saving.value = false
      }
    }
  })
}

onMounted(() => {
  if (isEdit.value && route.params.id) {
    fetchDetail()
  } else {
    addColumn() // At least 1 column by default
  }
})
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.breadcrumb {
  font-size: 14px;
  color: #6B7280;
}
.breadcrumb .back-link {
  color: #111827;
  text-decoration: none;
  font-weight: 500;
  cursor: pointer;
}
.breadcrumb .back-link:hover {
  color: #ef4444;
}
.breadcrumb .separator {
  margin: 0 8px;
  color: #D1D5DB;
}
.breadcrumb .active {
  color: #6B7280;
}
.box-card {
  border-radius: 8px;
  border: 1px solid #E5E7EB;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.column-card {
  background-color: #F9FAFB;
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.column-title {
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.preview-container {
  background-color: #ffffff;
  padding: 16px;
  border-radius: 8px;
  border: 1px dashed #D1D5DB;
}

.preview-help {
  font-size: 13px;
  color: #6B7280;
  margin-top: 0;
  margin-bottom: 12px;
}
</style>
