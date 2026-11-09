<template>
  <div class="page-container">
    <div class="page-header" style="margin-bottom: 24px;">
      <AppHeading level="1">Dashboard Overview</AppHeading>
      <p style="color: #6B7280; font-size: 14px; margin-top: 4px;">Ringkasan aktivitas dan metrik aplikasi Anda.</p>
    </div>

    <!-- Bento Grid Layout with Skeletons -->
    <div class="bento-grid">
      
      <!-- Top Row: Main Welcome/Hero Box -->
      <div class="bento-item col-span-2 row-span-1">
        <el-skeleton animated style="height: 100%;">
          <template #template>
            <el-skeleton-item variant="rect" class="bento-skeleton" />
          </template>
        </el-skeleton>
      </div>
      
      <!-- Top Row: Stat Box 1 -->
      <div class="bento-item col-span-1 row-span-1">
        <el-skeleton animated style="height: 100%;">
          <template #template>
            <el-skeleton-item variant="rect" class="bento-skeleton" />
          </template>
        </el-skeleton>
      </div>

      <!-- Top Row: Stat Box 2 -->
      <div class="bento-item col-span-1 row-span-1">
        <el-skeleton animated style="height: 100%;">
          <template #template>
            <el-skeleton-item variant="rect" class="bento-skeleton" />
          </template>
        </el-skeleton>
      </div>

      <!-- Middle Row: Large Chart Area -->
      <div class="bento-item col-span-3 row-span-2">
        <el-skeleton animated style="height: 100%;">
          <template #template>
            <el-skeleton-item variant="rect" class="bento-skeleton" />
          </template>
        </el-skeleton>
      </div>

      <!-- Middle Row: Tall Side List/Activity -->
      <div class="bento-item col-span-1 row-span-2">
        <el-skeleton animated style="height: 100%;">
          <template #template>
            <el-skeleton-item variant="rect" class="bento-skeleton" />
          </template>
        </el-skeleton>
      </div>

      <!-- Bottom Row: 4 Small Cards -->
      <div class="bento-item col-span-1 row-span-1" v-for="i in 4" :key="i">
        <el-skeleton animated style="height: 100%;">
          <template #template>
            <el-skeleton-item variant="rect" class="bento-skeleton" />
          </template>
        </el-skeleton>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import AppHeading from '@/components/common/AppHeading.vue'
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
}
.bento-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-auto-rows: 160px;
  gap: 24px;
}
.bento-item {
  width: 100%;
  height: 100%;
}
.bento-skeleton {
  width: 100%;
  height: 100% !important;
  border-radius: 16px;
  background-color: #F3F4F6;
  border: 1px solid #E5E7EB;
}

/* Grid Column Spans */
.col-span-1 { grid-column: span 1; }
.col-span-2 { grid-column: span 2; }
.col-span-3 { grid-column: span 3; }
.col-span-4 { grid-column: span 4; }

/* Grid Row Spans */
.row-span-1 { grid-row: span 1; }
.row-span-2 { grid-row: span 2; }
.row-span-3 { grid-row: span 3; }

/* Responsive adjustments */
@media (max-width: 1024px) {
  .bento-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .col-span-3 { grid-column: span 2; }
}

@media (max-width: 640px) {
  .bento-grid {
    grid-template-columns: 1fr;
    grid-auto-rows: 120px;
  }
  .col-span-2, .col-span-3 { grid-column: span 1; }
  .row-span-2 { grid-row: span 1; }
}
</style>
