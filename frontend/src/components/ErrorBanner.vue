<template>
  <div class="error-banner">
    <el-icon class="error-icon"><WarningFilled /></el-icon>
    <span>
      <slot>{{ message }}</slot>
    </span>
  </div>
</template>

<script setup lang="ts">
import { WarningFilled } from '@element-plus/icons-vue'

defineProps<{
  message?: string
}>()
</script>

<style scoped lang="scss">
.error-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-top: 1.5rem;
  padding: 12px 16px;
  background-color: #fef0f0;
  border-radius: 6px;
  color: #606266;
  font-size: 0.875rem;
  line-height: 1.4;

  .error-icon {
    color: #f56c6c;
    font-size: 1.25rem;
    margin-top: 2px;
  }
}
</style>
