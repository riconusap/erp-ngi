import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'

export interface AttendanceToday {
  tanggal: string
  has_clocked_in: boolean
  has_clocked_out: boolean
  clock_in: string | null
  clock_out: string | null
  status: string
  durasi_kerja: string | null
  lokasi_nama: string
}

export interface EmployeeInfo {
  id: string
  nama: string
  nip: string
  jabatan: string
  area: string
}

export interface AttendanceLog {
  id: number
  date: string
  dayName: string
  clockIn: string
  clockOut: string
  area: string
  workDuration: string
  status: string
}

export interface AttendanceStats {
  total_kehadiran: number
  tepat_waktu: number
  terlambat: number
  izin_cuti: number
  periode: string
}

export interface ShiftToday {
  id: number
  kode: string
  nama: string
  jam_masuk: string | null
  jam_pulang: string | null
  warna: string
  is_libur: boolean
}

export interface ScheduleInfo {
  has_schedule: boolean
  status: 'Terjadwal' | 'Belum Diset'
  notice: string
  default_rule: string
}

export const useAbsensiStore = defineStore('absensi', () => {
  const employee = ref<EmployeeInfo>({
    id: '',
    nama: '',
    nip: '',
    jabatan: '',
    area: ''
  })

  const shift = ref<ShiftToday | null>(null)
  const schedule = ref<ScheduleInfo>({
    has_schedule: true,
    status: 'Terjadwal',
    notice: '',
    default_rule: 'Jam Masuk 08:00 WIB (Batas Toleransi 08:15 WIB)'
  })

  const today = ref<AttendanceToday>({
    tanggal: '',
    has_clocked_in: false,
    has_clocked_out: false,
    clock_in: null,
    clock_out: null,
    status: 'Belum Presensi',
    durasi_kerja: null,
    lokasi_nama: 'Kantor Pusat - Jakarta'
  })

  const stats = ref<AttendanceStats>({
    total_kehadiran: 0,
    tepat_waktu: 0,
    terlambat: 0,
    izin_cuti: 0,
    periode: ''
  })

  const logs = ref<AttendanceLog[]>([])
  const isLoading = ref(false)
  const isSubmitting = ref(false)

  const fetchTodayStatus = async () => {
    try {
      const response = await ApiService.get('absensi/today')
      if (response.data?.status) {
        employee.value = response.data.data.pegawai
        today.value = response.data.data.today
        shift.value = response.data.data.shift || null
        schedule.value = response.data.data.schedule || {
          has_schedule: false,
          status: 'Belum Diset',
          notice: 'Jadwal kerja hari ini belum diatur oleh admin / supervisor area.',
          default_rule: 'Jam Masuk 08:00 WIB (Batas Toleransi 08:15 WIB)'
        }
      }
      return response.data?.data
    } catch (error) {
      console.error('Error fetching today attendance status:', error)
      throw error
    }
  }

  const fetchHistory = async () => {
    isLoading.value = true
    try {
      const response = await ApiService.get('absensi/history')
      if (response.data?.status) {
        stats.value = response.data.data.stats
        logs.value = response.data.data.logs
      }
      return response.data?.data
    } catch (error) {
      console.error('Error fetching attendance history:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const clockIn = async (payload: { lat?: number; long?: number; catatan?: string; area_id?: number } = {}) => {
    isSubmitting.value = true
    try {
      const response = await ApiService.post('absensi/clock-in', payload)
      await fetchTodayStatus()
      await fetchHistory()
      return response.data
    } catch (error) {
      throw error
    } finally {
      isSubmitting.value = false
    }
  }

  const clockOut = async (payload: { lat?: number; long?: number; catatan?: string } = {}) => {
    isSubmitting.value = true
    try {
      const response = await ApiService.post('absensi/clock-out', payload)
      await fetchTodayStatus()
      await fetchHistory()
      return response.data
    } catch (error) {
      throw error
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    employee,
    shift,
    schedule,
    today,
    stats,
    logs,
    isLoading,
    isSubmitting,
    fetchTodayStatus,
    fetchHistory,
    clockIn,
    clockOut
  }
})
