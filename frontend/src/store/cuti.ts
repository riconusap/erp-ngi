import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export interface PegawaiSimple {
  id: string
  nama: string
  nik?: string
  nip?: string
  avatar?: string
  active_kontrak?: {
    jabatan?: {
      nama_jabatan: string
    }
  }
}

export interface PengajuanCuti {
  id: number
  nomor_pengajuan: string
  pegawai_id: string
  user_id: number
  tipe_cuti: string
  tanggal_mulai: string
  tanggal_selesai: string
  jumlah_hari: number
  alasan: string
  dokumen_pendukung?: string | null
  status: 'Menunggu Persetujuan' | 'Disetujui' | 'Ditolak' | 'Dibatalkan'
  disetujui_otomatis: boolean
  disetujui_pada?: string | null
  catatan_persetujuan?: string | null
  ditolak_oleh_pegawai_id?: string | null
  ditolak_oleh_user_id?: number | null
  alasan_penolakan?: string | null
  ditolak_pada?: string | null
  dibatalkan_pada?: string | null
  alasan_pembatalan?: string | null
  created_at: string
  updated_at: string
  h_minus_info?: string
  can_auto_approve?: boolean
  can_reject?: boolean
  can_cancel?: boolean
  authorized_superiors?: PegawaiSimple[]
  pegawai?: PegawaiSimple
  penolak_pegawai?: PegawaiSimple
  penolak_user?: { id: number; username: string } | null
}

export const useCutiStore = defineStore('cuti', () => {
  const data = ref<PengajuanCuti[]>([])
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  })
  const summary = ref({
    total: 0,
    menunggu: 0,
    disetujui: 0,
    ditolak: 0,
    dibatalkan: 0,
    saya: 0,
    bawahan: 0
  })
  const eligibility = ref<{
    pegawai: PegawaiSimple | null
    superiors: PegawaiSimple[]
    min_date: string
    min_date_formatted: string
  }>({
    pegawai: null,
    superiors: [],
    min_date: '',
    min_date_formatted: ''
  })
  const loading = ref(false)

  const getData = async (params: {
    page?: number
    limit?: number
    search?: string
    status?: string
    tipe_cuti?: string
    tab?: string
  } = {}) => {
    loading.value = true
    try {
      const response = await ApiService.query('cuti', { params })
      data.value = response.data?.data || []
      if (response.data?.meta) {
        meta.value = response.data.meta
      }
      if (response.data?.summary) {
        summary.value = response.data.summary
      }
      return response.data
    } finally {
      loading.value = false
    }
  }

  const getDetail = async (id: number | string) => {
    const response = await ApiService.get(`cuti/${id}`)
    return response.data?.data
  }

  const getEligibility = async () => {
    const response = await ApiService.get('cuti/my-eligibility')
    if (response.data?.status) {
      eligibility.value = {
        pegawai: response.data.pegawai,
        superiors: response.data.superiors || [],
        min_date: response.data.min_date,
        min_date_formatted: response.data.min_date_formatted
      }
    }
    return eligibility.value
  }

  const getSuperiors = async (pegawaiId: string) => {
    const response = await ApiService.get(`cuti/superiors/${pegawaiId}`)
    return response.data
  }

  const create = async (payload: FormData | object) => {
    let response
    if (payload instanceof FormData) {
      response = await ApiService.post('cuti', payload, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    } else {
      response = await ApiService.post('cuti', payload)
    }
    return response.data
  }

  const reject = async (id: number | string, payload: { alasan_penolakan: string }) => {
    const response = await ApiService.post(`cuti/${id}/reject`, payload)
    return response.data
  }

  const cancel = async (id: number | string, payload: { alasan_pembatalan?: string } = {}) => {
    const response = await ApiService.post(`cuti/${id}/cancel`, payload)
    return response.data
  }

  const remove = async (id: number | string) => {
    const response = await ApiService.delete(`cuti/${id}`)
    return response.data
  }

  return {
    data,
    meta,
    summary,
    eligibility,
    loading,
    getData,
    getDetail,
    getEligibility,
    getSuperiors,
    create,
    reject,
    cancel,
    remove
  }
})
