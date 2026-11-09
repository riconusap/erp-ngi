<template>
  <div class="page-container">
    <!-- State: Akun Tidak Berelasi ke Pegawai (Akses Ditolak) -->
    <div v-if="isAccessDenied" class="access-denied-wrapper">
      <div class="access-denied-card">
        <div class="icon-container">
          <img src="@/assets/no-access.svg" alt="Akses Ditolak" class="no-access-img" />
        </div>
        <h2 class="access-denied-title">Akses Halaman Presensi Ditolak</h2>
        <p class="access-denied-desc">
          Akun login Anda (<strong>{{ authStore.user?.username || authStore.user?.email || 'Pengguna' }}</strong>) tidak terhubung dengan Data Pegawai. Halaman presensi hanya dapat diakses oleh akun pegawai terdaftar.
        </p>
        <div class="access-denied-actions">
          <el-button v-if="canAccessAdmin" type="primary" size="large" @click="goToDashboard">
            <el-icon class="mr-2"><Monitor /></el-icon>
            Kembali ke Dashboard Admin
          </el-button>
          <el-button v-else type="danger" plain size="large" @click="handleLogout">
            <el-icon class="mr-2"><SwitchButton /></el-icon>
            Logout
          </el-button>
        </div>
      </div>
    </div>

    <!-- Halaman Presensi Normal -->
    <template v-else>
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <div class="title-wrapper">
            <AppHeading level="1">Presensi Pegawai</AppHeading>
          </div>
          <p class="subtitle">Catat kehadiran harian kerja dan pantau riwayat presensi Anda</p>
        </div>
        <div class="header-right">
          <div class="clock-card">
            <div class="clock-time">{{ currentTimeStr }}</div>
            <div class="clock-date">{{ currentDateStr }}</div>
          </div>
        </div>
      </div>

      <!-- Notice: Jadwal Belum Diatur Admin -->
      <div v-if="schedule && !schedule.has_schedule" class="schedule-notice-card">
        <div class="notice-icon">
          <el-icon :size="20"><WarningFilled /></el-icon>
        </div>
        <div class="notice-body">
          <div class="notice-title">Jadwal Kerja Belum Diatur oleh Administrator</div>
          <p class="notice-desc">
            Presensi tetap dapat dilakukan seperti biasa menggunakan ketentuan jam kerja standar reguler: <strong>08:00 WIB</strong> (batas toleransi keterlambatan s/d <strong>08:15 WIB</strong>). Hubungi atasan atau PIC area jika Anda bertugas pada shift khusus.
          </p>
        </div>
      </div>

      <!-- Main Attendance Board -->
      <div class="attendance-card">
        <div class="attendance-card-main">
          <!-- Employee Details Header -->
          <div class="employee-header">
            <div class="employee-profile">
              <h2 class="employee-greeting">{{ greetingText }}, {{ employeeName }}</h2>
              <div class="employee-meta-list">
                <span class="meta-item">
                  <span class="meta-label">NIP:</span>
                  <span class="meta-value">{{ employeeNip }}</span>
                </span>
                <span class="meta-divider">•</span>
                <span class="meta-item">
                  <span class="meta-label">Jabatan:</span>
                  <span class="meta-value">{{ employeePosition }}</span>
                </span>
                <span class="meta-divider">•</span>
                <span class="meta-item">
                  <span class="meta-label">Penempatan:</span>
                  <span class="meta-value">{{ employeeArea }}</span>
                </span>
              </div>
            </div>

            <div class="shift-indicator">
              <div v-if="schedule && schedule.has_schedule && shift" class="shift-badge-active">
                <span class="shift-tag">Shift Aktif</span>
                <span class="shift-name">{{ shift.nama }} ({{ shift.jam_masuk }} - {{ shift.jam_pulang }})</span>
              </div>
              <div v-else class="shift-badge-default">
                <span class="shift-tag default">Jam Reguler Standar</span>
                <span class="shift-name">08:00 - 17:00 WIB</span>
              </div>
            </div>
          </div>

          <!-- Daily Timestamps Grid -->
          <div class="timestamps-grid">
            <!-- Clock In Slot -->
            <div class="time-slot" :class="{ 'is-recorded': today.has_clocked_in }">
              <div class="slot-header">
                <span class="slot-title">
                  <el-icon class="mr-1 text-muted"><Timer /></el-icon>
                  Jam Masuk (Clock In)
                </span>
                <el-tag 
                  v-if="today.has_clocked_in" 
                  size="small" 
                  :type="today.status === 'Terlambat' ? 'warning' : 'success'" 
                  effect="plain"
                >
                  {{ today.status }}
                </el-tag>
                <span v-else class="slot-pending-label">Belum Tercatat</span>
              </div>
              <div class="slot-value">{{ today.clock_in || '--:--:--' }}</div>
              <div class="slot-footer">
                <span v-if="today.has_clocked_in" class="footer-note">Tercatat di sistem</span>
                <span v-else class="footer-note">Standar masuk 08:00 WIB</span>
              </div>
            </div>

            <!-- Clock Out Slot -->
            <div class="time-slot" :class="{ 'is-recorded': today.has_clocked_out }">
              <div class="slot-header">
                <span class="slot-title">
                  <el-icon class="mr-1 text-muted"><CircleCheck /></el-icon>
                  Jam Pulang (Clock Out)
                </span>
                <el-tag 
                  v-if="today.has_clocked_out" 
                  size="small" 
                  type="info" 
                  effect="plain"
                >
                  {{ today.durasi_kerja || 'Selesai' }}
                </el-tag>
                <span v-else class="slot-pending-label">Belum Tercatat</span>
              </div>
              <div class="slot-value">{{ today.clock_out || '--:--:--' }}</div>
              <div class="slot-footer">
                <span v-if="today.has_clocked_out" class="footer-note">Jam kerja berakhir</span>
                <span v-else class="footer-note">Dapat dicatat saat jam pulang</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Attendance Action Sidebar / Panel -->
        <div class="attendance-card-action">
          <div class="action-panel">
            <div class="status-summary">
              <div class="summary-label">Status Presensi Hari Ini</div>
              <div class="summary-status">
                <span v-if="today.has_clocked_in && today.has_clocked_out" class="status-text text-complete">
                  Selesai Bekerja
                </span>
                <span v-else-if="today.has_clocked_in" class="status-text text-working">
                  Sedang Bertugas
                </span>
                <span v-else class="status-text text-idle">
                  Belum Clock In
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-btn-group">
              <el-button
                v-if="!today.has_clocked_in"
                type="primary"
                size="large"
                class="attendance-action-btn btn-primary-clock"
                :loading="isSubmitting"
                @click="triggerClockIn"
              >
                <el-icon class="mr-2"><Timer /></el-icon>
                <span>Clock In Sekarang</span>
              </el-button>

              <el-button
                v-else-if="!today.has_clocked_out"
                type="danger"
                size="large"
                class="attendance-action-btn btn-danger-clock"
                :loading="isSubmitting"
                @click="triggerClockOut"
              >
                <el-icon class="mr-2"><SwitchButton /></el-icon>
                <span>Clock Out Sekarang</span>
              </el-button>

              <div v-else class="attendance-done-card">
                <el-icon :size="24" class="done-icon"><CircleCheckFilled /></el-icon>
                <div class="done-text">Presensi hari ini telah lengkap</div>
              </div>
            </div>

            <!-- Location Meta -->
            <div class="location-info">
              <el-icon class="mr-1 text-muted"><Location /></el-icon>
              <span>Area: <strong>{{ today.lokasi_nama || employeeArea }}</strong></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Monthly Summary Statistics -->
      <div class="summary-section">
        <div class="section-header">
          <h3 class="section-title">Ringkasan Presensi Bulan Ini</h3>
          <span class="section-subtitle">Periode {{ stats.periode || 'Bulan Berjalan' }}</span>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <span class="stat-caption">Total Kehadiran</span>
            <div class="stat-metric">{{ stats.total_kehadiran }}</div>
            <span class="stat-helper">Hari Kerja</span>
          </div>

          <div class="stat-card">
            <span class="stat-caption">Tepat Waktu</span>
            <div class="stat-metric text-brand">{{ stats.tepat_waktu }}</div>
            <span class="stat-helper">Sesuai Jadwal</span>
          </div>

          <div class="stat-card">
            <span class="stat-caption">Terlambat</span>
            <div class="stat-metric text-warning-muted">{{ stats.terlambat }}</div>
            <span class="stat-helper">Lewat Toleransi</span>
          </div>

          <div class="stat-card">
            <span class="stat-caption">Izin / Cuti</span>
            <div class="stat-metric text-muted-dark">{{ stats.izin_cuti }}</div>
            <span class="stat-helper">Izin Khusus</span>
          </div>
        </div>
      </div>

      <!-- Attendance History Table -->
      <div class="history-section">
        <div class="section-header">
          <div class="section-title-group">
            <h3 class="section-title">Riwayat Presensi</h3>
            <span class="section-subtitle">Catatan presensi harian dalam 14 hari terakhir</span>
          </div>
        </div>

        <div class="table-container">
          <el-table 
            :data="logs" 
            v-loading="isLoading" 
            style="width: 100%" 
            empty-text="Belum ada data presensi"
            class="custom-attendance-table"
          >
            <el-table-column prop="date" label="Tanggal" min-width="160">
              <template #default="{ row }">
                <div class="table-date">{{ row.date }}</div>
                <div class="table-day">{{ row.dayName }}</div>
              </template>
            </el-table-column>
            
            <el-table-column label="Clock In" min-width="130" align="center">
              <template #default="{ row }">
                <span class="table-time-val">{{ row.clockIn || '-' }}</span>
              </template>
            </el-table-column>

            <el-table-column label="Clock Out" min-width="130" align="center">
              <template #default="{ row }">
                <span class="table-time-val">{{ row.clockOut || '-' }}</span>
              </template>
            </el-table-column>

            <el-table-column prop="area" label="Lokasi Penempatan" min-width="200">
              <template #default="{ row }">
                <div class="table-location">
                  <el-icon class="mr-1 text-muted"><Location /></el-icon>
                  <span>{{ row.area }}</span>
                </div>
              </template>
            </el-table-column>

            <el-table-column prop="workDuration" label="Durasi Kerja" min-width="140" align="center">
              <template #default="{ row }">
                <span class="table-duration">{{ row.workDuration || '-' }}</span>
              </template>
            </el-table-column>

            <el-table-column prop="status" label="Status" min-width="140" align="center">
              <template #default="{ row }">
                <el-tag :type="getStatusTagType(row.status)" size="small" effect="light">
                  {{ row.status }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>

      <!-- Modal Konfirmasi Clock In / Out -->
      <el-dialog
        v-model="isConfirmModalOpen"
        :title="confirmModalTitle"
        width="440px"
        align-center
        class="custom-confirm-dialog"
      >
        <div class="confirm-modal-content">
          <p class="confirm-lead-text">
            Pastikan data waktu dan area kerja berikut sudah sesuai sebelum menyimpan presensi:
          </p>

          <div class="confirm-info-panel">
            <div class="confirm-row">
              <span class="confirm-label">Waktu Presensi</span>
              <span class="confirm-val-highlight">{{ currentTimeStr }}</span>
            </div>
            <div class="confirm-row">
              <span class="confirm-label">Tanggal</span>
              <span class="confirm-val">{{ currentDateStr }}</span>
            </div>
            <div class="confirm-row">
              <span class="confirm-label">Lokasi Area</span>
              <span class="confirm-val">{{ employeeArea }}</span>
            </div>
            <div v-if="userCoords" class="confirm-row coords">
              <span class="confirm-label">Koordinat GPS</span>
              <span class="confirm-val-mono">{{ userCoords.lat.toFixed(5) }}, {{ userCoords.long.toFixed(5) }}</span>
            </div>
          </div>

          <div class="note-field-wrapper">
            <label class="field-label" for="presensi-catatan">Catatan Tambahan (Opsional)</label>
            <el-input
              id="presensi-catatan"
              v-model="catatan"
              type="textarea"
              :rows="2"
              placeholder="Contoh: Tugas lapangan / dinas luar kantor"
            />
          </div>
        </div>

        <template #footer>
          <div class="dialog-actions">
            <el-button @click="isConfirmModalOpen = false">Batal</el-button>
            <el-button
              :type="confirmModalAction === 'in' ? 'primary' : 'danger'"
              :loading="isSubmitting"
              class="confirm-submit-btn"
              @click="processAttendance"
            >
              {{ confirmModalAction === 'in' ? 'Konfirmasi Clock In' : 'Konfirmasi Clock Out' }}
            </el-button>
          </div>
        </template>
      </el-dialog>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { 
  User, 
  Briefcase, 
  Location, 
  Calendar, 
  Timer, 
  CircleCheck, 
  CircleCheckFilled, 
  Warning, 
  WarningFilled,
  Clock,
  Document, 
  SwitchButton,
  Monitor
} from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import { useAuthStore } from '@/store/auth'
import { useAbsensiStore } from '@/store/absensi'
import AppHeading from '@/components/common/AppHeading.vue'

const router = useRouter()
const authStore = useAuthStore()
const absensiStore = useAbsensiStore()
const { today, stats, logs, isLoading, isSubmitting, employee, shift, schedule } = storeToRefs(absensiStore)

const isAccessDenied = ref(false)

const canAccessAdmin = computed(() => {
  return !!(authStore.canAccessAdmin || authStore.user?.role?.can_access_admin || authStore.dataProfile?.role?.can_access_admin)
})

const goToDashboard = () => {
  router.push('/dashboard/overview')
}

const handleLogout = async () => {
  try {
    await authStore.logout()
  } catch {
    // ignore
  }
  router.push('/login')
}

// User info
const employeeName = computed(() => {
  return employee.value.nama || authStore.dataProfile?.nama || authStore.user?.nama || authStore.user?.username || 'Pegawai'
})

const employeeNip = computed(() => {
  return employee.value.nip || authStore.user?.pegawai?.nip || 'PEG-2026-088'
})

const employeePosition = computed(() => {
  return employee.value.jabatan || authStore.user?.pegawai?.jabatan?.nama_jabatan || 'Staf Operasional'
})

const employeeArea = computed(() => {
  return employee.value.area || authStore.user?.pegawai?.area?.nama_area || 'Kantor Pusat - Jakarta'
})

// Real-time Clock
const currentTimeStr = ref('')
const currentDateStr = ref('')
let timerInterval: any = null

const updateClock = () => {
  const now = new Date()
  currentTimeStr.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB'
  currentDateStr.value = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

const greetingText = computed(() => {
  const hour = new Date().getHours()
  if (hour < 11) return 'Selamat Pagi'
  if (hour < 15) return 'Selamat Siang'
  if (hour < 18) return 'Selamat Sore'
  return 'Selamat Malam'
})

// Confirm Modal & GPS Coordinates
const isConfirmModalOpen = ref(false)
const confirmModalAction = ref<'in' | 'out'>('in')
const catatan = ref('')
const userCoords = ref<{ lat: number; long: number } | null>(null)

const confirmModalTitle = computed(() => {
  return confirmModalAction.value === 'in' ? 'Presensi Masuk (Clock In)' : 'Presensi Pulang (Clock Out)'
})

const fetchCoordinates = () => {
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        userCoords.value = {
          lat: position.coords.latitude,
          long: position.coords.longitude
        }
      },
      () => {
        // Fallback or ignore if blocked
      },
      { timeout: 6000 }
    )
  }
}

const triggerClockIn = () => {
  confirmModalAction.value = 'in'
  catatan.value = ''
  fetchCoordinates()
  isConfirmModalOpen.value = true
}

const triggerClockOut = () => {
  confirmModalAction.value = 'out'
  catatan.value = ''
  fetchCoordinates()
  isConfirmModalOpen.value = true
}

const processAttendance = async () => {
  try {
    const payload = {
      lat: userCoords.value?.lat,
      long: userCoords.value?.long,
      catatan: catatan.value || undefined
    }

    if (confirmModalAction.value === 'in') {
      const res = await absensiStore.clockIn(payload)
      showSuccessToast(res?.message || 'Clock In berhasil dicatat!')
    } else {
      const res = await absensiStore.clockOut(payload)
      showSuccessToast(res?.message || 'Clock Out berhasil dicatat!')
    }
    isConfirmModalOpen.value = false
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal mencatat presensi')
  }
}

const getStatusTagType = (status: string) => {
  if (status.includes('Tepat Waktu') || status === 'Hadir') return 'success'
  if (status.includes('Terlambat')) return 'warning'
  if (status.includes('Izin') || status.includes('Cuti')) return 'info'
  return 'danger'
}

onMounted(async () => {
  updateClock()
  timerInterval = setInterval(updateClock, 1000)

  // Cek apakah akun memiliki relasi data pegawai
  if (!authStore.hasPegawai && !authStore.user?.pegawai_id && !authStore.user?.pegawai) {
    isAccessDenied.value = true
    return
  }

  try {
    await absensiStore.fetchTodayStatus()
    await absensiStore.fetchHistory()
  } catch (e: any) {
    if (e?.response?.status === 403) {
      isAccessDenied.value = true
    } else {
      console.error('Failed to load attendance initial data:', e)
    }
  }
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped lang="scss">
.page-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.subtitle {
  color: #64748b;
  margin-top: 4px;
  font-size: 14px;
}

.clock-card {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px 16px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.clock-time {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.01em;
}

.clock-date {
  font-size: 12px;
  color: #64748b;
  margin-top: 1px;
}

/* Schedule Notice Card */
.schedule-notice-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-radius: 8px;
  padding: 12px 16px;

  .notice-icon {
    color: #d97706;
    margin-top: 2px;
    flex-shrink: 0;
  }

  .notice-body {
    flex: 1;

    .notice-title {
      font-size: 13.5px;
      font-weight: 600;
      color: #92400e;
    }

    .notice-desc {
      font-size: 13px;
      color: #b45309;
      margin: 4px 0 0 0;
      line-height: 1.5;
    }
  }
}

/* Main Attendance Card */
.attendance-card {
  display: flex;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.attendance-card-main {
  flex: 1;
  padding: 24px;
  border-right: 1px solid #f1f5f9;
}

.employee-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
}

.employee-greeting {
  font-size: 19px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px 0;
  letter-spacing: -0.01em;
}

.employee-meta-list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #475569;

  .meta-label {
    color: #64748b;
    margin-right: 4px;
  }

  .meta-value {
    font-weight: 600;
    color: #1e293b;
  }

  .meta-divider {
    color: #cbd5e1;
    font-size: 10px;
  }
}

.shift-indicator {
  flex-shrink: 0;

  .shift-badge-active {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 4px 10px;

    .shift-tag {
      font-size: 11px;
      font-weight: 600;
      color: #166534;
      text-transform: uppercase;
      letter-spacing: 0.02em;
    }

    .shift-name {
      font-size: 12px;
      font-weight: 600;
      color: #15803d;
    }
  }

  .shift-badge-default {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 4px 10px;

    .shift-tag {
      font-size: 11px;
      font-weight: 600;
      color: #64748b;
    }

    .shift-name {
      font-size: 12px;
      font-weight: 600;
      color: #334155;
    }
  }
}

/* Timestamps Grid */
.timestamps-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.time-slot {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 14px 16px;
  transition: border-color 0.15s ease;

  &.is-recorded {
    background: #ffffff;
    border-color: #cbd5e1;
  }

  .slot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;

    .slot-title {
      font-size: 12.5px;
      font-weight: 600;
      color: #475569;
      display: flex;
      align-items: center;
    }

    .slot-pending-label {
      font-size: 11.5px;
      color: #94a3b8;
    }
  }

  .slot-value {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.01em;
    margin: 4px 0;
  }

  .slot-footer {
    .footer-note {
      font-size: 11.5px;
      color: #64748b;
    }
  }
}

/* Attendance Action Sidebar */
.attendance-card-action {
  width: 280px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: #fafafa;

  .action-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .status-summary {
    .summary-label {
      font-size: 11.5px;
      color: #64748b;
      font-weight: 500;
      margin-bottom: 2px;
    }

    .status-text {
      font-size: 14.5px;
      font-weight: 700;

      &.text-idle {
        color: #64748b;
      }

      &.text-working {
        color: #14746f;
      }

      &.text-complete {
        color: #166534;
      }
    }
  }

  .action-btn-group {
    display: flex;
    flex-direction: column;

    .attendance-action-btn {
      width: 100%;
      height: 44px;
      font-weight: 600;
      font-size: 14px;
      border-radius: 6px;
    }

    .btn-primary-clock {
      background-color: #14746f;
      border-color: #14746f;

      &:hover {
        background-color: #0f3d3e;
        border-color: #0f3d3e;
      }
    }

    .btn-danger-clock {
      background-color: #dc2626;
      border-color: #dc2626;

      &:hover {
        background-color: #b91c1c;
        border-color: #b91c1c;
      }
    }

    .attendance-done-card {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-radius: 6px;

      .done-icon {
        color: #16a34a;
        flex-shrink: 0;
      }

      .done-text {
        font-size: 12.5px;
        font-weight: 600;
        color: #15803d;
      }
    }
  }

  .location-info {
    font-size: 12px;
    color: #64748b;
    display: flex;
    align-items: center;

    strong {
      color: #334155;
      font-weight: 600;
      margin-left: 2px;
    }
  }
}

/* Summary Statistics */
.summary-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;

  .section-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
  }

  .section-subtitle {
    font-size: 12.5px;
    color: #64748b;
  }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.stat-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 14px 18px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);

  .stat-caption {
    font-size: 12.5px;
    font-weight: 500;
    color: #64748b;
  }

  .stat-metric {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    margin: 4px 0 2px 0;
    font-variant-numeric: tabular-nums;

    &.text-brand {
      color: #14746f;
    }

    &.text-warning-muted {
      color: #d97706;
    }

    &.text-muted-dark {
      color: #475569;
    }
  }

  .stat-helper {
    font-size: 11.5px;
    color: #94a3b8;
  }
}

/* History Section */
.history-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.section-title-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.table-container {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.custom-attendance-table {
  .table-date {
    font-weight: 600;
    color: #0f172a;
    font-size: 13.5px;
  }

  .table-day {
    font-size: 12px;
    color: #64748b;
  }

  .table-time-val {
    font-weight: 600;
    color: #1e293b;
    font-size: 13px;
    font-variant-numeric: tabular-nums;
  }

  .table-location {
    display: flex;
    align-items: center;
    font-size: 13px;
    color: #334155;
    font-weight: 500;
  }

  .table-duration {
    font-size: 13px;
    color: #475569;
  }
}

/* Confirm Dialog */
.confirm-lead-text {
  font-size: 13px;
  color: #475569;
  line-height: 1.5;
  margin: 0 0 14px 0;
}

.confirm-info-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 14px;

  .confirm-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;

    .confirm-label {
      color: #64748b;
    }

    .confirm-val {
      font-weight: 600;
      color: #1e293b;
    }

    .confirm-val-highlight {
      font-weight: 700;
      color: #14746f;
      font-size: 15px;
      font-variant-numeric: tabular-nums;
    }

    .confirm-val-mono {
      font-family: monospace;
      font-size: 11.5px;
      color: #64748b;
    }
  }
}

.note-field-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;

  .field-label {
    font-size: 12.5px;
    font-weight: 500;
    color: #334155;
  }
}

.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;

  .confirm-submit-btn {
    font-weight: 600;
  }
}

/* Access Denied */
.access-denied-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 50vh;
  padding: 32px 16px;
}

.access-denied-card {
  max-width: 480px;
  width: 100%;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 36px 28px;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}

.icon-container {
  margin-bottom: 16px;
}

.no-access-img {
  width: 80px;
  height: 80px;
  object-fit: contain;
}

.access-denied-title {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
}

.access-denied-desc {
  font-size: 13.5px;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 20px;
}

.access-denied-actions {
  display: flex;
  justify-content: center;
  gap: 10px;
}

/* Responsive */
@media (max-width: 900px) {
  .attendance-card {
    flex-direction: column;
  }

  .attendance-card-main {
    border-right: none;
    border-bottom: 1px solid #f1f5f9;
  }

  .attendance-card-action {
    width: 100%;
    padding: 20px 24px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .clock-card {
    align-items: flex-start;
    width: 100%;
  }

  .employee-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .timestamps-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
