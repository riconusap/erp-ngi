<template>
  <div class="data-table-wrapper">
    <el-table aria-live="polite" :data="data" v-loading="loading" style="width: 100%" class="custom-table" v-bind="$attrs">
      <template #empty>
        <div class="empty-state">
          <el-empty :image="emptyImage">
            <template #description>
              <div class="empty-text">
                <p class="empty-title">{{ emptyTitle }}</p>
                <span class="empty-subtitle">{{ emptySubtitle }}</span>
              </div>
            </template>
          </el-empty>
        </div>
      </template>
      
      <slot></slot>
    </el-table>

    <div v-if="showPagination" class="pagination-container">
      <div class="pagination-left">
        <span class="page-link" @click="goToFirstPage" tabindex="0" @keydown.enter="goToFirstPage" role="button">Pertama</span>
        <el-pagination
          :current-page="currentPage"
          :page-size="pageSize"
          @update:current-page="$emit('update:currentPage', $event)"
          @update:page-size="$emit('update:pageSize', $event)"
          layout="prev, pager, next"
          :total="total"
          class="custom-pagination"
        />
        <span class="page-link" @click="goToLastPage" tabindex="0" @keydown.enter="goToLastPage" role="button">Terakhir</span>
      </div>
      <div class="pagination-right">
        <span class="rows-info">{{ paginationInfo }}</span>
        <div class="page-size-selector">
          <span aria-hidden="true">Tampilkan baris</span>
          <el-select aria-label="Tampilkan jumlah baris per halaman" :model-value="pageSize" @update:model-value="$emit('update:pageSize', $event)" class="size-select">
            <el-option v-for="size in pageSizes" :key="size" :label="size.toString()" :value="size" />
          </el-select>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  data: any[]
  loading?: boolean
  emptyImage?: string
  emptyTitle?: string
  emptySubtitle?: string
  showPagination?: boolean
  total?: number
  currentPage?: number
  pageSize?: number
  pageSizes?: number[]
}>(), {
  loading: false,
  emptyTitle: 'Data tidak ditemukan',
  emptySubtitle: 'Anda perlu mengubah kata kunci pencarian',
  showPagination: true,
  total: 0,
  currentPage: 1,
  pageSize: 10,
  pageSizes: () => [5, 10, 20, 50]
})

const emit = defineEmits(['update:currentPage', 'update:pageSize'])

const paginationInfo = computed(() => {
  if (props.total === 0) return '0 of 0'
  const start = (props.currentPage - 1) * props.pageSize + 1
  const end = Math.min(start + props.pageSize - 1, props.total)
  return `${start} - ${end} of ${props.total}`
})

const goToFirstPage = () => {
  if (props.currentPage > 1) {
    emit('update:currentPage', 1)
  }
}

const goToLastPage = () => {
  const lastPage = Math.ceil(props.total / props.pageSize)
  if (props.currentPage < lastPage) {
    emit('update:currentPage', lastPage)
  }
}
</script>

<style scoped lang="scss">
.data-table-wrapper {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  width: 100%;
  overflow-x: auto;
}

.custom-table {
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  overflow: hidden;
  min-width: 100%;
  
  :deep(.el-table__header th) {
    background-color: #0F3D3E !important;
    color: #ffffff !important;
    font-weight: 600;
  }
}

.empty-state {
  padding: 0rem 0;
  
  :deep(.el-empty__description) {
    margin-top: 16px;
    
    .empty-text {
      display: flex;
      flex-direction: column;
      gap: 4px;
      
      .empty-title {
        color: #111827;
        font-weight: 600;
        font-size: 1.125rem;
        margin: 0;
      }
      
      .empty-subtitle {
        color: #9CA3AF;
        font-size: 0.875rem;
      }
    }
  }
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 0.5rem;
  flex-wrap: wrap;
  gap: 1rem;
  
  .pagination-left {
    display: flex;
    align-items: center;
    gap: 8px;
    
    .page-link {
      font-size: 0.875rem;
      color: #4b5563;
      cursor: pointer;
      &:hover {
        color: #111827;
      }
    }
    
    .custom-pagination {
      :deep(.el-pager li.is-active) {
        background-color: #F3F4F6;
        color: #111827;
        font-weight: 600;
        border-radius: 4px;
      }
    }
  }
  
  .pagination-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    
    .rows-info {
      font-size: 0.875rem;
      color: #4b5563;
    }
    
    .page-size-selector {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.875rem;
      color: #374151;
      
      .size-select {
        width: 70px;
      }
    }
  }
}

@media (max-width: 640px) {
  .pagination-container {
    flex-direction: column;
    align-items: center;
    gap: 12px;

    .pagination-left {
      width: 100%;
      justify-content: center;
    }

    .pagination-right {
      width: 100%;
      justify-content: space-between;
      gap: 0.5rem;
    }
  }
}
</style>
