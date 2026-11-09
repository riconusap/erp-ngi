<template>
  <div class="page-container">
    <div class="page-header">
      <AppHeading level="1">Pengaturan 2FA</AppHeading>
    </div>

    <div class="coming-soon-container">
      <el-empty
        description="Fitur Segera Hadir"
        :image-size="200"
      >
        <template #image>
          <!-- Using a simple inline SVG or icon for security/2FA theme -->
          <div class="icon-wrapper">
            <el-icon class="shield-icon"><Lock /></el-icon>
          </div>
        </template>
        <p class="subtitle">
          Modul Pengaturan Two-Factor Authentication (2FA) saat ini sedang dalam tahap pengembangan.<br/>
          Nantinya, Anda dapat mengatur keamanan ganda untuk akun admin di halaman ini.
        </p>
        <el-button type="danger" color="#ef4444" plain @click="router.push('/dashboard/admin')">Kembali ke Dashboard</el-button>
      </el-empty>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Lock } from '@element-plus/icons-vue'
import AppHeading from '@/components/common/AppHeading.vue'

const router = useRouter()
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.coming-soon-container {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #ffffff;
  border-radius: 8px;
  border: 1px dashed #E5E7EB;
  margin-top: 24px;
  min-height: 500px;
}
.icon-wrapper {
  background-color: #fff1f2;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0 auto;
}
.shield-icon {
  font-size: 64px;
  color: #ef4444;
}
.subtitle {
  color: #6B7280;
  line-height: 1.6;
  margin: 16px 0 24px 0;
  max-width: 500px;
}
:deep(.el-empty__description p) {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
}
</style>
