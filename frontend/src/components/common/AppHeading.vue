<template>
  <component :is="tag" :class="['app-heading', headingClass, customClass]">
    <slot></slot>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    level?: 1 | 2 | 3 | 4 | 5 | '1' | '2' | '3' | '4' | '5'
    customClass?: string
  }>(),
  {
    level: 1,
    customClass: ''
  }
)

const tag = computed(() => `h${props.level}`)
const headingClass = computed(() => `app-h${props.level}`)
</script>
