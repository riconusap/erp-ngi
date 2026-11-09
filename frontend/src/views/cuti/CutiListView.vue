<template>
  <div class="page-container">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Pengajuan Cuti</AppHeading>
          <el-tag type="info" class="total-badge">Total: {{ summary.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola pengajuan cuti pegawai dengan skema H-7 dan auto-approval H-3</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="router.push('/dashboard/cuti/pengajuan')">
          <el-icon class="me-1"><Plus /></el-icon> Ajukan Cuti Baru
        </AppButton>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="stats-grid mt-4">
      <div class="stat-card stat-total">
        <div class="stat-icon-wrapper">
          <el-icon :size="24"><Calendar /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Pengajuan</span>
          <span class="stat-value">{{ summary.total }}</span>
        </div>
      </div>

      <div class="stat-card stat-pending">
        <div class="stat-icon-wrapper">
          <el-icon :size="24"><Clock /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">Menunggu Persetujuan</span>
          <span class="stat-value">{{ summary.menunggu }}</span>
          <span class="stat-sub">Auto approve H-3</span>
        </div>
      </div>

      <div class="stat-card stat-approved">
        <div class="stat-icon-wrapper">
          <el-icon :size="24"><CircleCheck /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">Disetujui</span>
          <span class="stat-value">{{ summary.disetujui }}</span>
          <span class="stat-sub text-success">Sistem / Atasan</span>
        </div>
      </div>

      <div class="stat-card stat-rejected">
        <div class="stat-icon-wrapper">
          <el-icon :size="24"><CircleClose /></el-icon>
        </div>
        <div class="stat-info">
          <span class="stat-label">Ditolak / Batal</span>
          <span class="stat-value">{{ (summary.ditolak || 0) + (summary.dibatalkan || 0) }}</span>
          <span class="stat-sub">{{ summary.ditolak }} Tolak, {{ summary.dibatalkan }} Batal</span>
        </div>
      </div>
    </div>

    <!-- Tab Filter -->
    <div class="tabs-wrapper mt-4">
      <el-tabs v-model="activeTab" @tab-change="handleTabChange">
        <el-tab-pane label="Semua Pengajuan" name="semua" />
        <el-tab-pane label="Menunggu Persetujuan" name="menunggu" />
        <el-tab-pane label="Pengajuan Saya" name="saya" />
        <el-tab-pane label="Perlu Ditinjau (Tim / Bawahan)" name="bawahan" />
        <el-tab-pane label="Disetujui" name="disetujui" />
        <el-tab-pane label="Ditolak" name="ditolak" />
        <el-tab-pane label="Dibatalkan" name="dibatalkan" />
      </el-tabs>
    </div>

    <!-- Filters Section -->
    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Cari no. pengajuan, nama pegawai, NIP..."
        :prefix-icon="Search"
        class="search-input"
        clearable
        @input="handleSearch"
      />
      <div class="filter-actions">
        <el-select
          v-model="tipeCutiFilter"
          placeholder="Jenis Cuti"
          clearable
          class="custom-select"
          @change="fetchData"
        >
          <el-option label="Cuti Tahunan" value="Cuti Tahunan" />
          <el-option label="Cuti Sakit" value="Cuti Sakit" />
          <el-option label="Cuti Alasan Penting" value="Cuti Alasan Penting" />
          <el-option label="Cuti Melahirkan" value="Cuti Melahirkan" />
          <el-option label="Cuti Besar" value="Cuti Besar" />
          <el-option label="Lainnya" value="Lainnya" />
        </el-select>
        <el-select
          v-model="statusFilter"
          placeholder="Status"
          clearable
          class="custom-select"
          @change="fetchData"
        >
          <el-option label="Menunggu Persetujuan" value="Menunggu Persetujuan" />
          <el-option label="Disetujui" value="Disetujui" />
          <el-option label="Ditolak" value="Ditolak" />
          <el-option label="Dibatalkan" value="Dibatalkan" />
        </el-select>
      </div>
    </div>

    <!-- DataTable -->
    <DataTable
      :data="data"
      :loading="loading"
      :empty-image="EmptyStateImage"
      :total="meta.total"
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      @update:current-page="fetchData"
      @update:page-size="fetchData"
      row-key="id"
    >
      <el-table-column prop="nomor_pengajuan" label="No. Pengajuan" min-width="160">
        <template #default="{ row }">
          <div class="nomor-cell">
            <span class="nomor-text">{{ row.nomor_pengajuan }}</span>
            <span class="tgl-text">{{ formatDate(row.created_at) }}</span>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Pegawai Pemohon" min-width="200">
        <template #default="{ row }">
          <div class="pegawai-cell">
            <el-avatar :size="34" :src="row.pegawai?.avatar" class="me-2">
              {{ row.pegawai?.nama?.charAt(0) }}
            </el-avatar>
            <div class="pegawai-meta">
              <span class="pegawai-name">{{ row.pegawai?.nama || '-' }}</span>
              <span class="pegawai-sub">{{ row.pegawai?.active_kontrak?.jabatan?.nama_jabatan || row.pegawai?.nip || '-' }}</span>
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column prop="tipe_cuti" label="Jenis Cuti" min-width="140">
        <template #default="{ row }">
          <el-tag size="small" :type="getCutiTypeTag(row.tipe_cuti)" effect="plain">
            {{ row.tipe_cuti }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Rentang Cuti" min-width="200">
        <template #default="{ row }">
          <div class="rentang-cell">
            <span class="date-range">{{ formatDateOnly(row.tanggal_mulai) }} s/d {{ formatDateOnly(row.tanggal_selesai) }}</span>
            <el-tag size="small" type="info" class="days-badge">{{ row.jumlah_hari }} Hari</el-tag>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Timeline & H-" min-width="130" align="center">
        <template #default="{ row }">
          <el-tooltip
            v-if="row.status === 'Menunggu Persetujuan'"
            :content="`Pengajuan ini diajukan H-7 dan akan disetujui otomatis pada H-3 jika tidak ditolak.`"
            placement="top"
          >
            <span class="h-badge" :class="getHBadgeClass(row.h_minus_info)">
              {{ row.h_minus_info }}
            </span>
          </el-tooltip>
          <span v-else-if="row.disetujui_otomatis" class="h-badge h-auto-approved">
            Auto Approved
          </span>
          <span v-else class="h-badge h-neutral">
            {{ row.status }}
          </span>
        </template>
      </el-table-column>

      <el-table-column prop="status" label="Status" min-width="150" align="center">
        <template #default="{ row }">
          <el-tag :type="getStatusTag(row.status)" effect="light" class="status-badge">
            {{ row.status }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Aksi" width="160" align="center" fixed="right">
        <template #default="{ row }">
          <div class="action-buttons">
            <!-- Tombol Detail -->
            <el-tooltip content="Lihat Detail Pengajuan" placement="top">
              <el-button text circle size="small" @click="openDetail(row)">
                <el-icon><View /></el-icon>
              </el-button>
            </el-tooltip>

            <!-- Tombol Tolak (Atasan yang bersangkutan dalam 1 bulan / Admin) -->
            <el-tooltip
              v-if="row.can_reject && row.status === 'Menunggu Persetujuan'"
              content="Tolak Cuti (Hak Atasan Pemberi Tugas)"
              placement="top"
            >
              <el-button
                type="danger"
                plain
                circle
                size="small"
                @click="openRejectModal(row)"
              >
                <el-icon><Close /></el-icon>
              </el-button>
            </el-tooltip>

            <!-- Tombol Batalkan (Pegawai Pemohon) -->
            <el-tooltip
              v-if="row.can_cancel && row.status === 'Menunggu Persetujuan'"
              content="Batalkan Pengajuan Saya"
              placement="top"
            >
              <el-button
                type="warning"
                plain
                circle
                size="small"
                @click="handleCancel(row)"
              >
                <el-icon><Remove /></el-icon>
              </el-button>
            </el-tooltip>
          </div>
        </template>
      </el-table-column>
    </DataTable>

    <!-- Modal Detail Pengajuan Cuti -->
    <el-dialog
      v-model="isDetailVisible"
      title="Detail Pengajuan Cuti"
      width="640px"
      align-center
      class="custom-detail-dialog"
    >
      <div v-if="selectedCuti" class="detail-container">
        <div class="detail-header-card">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="detail-number">{{ selectedCuti.nomor_pengajuan }}</span>
              <p class="detail-subtitle">Diajukan pada {{ formatDateTime(selectedCuti.created_at) }}</p>
            </div>
            <el-tag :type="getStatusTag(selectedCuti.status)" size="large" effect="dark">
              {{ selectedCuti.status }}
            </el-tag>
          </div>
        </div>

        <el-descriptions :column="2" border class="mt-3">
          <el-descriptions-item label="Nama Pegawai">
            <strong>{{ selectedCuti.pegawai?.nama }}</strong>
          </el-descriptions-item>
          <el-descriptions-item label="NIP / Jabatan">
            {{ selectedCuti.pegawai?.active_kontrak?.jabatan?.nama_jabatan || selectedCuti.pegawai?.nip || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="Jenis Cuti">
            <el-tag size="small" :type="getCutiTypeTag(selectedCuti.tipe_cuti)">{{ selectedCuti.tipe_cuti }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Jumlah Hari">
            <strong>{{ selectedCuti.jumlah_hari }} Hari Kerja</strong>
          </el-descriptions-item>
          <el-descriptions-item label="Tanggal Mulai">
            {{ formatDateOnly(selectedCuti.tanggal_mulai) }}
          </el-descriptions-item>
          <el-descriptions-item label="Tanggal Selesai">
            {{ formatDateOnly(selectedCuti.tanggal_selesai) }}
          </el-descriptions-item>
          <el-descriptions-item label="Posisi Timeline" :span="2">
            <span class="text-primary font-weight-bold">{{ selectedCuti.h_minus_info }}</span>
            <span v-if="selectedCuti.status === 'Menunggu Persetujuan'" class="text-muted ms-2">
              (Akan disetujui otomatis pada H-3 jika tidak ada penolakan dari atasan)
            </span>
          </el-descriptions-item>
          <el-descriptions-item label="Alasan Cuti" :span="2">
            <div class="alasan-box">{{ selectedCuti.alasan }}</div>
          </el-descriptions-item>
          <el-descriptions-item v-if="selectedCuti.dokumen_pendukung" label="Dokumen Pendukung" :span="2">
            <a :href="getFileUrl(selectedCuti.dokumen_pendukung)" target="_blank" class="doc-link">
              <el-icon class="me-1"><Document /></el-icon> Unduh / Buka Dokumen Pendukung
            </a>
          </el-descriptions-item>
        </el-descriptions>

        <!-- Informasi Persetujuan / Penolakan -->
        <div v-if="selectedCuti.status === 'Disetujui'" class="decision-card approval-card mt-3">
          <div class="decision-title text-success">
            <el-icon class="me-1"><CircleCheck /></el-icon> Cuti Disetujui
          </div>
          <p class="decision-desc">
            Disetujui pada: <strong>{{ formatDateTime(selectedCuti.disetujui_pada) }}</strong><br>
            Metode: <strong>{{ selectedCuti.disetujui_otomatis ? 'Otomatis oleh Sistem (H-3)' : 'Disetujui Manual' }}</strong><br>
            <span v-if="selectedCuti.catatan_persetujuan">Catatan: {{ selectedCuti.catatan_persetujuan }}</span>
          </p>
        </div>

        <div v-else-if="selectedCuti.status === 'Ditolak'" class="decision-card rejection-card mt-3">
          <div class="decision-title text-danger">
            <el-icon class="me-1"><CircleClose /></el-icon> Cuti Ditolak oleh Atasan
          </div>
          <p class="decision-desc">
            Ditolak oleh: <strong>{{ selectedCuti.penolak_pegawai?.nama || selectedCuti.penolak_user?.username || 'Atasan' }}</strong><br>
            Waktu: <strong>{{ formatDateTime(selectedCuti.ditolak_pada) }}</strong><br>
            Alasan Penolakan: <em>"{{ selectedCuti.alasan_penolakan }}"</em>
          </p>
        </div>

        <div v-else-if="selectedCuti.status === 'Dibatalkan'" class="decision-card cancellation-card mt-3">
          <div class="decision-title text-warning">
            <el-icon class="me-1"><Warning /></el-icon> Pengajuan Dibatalkan oleh Pemohon
          </div>
          <p class="decision-desc">
            Waktu Pembatalan: <strong>{{ formatDateTime(selectedCuti.dibatalkan_pada) }}</strong><br>
            Keterangan: {{ selectedCuti.alasan_pembatalan || 'Dibatalkan sendiri oleh pegawai.' }}
          </p>
        </div>

        <!-- Daftar Atasan yang Berwenang -->
        <div v-if="selectedCuti.authorized_superiors && selectedCuti.authorized_superiors.length > 0" class="superiors-info mt-3">
          <span class="superiors-info-title">Atasan yang berwenang menolak (Pernah memberi tugas dalam 1 bulan):</span>
          <div class="d-flex flex-wrap gap-2 mt-2">
            <el-tag
              v-for="sup in selectedCuti.authorized_superiors"
              :key="sup.id"
              size="small"
              type="info"
              effect="plain"
            >
              {{ sup.nama }} ({{ sup.active_kontrak?.jabatan?.nama_jabatan || 'Atasan' }})
            </el-tag>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="isDetailVisible = false">Tutup</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Modal Penolakan Cuti -->
    <el-dialog
      v-model="isRejectModalVisible"
      title="Tolak Pengajuan Cuti"
      width="480px"
      align-center
    >
      <div v-if="targetCuti" class="reject-dialog-content">
        <p class="reject-prompt">
          Sebagai atasan yang pernah memberikan tugas kepada <strong>{{ targetCuti.pegawai?.nama }}</strong> dalam 1 bulan terakhir, Anda memiliki wewenang untuk menolak pengajuan cuti ini.
        </p>
        <el-form label-position="top">
          <el-form-item label="Alasan Penolakan (Wajib Diisi)" required>
            <el-input
              v-model="rejectReason"
              type="textarea"
              :rows="4"
              placeholder="Contoh: Beban kerja project sedang tinggi menjelang deadline, mohon dijadwal ulang..."
            />
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button text @click="isRejectModalVisible = false">Batal</el-button>
          <el-button
            type="danger"
            color="#ef4444"
            :loading="actionLoading"
            :disabled="!rejectReason || rejectReason.length < 5"
            @click="confirmReject"
          >
            Tolak Pengajuan
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import {
  Calendar,
  Clock,
  CircleCheck,
  CircleClose,
  Plus,
  Search,
  View,
  Close,
  Remove,
  Warning,
  Document
} from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import { ElMessageBox } from 'element-plus'
import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import { useCutiStore, type PengajuanCuti } from '@/store/cuti'

const router = useRouter()
const cutiStore = useCutiStore()
const { data, meta, summary, loading } = storeToRefs(cutiStore)

const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const activeTab = ref('semua')
const statusFilter = ref('')
const tipeCutiFilter = ref('')

const isDetailVisible = ref(false)
const selectedCuti = ref<PengajuanCuti | null>(null)

const isRejectModalVisible = ref(false)
const targetCuti = ref<PengajuanCuti | null>(null)
const rejectReason = ref('')
const actionLoading = ref(false)

const fetchData = async () => {
  await cutiStore.getData({
    page: currentPage.value,
    limit: pageSize.value,
    search: searchQuery.value,
    status: statusFilter.value,
    tipe_cuti: tipeCutiFilter.value,
    tab: activeTab.value
  })
}

const handleTabChange = () => {
  currentPage.value = 1
  fetchData()
}

const handleSearch = () => {
  currentPage.value = 1
  fetchData()
}

onMounted(() => {
  fetchData()
})

const openDetail = (row: PengajuanCuti) => {
  selectedCuti.value = row
  isDetailVisible.value = true
}

const openRejectModal = (row: PengajuanCuti) => {
  targetCuti.value = row
  rejectReason.value = ''
  isRejectModalVisible.value = true
}

const confirmReject = async () => {
  if (!targetCuti.value || !rejectReason.value) return
  actionLoading.value = true
  try {
    const res = await cutiStore.reject(targetCuti.value.id, {
      alasan_penolakan: rejectReason.value
    })
    showSuccessToast(res?.message || 'Pengajuan cuti berhasil ditolak.')
    isRejectModalVisible.value = false
    await fetchData()
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal menolak pengajuan cuti.')
  } finally {
    actionLoading.value = false
  }
}

const handleCancel = async (row: PengajuanCuti) => {
  try {
    await ElMessageBox.confirm(
      `Apakah Anda yakin ingin membatalkan pengajuan cuti <strong>${row.nomor_pengajuan}</strong>?`,
      'Konfirmasi Pembatalan Cuti',
      {
        confirmButtonText: 'Ya, Batalkan Pengajuan',
        cancelButtonText: 'Kembali',
        dangerouslyUseHTMLString: true,
        type: 'warning',
        confirmButtonClass: 'el-button--danger'
      }
    )
  } catch {
    return
  }

  try {
    const res = await cutiStore.cancel(row.id, {
      alasan_pembatalan: 'Dibatalkan oleh pegawai pemohon.'
    })
    showSuccessToast(res?.message || 'Pengajuan cuti berhasil dibatalkan.')
    await fetchData()
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal membatalkan pengajuan cuti.')
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const formatDateOnly = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const formatDateTime = (dateStr: string | null | undefined) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusTag = (status: string) => {
  switch (status) {
    case 'Disetujui':
      return 'success'
    case 'Menunggu Persetujuan':
      return 'warning'
    case 'Ditolak':
      return 'danger'
    case 'Dibatalkan':
      return 'info'
    default:
      return 'info'
  }
}

const getCutiTypeTag = (type: string) => {
  switch (type) {
    case 'Cuti Tahunan':
      return 'primary'
    case 'Cuti Sakit':
      return 'danger'
    case 'Cuti Alasan Penting':
      return 'warning'
    case 'Cuti Melahirkan':
      return 'success'
    default:
      return 'info'
  }
}

const getHBadgeClass = (hInfo?: string) => {
  if (!hInfo) return 'h-neutral'
  if (hInfo.includes('H-3') || hInfo.includes('H-2') || hInfo.includes('H-1')) {
    return 'h-approaching'
  }
  return 'h-pending'
}

const getFileUrl = (path: string) => {
  if (!path) return '#'
  if (path.startsWith('http')) return path
  return `http://localhost:8000/storage/${path}`
}
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.title-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
}
.total-badge {
  font-weight: 500;
}
.subtitle {
  color: #6b7280;
  margin: 4px 0 0 0;
  font-size: 0.9rem;
}

/* KPI Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background-color: #ffffff;
  border-radius: 12px;
  padding: 18px 20px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.stat-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 10px;
}
.stat-total .stat-icon-wrapper {
  background-color: #eff6ff;
  color: #3b82f6;
}
.stat-pending .stat-icon-wrapper {
  background-color: #fffbeb;
  color: #f59e0b;
}
.stat-approved .stat-icon-wrapper {
  background-color: #f0fdf4;
  color: #10b981;
}
.stat-rejected .stat-icon-wrapper {
  background-color: #fef2f2;
  color: #ef4444;
}
.stat-info {
  display: flex;
  flex-direction: column;
}
.stat-label {
  font-size: 13px;
  color: #6b7280;
}
.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #111827;
  line-height: 1.2;
}
.stat-sub {
  font-size: 11px;
  color: #9ca3af;
  margin-top: 2px;
}

.tabs-wrapper {
  background-color: #ffffff;
  border-radius: 8px;
  padding: 0 16px;
  border: 1px solid #e5e7eb;
}

.filter-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}
.search-input {
  width: 320px;
}
.filter-actions {
  display: flex;
  gap: 12px;
}
.custom-select {
  width: 170px;
}

/* Table Cells */
.nomor-cell {
  display: flex;
  flex-direction: column;
}
.nomor-text {
  font-weight: 600;
  color: #1f2937;
  font-size: 13.5px;
}
.tgl-text {
  font-size: 11.5px;
  color: #9ca3af;
}

.pegawai-cell {
  display: flex;
  align-items: center;
}
.pegawai-meta {
  display: flex;
  flex-direction: column;
}
.pegawai-name {
  font-weight: 600;
  color: #111827;
  font-size: 13.5px;
}
.pegawai-sub {
  font-size: 11.5px;
  color: #6b7280;
}

.rentang-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.date-range {
  font-size: 13px;
  color: #374151;
}
.days-badge {
  align-self: flex-start;
}

.h-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}
.h-pending {
  background-color: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}
.h-approaching {
  background-color: #ffedd5;
  color: #c2410c;
  border: 1px solid #fed7aa;
}
.h-auto-approved {
  background-color: #dcfce7;
  color: #15803d;
  border: 1px solid #bbf7d0;
}
.h-neutral {
  background-color: #f3f4f6;
  color: #4b5563;
}

.action-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

/* Detail Modal */
.detail-header-card {
  background-color: #f9fafb;
  border: 1px solid #e5e7eb;
  padding: 16px;
  border-radius: 8px;
}
.detail-number {
  font-size: 16px;
  font-weight: 700;
  color: #111827;
}
.detail-subtitle {
  font-size: 12px;
  color: #6b7280;
  margin: 2px 0 0 0;
}
.alasan-box {
  background-color: #f9fafb;
  padding: 10px 14px;
  border-radius: 6px;
  font-size: 13px;
  line-height: 1.5;
  color: #374151;
}
.doc-link {
  display: inline-flex;
  align-items: center;
  color: #2563eb;
  font-weight: 500;
  text-decoration: none;
}
.doc-link:hover {
  text-decoration: underline;
}

.decision-card {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 13px;
}
.decision-title {
  font-weight: 700;
  margin-bottom: 4px;
}
.decision-desc {
  margin: 0;
  line-height: 1.5;
}
.approval-card {
  background-color: #f0fdf4;
  border: 1px solid #bbf7d0;
}
.rejection-card {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
}
.cancellation-card {
  background-color: #fffbeb;
  border: 1px solid #fef3c7;
}

.superiors-info {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 12px 14px;
  border-radius: 8px;
}
.superiors-info-title {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

.reject-prompt {
  font-size: 13px;
  color: #4b5563;
  line-height: 1.5;
  margin-bottom: 16px;
}
</style>
