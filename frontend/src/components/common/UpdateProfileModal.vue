<template>
  <el-dialog v-model="visible" title="Update Profile" width="500px" align-center @close="closeModal">
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-form-item label="Foto Profil (Avatar)">
        <el-upload
          class="avatar-uploader"
          :show-file-list="false"
          :auto-upload="false"
          :on-change="handleAvatarChange"
          accept="image/jpeg,image/png,image/webp"
        >
          <img v-if="form.avatarUrl" :src="form.avatarUrl" class="avatar-preview" />
          <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
        </el-upload>
      </el-form-item>

      <el-form-item label="Username" prop="username">
        <el-input v-model="form.username" placeholder="Masukan username Anda" size="large" />
      </el-form-item>

      <el-form-item label="Email" prop="email">
        <el-input v-model="form.email" placeholder="contoh: user@domain.com" type="email" size="large" />
      </el-form-item>

      <el-form-item label="Password Baru" prop="password">
        <el-input v-model="form.password" type="password" show-password placeholder="Masukan password baru" size="large" />
        <p class="help-text">Kosongkan jika tidak ingin mengubah password.</p>
      </el-form-item>
    </el-form>

    <template #footer>
      <div class="dialog-footer" style="display: flex; justify-content: space-between; align-items: center;">
        <el-button type="warning" plain :loading="syncing" @click="syncPermissions" size="small">
          Update Hak Akses (Super Admin)
        </el-button>
        <div>
          <el-button text @click="closeModal">Batal</el-button>
          <el-button type="primary" :loading="saving" @click="saveProfile">Simpan</el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import ApiService from '@/core/services/ApiService'
import { ImageService } from '@/core/services/ImageService'
import { useAuthStore } from '@/store/auth'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits(['update:modelValue', 'profile-updated'])

const visible = ref(props.modelValue)
watch(() => props.modelValue, (val) => {
  visible.value = val
  if (val) initForm()
})
watch(visible, (val) => {
  emit('update:modelValue', val)
})

const authStore = useAuthStore()
const saving = ref(false)
const syncing = ref(false)
const formRef = ref<FormInstance>()

const syncPermissions = async () => {
  syncing.value = true
  try {
    await ApiService.post('roles/sync-super-admin')
    await authStore.getProfile()
    showSuccessToast('Hak akses Super Admin berhasil diperbarui dan diterapkan.')
    window.location.reload()
  } catch (error) {
    showErrorToast('Gagal melakukan sinkronisasi hak akses.')
  } finally {
    syncing.value = false
  }
}

const form = reactive({
  username: '',
  email: '',
  password: '',
  avatarFile: null as File | null,
  avatarUrl: ''
})

const validatePassword = (rule: any, value: any, callback: any) => {
  if (value) {
    if (value.length < 8) {
      callback(new Error('Password minimal 8 karakter'))
    } else if (!/(?=.*[a-z])/.test(value)) {
      callback(new Error('Password harus mengandung huruf kecil'))
    } else if (!/(?=.*[A-Z])/.test(value)) {
      callback(new Error('Password harus mengandung huruf besar'))
    } else if (!/(?=.*[0-9])/.test(value)) {
      callback(new Error('Password harus mengandung angka'))
    } else if (!/(?=.*[!@#$%^&*_=+-])/.test(value)) {
      callback(new Error('Password harus mengandung simbol (!@#$%^&*_=+-)' ))
    } else {
      callback()
    }
  } else {
    callback()
  }
}

const rules: FormRules = {
  username: [
    { required: true, message: 'Username wajib diisi', trigger: 'change' },
    { pattern: /^\\S*$/, message: 'Username tidak boleh mengandung spasi', trigger: 'change' }
  ],
  email: [
    { required: true, message: 'Email wajib diisi', trigger: 'change' },
    { type: 'email', message: 'Format email tidak valid', trigger: 'change' }
  ],
  password: [
    { validator: validatePassword, trigger: 'change' }
  ]
}

const getAvatarUrl = (path: string | null | undefined) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const baseUrl = import.meta.env.VITE_APP_API_URL?.replace('/api', '') || 'http://localhost:8000'
  return `${baseUrl}${path}`
}

const initForm = () => {
  const user = authStore.user
  const profile = authStore.dataProfile
  form.username = user?.username || profile?.nama || ''
  form.email = user?.email || profile?.email || ''
  form.password = ''
  form.avatarFile = null
  form.avatarUrl = getAvatarUrl(user?.avatar || profile?.avatar)
  if (formRef.value) formRef.value.clearValidate()
}

const closeModal = () => {
  visible.value = false
}

const handleAvatarChange = async (file: any) => {
  const rawFile = file.raw
  if (rawFile.type !== 'image/jpeg' && rawFile.type !== 'image/png' && rawFile.type !== 'image/webp') {
    showErrorToast('Avatar harus berupa format JPG, PNG, atau WebP!')
    return false
  }
  if (rawFile.size / 1024 / 1024 > 5) {
    showErrorToast('Ukuran avatar awal tidak boleh lebih dari 5MB!')
    return false
  }
  
  try {
    const webpFile = await ImageService.compressToWebP(rawFile)
    form.avatarFile = webpFile
    form.avatarUrl = URL.createObjectURL(webpFile)
  } catch (error) {
    console.error(error)
    showErrorToast('Gagal memproses gambar avatar')
  }
}

const saveProfile = async () => {
  if (!formRef.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    const payload = new FormData()
    payload.append('username', form.username)
    payload.append('email', form.email)
    
    if (form.password) {
      payload.append('password', form.password)
    }
    
    if (form.avatarFile) {
      payload.append('avatar', form.avatarFile)
    }

    const response = await ApiService.post('profile', payload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    // Update authStore with new data
    const updatedData = response.data?.data
    if (updatedData) {
      if (authStore.dataProfile) authStore.dataProfile = updatedData
      if (authStore.user) authStore.user = updatedData
    }

    showSuccessToast('Profil berhasil diperbarui')
    emit('profile-updated')
    closeModal()
  } catch (error: any) {
    console.error(error)
    const msg = error.response?.data?.message || 'Gagal memperbarui profil'
    showErrorToast(msg)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.help-text {
  font-size: 12px;
  color: #6B7280;
  margin-top: 4px;
}
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* Avatar Uploader */
.avatar-uploader :deep(.el-upload) {
  border: 1px dashed var(--el-border-color);
  border-radius: 50%;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: var(--el-transition-duration-fast);
  width: 100px;
  height: 100px;
  background-color: #f9fafb;
}

.avatar-uploader :deep(.el-upload:hover) {
  border-color: var(--el-color-primary);
}

.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.avatar-preview {
  width: 100px;
  height: 100px;
  display: block;
  object-fit: cover;
}
</style>
