/**
 * Format string tanggal ISO ke format Indonesia yang mudah dibaca.
 * Contoh: 2026-08-13T07:00:00Z -> "13 Agustus 2026 14:00:00"
 */
export const formatDate = (value?: string | null): string => {
  if (!value) return '-'

  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    }).format(new Date(value))
  } catch {
    return value
  }
}

/**
 * Format status string menjadi huruf kapital di awal.
 * Contoh: "aktif" -> "Aktif"
 */
export const formatStatus = (status?: string | null): string => {
  if (!status) return '-'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

/**
 * Format angka atau string menjadi mata uang Rupiah Indonesia.
 * Contoh: 450000000 -> "Rp 450.000.000"
 */
export const formatRupiah = (val?: number | string | null): string => {
  if (val === null || val === undefined || val === '') return '-'
  const num = typeof val === 'string' ? parseFloat(val) : val
  if (isNaN(num)) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

