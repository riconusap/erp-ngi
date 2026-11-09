<template>
  <el-button
    :type="elType"
    :size="size"
    :loading="loading"
    :disabled="disabled"
    :color="buttonColor"
    class="app-button"
    v-bind="$attrs"
  >
    <template #icon v-if="$slots.icon">
      <slot name="icon" />
    </template>
    <slot></slot>
  </el-button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'danger' | 'secondary' | 'outline' | 'ghost' | 'info'
    size?: 'small' | 'default' | 'large'
    loading?: boolean
    disabled?: boolean
  }>(),
  {
    variant: 'primary',
    size: 'default',
    loading: false,
    disabled: false
  }
)

const elType = computed(() => {
  switch (props.variant) {
    case 'primary':
      return 'primary'
    case 'danger':
      return 'danger'
    case 'secondary':
    case 'info':
      return 'info'
    case 'ghost':
      return 'text'
    case 'outline':
    default:
      return 'default'
  }
})

const buttonColor = computed(() => {
  if (props.variant === 'primary') return '#14746F'
  if (props.variant === 'danger') return '#ef4444'
  return undefined
})
</script>
