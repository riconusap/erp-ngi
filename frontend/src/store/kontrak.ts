import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

import type { Project } from './project'

export interface KontrakKomponen {
  id?: number
  pegawai_kontrak_id?: number
  jenis: 'tunjangan' | 'potongan' | 'bonus' | 'komisi'
  nama_komponen: string
  metode_hitung: 'tetap' | 'persentase_tetap' | 'persentase_variabel' | 'kali_hari_variabel' | 'custom'
  nilai: number
  keterangan?: string
}

export interface PegawaiKontrak {
  id: number
  pegawai_id: string
  no_kontrak: string
  terhitung_mulai_tanggal: string
  terhitung_sampai_tanggal?: string | null
  jabatan_id: number
  area_id: number
  tipe_kontrak: 'probation' | 'kontrak' | 'permanen'
  status: 'aktif' | 'tidak aktif'
  element_variabel: number
  element_tetap: number
  is_locked: boolean
  keterangan?: string
  project_ids?: number[]
  projects?: Project[]
  pegawai?: {
    id: string
    nama: string
    nik: string
    nip: string
  }
  jabatan?: {
    id: number
    kode_jabatan: string
    nama_jabatan: string
    komponens?: any[]
  }
  area?: {
    id: number
    kode_area: string
    nama_area: string
    client?: {
      id: number
      kode_client: string
      nama_client: string
    }
  }
  komponens?: KontrakKomponen[]
}

export const useKontrakStore = defineStore('kontrak', () => {
  const data = ref<PegawaiKontrak[]>([])
  const meta = ref({ total: 0, page: 1, limit: 10 })

  const getData = async (params: {
    search?: string
    pegawai_id?: string
    jabatan_id?: number
    area_id?: number
    project_id?: number
    status?: string
    tipe_kontrak?: string
    page?: number
    limit?: number
  } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.pegawai_id) queryParams.pegawai_id = params.pegawai_id
    if (params.jabatan_id) queryParams.jabatan_id = params.jabatan_id
    if (params.area_id) queryParams.area_id = params.area_id
    if (params.project_id) queryParams.project_id = params.project_id
    if (params.status) queryParams.status = params.status
    if (params.tipe_kontrak) queryParams.tipe_kontrak = params.tipe_kontrak
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('pegawai-kontraks', { params: queryParams })
    const resData = response.data?.data
    data.value = resData?.data || []
    meta.value.total = resData?.total || 0
    meta.value.page = resData?.current_page || 1
    meta.value.limit = resData?.per_page || 10
    return { data: data.value, meta: meta.value }
  }

  const getById = async (id: number) => {
    const response = await ApiService.get(`pegawai-kontraks/${id}`)
    return response.data?.data
  }

  const getLatestByPegawai = async (pegawaiId: string) => {
    const response = await ApiService.get(`pegawai-kontraks/latest/${pegawaiId}`)
    return response.data
  }

  const create = async (payload: any) => {
    const response = await ApiService.post('pegawai-kontraks', payload)
    return response.data
  }

  const update = async (id: number, payload: any) => {
    const response = await ApiService.put(`pegawai-kontraks/${id}`, payload)
    return response.data
  }

  const destroy = async (id: number) => {
    const response = await ApiService.delete(`pegawai-kontraks/${id}`)
    return response.data
  }

  const lock = async (id: number) => {
    const response = await ApiService.post(`pegawai-kontraks/${id}/lock`, {})
    return response.data
  }

  return {
    data,
    meta,
    getData,
    getById,
    getLatestByPegawai,
    create,
    update,
    destroy,
    lock
  }
})
