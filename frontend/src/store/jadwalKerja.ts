import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export interface Shift {
  id: number
  kode_shift: string
  nama_shift: string
  jam_masuk: string | null
  jam_pulang: string | null
  toleransi_menit: number
  warna: string
  is_libur: boolean
  status: 'Aktif' | 'Nonaktif'
  keterangan?: string
}

export interface ScheduledDay {
  date: string
  day: number
  dayName: string
  isWeekend: boolean
  isToday: boolean
}

export interface EmployeeScheduleCell {
  id: number
  shift_id: number
  kode_shift: string
  nama_shift: string
  jam_masuk: string | null
  jam_pulang: string | null
  warna: string
  is_libur: boolean
  keterangan?: string
}

export interface EmployeeRoster {
  id: string
  nama: string
  nip: string
  jabatan: string
  schedules: Record<string, EmployeeScheduleCell>
  total_shifts: number
  total_off: number
}

export interface RosterData {
  area: {
    id: number
    kode_area: string
    nama_area: string
    client: {
      id: number
      nama_client: string
    } | null
  }
  periode: {
    bulan: number
    tahun: number
    bulan_nama: string
    total_hari: number
  }
  days: ScheduledDay[]
  shifts: Shift[]
  employees: EmployeeRoster[]
  stats: {
    total_pegawai: number
    total_shifts_assigned: number
    coverage_percentage: number
  }
}

export const useJadwalKerjaStore = defineStore('jadwalKerja', () => {
  const rosterData = ref<RosterData | null>(null)
  const shifts = ref<Shift[]>([])
  const isLoading = ref(false)
  const isSaving = ref(false)

  const getRoster = async (params: { area_id?: number, client_id?: number, bulan?: number, tahun?: number, search?: string } = {}) => {
    isLoading.value = true
    try {
      const response = await ApiService.query('jadwal-kerja/roster', { params })
      rosterData.value = response.data?.data || null
      return rosterData.value
    } finally {
      isLoading.value = false
    }
  }

  const assignCell = async (payload: {
    pegawai_id: string
    area_id: number
    tanggal: string
    shift_id?: number | null
    keterangan?: string
  }) => {
    const response = await ApiService.post('jadwal-kerja/assign-cell', payload)
    return response.data
  }

  const bulkAssign = async (payload: {
    area_id: number
    pegawai_ids: string[]
    shift_id: number
    start_date: string
    end_date: string
    days_of_week?: number[]
    sabtu_action?: 'libur' | 'kerja' | 'lewati'
    minggu_action?: 'libur' | 'kerja' | 'lewati'
    sabtu_libur?: boolean
    minggu_libur?: boolean
    overwrite?: boolean
  }) => {
    isSaving.value = true
    try {
      const response = await ApiService.post('jadwal-kerja/bulk-assign', payload)
      return response.data
    } finally {
      isSaving.value = false
    }
  }

  const copyPreviousMonth = async (payload: {
    area_id: number
    target_bulan: number
    target_tahun: number
    overwrite?: boolean
  }) => {
    isSaving.value = true
    try {
      const response = await ApiService.post('jadwal-kerja/copy-previous-month', payload)
      return response.data
    } finally {
      isSaving.value = false
    }
  }

  const getShifts = async () => {
    const response = await ApiService.get('shifts')
    shifts.value = response.data?.data || []
    return shifts.value
  }

  const createShift = async (payload: Partial<Shift>) => {
    const response = await ApiService.post('shifts', payload)
    return response.data
  }

  const updateShift = async (id: number, payload: Partial<Shift>) => {
    const response = await ApiService.put(`shifts/${id}`, payload)
    return response.data
  }

  const deleteShift = async (id: number) => {
    const response = await ApiService.delete(`shifts/${id}`)
    return response.data
  }

  return {
    rosterData,
    shifts,
    isLoading,
    isSaving,
    getRoster,
    assignCell,
    bulkAssign,
    copyPreviousMonth,
    getShifts,
    createShift,
    updateShift,
    deleteShift,
  }
})
