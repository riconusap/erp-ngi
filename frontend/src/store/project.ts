import { defineStore } from 'pinia'
import { ref } from 'vue'
import ApiService from '@/core/services/ApiService'
import type { Client, Area } from './clientArea'

export type ProjectStatus = 'Perencanaan' | 'Berjalan' | 'Selesai' | 'Ditunda' | 'Batal'

export interface Project {
  id: number
  client_id: number
  area_id?: number | null
  kode_project: string
  nama_project: string
  deskripsi?: string | null
  tanggal_mulai?: string | null
  tanggal_selesai?: string | null
  nilai_kontrak?: number | null
  status: ProjectStatus
  created_by?: number | null
  client?: Client
  area?: Area | null
  creator?: { id: number; username: string }
  pegawai_kontraks_count?: number
  pegawai_kontraks?: any[]
  created_at?: string
  updated_at?: string
}

export const useProjectStore = defineStore('project', () => {
  const projects = ref<Project[]>([])
  const projectMeta = ref({ total: 0, page: 1, limit: 10 })

  const getProjects = async (params: {
    search?: string
    client_id?: number | string
    area_id?: number | string
    status?: string
    page?: number
    limit?: number
  } = {}) => {
    const queryParams: any = {}
    if (params.search) queryParams.search = params.search
    if (params.client_id) queryParams.client_id = params.client_id
    if (params.area_id) queryParams.area_id = params.area_id
    if (params.status) queryParams.status = params.status
    if (params.page) queryParams.page = params.page
    if (params.limit) queryParams.limit = params.limit

    const response = await ApiService.query('projects', { params: queryParams })
    const resData = response.data?.data
    projects.value = resData?.data || []
    projectMeta.value.total = resData?.total || 0
    projectMeta.value.page = resData?.current_page || 1
    projectMeta.value.limit = resData?.per_page || 10
    return { data: projects.value, meta: projectMeta.value }
  }

  const getAllProjects = async (params: { client_id?: number; status?: string } = {}) => {
    const response = await ApiService.query('projects/all', { params })
    return response.data?.data || []
  }

  const getProject = async (id: number) => {
    const response = await ApiService.get(`projects/${id}`)
    return response.data?.data
  }

  const createProject = async (payload: Partial<Project>) => {
    const response = await ApiService.post('projects', payload)
    return response.data
  }

  const updateProject = async (id: number, payload: Partial<Project>) => {
    const response = await ApiService.put(`projects/${id}`, payload)
    return response.data
  }

  const destroyProject = async (id: number) => {
    const response = await ApiService.delete(`projects/${id}`)
    return response.data
  }

  return {
    projects,
    projectMeta,
    getProjects,
    getAllProjects,
    getProject,
    createProject,
    updateProject,
    destroyProject
  }
})
