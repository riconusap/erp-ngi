<template>
  <aside class="sidebar" :class="{ 'collapsed': isCollapsed }">
    <div class="sidebar-header" :class="{ 'is-collapsed': isCollapsed }">
      <div class="brand">
        <img v-if="!isCollapsed" src="@/assets/logo.png" alt="GreenlyConnect Logo" class="logo" />
        <img v-else src="@/assets/logoOnly.svg" alt="GreenlyConnect Logo" class="logo" />
      </div>
      <el-button circle class="collapse-btn" aria-label="Kecilkan sidebar" @click="$emit('toggle-collapse')">
        <el-icon :size="16">
          <ArrowLeft v-if="!isCollapsed" />
          <ArrowRight v-else />
        </el-icon>
      </el-button>
    </div>

    <nav class="sidebar-nav">
      <!-- DASHBOARD (JIKA AKSES ADMIN AKTIF) -->
      <el-tooltip v-if="canAccessAdmin" content="Dashboard Overview" placement="right" :disabled="!isCollapsed">
        <router-link to="/dashboard/overview" class="nav-item" active-class="active" @click="$emit('navigate')">
          <el-icon>
            <DataLine />
          </el-icon>
          <span class="nav-text">Dashboard</span>
        </router-link>
      </el-tooltip>

      <!-- PRESENSI PEGAWAI (Hanya untuk akun yang terhubung ke data pegawai) -->
      <el-tooltip v-if="hasPegawai" content="Presensi Pegawai" placement="right" :disabled="!isCollapsed">
        <router-link to="/absensi" class="nav-item" active-class="active" @click="$emit('navigate')">
          <el-icon>
            <Timer />
          </el-icon>
          <span class="nav-text">Presensi Pegawai</span>
        </router-link>
      </el-tooltip>

      <!-- DYNAMIC MENUS DARI DATABASE -->
      <template v-for="menu in dynamicMenus" :key="menu.id">
        <!-- SINGLE MENU (TANPA SUBMENU) -->
        <el-tooltip v-if="!menu.children || menu.children.length === 0" :content="menu.name" placement="right" :disabled="!isCollapsed">
          <router-link :to="menu.path || ''" class="nav-item" active-class="active" @click="$emit('navigate')">
            <template v-if="menu.icon">
              <i v-if="menu.icon.startsWith('fa-')" :class="[menu.icon, 'text-xl', 'me-2', 'sidebar-fa-icon']" style="font-size: 20px; width: 24px; text-align: center;"></i>
              <el-icon v-else><component :is="menu.icon" /></el-icon>
            </template>
            <el-icon v-else><Document /></el-icon>
            <span class="nav-text">{{ menu.name }}</span>
          </router-link>
        </el-tooltip>

        <!-- MENU DENGAN SUBMENU -->
        <div v-else class="nav-group">
          <el-tooltip :content="menu.name" placement="right" :disabled="!isCollapsed">
            <div class="nav-item parent-item" :class="{ 'active': route.path.startsWith(menu.path || '___') }" @click="toggleMenu(menu.id)">
              <template v-if="menu.icon">
                <i v-if="menu.icon.startsWith('fa-')" :class="[menu.icon, 'text-xl', 'me-2', 'sidebar-fa-icon']" style="font-size: 20px; width: 24px; text-align: center;"></i>
                <el-icon v-else><component :is="menu.icon" /></el-icon>
              </template>
              <el-icon v-else><Setting /></el-icon>
              
              <span class="nav-text">{{ menu.name }}</span>
              <el-icon v-if="!isCollapsed" class="arrow-icon" :class="{ 'is-open': openMenus.includes(menu.id) }">
                <ArrowDown />
              </el-icon>
            </div>
          </el-tooltip>
          
          <div class="sub-menu" :class="{ 'is-open': openMenus.includes(menu.id) && !isCollapsed }">
            <template v-for="child in menu.children" :key="child.id">
              <router-link v-if="child.status === 'Aktif' && hasRouteAccess(child.path || child.name)" :to="child.path || ''" class="nav-item sub-item" active-class="active" @click="$emit('navigate')">
                <span class="nav-text">{{ child.name }}</span>
              </router-link>
            </template>
          </div>
        </div>
      </template>
    </nav>

    <div class="sidebar-footer">
      <div class="user-profile" :class="{ 'collapsed': isCollapsed }">
        <el-dropdown trigger="click" placement="top-end" :disabled="!isCollapsed" @command="handleUserCommand">
          <el-avatar v-if="authStore.dataProfile?.avatar || authStore.user?.avatar" :size="36" :src="getAvatarUrl(authStore.dataProfile?.avatar || authStore.user?.avatar)"></el-avatar>
          <el-avatar v-else aria-label="Profil pengguna" :icon="UserFilled" :size="36" class="avatar"></el-avatar>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="profile-view">Profil Saya</el-dropdown-item>
              <el-dropdown-item command="profile">Edit Profil</el-dropdown-item>
              <el-dropdown-item command="logout" style="color: #ef4444;">Logout</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
        
        <div class="user-info" @click="handleUserCommand('profile-view')" style="cursor: pointer;" title="Buka Profil Saya">
          <div class="name">{{ userDisplayName }}</div>
          <div class="role">{{ userDisplayRole }}</div>
        </div>
        <el-dropdown trigger="click" placement="top-end" @command="handleUserCommand" class="desktop-more-dropdown">
          <span class="el-dropdown-link" aria-label="Buka menu aksi" role="button" tabindex="0">
            <el-icon class="more-icon" style="cursor: pointer; transform: rotate(90deg); color: #9CA3AF;">
              <MoreFilled />
            </el-icon>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="profile-view">Profil Saya</el-dropdown-item>
              <el-dropdown-item command="profile">Edit Profil</el-dropdown-item>
              <el-dropdown-item command="logout" style="color: #ef4444;">Logout</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>
    
    <UpdateProfileModal v-model="isProfileModalVisible" />
  </aside>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { getTokenData, destroyToken } from '@/core/services/JwtService'
import { ElMessage } from 'element-plus'
import UpdateProfileModal from '@/components/common/UpdateProfileModal.vue'

import {
  User,
  Document,
  UserFilled,
  DataLine,
  DocumentDelete,
  MoreFilled,
  Setting,
  Connection,
  Fold,
  Expand,
  ArrowLeft,
  ArrowRight,
  ArrowDown,
  Timer
} from '@element-plus/icons-vue'

const props = defineProps<{
  isCollapsed?: boolean
}>()

const emit = defineEmits(['navigate', 'toggle-collapse'])

const router = useRouter()
const route = useRoute()
import { usePermissions } from '@/composables/usePermissions'
import { useMenuStore } from '@/store/menu'

const authStore = useAuthStore()
const menuStore = useMenuStore()
const { hasRouteAccess } = usePermissions()

const canAccessAdmin = computed(() => {
  return !!(authStore.canAccessAdmin || authStore.user?.role?.can_access_admin || authStore.dataProfile?.role?.can_access_admin)
})

const hasPegawai = computed(() => {
  return !!(authStore.hasPegawai || authStore.user?.pegawai_id || authStore.user?.pegawai)
})

const dynamicMenus = computed(() => {
  if (!canAccessAdmin.value) return []
  return menuStore.data
    .filter(menu => menu.status === 'Aktif' && hasRouteAccess(menu.path || menu.name))
    .map(menu => {
      if (menu.children && menu.children.length > 0) {
        return {
          ...menu,
          children: menu.children.filter(child => child.status === 'Aktif' && hasRouteAccess(child.path || child.name))
        }
      }
      return menu
    })
    .filter(menu => {
      if (!menu.path && (!menu.children || menu.children.length === 0)) {
        return false
      }
      return true
    })
    .sort((a, b) => a.order - b.order)
})

const userDisplayName = computed(() => {
  return authStore.dataProfile?.nama || authStore.user?.username || authStore.user?.nama || 'Admin'
})

const userDisplayRole = computed(() => {
  return authStore.dataProfile?.role?.name || authStore.user?.role?.name || 'Administrator'
})

const getAvatarUrl = (path: string | null | undefined) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const baseUrl = import.meta.env.VITE_APP_API_URL?.replace('/api', '') || 'http://localhost:8000'
  return `${baseUrl}${path}`
}

const isConfigGroupVisible = computed(() => {
  return true // Deprecated
})

onMounted(async () => {
  if (authStore.isAuthenticated && !authStore.user) {
    try {
      await authStore.getProfile()
    } catch {
      // Ignore if session not active
    }
  }

  if (menuStore.data.length === 0 && authStore.isAuthenticated) {
    const permMenus = authStore.user?.role?.permissions?.menus || authStore.user?.permissions?.menus
    if (permMenus && Array.isArray(permMenus)) {
      menuStore.setMenusFromPermissions(permMenus)
    }
  }
})

const openMenus = ref<string[]>([])
const toggleMenu = (menu: string) => {
  if (props.isCollapsed) return
  if (openMenus.value.includes(menu)) {
    openMenus.value = openMenus.value.filter(m => m !== menu)
  } else {
    openMenus.value.push(menu)
  }
}

watch(() => props.isCollapsed, (newVal) => {
  if (newVal) {
    openMenus.value = []
  }
})

const isProfileModalVisible = ref(false)

const handleUserCommand = async (command: string) => {
  if (command === 'logout') {
    await authStore.logout()

    await router.push({
      name: 'Login'
    })
  } else if (command === 'profile-view') {
    router.push('/dashboard/profile')
  } else if (command === 'profile') {
    isProfileModalVisible.value = true
  }
}
</script>

<style lang="scss">
.mobile-sidebar-drawer {
  .el-drawer__body {
    padding: 0 !important;
    .sidebar {
      width: 100% !important;
    }
  }
}
</style>
<style scoped lang="scss">


.sidebar {
  width: 260px;
  background-color: #F3F4F6;
  display: flex;
  flex-direction: column;
  height: 100%;
  border-right: 1px solid #E5E7EB;
  padding: 0px !important;
  transition: width 0.3s ease;
  position: relative;
  z-index: 30;

  &.collapsed {
    width: 80px;

    .sidebar-header {
      flex-direction: column;
      gap: 16px;
      padding: 1.5rem 0;
      align-items: center;

      .brand {
        justify-content: center;
        width: 100%;
        gap: 0;
        
        .logo {
          height: 24px; /* Logo ringkas */
          margin: 0;
        }
      }
    }
    
    .sidebar-nav {
      padding: 0 0.5rem;
    }

    .nav-item {
      justify-content: center;
      padding: 10px;
      gap: 0;
      
      .nav-text {
        opacity: 0;
        width: 0;
        visibility: hidden;
        overflow: hidden;
      }
    }
    
    .sidebar-footer {
      padding: 1.5rem 0;
    }

    .user-profile {
      justify-content: center;
      gap: 0;
      
      .user-info, .desktop-more-dropdown {
        opacity: 0;
        width: 0;
        flex: none;
        visibility: hidden;
        margin: 0;
        padding: 0;
        overflow: hidden;
      }
    }
  }
}

.sidebar-header {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;

  .brand {
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;

    .logo {
      height: 28px;
      transition: height 0.3s ease;
    }

    .brand-text {
      font-weight: 700;
      font-size: 1.1rem;
      color: #111827;
    }
  }

  .collapse-btn {
    position: absolute;
    right: -14px;
    top: 36px;
    width: 28px;
    height: 28px;
    background-color: #ffffff;
    border: 1px solid #E5E7EB;
    color: #4B5563;
    transition: all 0.2s ease;
    z-index: 40;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;

    &:hover {
      background-color: #F9FAFB;
      color: #111827;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
    }

    &:focus-visible {
      outline: 2px solid var(--color-primary, #2563EB);
      outline-offset: 2px;
    }
  }
}

.sidebar-nav {
  flex: 1;
  padding: 0 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-top: 1rem;
  overflow-x: hidden;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  border-radius: 8px;
  text-decoration: none;
  color: #4B5563;
  font-weight: 500;
  font-size: 0.95rem;
  transition: all 0.2s;

  &:hover {
    background-color: #E5E7EB;
  }

  &.active {
    background-color: #ffffff;
    color: #111827;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .el-icon {
    font-size: 1.2rem;
    flex-shrink: 0;
  }
  .nav-text {
    opacity: 1;
    width: auto;
    visibility: visible;
    transition: opacity 0.2s ease, width 0.2s ease;
    white-space: nowrap;
  }
}

.nav-group {
  display: flex;
  flex-direction: column;
}

.sub-menu {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.3s ease-in-out;
  
  &.is-open {
    max-height: 500px;
  }

  .sub-item {
    padding-left: 44px;
    font-size: 0.9rem;
  }
}

.parent-item {
  cursor: pointer;
  .arrow-icon {
    margin-left: auto;
    transition: transform 0.3s ease;
    &.is-open {
      transform: rotate(180deg);
    }
  }
}

.sidebar-footer {
  padding: 1.5rem 1rem;
  overflow-x: hidden;

  .user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.3s ease;

    .avatar {
      background-color: #D1D5DB;
      flex-shrink: 0;
    }

    .user-info {
      flex: 1;
      overflow: hidden;
      opacity: 1;
      transition: opacity 0.2s ease;

      .name {
        font-weight: 600;
        color: #111827;
        font-size: 0.9rem;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
      }

      .role {
        color: #4b5563;
        font-size: 0.8rem;
      }
    }

    .more-icon {
      color: #9CA3AF;
      transform: rotate(90deg);
    }
    
    .desktop-more-dropdown {
      opacity: 1;
      transition: opacity 0.2s ease;
    }
  }
}

@media (max-width: 1023px) {
  .sidebar-header .collapse-btn {
    display: none !important;
  }
}
</style>
