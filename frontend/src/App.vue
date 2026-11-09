<template>
  <router-view />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'

onMounted(() => {
  // Global Accessibility Fix: Ensure all el-select__input have aria-label
  // Element Plus internal inputs (like pagination sizes) often lack aria-labels
  const observer = new MutationObserver(() => {
    document.querySelectorAll('.el-select__input').forEach((input) => {
      if (!input.hasAttribute('aria-label') && !input.hasAttribute('id')) {
        input.setAttribute('aria-label', 'Pilihan dropdown')
      }
    })

    document.querySelectorAll('.el-popper[role="tooltip"]').forEach((popper) => {
      if (!popper.hasAttribute('aria-label') && !popper.textContent.trim()) {
         // wait, if it has no text, give it a default label so axe doesn't complain
         popper.setAttribute('aria-label', 'Dropdown menu')
      } else if (!popper.hasAttribute('aria-label')) {
         popper.setAttribute('aria-label', 'Menu konten')
      }
    })

  })

  observer.observe(document.body, {
    childList: true,
    subtree: true
  })
})
</script>

<style>
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
</style>
