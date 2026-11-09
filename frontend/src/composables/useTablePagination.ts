import { ref, watch, type Ref } from 'vue'

export interface TablePaginationOptions {
  defaultPageSize?: number
  debounceMs?: number
  onFetch: () => void | Promise<void>
}

export function useTablePagination(options: TablePaginationOptions) {
  const { defaultPageSize = 5, debounceMs = 400, onFetch } = options

  const searchQuery = ref('')
  const currentPage = ref(1)
  const pageSize = ref(defaultPageSize)
  const activeFilter = ref('')
  const activeSort = ref('')
  const isLoading = ref(false)

  let searchTimeout: ReturnType<typeof setTimeout> | null = null

  const fetchWithLoading = async () => {
    isLoading.value = true
    try {
      await onFetch()
    } finally {
      isLoading.value = false
    }
  }

  const resetPageAndFetch = () => {
    if (currentPage.value !== 1) {
      currentPage.value = 1
      return
    }
    fetchWithLoading()
  }

  watch(currentPage, () => {
    fetchWithLoading()
  })

  watch(pageSize, () => {
    resetPageAndFetch()
  })

  watch([activeFilter, activeSort], () => {
    resetPageAndFetch()
  })

  watch(searchQuery, () => {
    if (searchTimeout) {
      clearTimeout(searchTimeout)
    }
    searchTimeout = setTimeout(() => {
      resetPageAndFetch()
    }, debounceMs)
  })

  return {
    searchQuery,
    currentPage,
    pageSize,
    activeFilter,
    activeSort,
    isLoading,
    resetPageAndFetch,
    fetchWithLoading
  }
}
