import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'
import type { Project } from './project'

export type TaskStatus =
  | 'Todo'
  | 'In Progress'
  | 'In Review'
  | 'Done'
  | 'On Hold'
  | 'Canceled'
  | 'Rejected'
  | 'Reassigned'
  | 'Reopened'

export type TaskPriority = 'Low' | 'Medium' | 'High'
export type TaskType = 'Reguler' | 'Rutin'
export type RecurringFrequency = 'Harian' | 'Mingguan' | 'Bulanan'

export interface TaskItem {
  id: number
  project_id: number
  assigned_to: string
  pegawai_kontrak_id?: number | null
  created_by?: number | null
  assigned_by?: string | null
  judul: string
  deskripsi?: string | null
  status: TaskStatus
  prioritas: TaskPriority
  deadline?: string | null
  tanggal_selesai?: string | null
  tipe: TaskType
  nilai?: number | null
  catatan_review?: string | null
  frekuensi_rutin?: RecurringFrequency | null
  tanggal_mulai_rutin?: string | null
  tanggal_selesai_rutin?: string | null
  parent_task_id?: number | null
  status_keterlambatan?: string
  project?: Project
  assigned_to_user?: any
  assignedTo?: {
    id: string
    nama: string
    nik: string
    nip?: string
    avatar?: string
  }
  assignedBy?: {
    id: string
    nama: string
    nik: string
  }
  creator?: {
    id: number
    username: string
    email: string
  }
  pegawaiKontrak?: {
    id: number
    no_kontrak: string
    tipe_kontrak: string
    status: string
  }
  created_at?: string
  updated_at?: string
}

export interface TaskSummary {
  total: number
  todo: number
  in_progress: number
  in_review: number
  done: number
  terlambat: number
  rutin: number
  reguler: number
}

export const useTaskStore = defineStore('task', () => {
  const tasks = ref<TaskItem[]>([])
  const taskMeta = ref({ total: 0, page: 1, limit: 10 })
  const taskSummary = ref<TaskSummary>({
    total: 0,
    todo: 0,
    in_progress: 0,
    in_review: 0,
    done: 0,
    terlambat: 0,
    rutin: 0,
    reguler: 0
  })

  const getTasks = async (params: {
    tab?: string
    search?: string
    project_id?: number | string
    assigned_to?: string
    status?: string
    prioritas?: string
    tipe?: string
    page?: number
    limit?: number
  } = {}) => {
    const queryParams: any = {}
    if (params.tab) queryParams.tab = params.tab
    if (params.search) queryParams.search = params.search
    if (params.project_id) queryParams.project_id = params.project_id
    if (params.assigned_to) queryParams.assigned_to = params.assigned_to
    if (params.status) queryParams.status = params.status
    if (params.prioritas) queryParams.prioritas = params.prioritas
    if (params.tipe) queryParams.tipe = params.tipe
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('tasks', { params: queryParams })
    const resData = response.data?.data
    tasks.value = resData?.data || []
    taskMeta.value.total = resData?.total || 0
    taskMeta.value.page = resData?.current_page || 1
    taskMeta.value.limit = resData?.per_page || 10
    return { data: tasks.value, meta: taskMeta.value }
  }

  const getTaskSummary = async (params: { tab?: string; project_id?: number | string } = {}) => {
    const response = await ApiService.query('tasks/summary', { params })
    taskSummary.value = response.data?.data || taskSummary.value
    return taskSummary.value
  }

  const getTask = async (id: number) => {
    const response = await ApiService.get(`tasks/${id}`)
    return response.data?.data
  }

  const createTask = async (payload: Partial<TaskItem>) => {
    const response = await ApiService.post('tasks', payload)
    return response.data
  }

  const updateTask = async (id: number, payload: Partial<TaskItem>) => {
    const response = await ApiService.put(`tasks/${id}`, payload)
    return response.data
  }

  const updateTaskStatus = async (
    id: number,
    status: TaskStatus,
    catatan_review?: string,
    nilai?: number
  ) => {
    const payload: any = { status }
    if (catatan_review !== undefined) payload.catatan_review = catatan_review
    if (nilai !== undefined) payload.nilai = nilai
    const response = await ApiService.patch(`tasks/${id}/status`, payload)
    return response.data
  }

  const rateTask = async (id: number, nilai: number, catatan_review?: string) => {
    const payload: any = { nilai }
    if (catatan_review !== undefined) payload.catatan_review = catatan_review
    const response = await ApiService.patch(`tasks/${id}/rate`, payload)
    return response.data
  }

  const deleteTask = async (id: number) => {
    const response = await ApiService.delete(`tasks/${id}`)
    return response.data
  }

  return {
    tasks,
    taskMeta,
    taskSummary,
    getTasks,
    getTaskSummary,
    getTask,
    createTask,
    updateTask,
    updateTaskStatus,
    rateTask,
    deleteTask,
  }
})
