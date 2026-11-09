<template>
  <div class="login-container">
    <div class="login-box">
      <div class="login-header">
        <div class="brand">
          <img src="@/assets/logo.png" alt="MKP Logo" class="logo" />
        </div>
        <AppHeading level="2">Lupa Password</AppHeading>
        <p>Masukan email Anda untuk menerima link reset password.</p>
      </div>

      <el-form ref="formRef" :model="form" :rules="rules" label-position="top" @keyup.enter="handleSubmit(formRef)">
        <el-form-item label="Email" prop="email">
          <el-input v-model="form.email" placeholder="Masukan email Anda" size="large" />
        </el-form-item>

        <AppButton variant="primary" size="large" class="submit-btn" :loading="loading" @click="handleSubmit(formRef)">
          Kirim Link Reset
        </AppButton>
        
        <div class="back-to-login">
          <router-link to="/login">Kembali ke halaman Login</router-link>
        </div>
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import ApiService from '@/core/services/ApiService'
import type { FormInstance, FormRules } from 'element-plus'
import { focusFirstError } from '@/utils/focusHelper'

import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'

const router = useRouter()
const formRef = ref<FormInstance>()
const loading = ref(false)

const form = reactive({
  email: ''
})

const rules = reactive<FormRules>({
  email: [
    { required: true, message: 'Silakan isi email', trigger: 'change' },
    { type: 'email', message: 'Format email tidak valid', trigger: 'change' }
  ]
})

const handleSubmit = async (formEl: FormInstance | undefined) => {
  if (!formEl) return
  await formEl.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        const response = await ApiService.post('forgot-password', form)
        ElMessage({
          message: response.data.message || 'Link reset password telah dikirim.',
          type: 'success',
        })
        form.email = ''
      } catch (error: any) {
        ElMessage({
          message: error.response?.data?.message || 'Gagal mengirim link reset.',
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
