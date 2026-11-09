<template>
  <NoAccessView v-if="isNoAccess" @back="handleBackToLogin" />
  <div v-else class="login-container">
    <div class="login-box">
      <div class="login-header">
        <div class="brand">
          <img src="@/assets/logo.png" alt="MKP Logo" class="logo" />
        </div>
        <AppHeading level="2">Selamat datang</AppHeading>
        <p>Silakan masukan email & password Anda</p>
      </div>

      <el-form ref="formRef" :model="loginForm" :rules="rules" label-position="top" @keyup.enter="handleLogin(formRef)">
        <el-form-item label="Email" prop="email">
          <el-input v-model="loginForm.email" placeholder="Masukan email Anda" size="large" />
        </el-form-item>

        <el-form-item label="Password" prop="password">
          <el-input v-model="loginForm.password" type="password" placeholder="Masukan password Anda" show-password
            size="large" />
        </el-form-item>

        <div class="forgot-password-link" style="text-align: right; margin-bottom: 1rem; margin-top: -10px;">
          <router-link to="/forgot-password" style="color: var(--color-primary); text-decoration: none; font-size: 0.875rem;">
            Lupa Password?
          </router-link>
        </div>

        <AppButton variant="primary" size="large" class="submit-btn" :loading="loading" @click="handleLogin(formRef)">
          Terhubung sekarang
        </AppButton>

        <ErrorBanner v-if="loginError"
          :message="`Email atau kata sandi yang Anda masukkan tidak valid. Silakan coba lagi`" />
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { focusFirstError } from '@/utils/focusHelper'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/store/auth.ts'

import type { FormInstance, FormRules } from 'element-plus'

import ErrorBanner from '@/components/ErrorBanner.vue'
import NoAccessView from './NoAccessView.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'

const { login } = useAuthStore()

const router = useRouter()
const formRef = ref<FormInstance>()
const loading = ref(false)
const loginError = ref(false)
const isNoAccess = ref(false)

const handleBackToLogin = () => {
  isNoAccess.value = false
  loginForm.email = ''
  loginForm.password = ''
}

const loginForm = reactive({
  email: '',
  password: ''
})

const rules = reactive<FormRules>({
  email: [
    { required: true, message: 'Please input email', trigger: 'change' },
    { type: 'email', message: 'Please input a valid email address', trigger: ['change', 'change'] }
  ],
  password: [
    { required: true, message: 'Please input password', trigger: 'change' }
  ]
})

const handleLogin = async (formEl: FormInstance | undefined) => {
  if (!formEl || loading.value) return

  const valid = await formEl.validate().catch((fields) => {
    if (fields) focusFirstError(fields)
    return false
  })

  if (!valid) return

  loading.value = true
  loginError.value = false
  isNoAccess.value = false

  try {
    const response = await login({
      email: loginForm.email.trim(),
      password: loginForm.password
    })

    if (
      response?.has_access === false ||
      response?.status === 'NO_ACCESS'
    ) {
      isNoAccess.value = true
      return
    }

    ElMessage.success(response?.message ?? 'Login berhasil')
    router.push('/success')
  } catch (error: any) {
    if (error?.response?.status === 403) {
      isNoAccess.value = true
      return
    }

    loginError.value = true
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.login-container {
  display: flex;
  min-height: 100vh;
  width: 100vw;
  justify-content: center;
  align-items: flex-start;
  padding-top: 5rem;
  background-color: #ffffff;
}

.login-box {
  width: 100%;
  max-width: 440px;
  padding: 2rem;

  .login-header {
    text-align: center;
    margin-bottom: 2.5rem;

    .brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 0rem;

      .logo {
        height: 32px;
      }

      .brand-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
      }
    }

    h2 {
      font-size: 1.5rem;
      color: #111827;
      margin-bottom: 0.5rem;
      font-weight: 700;
    }

    p {
      color: #4b5563;
      font-size: 0.875rem;
    }
  }

  .el-form-item__label {
    font-weight: 500;
    color: #374151;
    padding-bottom: 4px;
  }

  .el-form-item {
    margin-bottom: 1.5rem;
  }

  .submit-btn {
    width: 100%;
    font-weight: 500;
    margin-top: 1rem;
    border-radius: 6px;
  }
}
</style>
