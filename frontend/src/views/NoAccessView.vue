<template>
  <div class="no-access-container">
    <div class="no-access-box">
      <div class="brand">
        <img src="@/assets/logo.png" alt="MKP Logo" class="logo" />
      </div>
      
      <div class="icon-container">
        <img src="@/assets/no-access.svg" alt="No Access" class="no-access-icon-img" />
      </div>
      
      <h2>Akses Ditolak</h2>
      <p>Akun Anda tidak memiliki izin untuk mengakses Dashboard Konsen Manajemen.</p>
      
      <div class="user-card">
        <el-avatar aria-label="Profil pengguna" :icon="UserFilled" :size="48" class="avatar"></el-avatar>
        <div class="user-info">
          <div class="name">Ahmad Subagjo</div>
          <div class="role">Guest / No Access</div>
        </div>
      </div>
      
      <el-button type="primary" plain size="large" class="action-btn" @click="$emit('back')">
        Kembali ke Login
      </el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { UserFilled } from '@element-plus/icons-vue'

defineEmits(['back'])
</script>

<style scoped lang="scss">
.no-access-container {
  display: flex;
  min-height: 100vh;
  width: 100vw;
  justify-content: center;
  align-items: flex-start;
  padding-top: 5rem;
  background-color: #ffffff;
}

.no-access-box {
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
    .no-access-icon-img {
      height: 80px;
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

  .action-btn {
    width: 100%;
    font-weight: 500;
    border-radius: 6px;
  }
}
</style>
