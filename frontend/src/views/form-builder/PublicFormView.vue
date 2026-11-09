<template>
  <div class="public-form-container">
    <div class="form-wrapper" v-loading="isLoading">
      <div v-if="!form && !isLoading" class="not-found">
        <el-empty description="Form tidak ditemukan atau tidak aktif" />
      </div>
      
      <div v-else-if="form && isSubmitted" class="success-state">
        <el-result icon="success" title="Berhasil!" sub-title="Terima kasih, respon Anda telah kami terima.">
          <template #extra>
            <el-button type="primary" @click="isSubmitted = false">Kirim Respon Lain</el-button>
          </template>
        </el-result>
      </div>
      
      <el-card v-else-if="form" shadow="hover" class="form-card">
        <div class="form-header">
          <h1 class="form-title">{{ form.name }}</h1>
          <p class="form-desc" v-if="form.description">{{ form.description }}</p>
        </div>

        <el-form ref="formRef" :model="formData" :rules="formRules" label-position="top" @submit.prevent>
          <div v-for="field in form.schema" :key="field.key" class="form-field">
            <el-form-item :label="field.label" :prop="field.key">
              
              <!-- Text / Paragraf -->
              <el-input 
                v-if="field.type === 'text'" 
                v-model="formData[field.key]" 
                placeholder="Jawaban Anda" 
              />
              <el-input 
                v-else-if="field.type === 'textarea'" 
                v-model="formData[field.key]" 
                type="textarea" 
                :rows="4" 
                placeholder="Jawaban Anda" 
              />
              
              <!-- Number -->
              <el-input-number 
                v-else-if="field.type === 'number'" 
                v-model="formData[field.key]" 
                style="width: 100%" 
                placeholder="Angka" 
              />
              
              <!-- Date -->
              <el-date-picker 
                v-else-if="field.type === 'date'" 
                v-model="formData[field.key]" 
                type="date" 
                placeholder="Pilih Tanggal" 
                format="YYYY-MM-DD" 
                value-format="YYYY-MM-DD" 
                style="width: 100%" 
              />
              
              <!-- Select -->
              <el-select 
                v-else-if="field.type === 'select'" 
                v-model="formData[field.key]" 
                placeholder="Pilih Salah Satu"
                style="width: 100%"
              >
                <el-option v-for="(opt, idx) in field.options" :key="idx" :label="opt" :value="opt" />
              </el-select>

              <!-- File Upload -->
              <el-upload
                v-else-if="field.type === 'file'"
                :action="`${uploadUrl}/${slug}/upload`"
                :accept="field.allowedTypes ? field.allowedTypes.join(',') : '*'"
                :limit="1"
                :on-success="(res: any, file: any) => handleUploadSuccess(res, file, field.key)"
                :before-upload="(file: any) => beforeUpload(file, field.maxSize)"
                :on-remove="() => handleUploadRemove(field.key)"
                class="form-upload"
              >
                <el-button type="primary" plain><el-icon><Upload /></el-icon> Pilih File</el-button>
                <template #tip>
                  <div class="el-upload__tip">
                    Maksimal ukuran file {{ field.maxSize || 50 }}MB
                  </div>
                </template>
              </el-upload>
              
            </el-form-item>
          </div>
          
          <div class="submit-action">
            <el-button type="primary" size="large" @click="submitResponse" :loading="isSaving">Kirim Jawaban</el-button>
          </div>
        </el-form>
      </el-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { Upload } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import type { FormInstance } from 'element-plus'
import ApiService from '@/core/services/ApiService'

const route = useRoute()
const slug = route.params.slug as string

const form = ref<any>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const isSubmitted = ref(false)

const formRef = ref<FormInstance>()
const formData = ref<any>({})
const formRules = ref<any>({})

const uploadUrl = computed(() => `${import.meta.env.VITE_APP_API_URL}/public-forms`)

const fetchForm = async () => {
  isLoading.value = true
  try {
    const res = await ApiService.get(`public-forms/${slug}`)
    const data = res.data.data
    data.schema = typeof data.schema === 'string' ? JSON.parse(data.schema) : (data.schema || [])
    form.value = data
    
    // Build initial data and rules
    const initData: any = {}
    const rules: any = {}
    
    data.schema.forEach((field: any) => {
      initData[field.key] = field.type === 'number' ? null : ''
      if (field.required) {
        rules[field.key] = [
          { required: true, message: 'Pertanyaan ini wajib diisi', trigger: ['blur', 'change'] }
        ]
      }
    })
    
    formData.value = initData
    formRules.value = rules
    
    document.title = form.value.name
  } catch (error) {
    form.value = null
  } finally {
    isLoading.value = false
  }
}

const beforeUpload = (file: File, maxSizeMB: number = 50) => {
  const isLtSize = file.size / 1024 / 1024 < maxSizeMB
  if (!isLtSize) {
    ElMessage.error(`Ukuran file tidak boleh melebihi ${maxSizeMB}MB!`)
  }
  return isLtSize
}

const handleUploadSuccess = (response: any, uploadFile: any, key: string) => {
  if (response.status) {
    formData.value[key] = response.url
    ElMessage.success('File berhasil diunggah')
  } else {
    ElMessage.error('Gagal mengunggah file')
  }
}

const handleUploadRemove = (key: string) => {
  formData.value[key] = ''
}

const submitResponse = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      isSaving.value = true
      try {
        await ApiService.post(`public-forms/${slug}/submit`, {
          data: formData.value
        })
        isSubmitted.value = true
        
        // Reset form for next submission
        Object.keys(formData.value).forEach(key => {
          formData.value[key] = ''
        })
      } catch (error) {
        ElMessage.error('Gagal mengirim respon, silakan coba lagi')
      } finally {
        isSaving.value = false
      }
    }
  })
}

onMounted(() => {
  fetchForm()
})
</script>

<style scoped lang="scss">
.public-form-container {
  min-height: 100vh;
  background-color: #f3f4f6;
  padding: 40px 20px;
  display: flex;
  justify-content: center;
}

.form-wrapper {
  width: 100%;
  max-width: 680px;
}

.form-card {
  border-top: 8px solid var(--el-color-primary);
  border-radius: 8px;
  
  .form-header {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
    
    .form-title {
      font-size: 32px;
      margin: 0 0 12px 0;
      color: #111827;
      font-weight: 600;
    }
    .form-desc {
      font-size: 16px;
      color: #4b5563;
      margin: 0;
      white-space: pre-wrap;
    }
  }
}

.form-field {
  background: #fff;
  padding: 20px;
  margin-bottom: 16px;
  border-radius: 8px;
  border: 1px solid #f3f4f6;
  transition: all 0.2s;
  
  &:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  }

  :deep(.el-form-item__label) {
    font-size: 16px;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 12px;
    padding-bottom: 0;
  }
}

.form-upload {
  width: 100%;
}

.submit-action {
  margin-top: 32px;
  display: flex;
  justify-content: flex-start;
  padding: 0 20px;
}

.not-found {
  background: #fff;
  padding: 60px;
  border-radius: 8px;
}
</style>
