<template>
  <div class="login-container">
    <div class="login-box">
      <div class="login-header">
        <div class="brand">
          <img src="@/assets/logo.png" alt="MKP Logo" class="logo" />
        </div>
        <AppHeading level="2">Reset Password</AppHeading>
        <p>Silakan masukan password baru Anda.</p>
      </div>

      <el-form ref="formRef" :model="form" :rules="rules" label-position="top" @keyup.enter="handleSubmit(formRef)">
        <el-form-item label="Password Baru" prop="password">
          <el-input v-model="form.password" type="password" placeholder="Masukan password baru" show-password size="large" />
        </el-form-item>

        <el-form-item label="Konfirmasi Password" prop="password_confirmation">
          <el-input v-model="form.password_confirmation" type="password" placeholder="Konfirmasi password baru" show-password size="large" />
        </el-form-item>

        <AppButton variant="primary" size="large" class="submit-btn" :loading="loading" @click="handleSubmit(formRef)">
          Simpan Password Baru
        </AppButton>
        
        <div class="back-to-login">
          <router-link to="/login">Kembali ke halaman Login</router-link>
        </div>
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import ApiService from '@/core/services/ApiService'
import type { FormInstance, FormRules } from 'element-plus'
import { focusFirstError } from '@/utils/focusHelper'

import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'

const router = useRouter()
const route = useRoute()
const formRef = ref<FormInstance>()
const loading = ref(false)

const form = reactive({
  email: '',
  token: '',
  password: '',
  password_confirmation: ''
})

onMounted(() => {
  form.email = route.query.email as string || ''
  form.token = route.query.token as string || ''
})

const validatePassword = (rule: any, value: any, callback: any) => {
  if (value === '') {
    callback(new Error('Silakan isi password baru'))
  } else if (value.length < 8) {
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
}

const validatePass2 = (rule: any, value: any, callback: any) => {
  if (value === '') {
    callback(new Error('Silakan masukan password lagi'))
  } else if (value !== form.password) {
    callback(new Error('Password tidak cocok!'))
  } else {
    callback()
  }
}

const rules = reactive<FormRules>({
  password: [
    { required: true, message: 'Silakan isi password baru', trigger: 'change' },
    { validator: validatePassword, trigger: 'change' }
  ],
  password_confirmation: [
    { validator: validatePass2, trigger: 'change' }
  ]
})

const handleSubmit = async (formEl: FormInstance | undefined) => {
  if (!formEl) return
  await formEl.validate(async (valid) => {
    if (valid) {
      if (!form.token || !form.email) {
        ElMessage({
          message: 'Token atau email tidak valid. Silakan minta link reset baru.',
          type: 'error',
        })
        return
      }
      
      loading.value = true
      try {
        const response = await ApiService.post('reset-password', form)
        ElMessage({
          message: response.data.message || 'Password berhasil direset.',
          type: 'success',
        })
        router.push({ name: 'Login' })
      } catch (error: any) {
        ElMessage({
          message: error.response?.data?.message || 'Gagal mereset password.',
          type: 'error',
        })
      } finally {
        loading.value = false
      }
    } else {
      focusFirstError(formEl)
    }
  })
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-background);
  padding: 1rem;
}

.login-box {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 400px;
}

.login-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.brand {
  margin-bottom: 1.5rem;
}

.logo {
  height: 40px;
}

.login-header p {
  color: var(--color-text-light);
  margin-top: 0.5rem;
  font-size: 0.875rem;
}

.submit-btn {
  width: 100%;
  margin-top: 1rem;
}

.back-to-login {
  text-align: center;
  margin-top: 1.5rem;
  font-size: 0.875rem;
}
.back-to-login a {
  color: var(--color-primary);
  text-decoration: none;
}
.back-to-login a:hover {
  text-decoration: underline;
}
</style>
