<template>
  <el-drawer
    v-model="isOpen"
    :size="computedDrawerSize"
    :with-header="false"
    title="Panel Detail"
    class="custom-drawer"
    aria-label="Panel Detail"
  >
    <div class="drawer-controls">
      <el-button size="small" aria-label="Tutup menu" @click="isOpen = false">
        <img src="@/assets/chevrons-right.svg" alt="Close" />
      </el-button>
      <el-button size="small" class="expand-toggle-btn" aria-label="Perbesar menu" @click="isExpanded = !isExpanded">
        <img src="@/assets/maximize.svg" alt="Expand" />
      </el-button>
    </div>
    <div class="drawer-body">
      <div class="drawer-header" v-if="title">
        <h2 class="drawer-title">{{ title }}</h2>
      </div>
      <slot></slot>
    </div>
  </el-drawer>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  defaultSize: {
    type: String,
    default: '700px'
  },
  expandedSize: {
    type: String,
    default: 'calc(100vw - 260px)'
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const isExpanded = ref(false)
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1200)

const updateWidth = () => {
  windowWidth.value = window.innerWidth
}

onMounted(() => {
  window.addEventListener('resize', updateWidth)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth)
})

const computedDrawerSize = computed(() => {
  if (windowWidth.value < 768) {
    return '100%'
  } else if (windowWidth.value < 1024) {
    return '85%'
  }
  return isExpanded.value ? props.expandedSize : props.defaultSize
})
</script>

<style lang="scss" scoped>
.custom-drawer {
  :deep(.el-drawer__body) {
    padding: 0;
    position: relative;
    display: flex;
    flex-direction: row;
  }
}

.drawer-controls {
  position: absolute;
  top: 16px;
  left: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 10;
  
  .el-button {
    margin: 0;
    padding: 8px;
    height: auto;
    border: 1px solid #E5E7EB;
    border-radius: 6px;
    background-color: white;
    
    &:hover {
      background-color: #F9FAFB;
    }
  }
}

.drawer-body {
  flex: 1;
  padding: 32px 40px;
  padding-left: 80px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.drawer-header {
  margin-bottom: 24px;
  
  .drawer-title {
    font-size: 20px;
    font-weight: 600;
    color: #111827;
    margin: 0;
  }
}

@media (max-width: 767px) {
  .drawer-controls {
    top: 12px;
    left: 16px;
    flex-direction: row;

    .expand-toggle-btn {
      display: none;
    }
  }

  .drawer-body {
    padding: 20px 16px;
    padding-top: 56px;
  }
}
</style>
