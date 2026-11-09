import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export type TipeNilaiKomponen = 'tetap' | 'persentase_tetap' | 'persentase_variabel' | 'kali_hari_variabel' | 'custom'

export interface JabatanKomponen {
  id?: number
  jenis: 'tunjangan' | 'potongan' | 'bonus' | 'komisi'
  nama_komponen: string
  tipe_nilai: TipeNilaiKomponen
  nilai_default: number
  keterangan?: string
}

export interface Jabatan {
  id: number
  kode_jabatan: string
  nama_jabatan: string
  deskripsi?: string
  status: 'Aktif' | 'Nonaktif'
  komponens?: JabatanKomponen[]
  kontraks_count?: number
}

export const useJabatanStore = defineStore('jabatan', () => {
  const data = ref<Jabatan[]>([])
  const meta = ref({ total: 0, page: 1, limit: 10 })

  const getData = async (params: { search?: string, status?: string, page?: number, limit?: number } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.status) queryParams.status = params.status
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('jabatans', { params: queryParams })
    const resData = response.data?.data
    data.value = resData?.data || []
    meta.value.total = resData?.total || 0
    meta.value.page = resData?.current_page || 1
    meta.value.limit = resData?.per_page || 10
    return { data: data.value, meta: meta.value }
  }

  const getAll = async () => {
    const response = await ApiService.get('jabatans/all')
    return response.data?.data || []
  }

  const getById = async (id: number) => {
    const response = await ApiService.get(`jabatans/${id}`)
    return response.data?.data
  }

  const create = async (payload: any) => {
    const response = await ApiService.post('jabatans', payload)
    return response.data
  }

  const update = async (id: number, payload: any) => {
    const response = await ApiService.put(`jabatans/${id}`, payload)
    return response.data
  }

  const destroy = async (id: number) => {
    const response = await ApiService.delete(`jabatans/${id}`)
    return response.data
  }

  return {
    data,
    meta,
    getData,
    getAll,
    getById,
    create,
    update,
    destroy
  }
})
