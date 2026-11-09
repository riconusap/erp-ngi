import { defineStore } from 'pinia'
import type { Meta, Params } from '@/types/global-types'

export interface MasterDataItem {
  id: string
  code: string
  name: string
  description: string
  status: string
  createdAt: string
}

// Initial dummy data
const DUMMY_MASTER: MasterDataItem[] = [
  {
    id: '1',
    code: 'CAT-001',
    name: 'Kategori Umum',
    description: 'Data referensi kategori umum',
    status: 'Aktif',
    createdAt: '2024-01-01'
  },
  {
    id: '2',
    code: 'CAT-002',
    name: 'Kategori Khusus',
    description: 'Data referensi kategori khusus (terbatas)',
    status: 'Aktif',
    createdAt: '2024-02-15'
  }
]

export const useMasterDataStore = defineStore('masterDataStore', {
  state: () => ({
    allData: [...DUMMY_MASTER] as MasterDataItem[],
    data: [] as MasterDataItem[],
    meta: {
      page: 1,
      limit: 10,
      total: 2,
      total_pages: 1
    } as Meta
  }),
  actions: {
    async getData(params: Params = {}) {
      // Simulate API delay
      await new Promise(resolve => setTimeout(resolve, 400))
      
      let filtered = [...this.allData]
      
      if (params.search) {
        const q = params.search.toLowerCase()
        filtered = filtered.filter(m => m.name.toLowerCase().includes(q) || m.code.toLowerCase().includes(q))
      }
      
      this.meta.total = filtered.length
      this.meta.total_pages = Math.ceil(filtered.length / (params.limit || 10))
      
      const page = params.page || 1
      const limit = params.limit || 10
      const start = (page - 1) * limit
      const end = start + limit
      
      this.data = filtered.slice(start, end)
      
      return { items: this.data, meta: this.meta }
    },
    async create(payload: any) {
      await new Promise(resolve => setTimeout(resolve, 400))
      const newItem: MasterDataItem = {
        id: Date.now().toString(),
        code: payload.code,
        name: payload.name,
        description: payload.description,
        status: payload.status || 'Aktif',
        createdAt: new Date().toISOString().split('T')[0]
      }
      this.allData.push(newItem)
      return { success: true }
    },
    async update(id: string, payload: any) {
      await new Promise(resolve => setTimeout(resolve, 400))
      const idx = this.allData.findIndex(m => m.id === id)
      if (idx !== -1) {
        if (payload.code) this.allData[idx].code = payload.code
        if (payload.name) this.allData[idx].name = payload.name
        if (payload.description !== undefined) this.allData[idx].description = payload.description
        if (payload.status) this.allData[idx].status = payload.status
      }
      return { success: true }
    },
    async delete(id: string) {
      await new Promise(resolve => setTimeout(resolve, 400))
      this.allData = this.allData.filter(m => m.id !== id)
      return { success: true }
    }
  }
})
