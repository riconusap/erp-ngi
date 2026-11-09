<template>
  <div class="not-found-container">
    <el-result sub-title="Halaman yang Anda cari tidak ditemukan">
      <template #icon>
        <img src="@/assets/404-illustration.svg" alt="404 Not Found" class="not-found-image" />
      </template>
      <template #extra>
        <el-button type="primary" @click="router.push('/dashboard')">Kembali ke Beranda</el-button>
      </template>
    </el-result>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()
</script>

<style scoped>
.not-found-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  width: 100%;
}

.not-found-image {
  max-width: 400px;
  width: 100%;
  height: auto;
}
</style>
