<template>
  <div class="success-container">
    <div class="success-box">
      <div class="brand">
        <img src="@/assets/logo.png" alt="GreenlyConnect Logo" class="logo" />
      </div>

      <div class="icon-container">
        <img src="@/assets/success-icon.svg" alt="Success" class="success-icon-img" />
      </div>

      <h2>Berhasil terhubung ke<br />GreenlyConnect</h2>
      <p>Sistem ERP & Kepegawaian PT Naturally Green Indonesia</p>

      <div class="user-card">
        <el-avatar v-if="authStore.dataProfile?.avatar || authStore.user?.avatar" :size="48" :src="getAvatarUrl(authStore.dataProfile?.avatar || authStore.user?.avatar)"></el-avatar>
        <el-avatar v-else aria-label="Profil pengguna" :icon="UserFilled" :size="48" class="avatar"></el-avatar>
        <div class="user-info">
          <div class="name">{{ userDisplayName }}</div>
          <div class="role">{{ userDisplayRole }}</div>
        </div>
      </div>

      <div class="actions-group">
        <el-button
          v-if="canAccessAdmin"
          type="primary"
          size="large"
          class="action-btn btn-admin"
          @click="goToAdmin"
        >
          <el-icon class="mr-2"><Monitor /></el-icon>
          Mulai Akses Dashboard
        </el-button>

        <el-button
          v-if="hasPegawai"
          :type="canAccessAdmin ? 'default' : 'primary'"
          size="large"
          class="action-btn btn-absensi"
          @click="goToAbsensi"
        >
          <el-icon class="mr-2"><Timer /></el-icon>
          Clock In Absensi
        </el-button>

        <div v-if="!canAccessAdmin && !hasPegawai" class="unlinked-account-box">
          <p class="unlinked-text">
            Akun Anda belum memiliki akses Dashboard Admin dan belum terhubung ke Data Pegawai. Silakan hubungi Administrator.
          </p>
          <el-button
            type="danger"
            plain
            size="default"
            class="action-btn"
            @click="handleLogout"
          >
            Logout
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { UserFilled, Monitor, Timer } from '@element-plus/icons-vue'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()

const userDisplayName = computed(() => {
  return authStore.dataProfile?.nama || authStore.user?.username || authStore.user?.nama || 'Admin'
})

const userDisplayRole = computed(() => {
  return authStore.dataProfile?.role?.name || authStore.user?.role?.name || 'Administrator'
})

const canAccessAdmin = computed(() => {
  return !!(authStore.canAccessAdmin || authStore.user?.role?.can_access_admin || authStore.dataProfile?.role?.can_access_admin)
})

const hasPegawai = computed(() => {
  return !!(authStore.hasPegawai || authStore.user?.pegawai_id || authStore.user?.pegawai)
})

const getAvatarUrl = (path: string | null | undefined) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const baseUrl = import.meta.env.VITE_APP_API_URL?.replace('/api', '') || 'http://localhost:8000'
  return `${baseUrl}${path}`
}

const goToAdmin = () => {
  router.push('/admin')
}

const goToAbsensi = () => {
  router.push('/absensi')
}

const handleLogout = async () => {
  try {
    await authStore.logout()
  } catch {
    // ignore
  }
  router.push('/login')
}
</script>

<style scoped lang="scss">
.success-container {
  display: flex;
  min-height: 100vh;
  width: 100vw;
  justify-content: center;
  align-items: flex-start;
  padding-top: 5rem;
  background-color: #ffffff;
}

.success-box {
  width: 100%;
  max-width: 440px;
  padding: 2rem;
  text-align: center;

  .brand {
    margin-bottom: 2rem;

    .logo {
      height: 32px;
    }
  }

  .icon-container {
    margin-bottom: 1rem;

    .success-icon-img {
      height: 48px;
    }
  }

  h2 {
    font-size: 1.5rem;
    color: #111827;
    margin-bottom: 0.5rem;
    font-weight: 700;
    line-height: 1.3;
  }

  p {
    color: #4b5563;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
  }

  .user-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: left;

    .avatar {
      background-color: #D1D5DB;
      color: #ffffff;
    }

    .user-info {
      .name {
        font-weight: 700;
        color: #111827;
        font-size: 1rem;
      }

      .role {
        color: #4b5563;
        font-size: 0.875rem;
      }
    }
  }

  .actions-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;

    .action-btn {
      width: 100%;
      height: 44px;
      font-weight: 600;
      border-radius: 8px;
      font-size: 14.5px;
      margin-left: 0 !important;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-admin {
      background-color: #ef4444;
      border-color: #ef4444;

      &:hover {
        background-color: #dc2626;
        border-color: #dc2626;
      }
    }

    .btn-absensi {
      border-color: #10b981;
      color: #059669;

      &:hover {
        background-color: #ecfdf5;
        border-color: #059669;
        color: #047857;
      }
    }

    .unlinked-account-box {
      margin-top: 6px;
      padding: 14px 16px;
      background-color: #fef2f2;
      border: 1px solid #fee2e2;
      border-radius: 8px;
      text-align: center;

      .unlinked-text {
        color: #991b1b;
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 12px;
      }
    }
  }
}
</style>
