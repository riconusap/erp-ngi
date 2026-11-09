<template>
  <div class="page-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Jadwal Kerja Pegawai</AppHeading>
        </div>
        <p class="subtitle">Atur dan kelola penugasan shift kerja karyawan per area penempatan</p>
      </div>
      <div class="header-right">
        <div class="header-actions">
          <AppButton variant="secondary" @click="openShiftDrawer">
            <template #icon>
              <el-icon><Clock /></el-icon>
            </template>
            Master Shift
          </AppButton>
          <AppButton variant="secondary" :disabled="!selectedAreaId || isStoreLoading" @click="handleCopyPreviousMonth">
            <template #icon>
              <el-icon><CopyDocument /></el-icon>
            </template>
            Salin Bulan Lalu
          </AppButton>
          <AppButton variant="primary" :disabled="!selectedAreaId || isStoreLoading" @click="openBulkAssignDrawer">
            <template #icon>
              <el-icon><Calendar /></el-icon>
            </template>
            Terapkan Jadwal Massal
          </AppButton>
        </div>
      </div>
    </div>

    <!-- FILTER & NAVIGATOR BAR -->
    <div class="control-panel">
      <div class="selector-row">
        <!-- Client Filter -->
        <div class="control-item item-client">
          <label class="control-label" for="filter-client">Client Mitra</label>
          <el-select
            id="filter-client"
            v-model="selectedClientId"
            placeholder="Semua Client"
            clearable
            filterable
            class="control-select"
            @change="handleClientChange"
          >
            <el-option
              v-for="c in clients"
              :key="c.id"
              :label="c.nama_client"
              :value="c.id"
            />
          </el-select>
        </div>

        <!-- Area Penempatan (Grup Utama) -->
        <div class="control-item item-area">
          <label class="control-label" for="filter-area">Area Penempatan <span class="required-star">*</span></label>
          <el-select
            id="filter-area"
            v-model="selectedAreaId"
            placeholder="Pilih Area Penempatan"
            filterable
            class="control-select highlight-select"
            @change="fetchRoster"
          >
            <el-option
              v-for="a in filteredAreas"
              :key="a.id"
              :label="`${a.nama_area} (${a.client?.nama_client || 'Tanpa Klien'})`"
              :value="a.id"
            />
          </el-select>
        </div>

        <!-- Month & Year Navigator -->
        <div class="control-item month-navigator">
          <label class="control-label">Periode Kerja</label>
          <div class="nav-controls">
            <el-button size="default" class="nav-arrow-btn" @click="prevMonth" aria-label="Bulan sebelumnya">
              <el-icon><ArrowLeft /></el-icon>
            </el-button>
            <el-date-picker
              v-model="selectedMonthDate"
              type="month"
              placeholder="Pilih Bulan"
              format="MMMM YYYY"
              value-format="YYYY-MM"
              :clearable="false"
              class="month-picker"
              @change="handleMonthChange"
            />
            <el-button size="default" class="nav-arrow-btn" @click="nextMonth" aria-label="Bulan berikutnya">
              <el-icon><ArrowRight /></el-icon>
            </el-button>
            <el-button size="default" class="btn-today" @click="resetToCurrentMonth">
              Bulan Ini
            </el-button>
          </div>
        </div>

        <!-- Search Pegawai -->
        <div class="control-item search-box">
          <label class="control-label" for="search-pegawai">Cari Pegawai</label>
          <el-input
            id="search-pegawai"
            v-model="searchQuery"
            placeholder="Cari nama atau NIP..."
            clearable
            @keyup.enter="fetchRoster"
            @clear="fetchRoster"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
      </div>

      <!-- KPI Summary Bar -->
      <div v-if="rosterData" class="summary-kpi-bar">
        <div class="kpi-metrics-group">
          <div class="kpi-card">
            <span class="kpi-label">Area Penempatan</span>
            <span class="kpi-val">{{ rosterData.area.nama_area }}</span>
            <span class="kpi-sub">{{ rosterData.area.client?.nama_client || 'Internal' }}</span>
          </div>

          <div class="kpi-card">
            <span class="kpi-label">Total Pegawai</span>
            <span class="kpi-val text-brand">{{ rosterData.stats.total_pegawai }} Pegawai</span>
            <span class="kpi-sub">Penempatan Aktif</span>
          </div>

          <div class="kpi-card">
            <span class="kpi-label">Shift Terjadwal</span>
            <span class="kpi-val text-success-val">{{ rosterData.stats.total_shifts_assigned }} Slot</span>
            <span class="kpi-sub">{{ rosterData.periode.bulan_nama }}</span>
          </div>

          <div class="kpi-card">
            <span class="kpi-label">Coverage Roster</span>
            <div class="coverage-row">
              <span class="kpi-val">{{ rosterData.stats.coverage_percentage }}%</span>
              <el-progress 
                :percentage="rosterData.stats.coverage_percentage" 
                :show-text="false" 
                :stroke-width="5" 
                color="#14746f" 
                class="coverage-progress"
              />
            </div>
            <span class="kpi-sub">Kebutuhan Shift Terpenuhi</span>
          </div>
        </div>

        <!-- Shift Legend List -->
        <div class="shift-legend-container">
          <span class="legend-header">Legenda Shift Kerja:</span>
          <div class="shift-legend-list">
            <span
              v-for="s in rosterData.shifts"
              :key="s.id"
              class="legend-tag"
              :style="{ backgroundColor: s.warna, color: '#ffffff' }"
            >
              {{ s.kode_shift }}: {{ s.jam_masuk ? s.jam_masuk.substring(0, 5) : 'Libur' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN ROSTER MATRIX VIEW -->
    <div class="matrix-card" v-loading="isStoreLoading">
      <div v-if="!selectedAreaId" class="empty-roster-state">
        <img :src="EmptyStateImage" alt="Pilih Area" class="empty-state-img" />
        <h3 class="empty-title">Silakan Pilih Area Penempatan</h3>
        <p class="empty-desc">Pilih cabang atau area penempatan kerja pada dropdown di atas untuk memuat jadwal kerja karyawan.</p>
      </div>

      <div v-else-if="rosterData && rosterData.employees.length === 0" class="empty-roster-state">
        <img :src="EmptyStateImage" alt="Belum ada pegawai" class="empty-state-img" />
        <h3 class="empty-title">Belum Ada Pegawai di Area Ini</h3>
        <p class="empty-desc">Belum ditemukan pegawai dengan kontrak aktif di area {{ rosterData.area.nama_area }}. Silakan tambahkan kontrak pegawai di menu Kontrak Pegawai.</p>
      </div>

      <div v-else-if="rosterData" class="roster-table-wrapper">
        <table class="roster-table">
          <thead>
            <tr>
              <th class="col-sticky col-index">No</th>
              <th class="col-sticky col-emp">Pegawai & Posisi</th>
              <!-- Date Headers -->
              <th
                v-for="d in rosterData.days"
                :key="d.date"
                class="col-day"
                :class="{
                  'is-weekend': d.isWeekend,
                  'is-today': d.isToday
                }"
              >
                <div class="day-wrapper">
                  <span class="day-num">{{ d.day }}</span>
                  <span class="day-name">{{ d.dayName }}</span>
                  <span v-if="d.isToday" class="today-marker">Hari Ini</span>
                </div>
              </th>
              <!-- Summary Column -->
              <th class="col-summary">Total Shift</th>
              <th class="col-summary">Total Off</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(emp, empIdx) in rosterData.employees" :key="emp.id">
              <td class="col-sticky col-index">{{ empIdx + 1 }}</td>
              <td class="col-sticky col-emp">
                <div class="emp-info">
                  <div class="emp-name">{{ emp.nama }}</div>
                  <div class="emp-meta">
                    <span class="emp-nip">{{ emp.nip }}</span>
                    <span class="emp-bullet">•</span>
                    <span class="emp-pos">{{ emp.jabatan }}</span>
                  </div>
                </div>
              </td>

              <!-- Date Cells -->
              <td
                v-for="d in rosterData.days"
                :key="d.date"
                class="col-cell"
                :class="{
                  'is-weekend': d.isWeekend,
                  'is-today': d.isToday,
                  'is-assigned': !!emp.schedules[d.date]
                }"
              >
                <!-- Clickable Cell with Popover -->
                <el-popover
                  placement="bottom"
                  :width="260"
                  trigger="click"
                  popper-class="shift-cell-popover"
                >
                  <template #reference>
                    <div class="cell-content">
                      <template v-if="emp.schedules[d.date]">
                        <span
                          class="shift-pill"
                          :style="{ backgroundColor: emp.schedules[d.date].warna, color: '#ffffff' }"
                        >
                          {{ emp.schedules[d.date].kode_shift }}
                        </span>
                      </template>
                      <template v-else>
                        <span class="cell-empty-placeholder">+</span>
                      </template>
                    </div>
                  </template>

                  <!-- Popover Content: Quick Shift Selector -->
                  <div class="quick-shift-menu">
                    <div class="quick-shift-header">
                      <div class="quick-emp-name">{{ emp.nama }}</div>
                      <div class="quick-date-info">{{ formatIndoDate(d.date) }} ({{ d.dayName }})</div>
                    </div>

                    <div class="quick-shift-options">
                      <div class="quick-options-label">Pilih Shift:</div>
                      <button
                        v-for="s in rosterData.shifts"
                        :key="s.id"
                        type="button"
                        class="shift-option-btn"
                        :class="{ 'is-active': emp.schedules[d.date]?.shift_id === s.id }"
                        @click="handleQuickAssign(emp.id, d.date, s.id)"
                      >
                        <span class="option-pill" :style="{ backgroundColor: s.warna }">
                          {{ s.kode_shift }}
                        </span>
                        <div class="option-text">
                          <span class="option-name">{{ s.nama_shift }}</span>
                          <span class="option-time">{{ s.jam_masuk ? `${s.jam_masuk.substring(0, 5)} - ${s.jam_pulang?.substring(0, 5)}` : 'Libur / Off' }}</span>
                        </div>
                        <el-icon v-if="emp.schedules[d.date]?.shift_id === s.id" class="check-icon"><Check /></el-icon>
                      </button>
                    </div>

                    <div v-if="emp.schedules[d.date]" class="quick-actions-footer">
                      <el-button
                        size="small"
                        type="danger"
                        plain
                        class="btn-clear-shift"
                        @click="handleQuickAssign(emp.id, d.date, null)"
                      >
                        <el-icon class="mr-1"><Delete /></el-icon>
                        Kosongkan Jadwal
                      </el-button>
                    </div>
                  </div>
                </el-popover>
              </td>

              <!-- Summary Cells -->
              <td class="col-summary">
                <span class="badge-total-shifts">{{ emp.total_shifts }} Hari</span>
              </td>
              <td class="col-summary">
                <span class="badge-total-off">{{ emp.total_off }} Hari</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DRAWER: TERAPKAN JADWAL MASSAL (BULK ASSIGN) -->
    <CustomDrawer
      v-model="isBulkDrawerOpen"
      title="Terapkan Jadwal Kerja Massal"
      default-size="720px"
    >
      <div class="bulk-drawer-container" v-if="rosterData">
        <!-- Area & Client Scope Header -->
        <div class="bulk-scope-banner">
          <div class="scope-main-content">
            <div class="scope-title-line">
              <span class="scope-area-title">{{ rosterData.area.nama_area }}</span>
              <span class="scope-client-tag">{{ rosterData.area.client?.nama_client || 'Internal' }}</span>
            </div>
            <p class="scope-description">
              Terapkan jadwal kerja secara serentak untuk seluruh atau sebagian staf yang ditugaskan pada area ini.
            </p>
          </div>
          <div class="scope-count-badge">
            <span class="count-val">{{ rosterData.employees.length }}</span>
            <span class="count-label">Total Pegawai</span>
          </div>
        </div>

        <!-- STEP 1: PILIHAN SHIFT KERJA -->
        <div class="drawer-step-section">
          <div class="section-top-header">
            <div class="section-title-wrap">
              <span class="section-title">1. Pilihan Shift Kerja</span>
              <span class="section-hint">Pilih jam kerja atau libur operasional yang akan diterapkan</span>
            </div>
          </div>

          <div class="shift-cards-grid">
            <div
              v-for="s in rosterData.shifts"
              :key="s.id"
              class="shift-card-tile"
              :class="{ 'is-selected': bulkForm.shift_id === s.id }"
              @click="bulkForm.shift_id = s.id"
            >
              <div class="shift-tile-header">
                <span class="shift-tile-badge" :style="{ backgroundColor: s.warna }">
                  {{ s.kode_shift }}
                </span>
                <div v-if="bulkForm.shift_id === s.id" class="tile-check-indicator">
                  <el-icon><Check /></el-icon>
                </div>
              </div>
              <div class="shift-tile-name">{{ s.nama_shift }}</div>
              <div class="shift-tile-time">
                <el-icon><Clock /></el-icon>
                <span>{{ s.is_libur ? 'Libur / Off' : (s.jam_masuk ? `${s.jam_masuk.substring(0, 5)} - ${s.jam_pulang?.substring(0, 5)}` : 'OFF') }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 2: RENTANG TANGGAL PENUGASAN -->
        <div class="drawer-step-section">
          <div class="section-top-header">
            <div class="section-title-wrap">
              <span class="section-title">2. Rentang Tanggal Penugasan</span>
              <span class="section-hint">Pilih periode berlaku jadwal pada kalender atau gunakan opsi cepat</span>
            </div>
            <div class="preset-pill-group">
              <button
                type="button"
                class="preset-pill-btn"
                :class="{ 'is-active': activeDatePreset === 'month' }"
                @click="applyDatePreset('month')"
              >
                Bulan Ini
              </button>
              <button
                type="button"
                class="preset-pill-btn"
                :class="{ 'is-active': activeDatePreset === 'first-half' }"
                @click="applyDatePreset('first-half')"
              >
                Tgl 1 - 15
              </button>
              <button
                type="button"
                class="preset-pill-btn"
                :class="{ 'is-active': activeDatePreset === 'second-half' }"
                @click="applyDatePreset('second-half')"
              >
                Tgl 16 - Akhir
              </button>
              <button
                type="button"
                class="preset-pill-btn"
                :class="{ 'is-active': activeDatePreset === 'next-month' }"
                @click="applyDatePreset('next-month')"
              >
                Bulan Depan
              </button>
            </div>
          </div>

          <div class="date-picker-container">
            <el-date-picker
              v-model="bulkForm.dateRange"
              type="daterange"
              range-separator="sampai dengan"
              start-placeholder="Tanggal Mulai"
              end-placeholder="Tanggal Selesai"
              value-format="YYYY-MM-DD"
              format="DD MMMM YYYY"
              style="width: 100%"
              @change="activeDatePreset = 'custom'"
            />
          </div>
        </div>

        <!-- STEP 3: POLA HARI & AKHIR PEKAN -->
        <div class="drawer-step-section">
          <div class="section-top-header">
            <div class="section-title-wrap">
              <span class="section-title">3. Pola Hari & Akhir Pekan</span>
              <span class="section-hint">Atur hari masuk reguler serta perlakuan libur/masuk untuk hari Sabtu dan Minggu</span>
            </div>
            <div class="preset-pill-group">
              <button
                type="button"
                class="preset-pill-btn"
                @click="applySchedulePattern('5days')"
              >
                5 Hari (Sen-Jum)
              </button>
              <button
                type="button"
                class="preset-pill-btn"
                @click="applySchedulePattern('6days')"
              >
                6 Hari (Sen-Sab)
              </button>
              <button
                type="button"
                class="preset-pill-btn"
                @click="applySchedulePattern('7days')"
              >
                7 Hari Penuh
              </button>
            </div>
          </div>

          <!-- Hari Kerja Reguler -->
          <div class="workday-row-wrap">
            <span class="field-item-label">Hari Kerja Reguler (Senin s/d Jumat)</span>
            <div class="day-chips-cluster">
              <button
                v-for="d in [
                  { id: 1, label: 'Senin' },
                  { id: 2, label: 'Selasa' },
                  { id: 3, label: 'Rabu' },
                  { id: 4, label: 'Kamis' },
                  { id: 5, label: 'Jumat' }
                ]"
                :key="d.id"
                type="button"
                class="day-chip-toggle"
                :class="{ 'is-active': bulkForm.days_of_week.includes(d.id) }"
                @click="toggleDayOfWeek(d.id)"
              >
                <span class="day-chip-label">{{ d.label }}</span>
                <el-icon v-if="bulkForm.days_of_week.includes(d.id)" class="day-chip-icon"><Check /></el-icon>
              </button>
            </div>
          </div>

          <!-- Aturan Akhir Pekan -->
          <div class="weekend-rules-grid">
            <div class="weekend-rule-card">
              <div class="weekend-card-label">
                <span class="rule-day-title">Hari Sabtu</span>
                <span class="rule-day-desc">Perlakuan jadwal pada hari Sabtu</span>
              </div>
              <el-radio-group v-model="bulkForm.sabtu_action" size="default" class="weekend-radio-group">
                <el-radio-button label="libur">Libur (OFF)</el-radio-button>
                <el-radio-button label="kerja">Masuk Shift</el-radio-button>
                <el-radio-button label="lewati">Lewati</el-radio-button>
              </el-radio-group>
            </div>

            <div class="weekend-rule-card">
              <div class="weekend-card-label">
                <span class="rule-day-title">Hari Minggu</span>
                <span class="rule-day-desc">Perlakuan jadwal pada hari Minggu</span>
              </div>
              <el-radio-group v-model="bulkForm.minggu_action" size="default" class="weekend-radio-group">
                <el-radio-button label="libur">Libur (OFF)</el-radio-button>
                <el-radio-button label="kerja">Masuk Shift</el-radio-button>
                <el-radio-button label="lewati">Lewati</el-radio-button>
              </el-radio-group>
            </div>
          </div>
        </div>

        <!-- STEP 4: PEMILIHAN PEGAWAI -->
        <div class="drawer-step-section">
          <div class="section-top-header">
            <div class="section-title-wrap">
              <div class="title-counter-row">
                <span class="section-title">4. Pilih Pegawai Penerima Jadwal</span>
                <span class="selection-tag">{{ bulkForm.pegawai_ids.length }} dari {{ rosterData.employees.length }} Terpilih</span>
              </div>
              <span class="section-hint">Tentukan karyawan yang akan ditugaskan ke jadwal ini</span>
            </div>
            <div class="emp-action-links">
              <button type="button" class="emp-link-btn" @click="selectAllEmployees">Pilih Semua</button>
              <span class="emp-link-sep">•</span>
              <button type="button" class="emp-link-btn" @click="deselectAllEmployees">Batal Semua</button>
            </div>
          </div>

          <!-- Filter Toolbar for Employees -->
          <div class="emp-filter-toolbar">
            <el-input
              v-model="drawerEmpSearch"
              placeholder="Cari nama, NIP, atau jabatan pegawai..."
              size="default"
              clearable
              class="emp-search-field"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>

            <div v-if="drawerJabatanOptions.length > 1" class="emp-jabatan-pills">
              <button
                type="button"
                class="jab-filter-chip"
                :class="{ 'is-active': selectedDrawerJabatan === 'ALL' }"
                @click="selectedDrawerJabatan = 'ALL'"
              >
                Semua
              </button>
              <button
                v-for="jab in drawerJabatanOptions"
                :key="jab"
                type="button"
                class="jab-filter-chip"
                :class="{ 'is-active': selectedDrawerJabatan === jab }"
                @click="selectedDrawerJabatan = jab"
              >
                {{ jab }}
              </button>
            </div>
          </div>

          <!-- Employees Cards Grid -->
          <div class="emp-selection-viewport">
            <div
              v-for="e in filteredDrawerEmployees"
              :key="e.id"
              class="emp-choice-tile"
              :class="{ 'is-selected': bulkForm.pegawai_ids.includes(e.id) }"
              @click="toggleEmployeeSelect(e.id)"
            >
              <div class="choice-checkbox">
                <el-icon v-if="bulkForm.pegawai_ids.includes(e.id)"><Check /></el-icon>
              </div>
              <div class="choice-avatar">
                {{ getInitials(e.nama) }}
              </div>
              <div class="choice-info">
                <div class="choice-name">{{ e.nama }}</div>
                <div class="choice-meta">
                  <span class="choice-nip">{{ e.nip || '-' }}</span>
                  <span class="choice-dot">•</span>
                  <span class="choice-pos">{{ e.jabatan || 'Staf Operasional' }}</span>
                </div>
              </div>
            </div>

            <div v-if="filteredDrawerEmployees.length === 0" class="empty-emp-message">
              <el-icon><Search /></el-icon>
              <span>Tidak ada pegawai yang sesuai dengan filter pencarian.</span>
            </div>
          </div>
        </div>

        <!-- STEP 5: PENGATURAN TAMBAHAN & RINGKASAN -->
        <div class="drawer-step-section section-summary-wrap">
          <!-- Overwrite Toggle Box -->
          <div class="overwrite-toggle-box">
            <div class="overwrite-text-block">
              <span class="overwrite-title">Timpa Jadwal yang Sudah Ada</span>
              <span class="overwrite-description">Bila diaktifkan, jadwal yang telah terisi sebelumnya pada rentang tanggal ini akan digantikan oleh shift yang baru.</span>
            </div>
            <el-switch v-model="bulkForm.overwrite" active-color="#14746f" />
          </div>

          <!-- Live Preview Summary Card -->
          <div class="assignment-preview-card" v-if="bulkForm.shift_id && bulkForm.dateRange?.length === 2">
            <div class="preview-card-title">
              <el-icon><Calendar /></el-icon>
              <span>Ringkasan Penugasan Jadwal</span>
            </div>
            <div class="preview-metrics-grid">
              <div class="metric-cell">
                <span class="metric-label">Shift Terpilih</span>
                <span class="metric-val text-brand">{{ selectedShiftObj?.nama_shift || '-' }} ({{ selectedShiftObj?.kode_shift }})</span>
              </div>
              <div class="metric-cell">
                <span class="metric-label">Rentang Periode</span>
                <span class="metric-val">{{ formatDateDisplay(bulkForm.dateRange[0]) }} s/d {{ formatDateDisplay(bulkForm.dateRange[1]) }}</span>
              </div>
              <div class="metric-cell">
                <span class="metric-label">Pegawai Penerima</span>
                <span class="metric-val font-semibold">{{ bulkForm.pegawai_ids.length }} Orang</span>
              </div>
              <div class="metric-cell">
                <span class="metric-label">Estimasi Slot Jadwal</span>
                <span class="metric-val text-accent font-semibold">~{{ estimatedSlotsCount }} Slot</span>
              </div>
            </div>
          </div>
        </div>

        <!-- STICKY ACTION FOOTER -->
        <div class="bulk-sticky-footer">
          <div class="footer-status-text">
            <span v-if="bulkForm.pegawai_ids.length > 0">
              <strong>{{ bulkForm.pegawai_ids.length }}</strong> dari {{ rosterData.employees.length }} pegawai dipilih
            </span>
            <span v-else class="text-warning">
              Silakan pilih minimal 1 pegawai
            </span>
          </div>
          <div class="footer-action-buttons">
            <AppButton variant="secondary" @click="isBulkDrawerOpen = false">
              Batal
            </AppButton>
            <AppButton
              variant="primary"
              :loading="isSaving"
              :disabled="bulkForm.pegawai_ids.length === 0 || !bulkForm.shift_id || !bulkForm.dateRange?.length"
              @click="submitBulkAssign"
            >
              <template #icon>
                <el-icon><Calendar /></el-icon>
              </template>
              Terapkan ke {{ bulkForm.pegawai_ids.length }} Pegawai
            </AppButton>
          </div>
        </div>
      </div>
    </CustomDrawer>

    <!-- DRAWER: MASTER SHIFT MANAGEMENT -->
    <CustomDrawer
      v-model="isShiftDrawerOpen"
      title="Master Shift & Jam Kerja"
      default-size="750px"
    >
      <div class="drawer-inner-content">
        <div class="shift-mgmt-header mb-4">
          <p class="subtitle">Kelola daftar jam kerja dan toleransi keterlambatan shift operasional.</p>
          <AppButton variant="primary" size="small" @click="openCreateShift">
            <template #icon>
              <el-icon><Plus /></el-icon>
            </template>
            Tambah Shift
          </AppButton>
        </div>

        <!-- Shift List Table -->
        <el-table :data="allShifts" stripe border class="mb-4">
          <el-table-column label="Kode & Warna" width="120">
            <template #default="{ row }">
              <span class="shift-pill" :style="{ backgroundColor: row.warna, color: '#ffffff' }">
                {{ row.kode_shift }}
              </span>
            </template>
          </el-table-column>
          <el-table-column prop="nama_shift" label="Nama Shift" min-width="180" />
          <el-table-column label="Jam Kerja" width="160">
            <template #default="{ row }">
              <span v-if="row.is_libur" class="text-muted">Hari Libur</span>
              <span v-else>
                {{ row.jam_masuk ? row.jam_masuk.substring(0, 5) : '-' }} s/d {{ row.jam_pulang ? row.jam_pulang.substring(0, 5) : '-' }}
              </span>
            </template>
          </el-table-column>
          <el-table-column label="Toleransi" width="100" align="center">
            <template #default="{ row }">
              <span v-if="!row.is_libur">{{ row.toleransi_menit }} mnt</span>
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column prop="status" label="Status" width="100" align="center">
            <template #default="{ row }">
              <el-tag :type="row.status === 'Aktif' ? 'success' : 'info'" size="small">
                {{ row.status }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Aksi" width="100" align="center">
            <template #default="{ row }">
              <el-button size="small" text type="primary" @click="editShift(row)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button size="small" text type="danger" @click="deleteShiftConfirm(row)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <!-- Form Add/Edit Shift (Inline Dialog/Section) -->
        <el-dialog
          v-model="isShiftFormModalOpen"
          :title="shiftFormMode === 'create' ? 'Tambah Master Shift Baru' : 'Edit Master Shift'"
          width="480px"
          append-to-body
        >
          <el-form label-position="top">
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Kode Shift (singkat)" required>
                  <el-input v-model="shiftFormData.kode_shift" placeholder="Contoh: PAGI" maxlength="10" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Warna Label">
                  <div class="color-picker-row">
                    <el-color-picker v-model="shiftFormData.warna" />
                    <span class="color-hex-text">{{ shiftFormData.warna }}</span>
                  </div>
                </el-form-item>
              </el-col>
            </el-row>

            <el-form-item label="Nama Shift" required>
              <el-input v-model="shiftFormData.nama_shift" placeholder="Contoh: Shift Pagi (08:00 - 17:00)" />
            </el-form-item>

            <el-form-item label="Tipe Shift">
              <el-switch
                v-model="shiftFormData.is_libur"
                active-text="Hari Libur / Off (Tanpa Jam Masuk)"
              />
            </el-form-item>

            <template v-if="!shiftFormData.is_libur">
              <el-row :gutter="12">
                <el-col :span="12">
                  <el-form-item label="Jam Masuk" required>
                    <el-time-picker
                      v-model="shiftFormData.jam_masuk"
                      format="HH:mm"
                      value-format="HH:mm"
                      placeholder="08:00"
                      style="width: 100%"
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="Jam Pulang" required>
                    <el-time-picker
                      v-model="shiftFormData.jam_pulang"
                      format="HH:mm"
                      value-format="HH:mm"
                      placeholder="17:00"
                      style="width: 100%"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-form-item label="Toleransi Keterlambatan (Menit)">
                <el-input-number v-model="shiftFormData.toleransi_menit" :min="0" :max="120" style="width: 100%" />
              </el-form-item>
            </template>

            <el-form-item label="Status">
              <el-radio-group v-model="shiftFormData.status">
                <el-radio label="Aktif">Aktif</el-radio>
                <el-radio label="Nonaktif">Nonaktif</el-radio>
              </el-radio-group>
            </el-form-item>

            <el-form-item label="Keterangan">
              <el-input v-model="shiftFormData.keterangan" type="textarea" :rows="2" placeholder="Catatan shift..." />
            </el-form-item>
          </el-form>

          <template #footer>
            <div class="dialog-footer">
              <el-button @click="isShiftFormModalOpen = false">Batal</el-button>
              <el-button type="primary" :loading="isSavingShift" @click="saveShiftForm">Simpan</el-button>
            </div>
          </template>
        </el-dialog>
      </div>
    </CustomDrawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import EmptyStateImage from '@/assets/empty-state.svg'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import CustomDrawer from '@/components/common/CustomDrawer.vue'
import { useJadwalKerjaStore, type Shift } from '@/store/jadwalKerja'
import { useClientAreaStore, type Client, type Area } from '@/store/clientArea'
import dayjs from 'dayjs'
import 'dayjs/locale/id'
import {
  Calendar,
  Clock,
  CopyDocument,
  ArrowLeft,
  ArrowRight,
  Search,
  Check,
  CircleCheck,
  Close,
  Delete,
  Plus,
  Edit,
  Location,
  OfficeBuilding,
  InfoFilled,
  User
} from '@element-plus/icons-vue'

dayjs.locale('id')

const jadwalStore = useJadwalKerjaStore()
const clientAreaStore = useClientAreaStore()

// State
const clients = ref<Client[]>([])
const areas = ref<Area[]>([])
const selectedClientId = ref<number | null>(null)
const selectedAreaId = ref<number | null>(null)
const selectedMonthDate = ref(dayjs().format('YYYY-MM'))
const searchQuery = ref('')

const isStoreLoading = computed(() => jadwalStore.isLoading)
const isSaving = computed(() => jadwalStore.isSaving)
const rosterData = computed(() => jadwalStore.rosterData)

// Filter Areas by Client
const filteredAreas = computed(() => {
  if (!selectedClientId.value) {
    return areas.value
  }
  return areas.value.filter(a => a.client_id === selectedClientId.value)
})

// Initialize Data
onMounted(async () => {
  try {
    const [allClients, allAreas] = await Promise.all([
      clientAreaStore.getAllClients(),
      clientAreaStore.getAllAreas(),
      jadwalStore.getShifts()
    ])
    clients.value = allClients
    areas.value = allAreas

    // Default to first active area if available
    if (allAreas.length > 0) {
      selectedAreaId.value = allAreas[0].id
      selectedClientId.value = allAreas[0].client_id
      await fetchRoster()
    }
  } catch (err: any) {
    ElMessage.error(err?.response?.data?.message || 'Gagal memuat data awal penjadwalan.')
  }
})

// Client change filter
const handleClientChange = () => {
  if (selectedClientId.value) {
    const areasInClient = areas.value.filter(a => a.client_id === selectedClientId.value)
    if (areasInClient.length > 0) {
      selectedAreaId.value = areasInClient[0].id
    } else {
      selectedAreaId.value = null
    }
  }
  fetchRoster()
}

// Fetch Roster Matrix
const fetchRoster = async () => {
  if (!selectedAreaId.value) return

  const dateObj = dayjs(selectedMonthDate.value, 'YYYY-MM')
  const bulan = dateObj.month() + 1
  const tahun = dateObj.year()

  try {
    await jadwalStore.getRoster({
      area_id: selectedAreaId.value,
      bulan,
      tahun,
      search: searchQuery.value || undefined
    })
  } catch (err: any) {
    ElMessage.error(err?.response?.data?.message || 'Gagal memuat matrix jadwal kerja.')
  }
}

// Month Navigation
const prevMonth = () => {
  selectedMonthDate.value = dayjs(selectedMonthDate.value, 'YYYY-MM').subtract(1, 'month').format('YYYY-MM')
  fetchRoster()
}

const nextMonth = () => {
  selectedMonthDate.value = dayjs(selectedMonthDate.value, 'YYYY-MM').add(1, 'month').format('YYYY-MM')
  fetchRoster()
}

const resetToCurrentMonth = () => {
  selectedMonthDate.value = dayjs().format('YYYY-MM')
  fetchRoster()
}

const handleMonthChange = () => {
  fetchRoster()
}

// Format Date for Cell Popover
const formatIndoDate = (dateStr: string) => {
  return dayjs(dateStr).format('D MMMM YYYY')
}

// Quick Assign from Cell Popover
const handleQuickAssign = async (pegawaiId: string, tanggal: string, shiftId: number | null) => {
  if (!selectedAreaId.value) return

  try {
    const res = await jadwalStore.assignCell({
      pegawai_id: pegawaiId,
      area_id: selectedAreaId.value,
      tanggal,
      shift_id: shiftId
    })

    ElMessage.success(res.message || 'Jadwal berhasil diperbarui.')

    // Update cell locally in store to avoid full table redraw flicker
    if (rosterData.value) {
      const emp = rosterData.value.employees.find(e => e.id === pegawaiId)
      if (emp) {
        if (!emp.schedules) emp.schedules = {}
        if (shiftId && res.data) {
          emp.schedules[tanggal] = res.data
        } else {
          delete emp.schedules[tanggal]
        }

        // Recalculate totals for this employee
        let shiftsCount = 0
        let offCount = 0
        Object.values(emp.schedules).forEach((s: any) => {
          if (s.is_libur) offCount++
          else shiftsCount++
        })
        emp.total_shifts = shiftsCount
        emp.total_off = offCount
      }
    }
  } catch (err: any) {
    ElMessage.error(err?.response?.data?.message || 'Gagal mengubah jadwal kerja.')
  }
}

// ========================
// BULK ASSIGN DRAWER LOGIC
// ========================
const isBulkDrawerOpen = ref(false)
const bulkForm = reactive({
  pegawai_ids: [] as string[],
  shift_id: null as number | null,
  dateRange: [] as string[],
  days_of_week: [1, 2, 3, 4, 5], // default Senin s/d Jumat
  sabtu_action: 'libur' as 'libur' | 'kerja' | 'lewati',
  minggu_action: 'libur' as 'libur' | 'kerja' | 'lewati',
  overwrite: true,
})

const activeDatePreset = ref<'month' | 'first-half' | 'second-half' | 'next-month' | 'custom'>('month')
const selectedDrawerJabatan = ref('ALL')

const selectedShiftObj = computed(() => {
  if (!rosterData.value || !bulkForm.shift_id) return null
  return rosterData.value.shifts.find(s => s.id === bulkForm.shift_id) || null
})

const drawerJabatanOptions = computed(() => {
  if (!rosterData.value) return []
  const jabatans = new Set<string>()
  rosterData.value.employees.forEach(e => {
    if (e.jabatan) jabatans.add(e.jabatan)
  })
  return Array.from(jabatans)
})

const filteredDrawerEmployees = computed(() => {
  if (!rosterData.value) return []
  let list = rosterData.value.employees
  if (selectedDrawerJabatan.value !== 'ALL') {
    list = list.filter(e => e.jabatan === selectedDrawerJabatan.value)
  }
  if (!drawerEmpSearch.value) return list
  const q = drawerEmpSearch.value.toLowerCase().trim()
  return list.filter(e => 
    e.nama.toLowerCase().includes(q) || 
    (e.nip && e.nip.toLowerCase().includes(q)) || 
    (e.jabatan && e.jabatan.toLowerCase().includes(q))
  )
})

const applyDatePreset = (preset: 'month' | 'first-half' | 'second-half' | 'next-month') => {
  activeDatePreset.value = preset
  const baseMonth = dayjs(selectedMonthDate.value, 'YYYY-MM')
  if (preset === 'month') {
    bulkForm.dateRange = [
      baseMonth.startOf('month').format('YYYY-MM-DD'),
      baseMonth.endOf('month').format('YYYY-MM-DD')
    ]
  } else if (preset === 'first-half') {
    bulkForm.dateRange = [
      baseMonth.startOf('month').format('YYYY-MM-DD'),
      baseMonth.date(15).format('YYYY-MM-DD')
    ]
  } else if (preset === 'second-half') {
    bulkForm.dateRange = [
      baseMonth.date(16).format('YYYY-MM-DD'),
      baseMonth.endOf('month').format('YYYY-MM-DD')
    ]
  } else if (preset === 'next-month') {
    const nextMonth = baseMonth.add(1, 'month')
    bulkForm.dateRange = [
      nextMonth.startOf('month').format('YYYY-MM-DD'),
      nextMonth.endOf('month').format('YYYY-MM-DD')
    ]
  }
}

const applySchedulePattern = (pattern: '5days' | '6days' | '7days') => {
  if (pattern === '5days') {
    bulkForm.days_of_week = [1, 2, 3, 4, 5]
    bulkForm.sabtu_action = 'libur'
    bulkForm.minggu_action = 'libur'
  } else if (pattern === '6days') {
    bulkForm.days_of_week = [1, 2, 3, 4, 5]
    bulkForm.sabtu_action = 'kerja'
    bulkForm.minggu_action = 'libur'
  } else if (pattern === '7days') {
    bulkForm.days_of_week = [1, 2, 3, 4, 5]
    bulkForm.sabtu_action = 'kerja'
    bulkForm.minggu_action = 'kerja'
  }
}

const getInitials = (name: string) => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

const estimatedSlotsCount = computed(() => {
  if (!bulkForm.dateRange || bulkForm.dateRange.length < 2 || bulkForm.pegawai_ids.length === 0) {
    return 0
  }
  const start = dayjs(bulkForm.dateRange[0])
  const end = dayjs(bulkForm.dateRange[1])
  if (!start.isValid() || !end.isValid() || end.isBefore(start)) return 0

  let activeDaysCount = 0
  let cur = start
  while (cur.isBefore(end) || cur.isSame(end, 'day')) {
    const dow = cur.day() // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
    if (dow === 6) { // Sabtu
      if (bulkForm.sabtu_action !== 'lewati') activeDaysCount++
    } else if (dow === 0) { // Minggu
      if (bulkForm.minggu_action !== 'lewati') activeDaysCount++
    } else { // Senin s/d Jumat (1..5)
      if (bulkForm.days_of_week.includes(dow)) activeDaysCount++
    }
    cur = cur.add(1, 'day')
  }

  return activeDaysCount * bulkForm.pegawai_ids.length
})

const formatDateDisplay = (dateStr: string) => {
  if (!dateStr) return '-'
  return dayjs(dateStr).format('D MMM YYYY')
}

// Drawer Employee Search & Selection Helpers
const drawerEmpSearch = ref('')

const selectAllEmployees = () => {
  if (!rosterData.value) return
  bulkForm.pegawai_ids = rosterData.value.employees.map(e => e.id)
}

const deselectAllEmployees = () => {
  bulkForm.pegawai_ids = []
}

const toggleEmployeeSelect = (id: string) => {
  const idx = bulkForm.pegawai_ids.indexOf(id)
  if (idx > -1) {
    bulkForm.pegawai_ids.splice(idx, 1)
  } else {
    bulkForm.pegawai_ids.push(id)
  }
}

const toggleDayOfWeek = (dayNum: number) => {
  const idx = bulkForm.days_of_week.indexOf(dayNum)
  if (idx > -1) {
    bulkForm.days_of_week.splice(idx, 1)
  } else {
    bulkForm.days_of_week.push(dayNum)
    bulkForm.days_of_week.sort()
  }
}

const openBulkAssignDrawer = () => {
  if (!rosterData.value) return
  drawerEmpSearch.value = ''
  selectedDrawerJabatan.value = 'ALL'
  activeDatePreset.value = 'month'
  // Pre-fill with current month range and all employees
  const start = dayjs(selectedMonthDate.value, 'YYYY-MM').startOf('month').format('YYYY-MM-DD')
  const end = dayjs(selectedMonthDate.value, 'YYYY-MM').endOf('month').format('YYYY-MM-DD')
  bulkForm.dateRange = [start, end]
  bulkForm.pegawai_ids = rosterData.value.employees.map(e => e.id)
  bulkForm.shift_id = rosterData.value.shifts[0]?.id || null
  bulkForm.days_of_week = [1, 2, 3, 4, 5]
  bulkForm.sabtu_action = 'libur'
  bulkForm.minggu_action = 'libur'
  bulkForm.overwrite = true
  isBulkDrawerOpen.value = true
}

const submitBulkAssign = async () => {
  if (!selectedAreaId.value) return
  if (bulkForm.pegawai_ids.length === 0) {
    return ElMessage.warning('Silakan pilih minimal satu pegawai.')
  }
  if (!bulkForm.shift_id) {
    return ElMessage.warning('Silakan pilih shift yang akan diterapkan.')
  }
  if (!bulkForm.dateRange || bulkForm.dateRange.length < 2) {
    return ElMessage.warning('Silakan pilih rentang tanggal penugasan.')
  }

  try {
    const res = await jadwalStore.bulkAssign({
      area_id: selectedAreaId.value,
      pegawai_ids: bulkForm.pegawai_ids,
      shift_id: bulkForm.shift_id,
      start_date: bulkForm.dateRange[0],
      end_date: bulkForm.dateRange[1],
      days_of_week: bulkForm.days_of_week,
      sabtu_action: bulkForm.sabtu_action,
      minggu_action: bulkForm.minggu_action,
      overwrite: bulkForm.overwrite
    })

    ElMessage.success(res.message || 'Jadwal massal berhasil diterapkan.')
    isBulkDrawerOpen.value = false
    await fetchRoster()
  } catch (err: any) {
    ElMessage.error(err?.response?.data?.message || 'Gagal menerapkan jadwal massal.')
  }
}

// ===========================
// COPY PREVIOUS MONTH ACTION
// ===========================
const handleCopyPreviousMonth = async () => {
  if (!selectedAreaId.value || !rosterData.value) return

  const curDate = dayjs(selectedMonthDate.value, 'YYYY-MM')
  const targetBulan = curDate.month() + 1
  const targetTahun = curDate.year()
  const prevMonthName = curDate.subtract(1, 'month').format('MMMM YYYY')

  try {
    await ElMessageBox.confirm(
      `Apakah Anda yakin ingin menyalin jadwal dari bulan sebelumnya (${prevMonthName}) ke bulan ${rosterData.value.periode.bulan_nama} untuk area ini?`,
      'Konfirmasi Salin Jadwal',
      {
        confirmButtonText: 'Ya, Salin Jadwal',
        cancelButtonText: 'Batal',
        type: 'info'
      }
    )

    const res = await jadwalStore.copyPreviousMonth({
      area_id: selectedAreaId.value,
      target_bulan: targetBulan,
      target_tahun: targetTahun,
      overwrite: true
    })

    ElMessage.success(res.message || 'Jadwal berhasil disalin.')
    await fetchRoster()
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err?.response?.data?.message || 'Gagal menyalin jadwal bulan lalu.')
    }
  }
}

// ==========================
// MASTER SHIFT DRAWER LOGIC
// ==========================
const isShiftDrawerOpen = ref(false)
const allShifts = computed(() => jadwalStore.shifts)
const isShiftFormModalOpen = ref(false)
const shiftFormMode = ref<'create' | 'edit'>('create')
const isSavingShift = ref(false)
const selectedShiftId = ref<number | null>(null)

const shiftFormData = reactive({
  kode_shift: '',
  nama_shift: '',
  jam_masuk: '08:00',
  jam_pulang: '17:00',
  toleransi_menit: 15,
  warna: '#10B981',
  is_libur: false,
  status: 'Aktif' as 'Aktif' | 'Nonaktif',
  keterangan: '',
})

const openShiftDrawer = () => {
  isShiftDrawerOpen.value = true
}

const openCreateShift = () => {
  shiftFormMode.value = 'create'
  selectedShiftId.value = null
  shiftFormData.kode_shift = ''
  shiftFormData.nama_shift = ''
  shiftFormData.jam_masuk = '08:00'
  shiftFormData.jam_pulang = '17:00'
  shiftFormData.toleransi_menit = 15
  shiftFormData.warna = '#10B981'
  shiftFormData.is_libur = false
  shiftFormData.status = 'Aktif'
  shiftFormData.keterangan = ''
  isShiftFormModalOpen.value = true
}

const editShift = (shift: Shift) => {
  shiftFormMode.value = 'edit'
  selectedShiftId.value = shift.id
  shiftFormData.kode_shift = shift.kode_shift
  shiftFormData.nama_shift = shift.nama_shift
  shiftFormData.jam_masuk = shift.jam_masuk ? shift.jam_masuk.substring(0, 5) : '08:00'
  shiftFormData.jam_pulang = shift.jam_pulang ? shift.jam_pulang.substring(0, 5) : '17:00'
  shiftFormData.toleransi_menit = shift.toleransi_menit
  shiftFormData.warna = shift.warna
  shiftFormData.is_libur = shift.is_libur
  shiftFormData.status = shift.status
  shiftFormData.keterangan = shift.keterangan || ''
  isShiftFormModalOpen.value = true
}

const saveShiftForm = async () => {
  if (!shiftFormData.kode_shift || !shiftFormData.nama_shift) {
    return ElMessage.warning('Kode shift dan nama shift wajib diisi.')
  }

  isSavingShift.value = true
  try {
    if (shiftFormMode.value === 'create') {
      await jadwalStore.createShift(shiftFormData)
      ElMessage.success('Master shift berhasil dibuat.')
    } else if (selectedShiftId.value) {
      await jadwalStore.updateShift(selectedShiftId.value, shiftFormData)
      ElMessage.success('Master shift berhasil diperbarui.')
    }

    isShiftFormModalOpen.value = false
    await jadwalStore.getShifts()
    await fetchRoster()
  } catch (err: any) {
    ElMessage.error(err?.response?.data?.message || 'Gagal menyimpan master shift.')
  } finally {
    isSavingShift.value = false
  }
}

const deleteShiftConfirm = async (shift: Shift) => {
  try {
    await ElMessageBox.confirm(
      `Hapus shift ${shift.nama_shift}? Jika sudah dipakai dalam jadwal, sebaiknya ubah statusnya menjadi Nonaktif.`,
      'Hapus Shift',
      {
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal',
        type: 'warning'
      }
    )

    await jadwalStore.deleteShift(shift.id)
    ElMessage.success('Shift berhasil dihapus.')
    await jadwalStore.getShifts()
    await fetchRoster()
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err?.response?.data?.message || 'Gagal menghapus shift.')
    }
  }
}
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
  align-items: flex-start;
  gap: 16px;

  .header-left {
    .title-wrapper {
      margin-bottom: 4px;
    }
    .subtitle {
      color: #64748b;
      font-size: 14px;
      margin: 0;
    }
  }

  .header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
  }
}

/* Control Panel & Filter Toolbar */
.control-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 18px 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);

  .selector-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 16px;
  }

  .control-item {
    display: flex;
    flex-direction: column;
    gap: 6px;

    .control-label {
      font-size: 12.5px;
      font-weight: 600;
      color: #334155;

      .required-star {
        color: #ef4444;
      }
    }

    &.item-client {
      width: 200px;
      .control-select {
        width: 100%;
      }
    }

    &.item-area {
      width: 260px;
      .control-select {
        width: 100%;
      }
    }

    &.month-navigator {
      .nav-controls {
        display: flex;
        align-items: center;
        gap: 6px;

        .nav-arrow-btn {
          padding: 8px 10px;
          border-color: #e2e8f0;
          color: #475569;

          &:hover {
            color: #14746f;
            border-color: #14746f;
            background-color: #f0fdf4;
          }
        }

        .month-picker {
          width: 155px;
        }

        .btn-today {
          font-weight: 600;
          font-size: 12.5px;
          color: #14746f;
          border-color: #cbd5e1;

          &:hover {
            background-color: #e7f1f0;
            border-color: #14746f;
          }
        }
      }
    }

    &.search-box {
      flex: 1;
      min-width: 200px;
    }
  }
}

/* KPI Summary Bar */
.summary-kpi-bar {
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  gap: 14px;

  .kpi-metrics-group {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;

    .kpi-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 10px 14px;
      display: flex;
      flex-direction: column;

      .kpi-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
      }

      .kpi-val {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 2px 0 1px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;

        &.text-brand {
          color: #14746f;
        }

        &.text-success-val {
          color: #15803d;
        }
      }

      .kpi-sub {
        font-size: 11.5px;
        color: #94a3b8;
      }

      .coverage-row {
        display: flex;
        align-items: center;
        gap: 10px;

        .coverage-progress {
          flex: 1;
        }
      }
    }
  }

  .shift-legend-container {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;

    .legend-header {
      font-weight: 600;
      color: #64748b;
      margin-right: 4px;
    }

    .shift-legend-list {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 6px;

      .legend-tag {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        letter-spacing: 0.01em;
      }
    }
  }
}

/* Matrix Roster Card & Table */
.matrix-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  overflow: hidden;
  min-height: 420px;
}

.empty-roster-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 20px;
  text-align: center;

  .empty-state-img {
    width: 130px;
    margin-bottom: 16px;
    opacity: 0.9;
  }

  .empty-title {
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px 0;
  }

  .empty-desc {
    font-size: 13.5px;
    color: #64748b;
    max-width: 460px;
    margin: 0;
    line-height: 1.5;
  }
}

/* Roster Table Grid */
.roster-table-wrapper {
  overflow-x: auto;
  max-width: 100%;
}

.roster-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;

  thead th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 600;
    padding: 10px 6px;
    border-bottom: 1px solid #cbd5e1;
    border-right: 1px solid #e2e8f0;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 5;

    &.col-day {
      min-width: 44px;
      max-width: 52px;

      .day-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1px;

        .day-num {
          font-size: 13.5px;
          font-weight: 700;
          color: #0f172a;
          font-variant-numeric: tabular-nums;
        }

        .day-name {
          font-size: 11px;
          font-weight: 500;
          color: #64748b;
        }

        .today-marker {
          font-size: 9px;
          font-weight: 700;
          color: #14746f;
          background: #e7f1f0;
          padding: 1px 4px;
          border-radius: 3px;
          margin-top: 1px;
        }
      }

      &.is-weekend {
        background-color: #fafafa;
        .day-wrapper .day-name {
          color: #dc2626;
        }
      }

      &.is-today {
        background-color: #f0fdf4;
        border-top: 2px solid #14746f;
      }
    }
  }

  tbody tr {
    transition: background-color 0.12s ease;

    &:hover {
      background-color: #f8fafc;

      .col-sticky {
        background-color: #f1f5f9;
      }
    }
  }

  tbody td {
    padding: 6px 4px;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    vertical-align: middle;
    text-align: center;
  }

  /* Sticky Left Columns */
  .col-sticky {
    position: sticky;
    background-color: #ffffff;
    z-index: 2;

    &.col-index {
      left: 0;
      width: 42px;
      min-width: 42px;
      text-align: center;
      font-weight: 600;
      color: #94a3b8;
      border-right: 1px solid #e2e8f0;
    }

    &.col-emp {
      left: 42px;
      width: 230px;
      min-width: 230px;
      text-align: left;
      border-right: 2px solid #cbd5e1;
      padding-left: 12px;
      padding-right: 12px;
    }
  }

  thead th.col-sticky {
    z-index: 10;
    background-color: #f8fafc;
  }

  /* Employee Card in Column */
  .emp-info {
    .emp-name {
      font-weight: 600;
      color: #0f172a;
      font-size: 13px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .emp-meta {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-top: 2px;
      font-size: 11px;
      color: #64748b;

      .emp-nip {
        font-variant-numeric: tabular-nums;
      }

      .emp-pos {
        color: #475569;
        font-weight: 500;
      }
    }
  }

  /* Cell Content & Shift Pill */
  .col-cell {
    cursor: pointer;
    padding: 3px;

    &.is-weekend {
      background-color: #fdfdfd;
    }

    &.is-today {
      background-color: #f0fdf4;
    }

    .cell-content {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 32px;
      border-radius: 4px;
      transition: background-color 0.12s ease;

      &:hover {
        background-color: #e2e8f0;
      }
    }

    .shift-pill {
      font-size: 11px;
      font-weight: 600;
      padding: 3px 6px;
      border-radius: 4px;
      display: inline-block;
      min-width: 34px;
      text-align: center;
      letter-spacing: 0.02em;
    }

    .cell-empty-placeholder {
      font-size: 13px;
      color: #cbd5e1;
      opacity: 0;
      transition: opacity 0.12s ease;
    }

    &:hover .cell-empty-placeholder {
      opacity: 1;
      color: #64748b;
    }
  }

  /* Summary Columns */
  .col-summary {
    width: 86px;
    min-width: 86px;
    text-align: center;
    font-weight: 600;

    .badge-total-shifts {
      display: inline-block;
      padding: 3px 8px;
      background: #e7f1f0;
      color: #14746f;
      border-radius: 4px;
      font-size: 11.5px;
      font-variant-numeric: tabular-nums;
    }

    .badge-total-off {
      display: inline-block;
      padding: 3px 8px;
      background: #f1f5f9;
      color: #475569;
      border-radius: 4px;
      font-size: 11.5px;
      font-variant-numeric: tabular-nums;
    }
  }
}

/* Quick Shift Popover */
.quick-shift-menu {
  .quick-shift-header {
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 8px;
    margin-bottom: 10px;

    .quick-emp-name {
      font-weight: 600;
      font-size: 13px;
      color: #0f172a;
    }
    .quick-date-info {
      font-size: 11.5px;
      color: #64748b;
      margin-top: 2px;
    }
  }

  .quick-options-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 6px;
    letter-spacing: 0.02em;
  }

  .quick-shift-options {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .shift-option-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #ffffff;
    cursor: pointer;
    text-align: left;
    transition: all 0.12s ease;

    &:hover {
      background-color: #f8fafc;
      border-color: #cbd5e1;
    }

    &.is-active {
      border-color: #14746f;
      background-color: #e7f1f0;
    }

    .option-pill {
      font-size: 10px;
      font-weight: 700;
      color: #ffffff;
      padding: 2px 6px;
      border-radius: 4px;
      min-width: 36px;
      text-align: center;
    }

    .option-text {
      flex: 1;
      display: flex;
      flex-direction: column;

      .option-name {
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
      }
      .option-time {
        font-size: 11px;
        color: #64748b;
      }
    }

    .check-icon {
      color: #14746f;
      font-size: 14px;
    }
  }

  .quick-actions-footer {
    border-top: 1px solid #f1f5f9;
    padding-top: 10px;
    margin-top: 10px;
    display: flex;
    justify-content: flex-end;

    .btn-clear-shift {
      width: 100%;
    }
  }
}

/* ========================================================
   BULK ASSIGN DRAWER REDESIGN (ENTERPRISE WORKFLOW SUITE)
   ======================================================== */
.bulk-drawer-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 24px;

  /* Scope Banner */
  .bulk-scope-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px 18px;

    .scope-main-content {
      flex: 1;

      .scope-title-line {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;

        .scope-area-title {
          font-size: 15px;
          font-weight: 700;
          color: #0f172a;
          letter-spacing: -0.01em;
        }

        .scope-client-tag {
          font-size: 11.5px;
          font-weight: 600;
          color: #14746f;
          background-color: #e7f1f0;
          padding: 2px 8px;
          border-radius: 4px;
        }
      }

      .scope-description {
        margin: 4px 0 0;
        font-size: 12.5px;
        color: #64748b;
        line-height: 1.4;
      }
    }

    .scope-count-badge {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 8px 14px;
      min-width: 90px;
      text-align: center;
      flex-shrink: 0;

      .count-val {
        font-size: 18px;
        font-weight: 700;
        color: #14746f;
        line-height: 1.1;
      }

      .count-label {
        font-size: 10.5px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        margin-top: 2px;
      }
    }
  }

  /* Step Section */
  .drawer-step-section {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;

    .section-top-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      flex-wrap: wrap;

      .section-title-wrap {
        display: flex;
        flex-direction: column;

        .title-counter-row {
          display: flex;
          align-items: center;
          gap: 10px;

          .selection-tag {
            font-size: 12px;
            font-weight: 600;
            color: #14746f;
            background: #e7f1f0;
            padding: 2px 8px;
            border-radius: 4px;
          }
        }

        .section-title {
          font-size: 14px;
          font-weight: 700;
          color: #0f172a;
          letter-spacing: -0.01em;
        }

        .section-hint {
          font-size: 12px;
          color: #64748b;
          margin-top: 2px;
        }
      }

      .preset-pill-group {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;

        .preset-pill-btn {
          border: 1px solid #e2e8f0;
          background: #ffffff;
          color: #475569;
          font-size: 11.5px;
          font-weight: 500;
          padding: 4px 10px;
          border-radius: 5px;
          cursor: pointer;
          transition: all 0.12s ease;

          &:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
            color: #0f172a;
          }

          &.is-active {
            background-color: #14746f;
            border-color: #14746f;
            color: #ffffff;
            font-weight: 600;
          }
        }
      }

      .emp-action-links {
        display: flex;
        align-items: center;
        gap: 6px;

        .emp-link-btn {
          background: none;
          border: none;
          color: #14746f;
          font-size: 12px;
          font-weight: 600;
          cursor: pointer;
          padding: 0;

          &:hover {
            text-decoration: underline;
          }
        }

        .emp-link-sep {
          color: #cbd5e1;
          font-size: 10px;
        }
      }
    }
  }

  /* STEP 1: Shift Selection Cards */
  .shift-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;

    .shift-card-tile {
      border: 1px solid #e2e8f0;
      background: #ffffff;
      border-radius: 6px;
      padding: 12px;
      cursor: pointer;
      transition: all 0.12s ease;
      display: flex;
      flex-direction: column;
      gap: 6px;

      &:hover {
        border-color: #cbd5e1;
        background-color: #f8fafc;
      }

      &.is-selected {
        border-color: #14746f;
        background-color: #f0fdf4;
        box-shadow: 0 0 0 1px #14746f;
      }

      .shift-tile-header {
        display: flex;
        justify-content: space-between;
        align-items: center;

        .shift-tile-badge {
          font-size: 10px;
          font-weight: 700;
          color: #ffffff;
          padding: 2px 7px;
          border-radius: 4px;
          letter-spacing: 0.02em;
        }

        .tile-check-indicator {
          width: 18px;
          height: 18px;
          border-radius: 50%;
          background-color: #14746f;
          color: #ffffff;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 11px;
        }
      }

      .shift-tile-name {
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .shift-tile-time {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        color: #64748b;

        .el-icon {
          font-size: 12px;
          color: #94a3b8;
        }
      }
    }
  }

  /* STEP 2: Date Picker */
  .date-picker-container {
    width: 100%;
  }

  /* STEP 3: Workday Pattern & Weekend */
  .workday-row-wrap {
    display: flex;
    flex-direction: column;
    gap: 8px;

    .field-item-label {
      font-size: 12.5px;
      font-weight: 600;
      color: #334155;
    }

    .day-chips-cluster {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;

      .day-chip-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 500;
        color: #475569;
        cursor: pointer;
        transition: all 0.12s ease;

        &:hover {
          border-color: #cbd5e1;
          background-color: #f8fafc;
        }

        &.is-active {
          background-color: #e7f1f0;
          border-color: #14746f;
          color: #14746f;
          font-weight: 600;

          .day-chip-icon {
            font-size: 12px;
            color: #14746f;
          }
        }
      }
    }
  }

  .weekend-rules-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;

    .weekend-rule-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 12px 14px;
      display: flex;
      flex-direction: column;
      gap: 10px;

      .weekend-card-label {
        display: flex;
        flex-direction: column;

        .rule-day-title {
          font-size: 13px;
          font-weight: 600;
          color: #0f172a;
        }

        .rule-day-desc {
          font-size: 11.5px;
          color: #64748b;
          margin-top: 1px;
        }
      }

      .weekend-radio-group {
        width: 100%;
        display: flex;

        :deep(.el-radio-button) {
          flex: 1;

          .el-radio-button__inner {
            width: 100%;
            padding: 7px 10px;
            font-size: 12px;
          }
        }
      }
    }
  }

  /* STEP 4: Employee Selection */
  .emp-filter-toolbar {
    display: flex;
    flex-direction: column;
    gap: 8px;

    .emp-search-field {
      width: 100%;
    }

    .emp-jabatan-pills {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;

      .jab-filter-chip {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 11.5px;
        font-weight: 500;
        padding: 3px 9px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.12s ease;

        &:hover {
          border-color: #cbd5e1;
          color: #0f172a;
        }

        &.is-active {
          background-color: #14746f;
          border-color: #14746f;
          color: #ffffff;
          font-weight: 600;
        }
      }
    }
  }

  .emp-selection-viewport {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    max-height: 280px;
    overflow-y: auto;
    padding-right: 4px;

    .emp-choice-tile {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border: 1px solid #e2e8f0;
      background: #ffffff;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.12s ease;
      user-select: none;

      &:hover {
        border-color: #cbd5e1;
        background-color: #f8fafc;
      }

      &.is-selected {
        border-color: #14746f;
        background-color: #f0fdf4;

        .choice-checkbox {
          background-color: #14746f;
          border-color: #14746f;
          color: #ffffff;
        }
      }

      .choice-checkbox {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        flex-shrink: 0;
      }

      .choice-avatar {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .choice-info {
        flex: 1;
        overflow: hidden;

        .choice-name {
          font-weight: 600;
          font-size: 12.5px;
          color: #0f172a;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .choice-meta {
          font-size: 11px;
          color: #64748b;
          display: flex;
          align-items: center;
          gap: 4px;
          margin-top: 1px;

          .choice-nip {
            font-variant-numeric: tabular-nums;
          }

          .choice-dot {
            color: #cbd5e1;
            font-size: 8px;
          }

          .choice-pos {
            color: #475569;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
        }
      }
    }

    .empty-emp-message {
      grid-column: span 2;
      padding: 24px;
      text-align: center;
      font-size: 12.5px;
      color: #64748b;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;

      .el-icon {
        font-size: 20px;
        color: #94a3b8;
      }
    }
  }

  /* STEP 5: Overwrite & Summary */
  .section-summary-wrap {
    background: #f8fafc;
    gap: 16px;

    .overwrite-toggle-box {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;

      .overwrite-text-block {
        display: flex;
        flex-direction: column;

        .overwrite-title {
          font-size: 13px;
          font-weight: 600;
          color: #0f172a;
        }

        .overwrite-description {
          font-size: 11.5px;
          color: #64748b;
          margin-top: 2px;
          line-height: 1.4;
        }
      }
    }

    .assignment-preview-card {
      background: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 14px 16px;
      display: flex;
      flex-direction: column;
      gap: 10px;

      .preview-card-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;

        .el-icon {
          color: #14746f;
        }
      }

      .preview-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;

        .metric-cell {
          display: flex;
          flex-direction: column;

          .metric-label {
            font-size: 10.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.02em;
          }

          .metric-val {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-top: 2px;

            &.text-brand {
              color: #14746f;
            }

            &.text-accent {
              color: #0284c7;
            }
          }
        }
      }
    }
  }

  /* Sticky Footer */
  .bulk-sticky-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding-top: 14px;
    border-top: 1px solid #e2e8f0;
    margin-top: 8px;

    .footer-status-text {
      font-size: 12.5px;
      color: #64748b;

      strong {
        color: #0f172a;
      }

      &.text-warning {
        color: #d97706;
      }
    }

    .footer-action-buttons {
      display: flex;
      align-items: center;
      gap: 10px;
    }
  }
}

/* Master Shift Management */
.shift-mgmt-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  .subtitle {
    margin: 0;
    color: #64748b;
    font-size: 13px;
  }
}

.color-picker-row {
  display: flex;
  align-items: center;
  gap: 10px;

  .color-hex-text {
    font-size: 13px;
    font-family: monospace;
    font-weight: 600;
    color: #334155;
  }
}

@media (max-width: 900px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;

    .header-actions {
      flex-wrap: wrap;
    }
  }

  .control-panel .selector-row {
    flex-direction: column;
    align-items: stretch;

    .control-item {
      width: 100% !important;
      .control-select, .highlight-select {
        width: 100% !important;
      }
    }
  }

  .summary-kpi-bar .kpi-metrics-group {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .summary-kpi-bar .kpi-metrics-group {
    grid-template-columns: 1fr;
  }
}
</style>
