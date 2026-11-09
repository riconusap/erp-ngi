import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export interface Pegawai {
  id: string
  nama: string
  nik: string
  nip: string
  no_telp: string | null
  email: string
  avatar: string | null
  user?: any
}

export const usePegawaiStore = defineStore('pegawai', () => {
  const data = ref<Pegawai[]>([])
  const meta = ref({ total: 0, page: 1, limit: 10 })

  const getData = async (params: { search?: string, sort_order?: string, page?: number, limit?: number } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.sort_order) queryParams.sort_order = params.sort_order
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('pegawai', { params: queryParams })
    data.value = response.data?.data || []
    meta.value.total = response.data?.meta?.total || 0
    meta.value.page = response.data?.meta?.current_page || 1
    meta.value.limit = response.data?.meta?.per_page || 10
    return { data: data.value, meta: meta.value }
  }

  const create = async (payload: FormData) => {
    const response = await ApiService.post('pegawai', payload)
    return response.data
  }

  const update = async (id: string, payload: FormData) => {
    // using POST because of FormData and method spoofing might be needed if standard PUT doesn't work,
    // but in our API route we used POST for update.
    const response = await ApiService.post(`pegawai/${id}`, payload)
    return response.data
  }

  const destroy = async (id: string) => {
    const response = await ApiService.delete(`pegawai/${id}`)
    return response.data
  }

  return {
    data,
    meta,
    getData,
    create,
    update,
    destroy
  }
})
