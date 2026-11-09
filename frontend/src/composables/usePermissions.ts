import { computed } from 'vue'
import { useAuthStore } from '@/store/auth'

export function usePermissions() {
  const authStore = useAuthStore()

  const isSuperAdmin = computed(() => {
    return authStore.user?.role?.name === 'Super Admin' ||
           authStore.dataProfile?.role?.name === 'Super Admin'
  })

  const userPermissions = computed(() => {
    const raw = authStore.user?.role?.permissions || authStore.user?.permissions
    if (raw && typeof raw === 'object' && Array.isArray((raw as any).menus)) {
      const flattened: any[] = []
      ;(raw as any).menus.forEach((menu: any) => {
        const actions = menu.actions || menu.list_action || []
        actions.forEach((act: any) => {
          flattened.push({
            id: act.id,
            code: act.code,
            name: act.name,
            menu: {
              id: menu.id,
              name: menu.name,
              path: menu.path,
              icon: menu.icon,
              sort_order: menu.sort_order,
              status: menu.status,
              parent_id: menu.parent_id
            }
          })
        })
      })
      return flattened
    }
    return Array.isArray(raw) ? raw : []
  })

  const userMenus = computed(() => {
    const raw = authStore.user?.role?.permissions || authStore.user?.permissions
    if (raw && typeof raw === 'object' && Array.isArray((raw as any).menus)) {
      return (raw as any).menus
    }
    return []
  })

  /**
   * Cek apakah pengguna memiliki hak akses terhadap modul/menu dan kode aksi tertentu
   * @param target Nama modul, ID modul, atau URL path (contoh: 'Pengguna', 'Akses Admin', '/dashboard/admin')
   * @param action Kode aksi ('read', 'create', 'update', 'delete', 'reset-password')
   */
  const can = (target: string, action: string = 'read'): boolean => {
    if (isSuperAdmin.value) {
      return true
    }

    const permissions = userPermissions.value
    if (!permissions || permissions.length === 0) {
      return false
    }

    const normalizedTarget = target.trim().toLowerCase()

    return permissions.some((p: any) => {
      if (p.code !== action) return false

      const menu = p.menu
      if (!menu) return false
      if (menu.status === 'Nonaktif') return false

      const menuName = (menu.name || '').toLowerCase()
      const menuPath = (menu.path || '').toLowerCase()
      const menuId = String(menu.id || '')

      if (
        menuId === normalizedTarget ||
        menuName === normalizedTarget ||
        menuPath === normalizedTarget ||
        menuPath.replace(/^\//, '') === normalizedTarget.replace(/^\//, '')
      ) {
        return true
      }



      return false
    })
  }

  /**
   * Cek apakah pengguna memiliki akses ke rute / URL tertentu
   */
  const hasRouteAccess = (path: string): boolean => {
    if (isSuperAdmin.value) return true

    // Halaman umum / default dashboard selalu diizinkan untuk semua user yang login
    if (
      path === '/dashboard/overview' ||
      path === '/dashboard' ||
      path === '/dashboard/profile' ||
      path === '/success' ||
      path === '/login'
    ) {
      return true
    }

    // Direct match check
    if (can(path, 'read')) {
      return true
    }

    // Cek sub-rute create (misal: /dashboard/konfigurasi/peran/create)
    if (path.endsWith('/create')) {
      const basePath = path.replace(/\/create$/, '')
      return can(basePath, 'create')
    }

    // Cek sub-rute edit (misal: /dashboard/konfigurasi/peran/1/edit)
    if (path.endsWith('/edit') || path.includes('/edit')) {
      const basePath = path.replace(/\/[^/]+\/edit$/, '').replace(/\/edit$/, '')
      return can(basePath, 'update')
    }

    // Prefix match dengan menu path yang diizinkan untuk read
    const permissions = userPermissions.value
    if (permissions && permissions.length > 0) {
      return permissions.some((p: any) => {
        if (p.code !== 'read') return false
        if (p.menu?.status === 'Nonaktif') return false
        const menuPath = p.menu?.path
        if (!menuPath || menuPath === '/' || menuPath === '/dashboard') return false
        return path.startsWith(menuPath)
      })
    }

    return false
  }

  const canCreate = (target: string) => can(target, 'create')
  const canRead = (target: string) => can(target, 'read')
  const canUpdate = (target: string) => can(target, 'update')
  const canDelete = (target: string) => can(target, 'delete')
  const canResetPassword = (target: string) => can(target, 'reset-password')

  return {
    isSuperAdmin,
    userPermissions,
    userMenus,
    can,
    hasRouteAccess,
    canCreate,
    canRead,
    canUpdate,
    canDelete,
    canResetPassword
  }
}

export default usePermissions
