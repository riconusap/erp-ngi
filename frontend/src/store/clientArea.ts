import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export interface Client {
  id: number
  kode_client: string
  nama_client: string
  alamat?: string
  telepon?: string
  email?: string
  status: 'Aktif' | 'Nonaktif'
  areas_count?: number
  areas?: Area[]
}

export interface Area {
  id: number
  client_id: number
  kode_area: string
  nama_area: string
  keterangan?: string
  status: 'Aktif' | 'Nonaktif'
  client?: Client
  kontraks_count?: number
}

export const useClientAreaStore = defineStore('clientArea', () => {
  const clients = ref<Client[]>([])
  const clientMeta = ref({ total: 0, page: 1, limit: 10 })

  const areas = ref<Area[]>([])
  const areaMeta = ref({ total: 0, page: 1, limit: 10 })

  // --- CLIENT ACTIONS ---
  const getClients = async (params: { search?: string, status?: string, page?: number, limit?: number } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.status) queryParams.status = params.status
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('clients', { params: queryParams })
    const resData = response.data?.data
    clients.value = resData?.data || []
    clientMeta.value.total = resData?.total || 0
    clientMeta.value.page = resData?.current_page || 1
    clientMeta.value.limit = resData?.per_page || 10
    return { data: clients.value, meta: clientMeta.value }
  }

  const getAllClients = async () => {
    const response = await ApiService.get('clients/all')
    return response.data?.data || []
  }

  const getClient = async (id: number) => {
    const response = await ApiService.get(`clients/${id}`)
    return response.data?.data
  }

  const createClient = async (payload: any) => {
    const response = await ApiService.post('clients', payload)
    return response.data
  }

  const updateClient = async (id: number, payload: any) => {
    const response = await ApiService.put(`clients/${id}`, payload)
    return response.data
  }

  const destroyClient = async (id: number) => {
    const response = await ApiService.delete(`clients/${id}`)
    return response.data
  }

  // --- AREA ACTIONS ---
  const getAreas = async (params: { search?: string, client_id?: number, status?: string, page?: number, limit?: number } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.client_id) queryParams.client_id = params.client_id
    if (params.status) queryParams.status = params.status
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('areas', { params: queryParams })
    const resData = response.data?.data
    areas.value = resData?.data || []
    areaMeta.value.total = resData?.total || 0
    areaMeta.value.page = resData?.current_page || 1
    areaMeta.value.limit = resData?.per_page || 10
    return { data: areas.value, meta: areaMeta.value }
  }

  const getAllAreas = async (clientId?: number) => {
    const url = clientId ? `areas/all?client_id=${clientId}` : 'areas/all'
    const response = await ApiService.get(url)
    return response.data?.data || []
  }

  const createArea = async (payload: any) => {
    const response = await ApiService.post('areas', payload)
    return response.data
  }

  const updateArea = async (id: number, payload: any) => {
    const response = await ApiService.put(`areas/${id}`, payload)
    return response.data
  }

  const destroyArea = async (id: number) => {
    const response = await ApiService.delete(`areas/${id}`)
    return response.data
  }

  return {
    clients,
    clientMeta,
    areas,
    areaMeta,
    getClients,
    getAllClients,
    getClient,
    createClient,
    updateClient,
    destroyClient,
    getAreas,
    getAllAreas,
    createArea,
    updateArea,
    destroyArea
  }
})
