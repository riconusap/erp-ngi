export interface Meta {
  page: number
  limit: number
  total: number
  total_pages: number
}

export interface Params {
  page?: number
  limit?: number
  search?: string
  q?: string
  status?: string
  sort?: string
  sort_by?: string
  sort_order?: string
}

export interface OptionModule {
  label: string
  value: string | number
}