import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export interface MenuAction {
  id?: string
  code: string
  name: string
}

export interface Menu {
  id: string
  name: string
  path: string
  icon: string
  parent_id: string | null
  order: number
  status: 'Aktif' | 'Nonaktif'
  children?: Menu[]
  actions?: MenuAction[]
}

const mapMenu = (m: any): Menu => ({
  id: String(m.id),
  name: m.name,
  path: m.path || '',
  icon: m.icon || '',
  parent_id: m.parent_id ? String(m.parent_id) : null,
  order: m.order ?? m.sort_order ?? 0,
  status: m.status,
  actions: m.actions || [],
  children: m.children ? m.children.map(mapMenu) : []
})

const countMenus = (items: Menu[]): number => {
  return items.reduce((acc, item) => acc + 1 + (item.children ? countMenus(item.children) : 0), 0)
}

export function mapPermissionsToMenuData(rawMenus: any[]): Menu[] {
  if (!Array.isArray(rawMenus) || rawMenus.length === 0) {
    return []
  }

  const hasNestedChildren = rawMenus.some(m => Array.isArray(m.children) && m.children.length > 0)
  if (hasNestedChildren) {
    return rawMenus.map(mapMenu)
  }

  const menuMap = new Map<string, Menu>()
  const rootMenus: Menu[] = []

  rawMenus.forEach((m: any) => {
    const item: Menu = {
      id: String(m.id),
      name: m.name || '',
      path: m.path || '',
      icon: m.icon || '',
      parent_id: m.parent_id ? String(m.parent_id) : null,
      order: m.order ?? m.sort_order ?? 0,
      status: m.status || 'Aktif',
      actions: m.actions || [],
      children: []
    }
    menuMap.set(item.id, item)
  })

  menuMap.forEach((item) => {
    if (item.parent_id && menuMap.has(item.parent_id)) {
      const parent = menuMap.get(item.parent_id)!
      if (!parent.children) {
        parent.children = []
      }
      parent.children.push(item)
    } else {
      rootMenus.push(item)
    }
  })

  rootMenus.sort((a, b) => a.order - b.order)
  rootMenus.forEach(root => {
    if (root.children && root.children.length > 0) {
      root.children.sort((a, b) => a.order - b.order)
    }
  })

  return rootMenus
}

export const useMenuStore = defineStore('menu', () => {
  const data = ref<Menu[]>([])
  const meta = ref({ total: 0, page: 1, limit: 10 })

  const setMenusFromPermissions = (permissionsMenus: any[]) => {
    const mapped = mapPermissionsToMenuData(permissionsMenus)
    data.value = mapped
    meta.value.total = countMenus(mapped)
    return mapped
  }

  const getParentMenus = () => {
    return data.value.map(item => ({ label: item.name, value: item.id }))
  }

  const getData = async (params: { search?: string, status?: string, page?: number, limit?: number } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.status) queryParams.status = params.status
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('menus', { params: queryParams })
    const items = response.data?.data || []
    const mapped = items.map(mapMenu)
    data.value = mapped
    meta.value.total = response.data?.meta?.total ?? countMenus(mapped)
    meta.value.page = response.data?.meta?.current_page ?? (params.page || 1)
    meta.value.limit = response.data?.meta?.per_page ?? (params.limit || 10)
    return { data: mapped, meta: meta.value }
  }

  const create = async (payload: any) => {
    const response = await ApiService.post('menus', payload)
    await getData()
    return response.data?.data
  }

  const update = async (id: string, payload: any) => {
    const response = await ApiService.put(`menus/${id}`, payload)
    await getData()
    return response.data?.data
  }

  const toggleStatus = async (id: string) => {
    const response = await ApiService.patch(`menus/${id}/toggle-status`, {})
    await getData()
    return response.data?.data
  }

  const remove = async (id: string) => {
    await ApiService.delete(`menus/${id}`)
    await getData()
    return true
  }

  const getSiblings = (parentId: string | null = null): Menu[] => {
    if (!parentId) {
      return data.value
    }
    const parent = data.value.find(item => item.id === String(parentId))
    return parent?.children || []
  }

  const findSiblingByOrder = (order: number, parentId: string | null = null, excludeId?: string): Menu | undefined => {
    const siblings = getSiblings(parentId)
    return siblings.find(item => Number(item.order) === Number(order) && (!excludeId || String(item.id) !== String(excludeId)))
  }

  const getNextAvailableOrder = (parentId: string | null = null): number => {
    const siblings = getSiblings(parentId)
    if (siblings.length === 0) return 1
    const maxOrder = Math.max(...siblings.map(item => Number(item.order) || 0))
    return maxOrder + 1
  }

  const reorder = async (id: string, targetOrder: number, confirmSwap: boolean = true) => {
    const response = await ApiService.post(`menus/${id}/reorder`, {
      target_order: targetOrder,
      confirm_swap: confirmSwap
    })
    await getData()
    return response.data
  }

  return {
    data,
    meta,
    getParentMenus,
    setMenusFromPermissions,
    getSiblings,
    findSiblingByOrder,
    getNextAvailableOrder,
    getData,
    create,
    update,
    reorder,
    toggleStatus,
    remove
  }
})
