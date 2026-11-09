<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">{{ isEdit ? 'Edit Data' : 'Tambah Data' }} {{ tableSchema?.name }}</AppHeading>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/data-dinamis" class="back-link">Data Dinamis</router-link> 
          <span class="separator">/</span> 
          <router-link :to="`/dashboard/data-dinamis/${slug}`" class="back-link">{{ tableSchema?.name || '...' }}</router-link>
          <span class="separator">/</span> 
          <span class="active">{{ isEdit ? 'Edit' : 'Tambah' }}</span>
        </div>
      </div>
    </div>

    <el-card class="box-card mt-4" v-loading="isLoadingSchema">
      <el-form 
        v-if="tableSchema"
        :model="form" 
        :rules="rules" 
        ref="formRef" 
        label-position="top" 
        @submit.prevent
      >
        <el-row :gutter="24">
          <el-col :span="12" v-for="col in tableSchema.columns" :key="col.key">
            <el-form-item :label="col.name" :prop="col.key">
              
              <!-- String (Teks Pendek) -->
              <el-input 
                v-if="col.type === 'string'" 
                v-model="form[col.key]" 
                :placeholder="`Masukkan ${col.name}`" 
              />
              
              <!-- Text (Teks Panjang) -->
              <el-input 
                v-else-if="col.type === 'text'" 
                v-model="form[col.key]" 
                type="textarea"
                :rows="3"
                :placeholder="`Masukkan ${col.name}`" 
              />
              
              <!-- Integer (Angka) -->
              <el-input-number 
                v-else-if="col.type === 'integer'" 
                v-model="form[col.key]" 
                :placeholder="`Masukkan ${col.name}`"
                style="width: 100%"
              />

              <!-- Date (Tanggal) -->
              <el-date-picker
                v-else-if="col.type === 'date'"
                v-model="form[col.key]"
                type="date"
                placeholder="Pilih Tanggal"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
              />
              
              <!-- Boolean (Ya/Tidak) -->
              <el-switch
                v-else-if="col.type === 'boolean'"
                v-model="form[col.key]"
                active-text="Ya"
                inactive-text="Tidak"
              />

              <!-- Default Fallback -->
              <el-input 
                v-else
                v-model="form[col.key]" 
                :placeholder="`Masukkan ${col.name}`" 
              />

              <!-- Hint for sensitive data -->
              <div v-if="col.is_sensitive" class="sensitive-hint">
                <el-icon><Warning /></el-icon> Data ini bersifat sensitif dan akan disembunyikan dari publik.
              </div>
            </el-form-item>
          </el-col>
        </el-row>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px;">
          <el-button @click="router.push(`/dashboard/data-dinamis/${slug}`)" size="large">Batal</el-button>
          <el-button type="primary" @click="submitForm" :loading="saving" size="large">Simpan</el-button>
        </div>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Warning } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const route = useRoute()

const slug = route.params.slug as string
const recordId = route.params.id as string
const isEdit = computed(() => !!recordId)

const saving = ref(false)
const isLoadingSchema = ref(true)
const formRef = ref<FormInstance>()

const tableSchema = ref<any>(null)
const form = ref<any>({})
const rules = ref<FormRules>({})

const fetchSchema = async () => {
  isLoadingSchema.value = true
  try {
    const res = await ApiService.get(`dynamic-data/${slug}/schema`)
    tableSchema.value = res.data.data
    
    // Initialize form object and validation rules based on schema
    const newRules: any = {}
    tableSchema.value.columns.forEach((col: any) => {
      // Set default value based on type
      if (form.value[col.key] === undefined) {
        if (col.type === 'boolean') form.value[col.key] = false
        else if (col.type === 'integer') form.value[col.key] = null
        else form.value[col.key] = ''
      }
      
      // Set validation rule
      if (col.is_required) {
        newRules[col.key] = [
          { required: true, message: `${col.name} wajib diisi`, trigger: ['blur', 'change'] }
        ]
      }
    })
    rules.value = newRules

  } catch (error) {
    ElMessage.error('Gagal mengambil struktur tabel')
    router.push('/dashboard/data-dinamis')
  } finally {
    isLoadingSchema.value = false
  }
}

const fetchDetail = async () => {
  try {
    const res = await ApiService.get(`dynamic-data/${slug}/${recordId}`)
    const data = res.data.data.data // The JSONB payload is inside `data` column
    
    // Merge fetched data into form
    Object.keys(data).forEach(key => {
      form.value[key] = data[key]
    })
  } catch (error) {
    ElMessage.error('Gagal mengambil data')
  }
}

const submitForm = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      saving.value = true
      try {
        const payload = {
          data: form.value
        }

        if (isEdit.value) {
          await ApiService.put(`dynamic-data/${slug}/${recordId}`, payload)
          ElMessage.success('Data berhasil diupdate')
        } else {
          await ApiService.post(`dynamic-data/${slug}`, payload)
          ElMessage.success('Data berhasil disimpan')
        }
        
        router.push(`/dashboard/data-dinamis/${slug}`)
      } catch (error) {
        ElMessage.error('Terjadi kesalahan saat menyimpan data')
      } finally {
        saving.value = false
      }
    }
  })
}

onMounted(async () => {
  await fetchSchema()
  if (isEdit.value) {
    await fetchDetail()
  }
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

.breadcrumb {
  font-size: 14px;
  color: #6B7280;
  .back-link {
    color: var(--el-color-primary);
    text-decoration: none;
    &:hover {
      text-decoration: underline;
    }
  }
  .separator {
    margin: 0 8px;
  }
  .active {
    color: #374151;
    font-weight: 500;
  }
}

.sensitive-hint {
  font-size: 12px;
  color: #d97706; // amber-600
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
  line-height: 1.4;
}
</style>
