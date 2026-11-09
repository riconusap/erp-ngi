import { defineStore } from 'pinia'
import ApiService from '@/core/services/ApiService'
import {
  saveToken,
  getToken,
  destroyToken
} from '@/core/services/JwtService'
import { useMenuStore } from '@/store/menu'

export interface UserAuth {
  id: string
  email: string
  nama: string
  role_id: string
  role: string
  avatar?: string
  exp: number
  iat: number
}

interface Role {
  name: string
  can_access_admin?: boolean
}

interface DataProfile {
  nama: string
  email?: string
  avatar?: string
  role: Role
}

export interface DetailedProfile {
  user: {
    id: number
    username: string
    email: string
    avatar?: string | null
    cover?: string | null
    status: string
    created_at?: string
    role?: {
      id: number
      name: string
      can_access_admin: boolean
    }
    pegawai_id?: string | null
  }
  pegawai?: {
    id: string
    nama: string
    nik: string
    nip: string
    no_telp: string
    email: string
    avatar?: string | null
    cover?: string | null
    created_at?: string
  } | null
  kontrak?: {
    id: number
    no_kontrak: string
    tipe_kontrak: string
    status: string
    terhitung_mulai_tanggal?: string
    terhitung_sampai_tanggal?: string
    element_tetap?: number
    element_variabel?: number
    keterangan?: string
    is_locked?: boolean
    jabatan?: {
      id: number
      kode_jabatan: string
      nama_jabatan: string
      deskripsi?: string
    }
    area?: {
      id: number
      kode_area: string
      nama_area: string
      client?: {
        id: number
        nama_client: string
      }
    }
    komponens?: any[]
    riwayat_kontrak?: any[]
  } | null
  projects: Array<{
    id: number
    kode_project: string
    nama_project: string
    deskripsi?: string
    status: string
    tanggal_mulai?: string
    tanggal_selesai?: string
    client?: { id: number; nama_client: string }
    area?: { id: number; nama_area: string }
    source: string
    tasks_count: number
  }>
  stats: {
    total_projects: number
    total_tasks: number
    completed_tasks: number
    total_cuti: number
  }
}

export const useAuthStore = defineStore('authStore', {
  state: () => ({
    token: getToken() as string | null,
    user: null as any,
    dataProfile: {
      nama: '',
      role: {
        name: '',
        can_access_admin: false
      }
    } as DataProfile,
    detailedProfile: null as DetailedProfile | null,
    loadingProfile: false,
  }),

   getters: {
    isAuthenticated: (state): boolean => {
      return !!state.token
    },
    canAccessAdmin: (state): boolean => {
      return !!(state.user?.role?.can_access_admin ?? state.dataProfile?.role?.can_access_admin)
    },
    hasPegawai: (state): boolean => {
      return !!(state.user?.pegawai_id || state.user?.pegawai)
    }
  },

  actions: {
    async login(payload: any) {
      try {
        const response = await ApiService.post('login', payload)
        const responseData = response.data
        const token = responseData.data?.token
        const user = responseData.data?.user

        if (token) {
          saveToken(token)
          this.token = token
        }

        if (user) {
          this.user = user
          this.dataProfile = {
            nama: user.name || user.nama || '',
            role: {
              name: user.role?.name || '',
              can_access_admin: !!user.role?.can_access_admin
            }
          }

          // Inisialisasi menu langsung dari permission login tanpa perlu hit GET /api/menus
          const permMenus = user.role?.permissions?.menus || user.permissions?.menus
          if (permMenus && Array.isArray(permMenus)) {
            const menuStore = useMenuStore()
            menuStore.setMenusFromPermissions(permMenus)
          }
        }

        return responseData
      } catch (error: any) {
        throw error
      }
    },

    async getProfile() {
      try {
        const response = await ApiService.get('user')
        const user = response.data?.data
        if (user) {
          this.user = user
          this.dataProfile = {
            nama: user.name || user.nama || '',
            role: {
              name: user.role?.name || '',
              can_access_admin: !!user.role?.can_access_admin
            }
          }

          // Inisialisasi menu langsung dari permission user profile
          const permMenus = user.role?.permissions?.menus || user.permissions?.menus
          if (permMenus && Array.isArray(permMenus)) {
            const menuStore = useMenuStore()
            menuStore.setMenusFromPermissions(permMenus)
          }
        }
        return this.dataProfile
      } catch (error: any) {
        throw error
      }
    },

    async fetchDetailedProfile() {
      this.loadingProfile = true
      try {
        const response = await ApiService.get('profile')
        const profileData = response.data?.data
        if (profileData) {
          this.detailedProfile = profileData
          if (profileData.user) {
            this.user = {
              ...profileData.user,
              pegawai: profileData.pegawai,
              role: profileData.user.role
            }
            this.dataProfile = {
              nama: profileData.pegawai?.nama || profileData.user.username || '',
              avatar: profileData.user.avatar || profileData.pegawai?.avatar,
              role: {
                name: profileData.user.role?.name || '',
                can_access_admin: !!profileData.user.role?.can_access_admin
              }
            }
          }
        }
        return this.detailedProfile
      } catch (error: any) {
        throw error
      } finally {
        this.loadingProfile = false
      }
    },

    async updateProfileData(formData: FormData) {
      const response = await ApiService.post('profile', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      await this.fetchDetailedProfile()
      return response.data
    },

    async logout() {
      if (!this.token) return

      try {
        const currentToken = this.token
        destroyToken()
        this.token = null
        this.user = null
        this.dataProfile = {
          nama: '',
          role: {
            name: ''
          }
        }
        const menuStore = useMenuStore()
        menuStore.data = []
        
        await ApiService.post('logout')
      } catch (error) {
        // Silently catch network errors on logout
      }
    }
  }
})