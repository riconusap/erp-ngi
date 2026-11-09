<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Kontrak Pegawai</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola masa berlaku kontrak, upah bulanan, upah harian, serta tunjangan dan potongan rutin</p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="openCreateDrawer">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Buat Kontrak Baru
        </AppButton>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Cari no. kontrak atau nama pegawai..."
        class="search-input"
        clearable
        @clear="fetchData"
        @keyup.enter="fetchData"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
      <div class="filter-actions">
        <el-select v-model="statusFilter" placeholder="Status Kontrak" clearable style="width: 150px" @change="fetchData">
          <el-option label="Semua Status" value="" />
          <el-option label="Aktif" value="aktif" />
          <el-option label="Tidak Aktif" value="tidak aktif" />
        </el-select>
        <el-select v-model="tipeFilter" placeholder="Tipe Kontrak" clearable style="width: 150px" @change="fetchData">
          <el-option label="Semua Tipe" value="" />
          <el-option label="Probation" value="probation" />
          <el-option label="Kontrak (PKWT)" value="kontrak" />
          <el-option label="Permanen (PKWTT)" value="permanen" />
        </el-select>
        <el-select v-model="projectFilter" placeholder="Filter Project" clearable filterable style="width: 200px" @change="fetchData">
          <el-option label="Semua Project" value="" />
          <el-option
            v-for="p in projectOptions"
            :key="p.id"
            :label="`${p.kode_project} - ${p.nama_project}`"
            :value="p.id"
          />
        </el-select>
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :data="data"
      :loading="isLoading"
      :total="meta.total"
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      empty-title="Belum ada kontrak pegawai"
      empty-subtitle="Silakan buat kontrak baru untuk pegawai"
      :empty-image="EmptyStateImage"
    >
      <el-table-column prop="no_kontrak" label="No. Kontrak" width="160">
        <template #default="{ row }">
          <div class="kontrak-no-wrapper">
            <span class="no-text">{{ row.no_kontrak }}</span>
            <el-tag v-if="row.is_locked" size="small" type="danger" effect="plain" class="mt-1">
              <el-icon class="mr-1"><Lock /></el-icon> Selesai / Terkunci
            </el-tag>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Pegawai" min-width="190">
        <template #default="{ row }">
          <div class="pegawai-cell">
            <span class="pegawai-name">{{ row.pegawai?.nama || '-' }}</span>
            <span class="pegawai-sub">NIK: {{ row.pegawai?.nik || '-' }}</span>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Jabatan & Area" min-width="210">
        <template #default="{ row }">
          <div>
            <div style="font-weight: 500; color: #1e293b;">{{ row.jabatan?.nama_jabatan || '-' }}</div>
            <div style="font-size: 12px; color: #64748b;">
              {{ row.area?.nama_area }} ({{ row.area?.client?.nama_client || '-' }})
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Project Ditugaskan" min-width="210">
        <template #default="{ row }">
          <div v-if="row.projects && row.projects.length" style="display: flex; flex-wrap: wrap; gap: 4px;">
            <el-tag
              v-for="prj in row.projects"
              :key="prj.id"
              size="small"
              effect="plain"
              type="primary"
            >
              <el-icon class="mr-1"><FolderOpened /></el-icon>
              {{ prj.nama_project }}
            </el-tag>
          </div>
          <span v-else style="color: #94a3b8; font-size: 13px;">-</span>
        </template>
      </el-table-column>

      <el-table-column label="Tipe & Periode" min-width="200">
        <template #default="{ row }">
          <div>
            <el-tag size="small" :type="getTipeBadgeType(row.tipe_kontrak)" class="mb-1">
              {{ formatTipeKontrak(row.tipe_kontrak) }}
            </el-tag>
            <div style="font-size: 13px; color: #334155;">
              {{ formatDate(row.terhitung_mulai_tanggal) }} s/d {{ row.terhitung_sampai_tanggal ? formatDate(row.terhitung_sampai_tanggal) : 'Seterusnya' }}
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Upah Bulanan / Harian" min-width="180">
        <template #default="{ row }">
          <div>
            <div style="font-weight: 600; color: #0f172a;">{{ formatRupiah(row.element_tetap) }} <span class="unit-sub">/bln</span></div>
            <div style="font-size: 12px; color: #64748b;">{{ formatRupiah(row.element_variabel) }} <span class="unit-sub">/hari</span></div>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Status" width="110" align="center">
        <template #default="{ row }">
          <el-tag :type="row.status === 'aktif' ? 'success' : 'info'" size="small">
            {{ row.status === 'aktif' ? 'Aktif' : 'Tidak Aktif' }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Aksi" width="80" fixed="right" align="center">
        <template #default="{ row }">
          <el-dropdown trigger="click" @command="(cmd: string) => handleAction(cmd, row)">
            <el-button type="info" text circle>
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="detail">
                  <el-icon><View /></el-icon> Detail Kontrak
                </el-dropdown-item>
                <el-dropdown-item command="edit" :disabled="row.is_locked">
                  <el-icon><Edit /></el-icon> Edit Kontrak
                </el-dropdown-item>
                <el-dropdown-item command="lock" v-if="!row.is_locked && row.status === 'aktif'" style="color: var(--el-color-warning);">
                  <el-icon><Lock /></el-icon> Selesaikan & Kunci
                </el-dropdown-item>
                <el-dropdown-item command="delete" :disabled="row.is_locked" class="text-danger">
                  <el-icon><Delete /></el-icon> Hapus Kontrak
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </DataTable>

    <!-- Drawer Form Tambah / Edit Kontrak -->
    <CustomDrawer
      v-model="isDrawerOpen"
      :title="drawerTitle"
      default-size="780px"
      @closed="resetForm"
    >
      <div class="admin-drawer-content">
        <!-- Banner Peringatan Kontrak Masih Aktif -->
        <el-alert
          v-if="hasActiveContractWarning && drawerMode === 'create'"
          type="warning"
          :closable="false"
          show-icon
          class="mb-4"
        >
          <template #title>
            <span style="font-weight: 700;">Pegawai Masih Memiliki Kontrak Berjalan (#{{ activeContractInfo?.no_kontrak }})</span>
          </template>
          <div style="font-size: 13px; margin-top: 4px; line-height: 1.5;">
            <p style="margin: 0 0 8px 0;">
              Silahkan selesaikan kontrak kerja yang sedang berjalan terlebih dahulu sebelum menambahkan kontrak baru untuk pegawai ini.
            </p>
            <el-button size="small" type="warning" plain @click="resolveActiveContractDirectly">
              <el-icon class="mr-1"><Lock /></el-icon> Selesaikan Kontrak Berjalan Ini
            </el-button>
          </div>
        </el-alert>

        <!-- Banner Auto Carry-over dari Kontrak Sebelumnya -->
        <el-alert
          v-if="hasPreviousContract && !hasActiveContractWarning && drawerMode === 'create'"
          type="info"
          :closable="false"
          show-icon
          class="mb-4"
        >
          <template #title>
            <span style="font-weight: 600;">Data Disalin Otomatis dari Kontrak Terakhir (#{{ previousContractNo }})</span>
          </template>
          <div style="font-size: 13px; margin-top: 4px; line-height: 1.5;">
            Nilai Upah Bulanan (Rp {{ formatRupiah(form.element_tetap) }}), Upah Harian (Rp {{ formatRupiah(form.element_variabel) }}), Jabatan, Area, serta rincian tunjangan & potongan telah disalin otomatis dari kontrak sebelumnya. Anda dapat mengubahnya sesuai kesepakatan kontrak baru ini.
          </div>
        </el-alert>

        <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
          <!-- 1. DATA UTAMA PEGAWAI & KONTRAK -->
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="Pegawai" prop="pegawai_id" required>
                <el-select 
                  v-model="form.pegawai_id" 
                  placeholder="Pilih Pegawai" 
                  filterable 
                  style="width: 100%;"
                  :disabled="drawerMode !== 'create'"
                  @change="handlePegawaiChange"
                >
                  <el-option
                    v-for="p in pegawaiOptions"
                    :key="p.id"
                    :label="p.nama + ' (' + p.nik + ')'"
                    :value="p.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="No. Kontrak" prop="no_kontrak" required>
                <el-input v-model="form.no_kontrak" placeholder="Cth: KTR/2026/001" :disabled="drawerMode === 'detail'" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="8">
              <el-form-item label="Tipe Kontrak" prop="tipe_kontrak" required>
                <el-select v-model="form.tipe_kontrak" placeholder="Pilih Tipe" style="width: 100%;" :disabled="drawerMode === 'detail'" @change="handleTipeChange">
                  <el-option label="Probation" value="probation" />
                  <el-option label="Kontrak (PKWT)" value="kontrak" />
                  <el-option label="Permanen (PKWTT)" value="permanen" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Terhitung Mulai (TMT)" prop="terhitung_mulai_tanggal" required>
                <el-date-picker
                  v-model="form.terhitung_mulai_tanggal"
                  type="date"
                  placeholder="Pilih Tanggal Mulai"
                  value-format="YYYY-MM-DD"
                  style="width: 100%;"
                  :disabled="drawerMode === 'detail'"
                />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Sampai Tanggal (TAT)" prop="terhitung_sampai_tanggal">
                <el-date-picker
                  v-model="form.terhitung_sampai_tanggal"
                  type="date"
                  placeholder="Kosongkan jika Permanen"
                  value-format="YYYY-MM-DD"
                  style="width: 100%;"
                  :disabled="drawerMode === 'detail' || form.tipe_kontrak === 'permanen'"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="Jabatan" prop="jabatan_id" required>
                <el-select v-model="form.jabatan_id" placeholder="Pilih Jabatan" filterable style="width: 100%;" :disabled="drawerMode === 'detail'" @change="handleJabatanChange">
                  <el-option
                    v-for="j in jabatanOptions"
                    :key="j.id"
                    :label="j.nama_jabatan + ' (' + j.kode_jabatan + ')'"
                    :value="j.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Area Penempatan" prop="area_id" required>
                <el-select v-model="form.area_id" placeholder="Pilih Area Penempatan" filterable style="width: 100%;" :disabled="drawerMode === 'detail'">
                  <el-option
                    v-for="a in areaOptions"
                    :key="a.id"
                    :label="a.nama_area + ' - ' + (a.client?.nama_client || '')"
                    :value="a.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="24">
              <el-form-item label="Project Terhubung (Otomatis dari Task Management)">
                <div v-if="form.project_ids && form.project_ids.length" style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
                  <el-tag
                    v-for="pId in form.project_ids"
                    :key="pId"
                    size="small"
                    type="primary"
                    effect="plain"
                  >
                    <el-icon class="mr-1"><FolderOpened /></el-icon>
                    {{ getProjectName(pId) }}
                  </el-tag>
                </div>
                <div v-else class="text-muted" style="font-size: 13px; margin-bottom: 6px;">
                  Belum terhubung ke project mana pun.
                </div>
                <div class="field-hint">
                  <el-icon class="mr-1"><InfoFilled /></el-icon>
                  Pegawai otomatis terhubung ke proyek setelah diberikan penugasan di modul Task Management.
                </div>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="Status Kontrak" prop="status" required>
                <el-radio-group v-model="form.status" :disabled="drawerMode === 'detail'">
                  <el-radio label="aktif">Aktif</el-radio>
                  <el-radio label="tidak aktif">Tidak Aktif</el-radio>
                </el-radio-group>
              </el-form-item>
            </el-col>
          </el-row>

          <el-divider content-position="left">
            <span style="font-weight: 600; color: #0f172a;">Besaran Upah Dasar</span>
          </el-divider>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="Element Tetap (Upah Bulanan)" prop="element_tetap" required>
                <el-input-number
                  v-model="form.element_tetap"
                  :min="0"
                  :step="100000"
                  :controls="false"
                  style="width: 100%;"
                  :disabled="drawerMode === 'detail'"
                />
                <div class="field-hint">Besaran gaji bulanan acuan kontrak</div>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Element Variabel (Upah Harian)" prop="element_variabel" required>
                <el-input-number
                  v-model="form.element_variabel"
                  :min="0"
                  :step="10000"
                  :controls="false"
                  style="width: 100%;"
                  :disabled="drawerMode === 'detail'"
                />
                <div class="field-hint">Besaran upah per kehadiran / per hari</div>
              </el-form-item>
            </el-col>
          </el-row>

          <!-- 2. TUNJANGAN & POTONGAN RUTIN KONTRAK -->
          <el-divider content-position="left">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
              <span style="font-weight: 600; color: #0f172a;">Tunjangan & Potongan Rutin Kontrak</span>
            </div>
          </el-divider>
          <p style="font-size: 13px; color: #64748b; margin-top: -12px; margin-bottom: 16px;">
            Atur elemen rutin yang akan otomatis masuk ke slip gaji pegawai pada periode kontrak ini.
          </p>

          <div class="komponen-list">
            <div v-for="(k, idx) in form.komponens" :key="idx" class="komponen-card">
              <div class="komponen-header">
                <div class="k-title">
                  <el-tag size="small" :type="k.jenis === 'tunjangan' || k.jenis === 'bonus' ? 'success' : (k.jenis === 'potongan' ? 'danger' : 'info')" effect="dark">
                    {{ k.jenis.toUpperCase() }}
                  </el-tag>
                  <span class="ml-2" style="font-weight: 600; font-size: 14px; color: #1e293b;">
                    {{ k.nama_komponen || 'Komponen #' + (idx + 1) }}
                  </span>
                </div>
                <el-button
                  v-if="drawerMode !== 'detail'"
                  type="danger"
                  text
                  circle
                  size="small"
                  @click="removeKomponen(idx)"
                >
                  <el-icon><Delete /></el-icon>
                </el-button>
              </div>

              <el-row :gutter="12" class="mt-2">
                <el-col :span="5">
                  <el-form-item label="Jenis" :prop="'komponens.' + idx + '.jenis'" required>
                    <el-select v-model="k.jenis" :disabled="drawerMode === 'detail'" style="width: 100%;">
                      <el-option label="Tunjangan" value="tunjangan" />
                      <el-option label="Potongan" value="potongan" />
                      <el-option label="Bonus" value="bonus" />
                      <el-option label="Komisi" value="komisi" />
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="7">
                  <el-form-item label="Nama Komponen" :prop="'komponens.' + idx + '.nama_komponen'" required>
                    <el-input v-model="k.nama_komponen" placeholder="Cth: Tunj. Makan" :disabled="drawerMode === 'detail'" />
                  </el-form-item>
                </el-col>

                <el-col :span="7">
                  <el-form-item label="Metode Hitung" :prop="'komponens.' + idx + '.metode_hitung'" required>
                    <el-select v-model="k.metode_hitung" :disabled="drawerMode === 'detail'" style="width: 100%;">
                      <el-option label="Nominal Tetap (Rp)" value="tetap" />
                      <el-option label="% dari Upah Bulanan" value="persentase_tetap" />
                      <el-option label="% dari Upah Harian" value="persentase_variabel" />
                      <el-option label="Jumlah Hari x Upah Harian" value="kali_hari_variabel" />
                      <el-option label="Custom / Lainnya" value="custom" />
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="5">
                  <el-form-item label="Nilai / Pengali" :prop="'komponens.' + idx + '.nilai'" required>
                    <el-input-number
                      v-model="k.nilai"
                      :min="0"
                      :step="k.metode_hitung.includes('persen') || k.metode_hitung === 'kali_hari_variabel' ? 1 : 10000"
                      :controls="false"
                      style="width: 100%;"
                      :disabled="drawerMode === 'detail'"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- Live Calc Preview -->
              <div class="calc-badge">
                <span class="calc-label">Estimasi Nilai:</span>
                <span class="calc-val">{{ formatRupiah(calculateKomponenValue(k)) }}</span>
              </div>
            </div>

            <div v-if="drawerMode !== 'detail'" style="margin-top: 12px;">
              <el-button plain @click="addKomponen">
                <el-icon class="mr-1"><Plus /></el-icon> Tambah Tunjangan / Potongan
              </el-button>
            </div>
          </div>

          <!-- SIMULASI RINGKASAN SLIP -->
          <div class="slip-summary-card mt-4">
            <div class="summary-header">Estimasi Slip Gaji Bulanan (22 Hari Kerja)</div>
            <div class="summary-row">
              <span>Upah Bulanan (Tetap)</span>
              <span>{{ formatRupiah(form.element_tetap) }}</span>
            </div>
            <div class="summary-row">
              <span>Estimasi Upah Harian (22 Hari x {{ formatRupiah(form.element_variabel) }})</span>
              <span>{{ formatRupiah(form.element_variabel * 22) }}</span>
            </div>
            <div class="summary-row text-success">
              <span>Total Tunjangan & Bonus (+)</span>
              <span>+ {{ formatRupiah(totalTunjangan) }}</span>
            </div>
            <div class="summary-row text-danger">
              <span>Total Potongan (-)</span>
              <span>- {{ formatRupiah(totalPotongan) }}</span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-total">
              <span>Estimasi Take Home Pay:</span>
              <span class="total-amount">{{ formatRupiah(estimatedTHP) }}</span>
            </div>
          </div>

          <el-form-item label="Keterangan Kontrak (Opsional)" class="mt-4">
            <el-input v-model="form.keterangan" type="textarea" :rows="2" placeholder="Catatan khusus kontrak..." :disabled="drawerMode === 'detail'" />
          </el-form-item>
        </el-form>

        <div class="drawer-actions">
          <el-button @click="isDrawerOpen = false" v-if="drawerMode !== 'detail'">Batal</el-button>
          <el-button @click="isDrawerOpen = false" v-if="drawerMode === 'detail'">Tutup</el-button>
          <AppButton
            v-if="drawerMode !== 'detail'"
            variant="primary"
            :loading="saving"
            :disabled="drawerMode === 'create' && hasActiveContractWarning"
            @click="handleSubmit"
          >
            {{ drawerMode === 'create' ? 'Simpan & Terbitkan Kontrak' : 'Perbarui Kontrak' }}
          </AppButton>
        </div>
      </div>
    </CustomDrawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Search, MoreFilled, Edit, Delete, Plus, View, Lock, FolderOpened, InfoFilled } from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessageBox } from 'element-plus'

import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import { useKontrakStore, type PegawaiKontrak, type KontrakKomponen } from '@/store/kontrak'
import { useJabatanStore, type Jabatan } from '@/store/jabatan'
import { useClientAreaStore, type Area } from '@/store/clientArea'
import { usePegawaiStore, type Pegawai } from '@/store/pegawai'
import { useProjectStore, type Project } from '@/store/project'

const kontrakStore = useKontrakStore()
const jabatanStore = useJabatanStore()
const clientAreaStore = useClientAreaStore()
const pegawaiStore = usePegawaiStore()
const projectStore = useProjectStore()

const { data, meta } = storeToRefs(kontrakStore)

const isLoading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const statusFilter = ref('')
const tipeFilter = ref('')
const projectFilter = ref<number | ''>('')

const isDrawerOpen = ref(false)
const drawerMode = ref<'create' | 'edit' | 'detail'>('create')
const drawerTitle = ref('Buat Kontrak Pegawai')
const saving = ref(false)

const pegawaiOptions = ref<Pegawai[]>([])
const jabatanOptions = ref<Jabatan[]>([])
const areaOptions = ref<Area[]>([])
const projectOptions = ref<Project[]>([])

const getProjectName = (id: number) => {
  const p = projectOptions.value.find((item: any) => item.id === id)
  return p ? `${p.kode_project} - ${p.nama_project}` : `Project #${id}`
}

const hasPreviousContract = ref(false)
const previousContractNo = ref('')
const hasActiveContractWarning = ref(false)
const activeContractInfo = ref<any>(null)

const formRef = ref<FormInstance>()
const form = reactive({
  id: 0,
  pegawai_id: '',
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
  project_ids: [] as number[],
  komponens: [] as KontrakKomponen[]
})

const rules: FormRules = {
  pegawai_id: [{ required: true, message: 'Pegawai wajib dipilih', trigger: 'change' }],
  no_kontrak: [{ required: true, message: 'No. kontrak wajib diisi', trigger: 'blur' }],
  terhitung_mulai_tanggal: [{ required: true, message: 'Tanggal mulai wajib diisi', trigger: 'change' }],
  jabatan_id: [{ required: true, message: 'Jabatan wajib dipilih', trigger: 'change' }],
  area_id: [{ required: true, message: 'Area penempatan wajib dipilih', trigger: 'change' }],
  tipe_kontrak: [{ required: true, message: 'Tipe kontrak wajib dipilih', trigger: 'change' }],
  status: [{ required: true, message: 'Status wajib dipilih', trigger: 'change' }]
}

const fetchData = async () => {
  isLoading.value = true
  try {
    await kontrakStore.getData({
      page: currentPage.value,
      limit: pageSize.value,
      search: searchQuery.value,
      status: statusFilter.value,
      tipe_kontrak: tipeFilter.value,
      project_id: projectFilter.value ? Number(projectFilter.value) : undefined
    })
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal memuat data kontrak')
  } finally {
    isLoading.value = false
  }
}

watch([currentPage, pageSize], () => {
  fetchData()
})

const loadOptions = async () => {
  try {
    const resPegawai = await pegawaiStore.getData({ limit: 1000 })
    pegawaiOptions.value = resPegawai.data || []
    jabatanOptions.value = await jabatanStore.getAll()
    areaOptions.value = await clientAreaStore.getAllAreas()
    projectOptions.value = await projectStore.getAllProjects()
  } catch (e) {
    console.error(e)
  }
}

const handlePegawaiChange = async (pegawaiId: string) => {
  if (!pegawaiId || drawerMode.value !== 'create') return

  try {
    const res = await kontrakStore.getLatestByPegawai(pegawaiId)

    // Cek apakah pegawai masih memiliki kontrak yang sedang berjalan (aktif)
    if (res?.has_active_contract && res?.active_contract) {
      activeContractInfo.value = res.active_contract
      hasActiveContractWarning.value = true

      const peg = pegawaiOptions.value.find(p => p.id === pegawaiId)
      const pegNama = peg?.nama || 'Pegawai yang dipilih'
      const activeNo = res.active_contract.no_kontrak

      try {
        await ElMessageBox.confirm(
          `Pegawai "${pegNama}" saat ini masih memiliki kontrak kerja aktif yang sedang berjalan (#${activeNo}).\n\n` +
          `Sistem mengharuskan kontrak yang sedang berjalan diselesaikan terlebih dahulu sebelum dapat menambahkan kontrak baru.\n\n` +
          `Apakah Anda ingin menyelesaikan & mengunci kontrak berjalan (#${activeNo}) sekarang?`,
          'Kontrak Kerja Sedang Berjalan',
          {
            confirmButtonText: 'Selesaikan Kontrak Sekarang',
            cancelButtonText: 'Batal / Pilih Pegawai Lain',
            type: 'warning',
            distinguishCancelAndClose: true,
          }
        )

        // Pengguna menyetujui penyelesaian kontrak
        await kontrakStore.lock(res.active_contract.id)
        showSuccessToast(`Kontrak #${activeNo} berhasil diselesaikan. Anda sekarang dapat melanjutkan pembuatan kontrak baru.`)
        hasActiveContractWarning.value = false
        activeContractInfo.value = null
        fetchData() // Refresh list table
      } catch (action: any) {
        // Pengguna membatalkan / memilih pegawai lain
        form.pegawai_id = ''
        hasActiveContractWarning.value = false
        activeContractInfo.value = null
        hasPreviousContract.value = false
        previousContractNo.value = ''
        return
      }
    } else {
      hasActiveContractWarning.value = false
      activeContractInfo.value = null
    }

    if (res?.has_previous_contract && res?.data) {
      hasPreviousContract.value = true
      previousContractNo.value = res.data.previous_contract_no
      
      // Auto carry-over dari kontrak sebelumnya
      form.element_tetap = Number(res.data.element_tetap) || 0
      form.element_variabel = Number(res.data.element_variabel) || 0
      form.jabatan_id = res.data.jabatan_id
      form.area_id = res.data.area_id
      form.tipe_kontrak = res.data.tipe_kontrak || 'kontrak'
      form.project_ids = [...(res.data.project_ids || [])]
      
      form.komponens = (res.data.komponens || []).map((k: any) => ({
        jenis: k.jenis,
        nama_komponen: k.nama_komponen,
        metode_hitung: k.metode_hitung,
        nilai: Number(k.nilai) || 0,
        keterangan: k.keterangan || ''
      }))
    } else {
      hasPreviousContract.value = false
      previousContractNo.value = ''
    }
  } catch (e) {
    console.error(e)
  }
}

const resolveActiveContractDirectly = async () => {
  if (!activeContractInfo.value?.id) return
  try {
    const no = activeContractInfo.value.no_kontrak
    await ElMessageBox.confirm(
      `Selesaikan dan kunci kontrak kerja #${no}? Status kontrak akan diubah menjadi tidak aktif sehingga kontrak baru dapat diterbitkan.`,
      'Konfirmasi Penyelesaian Kontrak',
      { confirmButtonText: 'Ya, Selesaikan & Kunci', cancelButtonText: 'Batal', type: 'warning' }
    )
    await kontrakStore.lock(activeContractInfo.value.id)
    showSuccessToast(`Kontrak #${no} berhasil diselesaikan. Anda dapat melanjutkan pembuatan kontrak baru.`)
    hasActiveContractWarning.value = false
    activeContractInfo.value = null
    fetchData()
  } catch (e: any) {
    if (e !== 'cancel') showErrorToast(e?.response?.data?.message || 'Gagal menyelesaikan kontrak')
  }
}

const handleJabatanChange = (jabatanId: number) => {
  // Jika pegawai belum punya kontrak sebelumnya dan komponen form masih kosong, load default jabatan
  if (!hasPreviousContract.value && form.komponens.length === 0) {
    const sel = jabatanOptions.value.find(j => j.id === jabatanId)
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

const handleTipeChange = (val: string) => {
  if (val === 'permanen') {
    form.terhitung_sampai_tanggal = null
  }
}

const addKomponen = () => {
  form.komponens.push({
    jenis: 'tunjangan',
    nama_komponen: '',
    metode_hitung: 'tetap',
    nilai: 0,
    keterangan: ''
  })
}

const removeKomponen = (idx: number) => {
  form.komponens.splice(idx, 1)
}

const calculateKomponenValue = (k: KontrakKomponen) => {
  const tetap = Number(form.element_tetap) || 0
  const variabel = Number(form.element_variabel) || 0
  const val = Number(k.nilai) || 0

  if (k.metode_hitung === 'tetap' || k.metode_hitung === 'custom') {
    return val
  } else if (k.metode_hitung === 'persentase_tetap') {
    return (val / 100) * tetap
  } else if (k.metode_hitung === 'persentase_variabel') {
    return (val / 100) * variabel
  } else if (k.metode_hitung === 'kali_hari_variabel') {
    return val * variabel
  }
  return val
}

const totalTunjangan = computed(() => {
  return form.komponens
    .filter(k => k.jenis === 'tunjangan' || k.jenis === 'bonus' || k.jenis === 'komisi')
    .reduce((acc, k) => acc + calculateKomponenValue(k), 0)
})

const totalPotongan = computed(() => {
  return form.komponens
    .filter(k => k.jenis === 'potongan')
    .reduce((acc, k) => acc + calculateKomponenValue(k), 0)
})

const estimatedTHP = computed(() => {
  const tetap = Number(form.element_tetap) || 0
  const variabel = (Number(form.element_variabel) || 0) * 22
  return Math.max(0, tetap + variabel + totalTunjangan.value - totalPotongan.value)
})

const openCreateDrawer = () => {
  drawerMode.value = 'create'
  drawerTitle.value = 'Buat Kontrak Pegawai Baru'
  resetForm()
  loadOptions()
  isDrawerOpen.value = true
}

const handleAction = async (cmd: string, row: PegawaiKontrak) => {
  if (cmd === 'detail' || cmd === 'edit') {
    drawerMode.value = cmd as 'detail' | 'edit'
    drawerTitle.value = cmd === 'detail' ? 'Detail Kontrak Pegawai' : 'Edit Kontrak Pegawai'
    loadOptions()
    
    form.id = row.id
    form.pegawai_id = row.pegawai_id
    form.no_kontrak = row.no_kontrak
    form.terhitung_mulai_tanggal = row.terhitung_mulai_tanggal
    form.terhitung_sampai_tanggal = row.terhitung_sampai_tanggal || null
    form.jabatan_id = row.jabatan_id
    form.area_id = row.area_id
    form.tipe_kontrak = row.tipe_kontrak
    form.status = row.status
    form.element_variabel = Number(row.element_variabel) || 0
    form.element_tetap = Number(row.element_tetap) || 0
    form.keterangan = row.keterangan || ''
    form.project_ids = (row.projects || []).map((p: any) => p.id)
    form.komponens = (row.komponens || []).map(k => ({
      jenis: k.jenis,
      nama_komponen: k.nama_komponen,
      metode_hitung: k.metode_hitung,
      nilai: Number(k.nilai) || 0,
      keterangan: k.keterangan || ''
    }))

    hasPreviousContract.value = false
    isDrawerOpen.value = true
  } else if (cmd === 'lock') {
    try {
      await ElMessageBox.confirm(
        `Selesaikan dan kunci kontrak "${row.no_kontrak}" untuk pegawai ${row.pegawai?.nama}? Setelah dikunci, status kontrak akan menjadi tidak aktif dan tidak dapat diubah lagi.`,
        'Konfirmasi Kunci Kontrak',
        { confirmButtonText: 'Ya, Selesaikan & Kunci', cancelButtonText: 'Batal', type: 'warning' }
      )
      await kontrakStore.lock(row.id)
      showSuccessToast('Kontrak berhasil diselesaikan dan dikunci')
      fetchData()
    } catch (e: any) {
      if (e !== 'cancel') showErrorToast(e?.response?.data?.message || 'Gagal mengunci kontrak')
    }
  } else if (cmd === 'delete') {
    try {
      await ElMessageBox.confirm(
        `Yakin ingin menghapus kontrak "${row.no_kontrak}"?`,
        'Konfirmasi Hapus',
        { confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', type: 'warning' }
      )
      await kontrakStore.destroy(row.id)
      showSuccessToast('Kontrak berhasil dihapus')
      fetchData()
    } catch (e: any) {
      if (e !== 'cancel') showErrorToast(e?.response?.data?.message || 'Gagal menghapus kontrak')
    }
  }
}

const resetForm = () => {
  form.id = 0
  form.pegawai_id = ''
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
  form.project_ids = []
  form.komponens = []
  hasPreviousContract.value = false
  previousContractNo.value = ''
  hasActiveContractWarning.value = false
  activeContractInfo.value = null
  formRef.value?.resetFields()
}

const handleSubmit = async () => {
  if (!formRef.value) return
  if (drawerMode.value === 'create' && hasActiveContractWarning.value) {
    showErrorToast('Silahkan selesaikan kontrak kerja yang sedang berjalan terlebih dahulu.')
    return
  }
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    saving.value = true
    try {
      const payload = {
        pegawai_id: form.pegawai_id,
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
        project_ids: form.project_ids,
        komponens: form.komponens
      }

      if (drawerMode.value === 'create') {
        await kontrakStore.create(payload)
        showSuccessToast('Kontrak pegawai berhasil dibuat')
      } else {
        await kontrakStore.update(form.id, payload)
        showSuccessToast('Kontrak pegawai berhasil diperbarui')
      }

      isDrawerOpen.value = false
      fetchData()
    } catch (e: any) {
      showErrorToast(e?.response?.data?.message || 'Gagal menyimpan kontrak pegawai')
    } finally {
      saving.value = false
    }
  })
}

const formatRupiah = (val: number | string) => {
  const num = Number(val) || 0
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num)
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatTipeKontrak = (tipe: string) => {
  if (tipe === 'probation') return 'Probation'
  if (tipe === 'permanen') return 'PKWTT (Tetap)'
  return 'PKWT (Kontrak)'
}

const getTipeBadgeType = (tipe: string) => {
  if (tipe === 'probation') return 'warning'
  if (tipe === 'permanen') return 'success'
  return 'primary'
}

onMounted(() => {
  fetchData()
  loadOptions()
})
</script>

<style scoped>
.page-container {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.subtitle {
  color: #64748b;
  margin-top: 4px;
  font-size: 14px;
}

.filter-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  gap: 16px;
}

.filter-actions {
  display: flex;
  gap: 12px;
}

.search-input {
  max-width: 340px;
}

.kontrak-no-wrapper {
  display: flex;
  flex-direction: column;
}

.no-text {
  font-weight: 600;
  color: #1e293b;
}

.pegawai-cell {
  display: flex;
  flex-direction: column;
}

.pegawai-name {
  font-weight: 600;
  color: #0f172a;
}

.pegawai-sub {
  font-size: 12px;
  color: #64748b;
}

.unit-sub {
  font-size: 11px;
  font-weight: 400;
  color: #64748b;
}

.admin-drawer-content {
  padding: 8px 16px 24px 16px;
}

.field-hint {
  font-size: 12px;
  color: #64748b;
  margin-top: 4px;
}

.drawer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 32px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.komponen-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px 14px;
  margin-bottom: 12px;
}

.komponen-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.calc-badge {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
  padding-top: 6px;
  border-top: 1px dashed #cbd5e1;
}

.calc-label {
  font-size: 12px;
  color: #64748b;
}

.calc-val {
  font-size: 13px;
  font-weight: 600;
  color: #047857;
}

.slip-summary-card {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 14px 16px;
}

.summary-header {
  font-weight: 600;
  font-size: 14px;
  color: #0f172a;
  margin-bottom: 10px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #334155;
  margin-bottom: 6px;
}

.summary-row.text-success {
  color: #15803d;
  font-weight: 500;
}

.summary-row.text-danger {
  color: #b91c1c;
  font-weight: 500;
}

.summary-divider {
  height: 1px;
  background: #cbd5e1;
  margin: 10px 0;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 700;
  font-size: 14px;
  color: #0f172a;
}

.total-amount {
  font-size: 16px;
  color: #1d4ed8;
}
</style>
