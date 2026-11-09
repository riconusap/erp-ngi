<template>
  <div class="layout-wrapper">
    <a href="#main-content" class="skip-link">Lompati ke konten utama</a>
    <!-- Top Bar Navigation untuk Mobile & Tablet (< 1024px) -->
    <header class="mobile-header">
      <div class="mobile-brand">
        <el-button text class="menu-toggle-btn" aria-label="Buka menu navigasi" @click="isMobileMenuOpen = true">
          <el-icon :size="24">
            <ArrowRight />
          </el-icon>
        </el-button>
        <img src="@/assets/logo.png" alt="MKP Logo" class="mobile-logo" />
      </div>
      <div class="mobile-actions">
        <NotificationBell />
      </div>
    </header>

    <!-- Sidebar Drawer untuk Mobile & Tablet -->
    <el-drawer
      v-model="isMobileMenuOpen"
      direction="ltr"
      size="280px"
      :with-header="false"
      title="Menu Navigasi Mobile"
      class="mobile-sidebar-drawer"
      aria-describedby=""
      aria-label="Sidebar Menu"
    >
      <Sidebar @navigate="isMobileMenuOpen = false" />
    </el-drawer>

    <!-- Permanent Desktop Sidebar (>= 1024px) -->
    <aside class="desktop-sidebar">
      <Sidebar 
        :is-collapsed="isDesktopSidebarCollapsed"
        @toggle-collapse="toggleDesktopSidebar" 
      />
    </aside>

    <!-- Content Wrapper with Desktop Topbar -->
    <div class="content-wrapper">
      <header class="desktop-header">
        <div class="desktop-header-left">
          <!-- Spacer or dynamic breadcrumb -->
        </div>
        <div class="desktop-header-right">
          <NotificationBell />
        </div>
      </header>

      <!-- Main Content Viewport -->
      <main id="main-content" class="main-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ArrowRight } from '@element-plus/icons-vue'
import Sidebar from '@/components/Sidebar.vue'
import NotificationBell from '@/components/common/NotificationBell.vue'

const isMobileMenuOpen = ref(false)
const isDesktopSidebarCollapsed = ref(false)

onMounted(() => {
  const savedState = localStorage.getItem('sidebar-collapsed')
  if (savedState) {
    isDesktopSidebarCollapsed.value = savedState === 'true'
  }
})

const toggleDesktopSidebar = () => {
  isDesktopSidebarCollapsed.value = !isDesktopSidebarCollapsed.value
  localStorage.setItem('sidebar-collapsed', String(isDesktopSidebarCollapsed.value))
}
</script>

<style scoped lang="scss">
.layout-wrapper {
  display: flex;
  height: 100vh;
  width: 100%;
  overflow: hidden;
  background-color: #F9FAFB;
}

.skip-link {
  position: absolute;
  top: -40px;
  left: 0;
  background: #ef4444;
  color: white;
  padding: 8px 16px;
  z-index: 9999;
  transition: top 0.2s ease;
  text-decoration: none;
  font-weight: 500;
  
  &:focus {
    top: 0;
  }
}

.desktop-sidebar {
  display: block;
  height: 100%;
  flex-shrink: 0;
  position: relative;
  z-index: 30;
}

.content-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
  background-color: #F9FAFB;
}

.desktop-header {
  height: 56px;
  background-color: #ffffff;
  border-bottom: 1px solid #E5E7EB;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  flex-shrink: 0;
  z-index: 10;

  .desktop-header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-left: auto;
  }
}

.mobile-header {
  display: none;
}

.main-content {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
  background-color: #F9FAFB;
}

/* Responsif untuk Tablet & Mobile (< 1024px) */
@media (max-width: 1023px) {
  .layout-wrapper {
    flex-direction: column;
  }

  .desktop-sidebar,
  .desktop-header {
    display: none;
  }

  .content-wrapper {
    height: auto;
  }

  .mobile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background-color: #ffffff;
    border-bottom: 1px solid #E5E7EB;

    .mobile-brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;

      .menu-toggle-btn {
        padding: 4px;
        color: #111827;
      }

      .mobile-logo {
        height: 36px;
      }
    }
  }

  .main-content {
    padding: 1rem;
  }
}

:deep(.mobile-sidebar-drawer .el-drawer__body) {
  padding: 0 !important;
}
</style>
