import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export interface User {
  id: string
  name: string
  nama?: string
  email: string
  role_id: string
  role?: any
  status: 'Aktif' | 'Nonaktif'
  created_at?: string
  updated_at?: string
}

export const useUserStore = defineStore('user', () => {
  const data = ref<User[]>([])
  const optionRole = ref<{ label: string, value: string }[]>([])
  const history = ref<any[]>([])
  const meta = ref({
    total: 0,
    page: 1,
    limit: 10
  })

  const getData = async (params: { page?: number, limit?: number, search?: string, status?: string, sort_by?: string, sort_order?: string } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.status) queryParams.status = params.status
    if (params.sort_by) queryParams.sort_by = params.sort_by
    if (params.sort_order) queryParams.sort_order = params.sort_order
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('users', { params: queryParams })
    const items = response.data?.data || []
    const mapped = items.map((u: any) => ({
      ...u,
      id: String(u.id),
      role_id: String(u.role_id),
      nama: u.name || u.nama
    }))
    data.value = mapped
    meta.value.total = response.data?.meta?.total ?? mapped.length
    meta.value.page = response.data?.meta?.current_page ?? (params.page || 1)
    meta.value.limit = response.data?.meta?.per_page ?? (params.limit || 10)
    return mapped
  }

  const getOptionRole = async () => {
    try {
      const response = await ApiService.get('roles')
      const roles = response.data?.data || []
      optionRole.value = roles.map((r: any) => ({
        label: r.name,
        value: String(r.id)
      }))
      return optionRole.value
    } catch {
      return []
    }
  }

  const create = async (payload: any) => {
    let body = payload
    let config = {}
    if (payload instanceof FormData) {
      if (!payload.has('password')) payload.append('password', 'password123')
      config = { headers: { 'Content-Type': 'multipart/form-data' } }
    } else {
      body = {
        ...payload,
        password: payload.password || 'password123'
      }
    }
    const response = await ApiService.post('users', body, config)
    const newUser = response.data?.data
    if (newUser) {
      newUser.id = String(newUser.id)
      newUser.nama = newUser.name
      data.value.unshift(newUser)
      meta.value.total++
    }
    return newUser
  }

  const update = async (id: string, payload: any) => {
    let response;
    if (payload instanceof FormData) {
      payload.append('_method', 'PUT')
      response = await ApiService.post(`users/${id}`, payload, { headers: { 'Content-Type': 'multipart/form-data' } })
    } else {
      response = await ApiService.put(`users/${id}`, payload)
    }
    const updated = response.data?.data
    if (updated) {
      updated.id = String(updated.id)
      updated.nama = updated.name
      const index = data.value.findIndex(item => item.id === String(id))
      if (index !== -1) {
        data.value[index] = { ...data.value[index], ...updated }
      }
    }
    return updated
  }

  const remove = async (id: string) => {
    await ApiService.delete(`users/${id}`)
    const index = data.value.findIndex(item => item.id === String(id))
    if (index !== -1) {
      data.value.splice(index, 1)
      meta.value.total--
    }
    return true
  }

  const getHistory = async (_id: string) => {
    history.value = []
    return []
  }

  return {
    data,
    meta,
    optionRole,
    history,
    getData,
    getOptionRole,
    create,
    update,
    remove,
    delete: remove,
    getHistory
  }
})