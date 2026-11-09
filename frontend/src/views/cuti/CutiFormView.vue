<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">Form Pengajuan Cuti</AppHeading>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/cuti" class="back-link">Daftar Pengajuan Cuti</router-link>
          <span class="separator">/</span>
          <span class="active">Pengajuan Baru</span>
        </div>
      </div>
      <div class="header-right">
        <el-button @click="router.push('/dashboard/cuti')" size="large">Kembali ke Daftar</el-button>
      </div>
    </div>

    <!-- Rule Info Card -->
    <div class="rule-banner mt-4">
      <div class="banner-icon">
        <el-icon :size="24"><InfoFilled /></el-icon>
      </div>
      <div class="banner-content">
        <h3 class="banner-title">Ketentuan Pengajuan & Persetujuan Cuti</h3>
        <p class="banner-text">
          1. <strong>Aturan H-7</strong>: Pengajuan cuti wajib diajukan minimal 7 hari (H-7) sebelum tanggal mulai cuti (paling awal tanggal <strong>{{ minDateFormatted }}</strong>).<br>
          2. <strong>Auto-Approve H-3</strong>: Pengajuan akan disetujui secara otomatis oleh sistem pada H-3 jika tidak ada penolakan dari atasan.<br>
          3. <strong>Hak Penolakan</strong>: Cuti dapat ditolak oleh atasan yang pernah memberikan tugas kepada Anda dalam jangka waktu 1 bulan terakhir.<br>
          4. <strong>Hak Pembatalan</strong>: Anda dapat membatalkan pengajuan cuti ini sewaktu-waktu selama belum terlaksana.
        </p>
      </div>
    </div>

    <el-card class="box-card mt-4" shadow="never">
      <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
        <el-row :gutter="24">
          <el-col :span="12">
            <el-form-item label="Pegawai Pemohon" prop="pegawai_id">
              <el-select
                v-model="form.pegawai_id"
                placeholder="Pilih pegawai pemohon"
                size="large"
                filterable
                style="width: 100%;"
                :disabled="isPegawaiLocked"
                @change="handlePegawaiChange"
              >
                <el-option
                  v-for="item in pegawaiOptions"
                  :key="item.id"
                  :label="`${item.nama} (${item.nip || item.nik || 'No NIP'})`"
                  :value="item.id"
                />
              </el-select>
              <div v-if="selectedPegawaiInfo" class="pegawai-badge-info mt-2">
                <el-avatar :size="28" :src="selectedPegawaiInfo.avatar" class="me-2">
                  {{ selectedPegawaiInfo.nama?.charAt(0) }}
                </el-avatar>
                <span>{{ selectedPegawaiInfo.nama }} - {{ selectedPegawaiInfo.active_kontrak?.jabatan?.nama_jabatan || 'Staf Pegawai' }}</span>
              </div>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="Jenis / Tipe Cuti" prop="tipe_cuti">
              <el-select v-model="form.tipe_cuti" placeholder="Pilih jenis cuti" size="large" style="width: 100%;">
                <el-option label="Cuti Tahunan" value="Cuti Tahunan" />
                <el-option label="Cuti Sakit" value="Cuti Sakit" />
                <el-option label="Cuti Alasan Penting" value="Cuti Alasan Penting" />
                <el-option label="Cuti Melahirkan" value="Cuti Melahirkan" />
                <el-option label="Cuti Besar" value="Cuti Besar" />
                <el-option label="Lainnya" value="Lainnya" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="24">
          <el-col :span="12">
            <el-form-item label="Tanggal Mulai Cuti (Minimal H-7)" prop="tanggal_mulai">
              <el-date-picker
                v-model="form.tanggal_mulai"
                type="date"
                placeholder="Pilih tanggal mulai (Minimal H-7)"
                size="large"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                :disabled-date="disabledStartDate"
                style="width: 100%;"
                @change="handleDateChange"
              />
              <span class="field-hint">Paling cepat tanggal: <strong>{{ minDateFormatted }}</strong></span>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="Tanggal Selesai Cuti" prop="tanggal_selesai">
              <el-date-picker
                v-model="form.tanggal_selesai"
                type="date"
                placeholder="Pilih tanggal selesai"
                size="large"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                :disabled-date="disabledEndDate"
                :disabled="!form.tanggal_mulai"
                style="width: 100%;"
                @change="handleDateChange"
              />
              <span v-if="calculatedDays > 0" class="field-hint text-success">
                Total durasi cuti: <strong>{{ calculatedDays }} Hari</strong>
              </span>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- Preview Atasan yang Pernah Memberi Tugas dalam 1 Bulan -->
        <div v-if="authorizedSuperiors.length > 0" class="superiors-section mb-4">
          <div class="superiors-header">
            <el-icon class="me-1 text-primary"><UserFilled /></el-icon>
            <span class="superiors-title">Atasan Berwenang (Pemberi Tugas dalam 1 Bulan Terakhir):</span>
          </div>
          <p class="superiors-desc">
            Berdasarkan riwayat penugasan task, atasan berikut memiliki hak untuk meninjau atau menolak permohonan cuti ini:
          </p>
          <div class="superiors-list">
            <div v-for="sup in authorizedSuperiors" :key="sup.id" class="superior-pill">
              <el-avatar :size="24" :src="sup.avatar" class="me-2">{{ sup.nama?.charAt(0) }}</el-avatar>
              <div class="superior-meta">
                <span class="sup-name">{{ sup.nama }}</span>
                <span class="sup-role">{{ sup.active_kontrak?.jabatan?.nama_jabatan || 'Atasan Penugasan' }}</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="form.pegawai_id && !loadingSuperiors" class="superiors-empty mb-4">
          <el-icon class="me-1"><Warning /></el-icon>
          <span>Tidak ada atasan langsung yang pernah memberi tugas dalam 1 bulan terakhir. Pengajuan akan diproses langsung oleh sistem / Super Admin.</span>
        </div>

        <el-form-item label="Alasan / Keterangan Cuti" prop="alasan">
          <el-input
            v-model="form.alasan"
            type="textarea"
            :rows="4"
            placeholder="Jelaskan alasan dan keperluan pengajuan cuti secara lengkap..."
            size="large"
          />
        </el-form-item>

        <el-form-item label="Dokumen Pendukung (Opsional)" prop="dokumen_pendukung">
          <div class="upload-wrapper">
            <el-upload
              class="upload-demo"
              action="#"
              :auto-upload="false"
              :limit="1"
              :on-change="handleFileChange"
              :on-remove="handleFileRemove"
              accept=".pdf,.jpg,.jpeg,.png"
            >
              <template #trigger>
                <el-button type="default">
                  <el-icon class="me-1"><Upload /></el-icon> Pilih Dokumen (PDF, JPG, PNG maks 5MB)
                </el-button>
              </template>
              <template #tip>
                <div class="el-upload__tip">
                  Contoh: Surat keterangan dokter untuk cuti sakit, surat undangan, atau dokumen resmi lainnya.
                </div>
              </template>
            </el-upload>
          </div>
        </el-form-item>

        <div class="form-actions">
          <el-button @click="router.push('/dashboard/cuti')" size="large">Batal</el-button>
          <el-button
            type="danger"
            color="#ef4444"
            @click="handleSubmit"
            :loading="submitting"
            size="large"
          >
            Kirim Pengajuan Cuti
          </el-button>
        </div>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { InfoFilled, UserFilled, Warning, Upload } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import { useCutiStore } from '@/store/cuti'
import { useAuthStore } from '@/store/auth'
import ApiService from '@/core/services/ApiService'

const router = useRouter()
const cutiStore = useCutiStore()
const authStore = useAuthStore()

const submitting = ref(false)
const loadingSuperiors = ref(false)
const formRef = ref<FormInstance>()
const pegawaiOptions = ref<any[]>([])
const authorizedSuperiors = ref<any[]>([])

const form = reactive({
  pegawai_id: '',
  tipe_cuti: 'Cuti Tahunan',
  tanggal_mulai: '',
  tanggal_selesai: '',
  alasan: '',
  dokumen_file: null as File | null
})

const rules = reactive<FormRules>({
  pegawai_id: [{ required: true, message: 'Pegawai pemohon wajib dipilih', trigger: 'change' }],
  tipe_cuti: [{ required: true, message: 'Tipe cuti wajib dipilih', trigger: 'change' }],
  tanggal_mulai: [{ required: true, message: 'Tanggal mulai cuti wajib diisi', trigger: 'change' }],
  tanggal_selesai: [{ required: true, message: 'Tanggal selesai cuti wajib diisi', trigger: 'change' }],
  alasan: [{ required: true, message: 'Alasan cuti wajib diisi minimal 5 karakter', trigger: 'blur', min: 5 }]
})

// Minimum H-7 Date
const minAllowedDate = computed(() => {
  const d = new Date()
  d.setDate(d.getDate() + 7)
  d.setHours(0, 0, 0, 0)
  return d
})

const minDateFormatted = computed(() => {
  return minAllowedDate.value.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

const disabledStartDate = (time: Date) => {
  // Hanya bisa memilih H-7 ke depan
  const minTime = new Date()
  minTime.setDate(minTime.getDate() + 7)
  minTime.setHours(0, 0, 0, 0)
  return time.getTime() < minTime.getTime()
}

const disabledEndDate = (time: Date) => {
  if (!form.tanggal_mulai) return false
  const startDate = new Date(form.tanggal_mulai)
  startDate.setHours(0, 0, 0, 0)
  return time.getTime() < startDate.getTime()
}

const calculatedDays = computed(() => {
  if (!form.tanggal_mulai || !form.tanggal_selesai) return 0
  const start = new Date(form.tanggal_mulai)
  const end = new Date(form.tanggal_selesai)
  const diffTime = end.getTime() - start.getTime()
  if (diffTime < 0) return 0
  return Math.round(diffTime / (1000 * 3600 * 24)) + 1
})

const isPegawaiLocked = computed(() => {
  const isSuper = authStore.user?.role?.name === 'Super Admin' || authStore.dataProfile?.role?.name === 'Super Admin'
  return !isSuper && !!authStore.user?.pegawai_id
})

const selectedPegawaiInfo = computed(() => {
  return pegawaiOptions.value.find(p => String(p.id) === String(form.pegawai_id))
})

const fetchPegawaiList = async () => {
  try {
    const res = await ApiService.get('pegawai/all')
    pegawaiOptions.value = res.data?.data || []
  } catch (err) {
    console.error('Failed to load pegawai list', err)
  }
}

const fetchSuperiors = async (pegawaiId: string) => {
  if (!pegawaiId) {
    authorizedSuperiors.value = []
    return
  }
  loadingSuperiors.value = true
  try {
    const res = await cutiStore.getSuperiors(pegawaiId)
    authorizedSuperiors.value = res.data || []
  } catch (err) {
    authorizedSuperiors.value = []
  } finally {
    loadingSuperiors.value = false
  }
}

const handlePegawaiChange = (val: string) => {
  fetchSuperiors(val)
}

const handleDateChange = () => {
  if (form.tanggal_mulai && form.tanggal_selesai) {
    const start = new Date(form.tanggal_mulai)
    const end = new Date(form.tanggal_selesai)
    if (end < start) {
      form.tanggal_selesai = form.tanggal_mulai
    }
  }
}

const handleFileChange = (file: any) => {
  form.dokumen_file = file.raw
}

const handleFileRemove = () => {
  form.dokumen_file = null
}

onMounted(async () => {
  await fetchPegawaiList()
  const myPegawaiId = authStore.user?.pegawai_id || (authStore.dataProfile as any)?.pegawai_id

  if (myPegawaiId) {
    form.pegawai_id = String(myPegawaiId)
    await fetchSuperiors(myPegawaiId)
  } else if (pegawaiOptions.value.length > 0) {
    form.pegawai_id = String(pegawaiOptions.value[0].id)
    await fetchSuperiors(form.pegawai_id)
  }
})

const handleSubmit = async () => {
  if (!formRef.value || submitting.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('pegawai_id', form.pegawai_id)
    formData.append('tipe_cuti', form.tipe_cuti)
    formData.append('tanggal_mulai', form.tanggal_mulai)
    formData.append('tanggal_selesai', form.tanggal_selesai)
    formData.append('alasan', form.alasan)
    if (form.dokumen_file) {
      formData.append('dokumen_pendukung', form.dokumen_file)
    }

    const res = await cutiStore.create(formData)
    showSuccessToast(res?.message || 'Pengajuan cuti berhasil dikirimkan.')
    router.push('/dashboard/cuti')
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Gagal mengirim pengajuan cuti'
    showErrorToast(msg)
  } finally {
    submitting.value = false
  }
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
.breadcrumb {
  font-size: 0.875rem;
}
.breadcrumb .back-link {
  color: #111827;
  text-decoration: none;
  font-weight: 500;
}
.breadcrumb .back-link:hover {
  color: #ef4444;
}
.breadcrumb .separator {
  margin: 0 8px;
  color: #d1d5db;
}
.breadcrumb .active {
  color: #6b7280;
}

.rule-banner {
  display: flex;
  gap: 16px;
  background-color: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  padding: 16px 20px;
}
.banner-icon {
  color: #16a34a;
  margin-top: 2px;
}
.banner-title {
  font-size: 15px;
  font-weight: 700;
  color: #166534;
  margin: 0 0 6px 0;
}
.banner-text {
  font-size: 13px;
  line-height: 1.6;
  color: #15803d;
  margin: 0;
}

.box-card {
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.field-hint {
  display: block;
  font-size: 12px;
  color: #6b7280;
  margin-top: 6px;
}
.text-success {
  color: #16a34a !important;
}

.pegawai-badge-info {
  display: flex;
  align-items: center;
  font-size: 13px;
  font-weight: 500;
  color: #374151;
  background-color: #f9fafb;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.superiors-section {
  background-color: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  padding: 14px 18px;
}
.superiors-header {
  display: flex;
  align-items: center;
  font-weight: 600;
  color: #1e40af;
  font-size: 14px;
}
.superiors-desc {
  font-size: 12.5px;
  color: #3b82f6;
  margin: 4px 0 10px 0;
}
.superiors-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.superior-pill {
  display: flex;
  align-items: center;
  background-color: #ffffff;
  border: 1px solid #dbeafe;
  padding: 6px 12px;
  border-radius: 20px;
}
.superior-meta {
  display: flex;
  flex-direction: column;
}
.sup-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #1e3a8a;
}
.sup-role {
  font-size: 11px;
  color: #6b7280;
}

.superiors-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: #fffbeb;
  border: 1px solid #fef3c7;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 12.5px;
  color: #b45309;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #f3f4f6;
}
</style>
