<template>
  <CustomDrawer
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :title="'Kelola Kontrak - ' + (pegawai?.nama || 'Pegawai')"
    default-size="820px"
    @closed="handleDrawerClosed"
  >
    <div class="kontrak-drawer-content" v-loading="isLoading">
      <!-- HEADER RINGKASAN PEGAWAI -->
      <div class="pegawai-summary-card">
        <div class="summary-left">
          <div class="avatar-box">
            {{ pegawai?.nama?.charAt(0).toUpperCase() }}
          </div>
          <div>
            <div class="p-name">{{ pegawai?.nama }}</div>
            <div class="p-meta">NIK: {{ pegawai?.nik }} | NIP: {{ pegawai?.nip }}</div>
            <div class="p-meta">{{ pegawai?.email }}</div>
          </div>
        </div>
        <div class="summary-right">
          <el-tooltip
            v-if="!isFormActive"
            :disabled="!hasActiveContract"
            content="Silahkan Selesaikan Kontrak Kerja yang sedang berjalan"
            placement="bottom-end"
          >
            <span style="display: inline-block;">
              <AppButton 
                variant="primary" 
                :disabled="hasActiveContract" 
                @click="openCreateForm"
              >
                <template #icon>
                  <el-icon><Plus /></el-icon>
                </template>
                Buat Kontrak Baru
              </AppButton>
            </span>
          </el-tooltip>
        </div>
      </div>

      <!-- FORM TAMBAH / EDIT KONTRAK (INLINE JIKA SEDANG EDIT/CREATE) -->
      <div v-if="isFormActive" class="kontrak-form-section">
        <div class="form-section-header">
          <span class="form-title">{{ formMode === 'create' ? 'Buat Kontrak Baru' : 'Edit Kontrak #' + form.no_kontrak }}</span>
          <el-button size="small" @click="closeForm">Batal</el-button>
        </div>

        <el-alert
          v-if="hasPrevious && formMode === 'create'"
          type="info"
          :closable="false"
          show-icon
          class="mb-3"
        >
          <template #title>
            <strong>Data Disalin dari Kontrak Terakhir (#{{ prevNo }})</strong>
          </template>
          <div style="font-size: 12px; margin-top: 2px;">
            Upah tetap, upah harian, jabatan, area, dan komponen tunjangan/potongan telah disalin otomatis. Sesuaikan jika terdapat perubahan.
          </div>
        </el-alert>

        <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
          <el-row :gutter="14">
            <el-col :span="12">
              <el-form-item label="No. Kontrak" prop="no_kontrak" required>
                <el-input v-model="form.no_kontrak" placeholder="Cth: KTR/2026/010" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Tipe Kontrak" prop="tipe_kontrak" required>
                <el-select v-model="form.tipe_kontrak" style="width: 100%;">
                  <el-option label="Probation" value="probation" />
                  <el-option label="Kontrak (PKWT)" value="kontrak" />
                  <el-option label="Permanen (PKWTT)" value="permanen" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="14">
            <el-col :span="12">
              <el-form-item label="Tanggal Mulai (TMT)" prop="terhitung_mulai_tanggal" required>
                <el-date-picker
                  v-model="form.terhitung_mulai_tanggal"
                  type="date"
                  value-format="YYYY-MM-DD"
                  placeholder="Pilih Tanggal Mulai"
                  style="width: 100%;"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Sampai Tanggal (TAT)" prop="terhitung_sampai_tanggal">
                <el-date-picker
                  v-model="form.terhitung_sampai_tanggal"
                  type="date"
                  value-format="YYYY-MM-DD"
                  placeholder="Kosongkan jika Permanen"
                  style="width: 100%;"
                  :disabled="form.tipe_kontrak === 'permanen'"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="14">
            <el-col :span="12">
              <el-form-item label="Jabatan" prop="jabatan_id" required>
                <el-select v-model="form.jabatan_id" placeholder="Pilih Jabatan" filterable style="width: 100%;" @change="handleJabatanChange">
                  <el-option
                    v-for="j in jabatanOptions"
                    :key="j.id"
                    :label="j.nama_jabatan"
                    :value="j.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Area Penempatan" prop="area_id" required>
                <el-select v-model="form.area_id" placeholder="Pilih Area" filterable style="width: 100%;">
                  <el-option
                    v-for="a in areaOptions"
                    :key="a.id"
                    :label="a.nama_area + ' (' + (a.client?.nama_client || '') + ')'"
                    :value="a.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="14">
            <el-col :span="12">
              <el-form-item label="Status Kontrak" prop="status" required>
                <el-radio-group v-model="form.status">
                  <el-radio label="aktif">Aktif</el-radio>
                  <el-radio label="tidak aktif">Tidak Aktif</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="14">
            <el-col :span="12">
              <el-form-item label="Upah Bulanan (Tetap)" prop="element_tetap" required>
                <el-input-number
                  v-model="form.element_tetap"
                  :min="0"
                  :step="100000"
                  :controls="false"
                  style="width: 100%;"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Upah Harian (Variabel)" prop="element_variabel" required>
                <el-input-number
                  v-model="form.element_variabel"
                  :min="0"
                  :step="10000"
                  :controls="false"
                  style="width: 100%;"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <!-- KOMPONEN TUNJANGAN / POTONGAN FORM -->
          <div class="komponen-title-bar mt-2">
            <span style="font-weight: 600; font-size: 13px; color: #1e293b;">Rincian Tunjangan & Potongan</span>
            <el-button size="small" plain @click="addKomponenRow">
              <el-icon class="mr-1"><Plus /></el-icon> Tambah Komponen
            </el-button>
          </div>

          <div v-for="(k, idx) in form.komponens" :key="idx" class="k-form-row">
            <el-row :gutter="8">
              <el-col :span="6">
                <el-select v-model="k.jenis" placeholder="Jenis" size="small" style="width: 100%;">
                  <el-option label="Tunjangan" value="tunjangan" />
                  <el-option label="Potongan" value="potongan" />
                  <el-option label="Bonus" value="bonus" />
                  <el-option label="Komisi" value="komisi" />
                </el-select>
              </el-col>
              <el-col :span="7">
                <el-input v-model="k.nama_komponen" placeholder="Nama Komponen" size="small" />
              </el-col>
              <el-col :span="6">
                <el-select v-model="k.metode_hitung" placeholder="Metode" size="small" style="width: 100%;">
                  <el-option label="Nominal Tetap" value="tetap" />
                  <el-option label="% Upah Bulanan" value="persentase_tetap" />
                  <el-option label="% Upah Harian" value="persentase_variabel" />
                  <el-option label="Hari x Upah Harian" value="kali_hari_variabel" />
                  <el-option label="Custom" value="custom" />
                </el-select>
              </el-col>
              <el-col :span="4">
                <el-input-number v-model="k.nilai" :min="0" :controls="false" size="small" style="width: 100%;" />
              </el-col>
              <el-col :span="1" class="text-center">
                <el-button type="danger" text circle size="small" @click="form.komponens.splice(idx, 1)">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </el-col>
            </el-row>
          </div>

          <div class="form-btn-row mt-4">
            <el-button @click="closeForm">Batal</el-button>
            <AppButton variant="primary" :loading="isSaving" @click="submitForm">
              {{ formMode === 'create' ? 'Simpan Kontrak' : 'Perbarui Kontrak' }}
            </AppButton>
          </div>
        </el-form>
      </div>

      <!-- LIST RIWAYAT KONTRAK PEGAWAI -->
      <div class="kontrak-history-section" v-if="!isFormActive">
        <div class="section-title">Riwayat Kontrak ({{ kontrakList.length }} Kontrak)</div>

        <div v-if="kontrakList.length === 0" class="empty-kontrak">
          <el-empty description="Pegawai ini belum memiliki riwayat kontrak." />
        </div>

        <div 
          v-for="k in kontrakList" 
          :key="k.id" 
          class="kontrak-card" 
          :class="{ 'is-active': k.status === 'aktif', 'is-locked': k.is_locked }"
        >
          <!-- CARD TOP HEADER -->
          <div class="card-header">
            <div class="header-identity">
              <div class="doc-icon-badge" :class="{ 'is-active-icon': k.status === 'aktif' }">
                <el-icon :size="18"><Document /></el-icon>
              </div>
              <div class="identity-info">
                <div class="no-row">
                  <span class="no-kontrak">{{ k.no_kontrak }}</span>
                  <span v-if="k.status === 'aktif'" class="status-pill active">
                    <span class="status-dot"></span> Aktif
                  </span>
                  <span v-else class="status-pill inactive">
                    Tidak Aktif
                  </span>
                  <span class="tipe-pill" :class="k.tipe_kontrak">
                    {{ formatTipe(k.tipe_kontrak) }}
                  </span>
                  <span v-if="k.is_locked" class="locked-pill">
                    <el-icon class="mr-1"><Lock /></el-icon> Terkunci
                  </span>
                </div>
              </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="header-actions">
              <el-tooltip content="Selesaikan & kunci kontrak agar arsip tidak berubah" placement="top" :disabled="k.is_locked || k.status !== 'aktif'">
                <el-button 
                  v-if="!k.is_locked && k.status === 'aktif'" 
                  size="small" 
                  type="warning" 
                  plain 
                  class="action-btn"
                  @click="lockKontrak(k)"
                >
                  <el-icon class="mr-1"><Lock /></el-icon> Selesaikan
                </el-button>
              </el-tooltip>
              <el-button 
                v-if="!k.is_locked" 
                size="small" 
                type="primary" 
                plain 
                class="action-btn"
                @click="editKontrak(k)"
              >
                <el-icon class="mr-1"><Edit /></el-icon> Edit
              </el-button>
              <el-button 
                v-if="!k.is_locked" 
                size="small" 
                type="danger" 
                text 
                class="action-btn-del"
                @click="deleteKontrak(k)"
              >
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
          </div>

          <!-- ROLE & PLACEMENT -->
          <div class="role-placement-row">
            <div class="role-box">
              <div class="role-title">{{ k.jabatan?.nama_jabatan || '-' }}</div>
              <div class="placement-subtitle">
                <el-icon class="icon-sub"><OfficeBuilding /></el-icon>
                <span>{{ k.area?.nama_area }}</span>
                <span class="client-dot">•</span>
                <span class="client-name">{{ k.area?.client?.nama_client || '-' }}</span>
              </div>
            </div>
            <div class="duration-badge">
              <el-icon class="mr-1"><Calendar /></el-icon>
              <span>{{ getDurasiKontrak(k) }}</span>
            </div>
          </div>

          <!-- TIMELINE STRIP -->
          <div class="timeline-strip">
            <div class="t-node">
              <span class="t-label">Mulai Kontrak (TMT)</span>
              <span class="t-date">{{ formatDate(k.terhitung_mulai_tanggal) }}</span>
            </div>
            <div class="t-divider">
              <div class="t-line"></div>
              <el-icon class="t-arrow"><ArrowRight /></el-icon>
            </div>
            <div class="t-node text-right">
              <span class="t-label">Selesai Kontrak (TAT)</span>
              <span class="t-date" :class="{ 'is-permanent': !k.terhitung_sampai_tanggal }">
                {{ k.terhitung_sampai_tanggal ? formatDate(k.terhitung_sampai_tanggal) : 'Seterusnya (Permanen)' }}
              </span>
            </div>
          </div>

          <!-- WAGE FINANCIAL GRID -->
          <div class="wage-grid">
            <div class="wage-card">
              <div class="wage-label">Upah Bulanan (Tetap)</div>
              <div class="wage-value">{{ formatRupiah(k.element_tetap) }}</div>
              <div class="wage-unit">per bulan kalender</div>
            </div>
            <div class="wage-card">
              <div class="wage-label">Upah Harian (Variabel)</div>
              <div class="wage-value">{{ formatRupiah(k.element_variabel) }}</div>
              <div class="wage-unit">per hari kerja aktif</div>
            </div>
          </div>

          <!-- DAFTAR KOMPONEN TUNJANGAN / POTONGAN KONTRAK INI -->
          <div class="komponen-breakdown-box" v-if="k.komponens && k.komponens.length > 0">
            <!-- Tunjangan, Bonus, Komisi -->
            <div v-if="getTunjanganKomponens(k.komponens).length > 0" class="komp-group mb-2">
              <div class="komp-group-title tunjangan-title">
                <span>Tunjangan & Pendapatan Rutin</span>
                <span class="badge-count">{{ getTunjanganKomponens(k.komponens).length }}</span>
              </div>
              <div class="komp-chips-grid">
                <div 
                  v-for="(comp, cIdx) in getTunjanganKomponens(k.komponens)" 
                  :key="cIdx" 
                  class="komp-chip positive"
                >
                  <span class="k-chip-name">{{ comp.nama_komponen }}</span>
                  <span class="k-chip-val">+ {{ formatKomponenNilai(comp, k) }}</span>
                </div>
              </div>
            </div>

            <!-- Potongan -->
            <div v-if="getPotonganKomponens(k.komponens).length > 0" class="komp-group">
              <div class="komp-group-title potongan-title">
                <span>Potongan Rutin</span>
                <span class="badge-count">{{ getPotonganKomponens(k.komponens).length }}</span>
              </div>
              <div class="komp-chips-grid">
                <div 
                  v-for="(comp, cIdx) in getPotonganKomponens(k.komponens)" 
                  :key="cIdx" 
                  class="komp-chip negative"
                >
                  <span class="k-chip-name">{{ comp.nama_komponen }}</span>
                  <span class="k-chip-val">- {{ formatKomponenNilai(comp, k) }}</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-komp-notice">
            <span>Tidak ada komponen tunjangan atau potongan tambahan</span>
          </div>
        </div>
      </div>
    </div>
  </CustomDrawer>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { Plus, Edit, Delete, Lock, Calendar, OfficeBuilding, Document, ArrowRight } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessageBox } from 'element-plus'

import CustomDrawer from '@/components/common/CustomDrawer.vue'
import AppButton from '@/components/common/AppButton.vue'
import { useKontrakStore, type PegawaiKontrak, type KontrakKomponen } from '@/store/kontrak'
import { useJabatanStore, type Jabatan } from '@/store/jabatan'
import { useClientAreaStore, type Area } from '@/store/clientArea'
import type { Pegawai } from '@/store/pegawai'

const props = defineProps<{
  modelValue: boolean
  pegawai: Pegawai | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
  (e: 'updated'): void
}>()

const kontrakStore = useKontrakStore()
const jabatanStore = useJabatanStore()
const clientAreaStore = useClientAreaStore()

const isLoading = ref(false)
const isSaving = ref(false)
const kontrakList = ref<PegawaiKontrak[]>([])
const jabatanOptions = ref<Jabatan[]>([])
const areaOptions = ref<Area[]>([])

const hasActiveContract = computed(() => {
  return kontrakList.value.some(k => k.status === 'aktif')
})

const isFormActive = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const hasPrevious = ref(false)
const prevNo = ref('')

const formRef = ref<FormInstance>()
const form = reactive({
  id: 0,
  no_kontrak: '',
  terhitung_mulai_tanggal: '',
  terhitung_sampai_tanggal: '' as string | null,
  jabatan_id: undefined as number | undefined,
  area_id: undefined as number | undefined,
  tipe_kontrak: 'kontrak' as 'probation' | 'kontrak' | 'permanen',
  status: 'aktif' as 'aktif' | 'tidak aktif',
  element_variabel: 0,
  element_tetap: 0,
  keterangan: '',
  komponens: [] as KontrakKomponen[]
})

const rules: FormRules = {
  no_kontrak: [{ required: true, message: 'No. kontrak wajib diisi', trigger: 'blur' }],
  terhitung_mulai_tanggal: [{ required: true, message: 'Tanggal mulai wajib diisi', trigger: 'change' }],
  jabatan_id: [{ required: true, message: 'Jabatan wajib dipilih', trigger: 'change' }],
  area_id: [{ required: true, message: 'Area wajib dipilih', trigger: 'change' }],
  status: [{ required: true, message: 'Status wajib dipilih', trigger: 'change' }]
}

const loadKontrakPegawai = async () => {
  if (!props.pegawai?.id) return
  isLoading.value = true
  try {
    const res = await kontrakStore.getData({
      pegawai_id: props.pegawai.id,
      limit: 100
    })
    kontrakList.value = res.data || []
    jabatanOptions.value = await jabatanStore.getAll()
    areaOptions.value = await clientAreaStore.getAllAreas()
  } catch (e: any) {
    showErrorToast(e?.response?.data?.message || 'Gagal memuat riwayat kontrak')
  } finally {
    isLoading.value = false
  }
}

watch(
  () => props.modelValue,
  (val) => {
    if (val && props.pegawai) {
      isFormActive.value = false
      loadKontrakPegawai()
    }
  }
)

const handleDrawerClosed = () => {
  isFormActive.value = false
}

const openCreateForm = async () => {
  if (!props.pegawai?.id) return
  if (hasActiveContract.value) {
    showErrorToast('Silahkan Selesaikan Kontrak Kerja yang sedang berjalan')
    return
  }
  formMode.value = 'create'
  isFormActive.value = true
  resetFormState()

  try {
    const res = await kontrakStore.getLatestByPegawai(props.pegawai.id)
    if (res?.has_previous_contract && res?.data) {
      hasPrevious.value = true
      prevNo.value = res.data.previous_contract_no

      // Carry-over data kontrak terakhir
      form.element_tetap = Number(res.data.element_tetap) || 0
      form.element_variabel = Number(res.data.element_variabel) || 0
      form.jabatan_id = res.data.jabatan_id
      form.area_id = res.data.area_id
      form.tipe_kontrak = res.data.tipe_kontrak || 'kontrak'
      form.komponens = (res.data.komponens || []).map((k: any) => ({
        jenis: k.jenis,
        nama_komponen: k.nama_komponen,
        metode_hitung: k.metode_hitung,
        nilai: Number(k.nilai) || 0,
        keterangan: k.keterangan || ''
      }))
    } else {
      hasPrevious.value = false
      prevNo.value = ''
    }
  } catch (e) {
    console.error(e)
  }
}

const editKontrak = (k: PegawaiKontrak) => {
  formMode.value = 'edit'
  isFormActive.value = true
  hasPrevious.value = false
  form.id = k.id
  form.no_kontrak = k.no_kontrak
  form.terhitung_mulai_tanggal = k.terhitung_mulai_tanggal
  form.terhitung_sampai_tanggal = k.terhitung_sampai_tanggal || null
  form.jabatan_id = k.jabatan_id
  form.area_id = k.area_id
  form.tipe_kontrak = k.tipe_kontrak
  form.status = k.status
  form.element_variabel = Number(k.element_variabel) || 0
  form.element_tetap = Number(k.element_tetap) || 0
  form.keterangan = k.keterangan || ''
  form.komponens = (k.komponens || []).map(comp => ({
    jenis: comp.jenis,
    nama_komponen: comp.nama_komponen,
    metode_hitung: comp.metode_hitung,
    nilai: Number(comp.nilai) || 0,
    keterangan: comp.keterangan || ''
  }))
}

const closeForm = () => {
  isFormActive.value = false
}

const resetFormState = () => {
  form.id = 0
  form.no_kontrak = ''
  form.terhitung_mulai_tanggal = ''
  form.terhitung_sampai_tanggal = null
  form.jabatan_id = undefined
  form.area_id = undefined
  form.tipe_kontrak = 'kontrak'
  form.status = 'aktif'
  form.element_variabel = 0
  form.element_tetap = 0
  form.keterangan = ''
  form.komponens = []
  formRef.value?.resetFields()
}

const handleJabatanChange = (jId: number) => {
  if (!hasPrevious.value && form.komponens.length === 0) {
    const sel = jabatanOptions.value.find(j => j.id === jId)
    if (sel && sel.komponens) {
      form.komponens = sel.komponens.map(k => ({
        jenis: k.jenis,
        nama_komponen: k.nama_komponen,
        metode_hitung: k.tipe_nilai,
        nilai: Number(k.nilai_default) || 0,
        keterangan: k.keterangan || ''
      }))
    }
  }
}

const addKomponenRow = () => {
  form.komponens.push({
    jenis: 'tunjangan',
    nama_komponen: '',
    metode_hitung: 'tetap',
    nilai: 0,
    keterangan: ''
  })
}

const submitForm = async () => {
  if (!formRef.value || !props.pegawai?.id) return
  const currentPegawaiId = props.pegawai.id
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    isSaving.value = true
    try {
      const payload = {
        pegawai_id: currentPegawaiId,
        no_kontrak: form.no_kontrak,
        terhitung_mulai_tanggal: form.terhitung_mulai_tanggal,
        terhitung_sampai_tanggal: form.terhitung_sampai_tanggal,
        jabatan_id: form.jabatan_id,
        area_id: form.area_id,
        tipe_kontrak: form.tipe_kontrak,
        status: form.status,
        element_variabel: form.element_variabel,
        element_tetap: form.element_tetap,
        keterangan: form.keterangan,
        komponens: form.komponens
      }

      if (formMode.value === 'create') {
        await kontrakStore.create(payload)
        showSuccessToast('Kontrak berhasil dibuat')
      } else {
        await kontrakStore.update(form.id, payload)
        showSuccessToast('Kontrak berhasil diperbarui')
      }

      isFormActive.value = false
      loadKontrakPegawai()
      emit('updated')
    } catch (e: any) {
      showErrorToast(e?.response?.data?.message || 'Gagal menyimpan kontrak')
    } finally {
      isSaving.value = false
    }
  })
}

const lockKontrak = async (k: PegawaiKontrak) => {
  try {
    await ElMessageBox.confirm(
      `Selesaikan dan kunci kontrak "${k.no_kontrak}"? Setelah dikunci, status kontrak tidak aktif dan tidak dapat diedit lagi.`,
      'Konfirmasi Kunci Kontrak',
      { confirmButtonText: 'Ya, Selesaikan & Kunci', cancelButtonText: 'Batal', type: 'warning' }
    )
    await kontrakStore.lock(k.id)
    showSuccessToast('Kontrak berhasil diselesaikan dan dikunci')
    loadKontrakPegawai()
    emit('updated')
  } catch (e: any) {
    if (e !== 'cancel') showErrorToast(e?.response?.data?.message || 'Gagal mengunci kontrak')
  }
}

const deleteKontrak = async (k: PegawaiKontrak) => {
  try {
    await ElMessageBox.confirm(
      `Hapus kontrak "${k.no_kontrak}"?`,
      'Konfirmasi Hapus',
      { confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', type: 'warning' }
    )
    await kontrakStore.destroy(k.id)
    showSuccessToast('Kontrak berhasil dihapus')
    loadKontrakPegawai()
    emit('updated')
  } catch (e: any) {
    if (e !== 'cancel') showErrorToast(e?.response?.data?.message || 'Gagal menghapus kontrak')
  }
}

const formatRupiah = (val: number | string) => {
  const num = Number(val) || 0
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num)
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatTipe = (tipe: string) => {
  if (tipe === 'probation') return 'Probation'
  if (tipe === 'permanen') return 'PKWTT (Tetap)'
  return 'PKWT (Kontrak)'
}

const getTipeBadge = (tipe: string) => {
  if (tipe === 'probation') return 'warning'
  if (tipe === 'permanen') return 'success'
  return 'primary'
}

const formatKomponenNilai = (comp: any, k: PegawaiKontrak) => {
  if (comp.metode_hitung === 'tetap' || comp.metode_hitung === 'custom') {
    return formatRupiah(comp.nilai)
  }
  if (comp.metode_hitung === 'persentase_tetap') {
    return `${comp.nilai}% = ${formatRupiah((comp.nilai / 100) * Number(k.element_tetap))}`
  }
  if (comp.metode_hitung === 'kali_hari_variabel') {
    return `${comp.nilai} hari x ${formatRupiah(k.element_variabel)}`
  }
  return formatRupiah(comp.nilai)
}

const getDurasiKontrak = (k: PegawaiKontrak) => {
  if (k.tipe_kontrak === 'permanen' || !k.terhitung_sampai_tanggal) {
    return 'Masa Kerja Permanen'
  }
  if (!k.terhitung_mulai_tanggal) return '-'
  
  const start = new Date(k.terhitung_mulai_tanggal)
  const end = new Date(k.terhitung_sampai_tanggal)
  const diffTime = end.getTime() - start.getTime()
  if (diffTime < 0) return '-'
  
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  const months = Math.round(diffDays / 30.4)
  
  if (months < 1) {
    return `${diffDays} Hari`
  } else if (months < 12) {
    return `${months} Bulan`
  } else {
    const years = (months / 12).toFixed(1).replace('.0', '')
    return `${years} Thn (${months} Bln)`
  }
}

const getTunjanganKomponens = (komponens?: KontrakKomponen[]) => {
  if (!komponens) return []
  return komponens.filter(c => c.jenis === 'tunjangan' || c.jenis === 'bonus' || c.jenis === 'komisi')
}

const getPotonganKomponens = (komponens?: KontrakKomponen[]) => {
  if (!komponens) return []
  return komponens.filter(c => c.jenis === 'potongan')
}
</script>

<style scoped>
.kontrak-drawer-content {
  padding: 8px 16px 24px 16px;
}

.pegawai-summary-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 14px 16px;
  margin-bottom: 20px;
}

.summary-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.avatar-box {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #2563eb;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 600;
}

.p-name {
  font-weight: 700;
  font-size: 16px;
  color: #0f172a;
}

.p-meta {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.section-title {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 12px;
}

.kontrak-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 18px;
  margin-bottom: 16px;
  position: relative;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
}

.kontrak-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
}

.kontrak-card.is-active {
  /* border-left: 4px solid #16a34a; */
}

.kontrak-card.is-locked {
  background: #fbfcfd;
  border-color: #e2e8f0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
}

.header-identity {
  display: flex;
  align-items: center;
  gap: 12px;
}

.doc-icon-badge {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #f1f5f9;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
}

.doc-icon-badge.is-active-icon {
  background: #ecfdf5;
  color: #059669;
}

.no-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.no-kontrak {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.status-pill {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.status-pill.active {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.status-pill.inactive {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
}

.tipe-pill {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 6px;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.tipe-pill.probation {
  background: #fffbeb;
  color: #b45309;
  border-color: #fde68a;
}

.tipe-pill.permanen {
  background: #f0fdf4;
  color: #15803d;
  border-color: #bbf7d0;
}

.locked-pill {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 6px;
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  display: inline-flex;
  align-items: center;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.action-btn {
  font-size: 12px;
}

.role-placement-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.role-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.3;
}

.placement-subtitle {
  display: flex;
  align-items: center;
  font-size: 12.5px;
  color: #475569;
  margin-top: 3px;
  gap: 4px;
}

.icon-sub {
  color: #64748b;
  font-size: 14px;
}

.client-dot {
  color: #94a3b8;
  margin: 0 2px;
}

.client-name {
  color: #2563eb;
  font-weight: 500;
}

.duration-badge {
  display: inline-flex;
  align-items: center;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 4px 10px;
  border-radius: 6px;
  white-space: nowrap;
}

.timeline-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 8px 12px;
  margin-bottom: 14px;
}

.t-node {
  display: flex;
  flex-direction: column;
}

.t-label {
  font-size: 10.5px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #64748b;
  font-weight: 600;
}

.t-date {
  font-size: 12.5px;
  font-weight: 600;
  color: #1e293b;
  margin-top: 1px;
}

.t-date.is-permanent {
  color: #059669;
}

.t-divider {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  margin: 0 16px;
  justify-content: center;
}

.t-line {
  height: 1px;
  flex: 1;
  background: #cbd5e1;
}

.t-arrow {
  color: #94a3b8;
  font-size: 12px;
}

.wage-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 14px;
}

.wage-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 12px;
}

.wage-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.wage-value {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin-top: 3px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.wage-unit {
  font-size: 11px;
  color: #94a3b8;
  margin-top: 2px;
}

.komponen-breakdown-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 12px;
}

.komp-group-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11.5px;
  font-weight: 600;
  margin-bottom: 6px;
}

.tunjangan-title {
  color: #15803d;
}

.potongan-title {
  color: #b91c1c;
}

.badge-count {
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 9999px;
  background: #f1f5f9;
  color: #475569;
  font-weight: 700;
}

.komp-chips-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.komp-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: 500;
}

.komp-chip.positive {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
}

.komp-chip.negative {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.k-chip-name {
  font-weight: 600;
}

.k-chip-val {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 11px;
  opacity: 0.9;
}

.empty-komp-notice {
  font-size: 12px;
  color: #94a3b8;
  font-style: italic;
  padding: 4px 0;
}

.kontrak-form-section {
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
}

.form-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.form-title {
  font-weight: 700;
  font-size: 15px;
  color: #0f172a;
}

.komponen-title-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.k-form-row {
  margin-bottom: 8px;
}

.form-btn-row {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 12px;
  border-top: 1px solid #e2e8f0;
}
</style>
