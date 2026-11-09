import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export interface Role {
  id: string
  name: string
  description?: string
  status: 'Aktif' | 'Nonaktif'
  can_access_admin?: boolean
  permissions: Record<string, string[]> // Record<MenuID, ActionCode[]>
}

export const useRoleStore = defineStore('role', () => {
  const data = ref<Role[]>([])
  const meta = ref({
    total: 0,
    page: 1,
    limit: 10
  })

  const getData = async (params: { page?: number, limit?: number, search?: string, status?: string } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.status) queryParams.status = params.status
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('roles', { params: queryParams })
    const items = response.data?.data || []
    const mapped = items.map((r: any) => ({
      ...r,
      id: String(r.id),
      can_access_admin: !!r.can_access_admin,
      permissions: r.permissions || {}
    }))
    data.value = mapped
    meta.value.total = response.data?.meta?.total ?? mapped.length
    meta.value.page = response.data?.meta?.current_page ?? (params.page || 1)
    meta.value.limit = response.data?.meta?.per_page ?? (params.limit || 10)
    return mapped
  }

  const create = async (payload: Omit<Role, 'id'>) => {
    const response = await ApiService.post('roles', payload)
    const newRole = response.data?.data
    if (newRole) {
      newRole.id = String(newRole.id)
      data.value.unshift(newRole)
      meta.value.total++
    }
    return newRole
  }

  const update = async (id: string, payload: Partial<Role>) => {
    const response = await ApiService.put(`roles/${id}`, payload)
    const updated = response.data?.data
    if (updated) {
      updated.id = String(updated.id)
      const index = data.value.findIndex(item => item.id === String(id))
      if (index !== -1) {
        data.value[index] = { ...data.value[index], ...updated }
      }
    }
    return updated
  }

  const remove = async (id: string) => {
    await ApiService.delete(`roles/${id}`)
    const index = data.value.findIndex(item => item.id === String(id))
    if (index !== -1) {
      data.value.splice(index, 1)
      meta.value.total--
    }
    return true
  }

  return {
    data,
    meta,
    getData,
    create,
    update,
    remove
  }
})
