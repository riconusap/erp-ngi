<template>
  <div class="page-container">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Task Management</AppHeading>
        </div>
        <p class="subtitle">
          Kelola penugasan proyek, tugas reguler dan rutin, serta evaluasi performa kerja pegawai.
        </p>
      </div>
      <div class="header-right">
        <AppButton variant="primary" @click="openCreateTask">
          <template #icon>
            <el-icon><Plus /></el-icon>
          </template>
          Tambah Task Baru
        </AppButton>
      </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-primary-soft">
          <el-icon :size="20"><Tickets /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Total Task</span>
          <span class="stat-value">{{ summary.total }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-info-soft">
          <el-icon :size="20"><Loading /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Sedang Dikerjakan</span>
          <span class="stat-value text-primary">{{ summary.in_progress + summary.todo }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-warning-soft">
          <el-icon :size="20"><View /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Menunggu Review</span>
          <span class="stat-value text-warning">{{ summary.in_review }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-success-soft">
          <el-icon :size="20"><CircleCheckFilled /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Selesai (Done)</span>
          <span class="stat-value text-success">{{ summary.done }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-danger-soft">
          <el-icon :size="20"><Clock /></el-icon>
        </div>
        <div class="stat-content">
          <span class="stat-label">Terlambat</span>
          <span class="stat-value text-danger">{{ summary.terlambat }}</span>
        </div>
      </div>
    </div>

    <!-- Tab Switcher -->
    <div class="tab-filter-wrapper">
      <el-radio-group v-model="activeTab" size="large" @change="handleTabChange">
        <el-radio-button value="all">Semua Task</el-radio-button>
        <el-radio-button value="my_tasks">Tugasku (Assigned)</el-radio-button>
        <el-radio-button value="assigned_by_me">Dibuat Olehku</el-radio-button>
      </el-radio-group>
    </div>

    <!-- Filter Bar -->
    <div class="filter-section">
      <el-input
        v-model="searchQuery"
        placeholder="Cari judul, deskripsi, project, atau pegawai..."
        class="search-input"
        clearable
        @clear="fetchTasks"
        @keyup.enter="fetchTasks"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>

      <div class="filter-actions">
        <!-- Filter Project -->
        <el-select
          v-model="projectFilter"
          placeholder="Pilih Project"
          clearable
          filterable
          class="filter-select"
          style="width: 220px;"
          @change="fetchTasks"
        >
          <el-option label="Semua Project" value="" />
          <el-option
            v-for="p in projectOptions"
            :key="p.id"
            :label="`${p.kode_project} - ${p.nama_project}`"
            :value="p.id"
          />
        </el-select>

        <!-- Filter Status -->
        <el-select
          v-model="statusFilter"
          placeholder="Status"
          clearable
          class="filter-select"
          style="width: 150px;"
          @change="fetchTasks"
        >
          <el-option label="Semua Status" value="" />
          <el-option label="Todo" value="Todo" />
          <el-option label="In Progress" value="In Progress" />
          <el-option label="In Review" value="In Review" />
          <el-option label="Done" value="Done" />
          <el-option label="On Hold" value="On Hold" />
          <el-option label="Reopened" value="Reopened" />
          <el-option label="Reassigned" value="Reassigned" />
          <el-option label="Rejected" value="Rejected" />
          <el-option label="Canceled" value="Canceled" />
        </el-select>

        <!-- Filter Prioritas -->
        <el-select
          v-model="priorityFilter"
          placeholder="Prioritas"
          clearable
          class="filter-select"
          style="width: 130px;"
          @change="fetchTasks"
        >
          <el-option label="Semua Prioritas" value="" />
          <el-option label="High" value="High" />
          <el-option label="Medium" value="Medium" />
          <el-option label="Low" value="Low" />
        </el-select>

        <!-- Filter Tipe -->
        <el-select
          v-model="typeFilter"
          placeholder="Tipe Tugas"
          clearable
          class="filter-select"
          style="width: 140px;"
          @change="fetchTasks"
        >
          <el-option label="Semua Tipe" value="" />
          <el-option label="Reguler" value="Reguler" />
          <el-option label="Rutin" value="Rutin" />
        </el-select>
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :data="tasks"
      :loading="isLoading"
      :total="taskMeta.total"
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      empty-title="Belum ada data task"
      empty-subtitle="Silakan buat tugas baru atau sesuaikan filter pencarian"
      :empty-image="EmptyStateImage"
    >
      <el-table-column label="Judul Task" min-width="240">
        <template #default="{ row }">
          <div class="task-title-cell">
            <span class="task-name-link" @click="openTaskDetail(row)">{{ row.judul }}</span>
            <span v-if="row.deskripsi" class="task-desc-sub text-truncate">{{ row.deskripsi }}</span>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Project" min-width="200">
        <template #default="{ row }">
          <div v-if="row.project" class="project-info-cell">
            <span class="project-name">{{ row.project.nama_project }}</span>
            <span class="project-code">{{ row.project.kode_project }}</span>
          </div>
          <span v-else class="text-muted">-</span>
        </template>
      </el-table-column>

      <el-table-column label="Ditugaskan Ke" min-width="190">
        <template #default="{ row }">
          <div v-if="row.assignedTo" class="assignee-cell">
            <el-icon class="assignee-icon"><User /></el-icon>
            <div class="assignee-info">
              <span class="assignee-name">{{ row.assignedTo.nama }}</span>
              <span class="assignee-sub">NIK: {{ row.assignedTo.nik }}</span>
            </div>
          </div>
          <span v-else class="text-muted">-</span>
        </template>
      </el-table-column>

      <el-table-column label="Tipe" width="130" align="center">
        <template #default="{ row }">
          <el-tag
            size="small"
            :type="row.tipe === 'Rutin' ? 'warning' : 'info'"
            effect="plain"
            class="type-pill"
          >
            <el-icon class="mr-1">
              <Refresh v-if="row.tipe === 'Rutin'" />
              <Document v-else />
            </el-icon>
            {{ row.tipe }}
          </el-tag>
          <div v-if="row.tipe === 'Rutin' && row.frekuensi_rutin" class="recurring-hint">
            {{ row.frekuensi_rutin }}
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Prioritas" width="110" align="center">
        <template #default="{ row }">
          <el-tag :type="getPriorityTagType(row.prioritas)" size="small">
            {{ row.prioritas }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Deadline & Status" min-width="200">
        <template #default="{ row }">
          <div class="deadline-cell">
            <span class="deadline-time">
              <el-icon class="mr-1"><Calendar /></el-icon>
              {{ formatDateTime(row.deadline) }}
            </span>
            <el-tag
              size="small"
              :type="getKeterlambatanTagType(row.status_keterlambatan)"
              effect="light"
              class="keterlambatan-badge"
            >
              {{ row.status_keterlambatan }}
            </el-tag>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Nilai" width="140" align="center">
        <template #default="{ row }">
          <div v-if="row.nilai" class="rating-cell">
            <el-rate
              :model-value="row.nilai"
              disabled
              text-color="#F59E0B"
            />
          </div>
          <span v-else class="text-muted font-11">Belum Dinilai</span>
        </template>
      </el-table-column>

      <el-table-column label="Status" width="130" align="center">
        <template #default="{ row }">
          <el-tag :type="getStatusTagType(row.status)" size="small">
            {{ row.status }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column label="Aksi" width="80" fixed="right" align="center">
        <template #default="{ row }">
          <el-dropdown trigger="click" @command="(cmd: string) => handleActionCommand(cmd, row)">
            <el-button type="info" text circle>
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="detail">
                  <el-icon><View /></el-icon> Detail Task
                </el-dropdown-item>
                <el-dropdown-item command="status">
                  <el-icon><CircleCheckFilled /></el-icon> Ubah Status
                </el-dropdown-item>
                <el-dropdown-item command="rate">
                  <el-icon><Star /></el-icon> Beri Penilaian
                </el-dropdown-item>
                <el-dropdown-item command="edit">
                  <el-icon><Edit /></el-icon> Edit Data
                </el-dropdown-item>
                <el-dropdown-item command="delete" class="text-danger">
                  <el-icon><Delete /></el-icon> Hapus Task
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </DataTable>

    <!-- Drawer Create / Edit Task -->
    <CustomDrawer
      v-model="drawerVisible"
      :title="drawerMode === 'create' ? 'Tambah Task Baru' : 'Edit Data Task'"
      default-size="640px"
    >
      <div class="admin-drawer-content">
        <el-form
          ref="formRef"
          :model="form"
          :rules="formRules"
          label-position="top"
          class="task-form"
          @submit.prevent="handleSubmitForm"
        >
          <!-- Section 1: Project & Pegawai -->
          <div class="form-section-card">
            <h4 class="form-section-title">
              <el-icon class="mr-1 text-primary"><FolderOpened /></el-icon> Proyek & Penugasan
            </h4>
            <el-form-item label="Project Terkait" prop="project_id">
              <el-select
                v-model="form.project_id"
                placeholder="Pilih project yang terhubung"
                filterable
                style="width: 100%;"
                size="large"
              >
                <el-option
                  v-for="p in projectOptions"
                  :key="p.id"
                  :label="`${p.kode_project} - ${p.nama_project}`"
                  :value="p.id"
                />
              </el-select>
              <div class="field-hint-box">
                <el-icon class="hint-icon"><InfoFilled /></el-icon>
                <span>Pegawai yang ditugaskan akan otomatis terhubung ke proyek ini.</span>
              </div>
            </el-form-item>

            <el-form-item label="Pegawai yang Ditugaskan" prop="assigned_to">
              <el-select
                v-model="form.assigned_to"
                placeholder="Pilih pegawai pelaksana tugas"
                filterable
                style="width: 100%;"
                size="large"
              >
                <el-option
                  v-for="peg in pegawaiOptions"
                  :key="peg.id"
                  :label="`${peg.nama} (${peg.nik})`"
                  :value="peg.id"
                />
              </el-select>
            </el-form-item>
          </div>

          <!-- Section 2: Identitas Task -->
          <div class="form-section-card">
            <h4 class="form-section-title">
              <el-icon class="mr-1 text-primary"><Tickets /></el-icon> Rincian Pekerjaan
            </h4>
            <el-form-item label="Judul Task" prop="judul">
              <el-input
                v-model="form.judul"
                placeholder="Contoh: Pemeriksaan Rack Server & Backup Harian"
                size="large"
              />
            </el-form-item>

            <el-form-item label="Deskripsi Task" prop="deskripsi">
              <el-input
                v-model="form.deskripsi"
                type="textarea"
                :rows="3"
                placeholder="Jelaskan detail instruksi pekerjaan, SOP, atau kriteria kelulusan..."
              />
            </el-form-item>
          </div>

          <!-- Section 3: Tipe Tugas & Pengulangan -->
          <div class="form-section-card">
            <h4 class="form-section-title">
              <el-icon class="mr-1 text-primary"><Refresh /></el-icon> Tipe Tugas & Periode
            </h4>
            <el-form-item label="Tipe Tugas" prop="tipe">
              <el-radio-group v-model="form.tipe" size="large">
                <el-radio value="Reguler">Tugas Reguler (Sekali Selesai)</el-radio>
                <el-radio value="Rutin">Tugas Rutin (Berulang)</el-radio>
              </el-radio-group>
            </el-form-item>

            <template v-if="form.tipe === 'Rutin'">
              <div class="form-grid-2">
                <el-form-item label="Frekuensi Pengulangan" prop="frekuensi_rutin">
                  <el-select v-model="form.frekuensi_rutin" style="width: 100%;" size="large">
                    <el-option label="Harian" value="Harian" />
                    <el-option label="Mingguan" value="Mingguan" />
                    <el-option label="Bulanan" value="Bulanan" />
                  </el-select>
                </el-form-item>

                <el-form-item label="Mulai Berlaku" prop="tanggal_mulai_rutin">
                  <el-date-picker
                    v-model="form.tanggal_mulai_rutin"
                    type="date"
                    placeholder="Pilih tanggal mulai"
                    format="YYYY-MM-DD"
                    value-format="YYYY-MM-DD"
                    style="width: 100%;"
                    size="large"
                  />
                </el-form-item>
              </div>

              <el-form-item label="Akhir Berlaku (Opsional)" prop="tanggal_selesai_rutin">
                <el-date-picker
                  v-model="form.tanggal_selesai_rutin"
                  type="date"
                  placeholder="Kosongkan jika berlaku seterusnya"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  style="width: 100%;"
                  size="large"
                />
              </el-form-item>
            </template>
          </div>

          <!-- Section 4: Prioritas, Deadline & Status -->
          <div class="form-section-card">
            <h4 class="form-section-title">
              <el-icon class="mr-1 text-primary"><Calendar /></el-icon> Prioritas & Tenggat Waktu
            </h4>
            <div class="form-grid-2">
              <el-form-item label="Prioritas" prop="prioritas">
                <el-select v-model="form.prioritas" style="width: 100%;" size="large">
                  <el-option label="High" value="High" />
                  <el-option label="Medium" value="Medium" />
                  <el-option label="Low" value="Low" />
                </el-select>
              </el-form-item>

              <el-form-item label="Status Awal" prop="status">
                <el-select v-model="form.status" style="width: 100%;" size="large">
                  <el-option label="Todo" value="Todo" />
                  <el-option label="In Progress" value="In Progress" />
                  <el-option label="In Review" value="In Review" />
                  <el-option label="Done" value="Done" />
                  <el-option label="On Hold" value="On Hold" />
                  <el-option label="Reopened" value="Reopened" />
                  <el-option label="Reassigned" value="Reassigned" />
                  <el-option label="Rejected" value="Rejected" />
                  <el-option label="Canceled" value="Canceled" />
                </el-select>
              </el-form-item>
            </div>

            <el-form-item label="Deadline Pengerjaan" prop="deadline">
              <el-date-picker
                v-model="form.deadline"
                type="datetime"
                placeholder="Pilih tanggal & jam batas waktu"
                format="YYYY-MM-DD HH:mm:ss"
                value-format="YYYY-MM-DD HH:mm:ss"
                style="width: 100%;"
                size="large"
              />
            </el-form-item>
          </div>

          <!-- Actions -->
          <div class="drawer-footer-actions">
            <el-button @click="drawerVisible = false">Batal</el-button>
            <AppButton variant="primary" :loading="isSubmitting" @click="handleSubmitForm">
              {{ drawerMode === 'create' ? 'Simpan Task' : 'Perbarui Task' }}
            </AppButton>
          </div>
        </el-form>
      </div>
    </CustomDrawer>

    <!-- Drawer Detail Task -->
    <CustomDrawer
      v-model="detailVisible"
      :title="selectedTask?.judul ? `Detail: ${selectedTask.judul}` : 'Detail Task'"
      default-size="640px"
    >
      <div v-if="selectedTask" class="admin-drawer-content detail-task-wrapper">
        <div class="detail-header-card">
          <div class="detail-header-main">
            <div class="task-avatar-badge">
              <el-icon :size="26"><Tickets /></el-icon>
            </div>
            <div class="detail-title-col">
              <div class="detail-title-row">
                <h3 class="detail-name">{{ selectedTask.judul }}</h3>
                <el-tag :type="getStatusTagType(selectedTask.status)" size="small">
                  {{ selectedTask.status }}
                </el-tag>
              </div>
              <div class="detail-sub-row">
                <span class="detail-code">Tipe: {{ selectedTask.tipe }}</span>
                <span class="dot-separator">•</span>
                <span class="priority-indicator">
                  Prioritas: {{ selectedTask.prioritas }}
                </span>
              </div>
            </div>
          </div>
          <div class="detail-quick-actions">
            <el-button size="small" @click="openQuickStatusModal(selectedTask)">
              <el-icon class="mr-1"><CircleCheckFilled /></el-icon> Ubah Status
            </el-button>
            <el-button size="small" @click="openRatingModal(selectedTask)">
              <el-icon class="mr-1"><Star /></el-icon> Nilai Bintang
            </el-button>
          </div>
        </div>

        <div class="detail-body-grid">
          <!-- Card Project & Pegawai -->
          <div class="info-block">
            <h4 class="info-block-title">
              <el-icon class="mr-1 text-primary"><FolderOpened /></el-icon> Project & Penugasan
            </h4>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Project</span>
                <span class="info-value font-semibold">
                  {{ selectedTask.project?.nama_project || '-' }} ({{ selectedTask.project?.kode_project || '-' }})
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Pelaksana Tugas</span>
                <span class="info-value">
                  {{ selectedTask.assignedTo?.nama || '-' }} (NIK: {{ selectedTask.assignedTo?.nik || '-' }})
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Dibuat Oleh</span>
                <span class="info-value text-muted">
                  {{ selectedTask.creator?.username || 'Sistem' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Card Waktu & Evaluasi -->
          <div class="info-block">
            <h4 class="info-block-title">
              <el-icon class="mr-1 text-primary"><Calendar /></el-icon> Waktu & Evaluasi
            </h4>
            <div class="info-list">
              <div class="info-item">
                <span class="info-label">Deadline</span>
                <span class="info-value">
                  {{ formatDateTime(selectedTask.deadline) }}
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Ketepatan Waktu</span>
                <span class="info-value">
                  <el-tag size="small" :type="getKeterlambatanTagType(selectedTask.status_keterlambatan)">
                    {{ selectedTask.status_keterlambatan }}
                  </el-tag>
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">Rating Evaluasi</span>
                <span class="info-value">
                  <el-rate
                    v-if="selectedTask.nilai"
                    :model-value="selectedTask.nilai"
                    disabled
                    text-color="#F59E0B"
                  />
                  <span v-else class="text-muted font-11">Belum ada evaluasi bintang</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Deskripsi -->
          <div class="info-block full-width">
            <h4 class="info-block-title">
              <el-icon class="mr-1 text-primary"><Document /></el-icon> Deskripsi Instruksi
            </h4>
            <p class="description-text">
              {{ selectedTask.deskripsi || 'Tidak ada deskripsi tambahan untuk tugas ini.' }}
            </p>
          </div>

          <!-- Catatan Evaluasi Review -->
          <div v-if="selectedTask.catatan_review" class="info-block full-width">
            <h4 class="info-block-title">
              <el-icon class="mr-1 text-primary"><ChatLineSquare /></el-icon> Catatan Evaluasi & Umpan Balik
            </h4>
            <p class="description-text">
              {{ selectedTask.catatan_review }}
            </p>
          </div>
        </div>
      </div>
    </CustomDrawer>

    <!-- Dialog Quick Status & Rating -->
    <el-dialog
      v-model="statusModalVisible"
      title="Pembaruan Status & Evaluasi Task"
      width="480px"
    >
      <el-form label-position="top">
        <el-form-item label="Status Pengerjaan">
          <el-select v-model="quickStatusForm.status" style="width: 100%;" size="large">
            <el-option label="Todo" value="Todo" />
            <el-option label="In Progress" value="In Progress" />
            <el-option label="In Review" value="In Review" />
            <el-option label="Done" value="Done" />
            <el-option label="On Hold" value="On Hold" />
            <el-option label="Reopened" value="Reopened" />
            <el-option label="Reassigned" value="Reassigned" />
            <el-option label="Rejected" value="Rejected" />
            <el-option label="Canceled" value="Canceled" />
          </el-select>
        </el-form-item>

        <el-form-item label="Evaluasi Nilai Bintang (1 - 5 Bintang)">
          <div style="display: flex; align-items: center; gap: 12px;">
            <el-rate v-model="quickStatusForm.nilai" size="large" />
            <span v-if="quickStatusForm.nilai" class="text-muted font-12">{{ quickStatusForm.nilai }} Bintang</span>
          </div>
        </el-form-item>

        <el-form-item label="Catatan Evaluasi / Catatan Review">
          <el-input
            v-model="quickStatusForm.catatan_review"
            type="textarea"
            :rows="3"
            placeholder="Berikan catatan umpan balik mengenai hasil pekerjaan..."
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="statusModalVisible = false">Batal</el-button>
        <AppButton variant="primary" :loading="isSubmitting" @click="handleSaveQuickStatus">
          Simpan Pembaruan
        </AppButton>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import EmptyStateImage from '@/assets/empty-state.svg'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import DataTable from '@/components/common/DataTable.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import { useTaskStore, type TaskItem, type TaskStatus, type TaskPriority, type TaskType, type RecurringFrequency } from '@/store/task'
import { useProjectStore } from '@/store/project'
import ApiService from '@/core/services/ApiService'

const route = useRoute()
const taskStore = useTaskStore()
const projectStore = useProjectStore()
const { tasks, taskMeta, taskSummary } = storeToRefs(taskStore)

// State
const isLoading = ref(false)
const isSubmitting = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const activeTab = ref('all')
const projectFilter = ref<number | string>('')
const statusFilter = ref('')
const priorityFilter = ref('')
const typeFilter = ref('')

const projectOptions = ref<any[]>([])
const pegawaiOptions = ref<any[]>([])

const summary = ref({
  total: 0,
  todo: 0,
  in_progress: 0,
  in_review: 0,
  done: 0,
  terlambat: 0,
  rutin: 0,
  reguler: 0,
})

// Drawer State
const drawerVisible = ref(false)
const detailVisible = ref(false)
const statusModalVisible = ref(false)
const drawerMode = ref<'create' | 'edit'>('create')
const selectedTaskId = ref<number | null>(null)
const selectedTask = ref<TaskItem | null>(null)
const formRef = ref<FormInstance>()

// Form State
const form = reactive({
  project_id: undefined as number | undefined,
  assigned_to: '' as string,
  judul: '',
  deskripsi: '',
  status: 'Todo' as TaskStatus,
  prioritas: 'Medium' as TaskPriority,
  tipe: 'Reguler' as TaskType,
  deadline: '' as string,
  frekuensi_rutin: 'Harian' as RecurringFrequency,
  tanggal_mulai_rutin: '',
  tanggal_selesai_rutin: '',
})

const quickStatusForm = reactive({
  status: 'Todo' as TaskStatus,
  nilai: 0 as number,
  catatan_review: '',
})

// Rules
const formRules: FormRules = {
  project_id: [{ required: true, message: 'Project wajib dipilih', trigger: 'change' }],
  assigned_to: [{ required: true, message: 'Pegawai yang ditugaskan wajib dipilih', trigger: 'change' }],
  judul: [
    { required: true, message: 'Judul task wajib diisi', trigger: 'blur' },
    { max: 255, message: 'Maksimal 255 karakter', trigger: 'blur' },
  ],
  prioritas: [{ required: true, message: 'Prioritas wajib dipilih', trigger: 'change' }],
  tipe: [{ required: true, message: 'Tipe tugas wajib dipilih', trigger: 'change' }],
}

// Helpers
const getStatusTagType = (status: TaskStatus) => {
  switch (status) {
    case 'Todo': return 'info'
    case 'In Progress': return 'primary'
    case 'In Review': return 'warning'
    case 'Done': return 'success'
    case 'On Hold': return 'info'
    case 'Reopened': return 'primary'
    case 'Reassigned': return 'warning'
    case 'Rejected': return 'danger'
    case 'Canceled': return 'danger'
    default: return 'info'
  }
}

const getPriorityTagType = (prioritas: TaskPriority) => {
  switch (prioritas) {
    case 'High': return 'danger'
    case 'Medium': return 'warning'
    case 'Low': return 'info'
    default: return 'info'
  }
}

const getKeterlambatanTagType = (statusKeterlambatan?: string) => {
  switch (statusKeterlambatan) {
    case 'Tepat Waktu': return 'success'
    case 'Mendekati Deadline': return 'warning'
    case 'Terlambat': return 'danger'
    default: return 'info'
  }
}

const formatDateTime = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return new Intl.DateTimeFormat('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(d)
  } catch {
    return dateStr
  }
}

// Data Fetching
const loadDropdowns = async () => {
  try {
    const [prjData, pegRes] = await Promise.all([
      projectStore.getAllProjects(),
      ApiService.get('pegawai/all'),
    ])
    projectOptions.value = prjData || []
    pegawaiOptions.value = pegRes.data?.data || []
  } catch (err) {
    console.error('Failed to load dropdown options', err)
  }
}

const fetchTasks = async () => {
  isLoading.value = true
  try {
    await taskStore.getTasks({
      tab: activeTab.value,
      search: searchQuery.value,
      project_id: projectFilter.value || undefined,
      status: statusFilter.value || undefined,
      prioritas: priorityFilter.value || undefined,
      tipe: typeFilter.value || undefined,
      page: currentPage.value,
      limit: pageSize.value,
    })

    const summaryData = await taskStore.getTaskSummary({
      tab: activeTab.value,
      project_id: projectFilter.value || undefined,
    })
    summary.value = summaryData
  } catch (err: any) {
    showErrorToast(err?.response?.data?.message || 'Gagal memuat data task')
  } finally {
    isLoading.value = false
  }
}

const handleTabChange = () => {
  currentPage.value = 1
  fetchTasks()
}

// Actions
const openCreateTask = () => {
  drawerMode.value = 'create'
  selectedTaskId.value = null
  form.project_id = undefined
  form.assigned_to = ''
  form.judul = ''
  form.deskripsi = ''
  form.status = 'Todo'
  form.prioritas = 'Medium'
  form.tipe = 'Reguler'
  form.deadline = ''
  form.frekuensi_rutin = 'Harian'
  form.tanggal_mulai_rutin = ''
  form.tanggal_selesai_rutin = ''
  drawerVisible.value = true
}

const openEditTask = (row: TaskItem) => {
  drawerMode.value = 'edit'
  selectedTaskId.value = row.id
  form.project_id = row.project_id
  form.assigned_to = row.assigned_to
  form.judul = row.judul
  form.deskripsi = row.deskripsi || ''
  form.status = row.status
  form.prioritas = row.prioritas
  form.tipe = row.tipe
  form.deadline = row.deadline || ''
  form.frekuensi_rutin = row.frekuensi_rutin || 'Harian'
  form.tanggal_mulai_rutin = row.tanggal_mulai_rutin || ''
  form.tanggal_selesai_rutin = row.tanggal_selesai_rutin || ''
  drawerVisible.value = true
}

const openTaskDetail = async (row: TaskItem) => {
  selectedTask.value = row
  detailVisible.value = true
  try {
    const detail = await taskStore.getTask(row.id)
    if (detail) selectedTask.value = detail
  } catch (err) {
    console.error('Failed to load task detail', err)
  }
}

const openQuickStatusModal = (row: TaskItem) => {
  selectedTaskId.value = row.id
  quickStatusForm.status = row.status
  quickStatusForm.nilai = row.nilai || 0
  quickStatusForm.catatan_review = row.catatan_review || ''
  statusModalVisible.value = true
}

const openRatingModal = (row: TaskItem) => {
  openQuickStatusModal(row)
}

const handleActionCommand = (cmd: string, row: TaskItem) => {
  if (cmd === 'detail') openTaskDetail(row)
  else if (cmd === 'status') openQuickStatusModal(row)
  else if (cmd === 'rate') openRatingModal(row)
  else if (cmd === 'edit') openEditTask(row)
  else if (cmd === 'delete') handleDeleteTask(row)
}

const handleSubmitForm = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (valid) {
      isSubmitting.value = true
      try {
        const payload: any = {
          project_id: form.project_id,
          assigned_to: form.assigned_to,
          judul: form.judul,
          deskripsi: form.deskripsi || null,
          status: form.status,
          prioritas: form.prioritas,
          tipe: form.tipe,
          deadline: form.deadline || null,
        }

        if (form.tipe === 'Rutin') {
          payload.frekuensi_rutin = form.frekuensi_rutin
          payload.tanggal_mulai_rutin = form.tanggal_mulai_rutin || null
          payload.tanggal_selesai_rutin = form.tanggal_selesai_rutin || null
        }

        if (drawerMode.value === 'create') {
          await taskStore.createTask(payload)
          showSuccessToast('Task baru berhasil dibuat & pegawai otomatis terhubung')
        } else if (selectedTaskId.value) {
          await taskStore.updateTask(selectedTaskId.value, payload)
          showSuccessToast('Data task berhasil diperbarui')
        }

        drawerVisible.value = false
        fetchTasks()
      } catch (err: any) {
        showErrorToast(err?.response?.data?.message || 'Gagal menyimpan data task')
      } finally {
        isSubmitting.value = false
      }
    }
  })
}

const handleSaveQuickStatus = async () => {
  if (!selectedTaskId.value) return
  isSubmitting.value = true
  try {
    await taskStore.updateTaskStatus(
      selectedTaskId.value,
      quickStatusForm.status,
      quickStatusForm.catatan_review || undefined,
      quickStatusForm.nilai || undefined
    )
    showSuccessToast('Status & evaluasi task berhasil disimpan')
    statusModalVisible.value = false
    if (selectedTask.value && selectedTask.value.id === selectedTaskId.value) {
      const updated = await taskStore.getTask(selectedTaskId.value)
      if (updated) selectedTask.value = updated
    }
    fetchTasks()
  } catch (err: any) {
    showErrorToast(err?.response?.data?.message || 'Gagal memperbarui status task')
  } finally {
    isSubmitting.value = false
  }
}

const handleDeleteTask = (row: TaskItem) => {
  ElMessageBox.confirm(
    `Apakah Anda yakin ingin menghapus task "${row.judul}"?`,
    'Konfirmasi Hapus',
    {
      confirmButtonText: 'Hapus Task',
      cancelButtonText: 'Batal',
      type: 'warning',
      confirmButtonClass: 'el-button--danger',
    }
  ).then(async () => {
    try {
      await taskStore.deleteTask(row.id)
      showSuccessToast('Task berhasil dihapus')
      if (selectedTask.value?.id === row.id) detailVisible.value = false
      fetchTasks()
    } catch (err: any) {
      showErrorToast(err?.response?.data?.message || 'Gagal menghapus task')
    }
  }).catch(() => {})
}

const handleRouteHighlight = async () => {
  const taskId = route.query.highlight_task ? Number(route.query.highlight_task) : null
  if (taskId && !isNaN(taskId)) {
    try {
      const detail = await taskStore.getTask(taskId)
      if (detail) {
        selectedTask.value = detail
        detailVisible.value = true
      }
    } catch (err) {
      console.error('Failed to open highlighted task:', err)
    }
  }
}

watch(
  () => route.query.highlight_task,
  () => {
    handleRouteHighlight()
  }
)

watch([currentPage, pageSize], () => {
  fetchTasks()
})

onMounted(async () => {
  await loadDropdowns()
  await fetchTasks()
  await handleRouteHighlight()
})
</script>

<style scoped lang="scss">
.page-container {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;

  .header-left {
    .title-wrapper {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .subtitle {
      margin: 4px 0 0;
      color: #64748B;
      font-size: 14px;
    }
  }
}

/* Stat Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;

  .stat-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 16px;

    .stat-icon-wrapper {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;

      &.bg-primary-soft { background: #E6F4F2; color: #14746F; }
      &.bg-info-soft { background: #F0F9FF; color: #0284C7; }
      &.bg-warning-soft { background: #FEF3C7; color: #D97706; }
      &.bg-success-soft { background: #DCFCE7; color: #16A34A; }
      &.bg-danger-soft { background: #FEE2E2; color: #DC2626; }
    }

    .stat-content {
      display: flex;
      flex-direction: column;

      .stat-label {
        font-size: 12px;
        color: #64748B;
        font-weight: 500;
      }
      .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #1E293B;

        &.text-primary { color: #14746F; }
        &.text-warning { color: #D97706; }
        &.text-success { color: #16A34A; }
        &.text-danger { color: #DC2626; }
      }
    }
  }
}

/* Tab Switcher */
.tab-filter-wrapper {
  margin-bottom: 20px;
}

/* Filter Toolbar */
.filter-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;

  .search-input {
    width: 320px;
  }

  .filter-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }
}

/* Table Cells */
.task-title-cell {
  display: flex;
  flex-direction: column;
  gap: 3px;

  .task-name-link {
    font-weight: 600;
    color: #14746F;
    cursor: pointer;
    font-size: 14px;
    transition: color 0.15s ease;

    &:hover {
      color: #0F5B57;
      text-decoration: underline;
    }
  }

  .task-desc-sub {
    font-size: 12px;
    color: #64748B;
  }
}

.project-info-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;

  .project-name {
    font-size: 13px;
    font-weight: 500;
    color: #1E293B;
  }
  .project-code {
    font-size: 11px;
    color: #94A3B8;
  }
}

.assignee-cell {
  display: flex;
  align-items: center;
  gap: 8px;

  .assignee-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #E2E8F0;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }

  .assignee-info {
    display: flex;
    flex-direction: column;

    .assignee-name {
      font-size: 13px;
      font-weight: 500;
      color: #1E293B;
    }
    .assignee-sub {
      font-size: 11px;
      color: #94A3B8;
    }
  }
}

.type-pill {
  font-weight: 500;
}

.recurring-hint {
  font-size: 11px;
  color: #D97706;
  font-weight: 500;
  margin-top: 2px;
}

.deadline-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;

  .deadline-time {
    font-size: 12px;
    color: #475569;
    display: flex;
    align-items: center;
  }

  .keterlambatan-badge {
    align-self: flex-start;
  }
}

.rating-cell {
  display: flex;
  justify-content: center;
}

.font-11 { font-size: 11px; }
.font-12 { font-size: 12px; }

/* Form Styles */
.task-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section-card {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 16px;

  .form-section-title {
    margin: 0 0 16px;
    font-size: 14px;
    font-weight: 600;
    color: #1E293B;
    display: flex;
    align-items: center;
  }
}

.form-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.field-hint-box {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
  font-size: 12px;
  color: #64748B;

  .hint-icon {
    color: #14746F;
  }
}

.drawer-footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #E2E8F0;
}

/* Detail Drawer Styles */
.detail-task-wrapper {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-header-card {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;

  .detail-header-main {
    display: flex;
    gap: 16px;
    align-items: center;

    .task-avatar-badge {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      background: #E6F4F2;
      color: #14746F;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .detail-title-col {
      display: flex;
      flex-direction: column;
      gap: 4px;

      .detail-title-row {
        display: flex;
        align-items: center;
        gap: 8px;

        .detail-name {
          margin: 0;
          font-size: 16px;
          font-weight: 600;
          color: #1E293B;
        }
      }

      .detail-sub-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: #64748B;

        .dot-separator { color: #CBD5E1; }
      }
    }
  }

  .detail-quick-actions {
    display: flex;
    gap: 8px;
  }
}

.detail-body-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;

  .info-block {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 16px;

    &.full-width {
      grid-column: span 2;
    }

    .info-block-title {
      margin: 0 0 12px;
      font-size: 13px;
      font-weight: 600;
      color: #334155;
      display: flex;
      align-items: center;
    }

    .info-list {
      display: flex;
      flex-direction: column;
      gap: 10px;

      .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;

        .info-label {
          color: #64748B;
        }
        .info-value {
          color: #1E293B;
          text-align: right;

          &.font-semibold {
            font-weight: 600;
          }
        }
      }
    }

    .description-text {
      margin: 0;
      font-size: 13px;
      line-height: 1.6;
      color: #475569;
      white-space: pre-wrap;
    }
  }
}
</style>
