<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <div class="title-wrapper">
          <AppHeading level="1">Daftar Menu</AppHeading>
          <el-tag type="info" class="total-badge">Total data: {{ meta.total }}</el-tag>
        </div>
        <p class="subtitle">Kelola daftar menu aplikasi</p>
      </div>
      <div class="header-right">
        <AppButton v-if="can('Menu', 'create')" variant="primary" @click="router.push('/dashboard/konfigurasi/menu/create')">Tambah menu</AppButton>
      </div>
    </div>

    <div class="filter-section">
      <el-input aria-label="Kotak pencarian" v-model="searchQuery" placeholder="Cari menu" :prefix-icon="Search" class="search-input" @input="fetchData" />
      <div class="filter-actions">
        <el-select v-model="activeFilter" placeholder="Status" clearable class="custom-select" @change="fetchData">
          <el-option label="Aktif" value="Aktif" />
          <el-option label="Nonaktif" value="Nonaktif" />
        </el-select>
      </div>
    </div>

    <DataTable 
      :data="data" 
      :loading="isLoading" 
      :empty-image="EmptyStateImage" 
      :total="meta.total" 
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      @update:current-page="fetchData"
      @update:page-size="fetchData"
      row-key="id"
      default-expand-all
      :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
    >
      <el-table-column prop="name" label="Nama Menu" min-width="200" />
      <el-table-column prop="path" label="Path URL" min-width="200" />
      <el-table-column prop="icon" label="Icon" min-width="120" align="center">
        <template #default="scope">
          <template v-if="!scope.row.parent_id && scope.row.icon">
            <i v-if="scope.row.icon.startsWith('fa-')" :class="scope.row.icon" style="font-size: 20px;"></i>
            <el-icon v-else :size="20">
              <component :is="scope.row.icon" />
            </el-icon>
          </template>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column prop="order" label="Urutan" min-width="120" align="center">
        <template #default="scope">
          <div class="order-cell">
            <span class="order-badge">{{ scope.row.order }}</span>
            <div v-if="can('Menu', 'update')" class="order-actions">
              <el-tooltip content="Pindah ke Atas (Tukar)" placement="top">
                <el-button 
                  text 
                  circle 
                  size="small" 
                  class="order-btn"
                  :disabled="isFirstSibling(scope.row)" 
                  @click.stop="quickMove(scope.row, 'up')"
                >
                  <el-icon><Top /></el-icon>
                </el-button>
              </el-tooltip>
              <el-tooltip content="Pindah ke Bawah (Tukar)" placement="top">
                <el-button 
                  text 
                  circle 
                  size="small" 
                  class="order-btn"
                  :disabled="isLastSibling(scope.row)" 
                  @click.stop="quickMove(scope.row, 'down')"
                >
                  <el-icon><Bottom /></el-icon>
                </el-button>
              </el-tooltip>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="status" label="Status" min-width="140">
        <template #default="scope">
          <div class="status-cell">
            <el-switch
              v-if="can('Menu', 'update')"
              :model-value="scope.row.status === 'Aktif'"
              :loading="togglingId === scope.row.id"
              inline-prompt
              active-text="Aktif"
              inactive-text="Nonaktif"
              style="--el-switch-on-color: #10b981; --el-switch-off-color: #ef4444;"
              @change="() => requestToggleStatus(scope.row)"
            />
            <el-tag
              v-else
              :type="scope.row.status === 'Aktif' ? 'success' : 'danger'"
              effect="light"
              class="status-badge"
            >
              {{ scope.row.status }}
            </el-tag>
          </div>
        </template>
      </el-table-column>
      <el-table-column v-if="can('Menu', 'update') || can('Menu', 'delete')" label="Action" width="80" align="center">
        <template #default="scope">
          <el-dropdown trigger="click" placement="bottom-end">
            <el-button text class="action-more-btn">
              <el-icon><MoreFilled /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-if="can('Menu', 'update')" @click="requestToggleStatus(scope.row)">
                  <el-icon><SwitchButton /></el-icon> {{ scope.row.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                </el-dropdown-item>
                <el-dropdown-item v-if="can('Menu', 'update')" @click="router.push(`/dashboard/konfigurasi/menu/${scope.row.id}/edit`)">
                  <el-icon><Edit /></el-icon> Edit
                </el-dropdown-item>
                <el-dropdown-item v-if="can('Menu', 'delete')" @click="openDeleteModal(scope.row)">
                  <el-icon><Delete /></el-icon> Hapus
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </DataTable>

    <!-- Modal Konfirmasi Status -->
    <el-dialog v-model="isStatusModalVisible" width="460px" align-center class="custom-status-modal" title="Ubah Status Menu">
      <div class="delete-content" style="padding-top: 8px;">
        <p v-if="targetStatusMenu?.status === 'Aktif'">
          Apakah Anda yakin ingin menonaktifkan menu <strong>{{ targetStatusMenu?.name }}</strong>?
          <span v-if="targetStatusMenu?.children && targetStatusMenu.children.length > 0" style="display: block; margin-top: 8px; color: #ef4444; font-size: 13px;">
            Seluruh sub-menu di dalamnya juga akan ikut dinonaktifkan dan disembunyikan dari navigasi.
          </span>
        </p>
        <p v-else>
          Apakah Anda yakin ingin mengaktifkan kembali menu <strong>{{ targetStatusMenu?.name }}</strong>?
        </p>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button text @click="isStatusModalVisible = false">Batal</el-button>
          <el-button
            :type="targetStatusMenu?.status === 'Aktif' ? 'danger' : 'primary'"
            :loading="togglingId === targetStatusMenu?.id"
            @click="confirmToggleStatus"
          >
            {{ targetStatusMenu?.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Modal Delete -->
    <el-dialog v-model="isDeleteModalVisible" width="480px" align-center class="custom-delete-modal" title="Konfirmasi Hapus">
      <div class="delete-content" style="padding-top: 8px;">
        Apakah Anda yakin ingin menghapus menu <strong>{{ selectedMenu?.name }}</strong>?
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button text @click="isDeleteModalVisible = false">Batal</el-button>
          <el-button type="danger" color="#ef4444" @click="confirmDelete" :loading="saving">Hapus</el-button>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { Search, MoreFilled, Edit, Delete, Operation, SwitchButton, Top, Bottom } from '@element-plus/icons-vue'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'
import { showSuccessToast, showErrorToast } from '@/utils/toast'

import EmptyStateImage from '@/assets/empty-state.svg'
import DataTable from '@/components/common/DataTable.vue'
import AppHeading from '@/components/common/AppHeading.vue'
import AppButton from '@/components/common/AppButton.vue'
import { useMenuStore } from '@/store/menu'
import { usePermissions } from '@/composables/usePermissions'

const router = useRouter()
const menuStore = useMenuStore()
const { can } = usePermissions()
const { data, meta } = storeToRefs(menuStore)

const isLoading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const searchQuery = ref('')
const activeFilter = ref('')

const saving = ref(false)

const isDeleteModalVisible = ref(false)
const selectedMenu = ref<any>(null)

const fetchData = async () => {
  isLoading.value = true
  await menuStore.getData({ search: searchQuery.value, status: activeFilter.value, page: currentPage.value, limit: pageSize.value })
  isLoading.value = false
}

onMounted(() => {
  fetchData()
})

const togglingId = ref<string | null>(null)
const isStatusModalVisible = ref(false)
const targetStatusMenu = ref<any>(null)

const requestToggleStatus = (row: any) => {
  targetStatusMenu.value = row
  isStatusModalVisible.value = true
}

const confirmToggleStatus = async () => {
  if (!targetStatusMenu.value) return
  const menu = targetStatusMenu.value
  togglingId.value = menu.id
  try {
    await menuStore.toggleStatus(menu.id)
    showSuccessToast(`Menu "${menu.name}" berhasil ${menu.status === 'Aktif' ? 'dinonaktifkan' : 'diaktifkan'}`)
    isStatusModalVisible.value = false
    await fetchData()
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal mengubah status menu')
  } finally {
    togglingId.value = null
  }
}

const openDeleteModal = (row: any) => {
  selectedMenu.value = row
  isDeleteModalVisible.value = true
}

const confirmDelete = async () => {
  saving.value = true
  try {
    await menuStore.remove(selectedMenu.value.id)
    showSuccessToast('Menu berhasil dihapus')
    isDeleteModalVisible.value = false
    fetchData()
  } catch (error) {
    showErrorToast('Gagal menghapus menu')
  } finally {
    saving.value = false
  }
}

const getSiblingGroup = (row: any) => {
  return menuStore.getSiblings(row.parent_id || null)
    .slice()
    .sort((a, b) => Number(a.order) - Number(b.order))
}

const isFirstSibling = (row: any) => {
  const siblings = getSiblingGroup(row)
  return siblings.length > 0 && String(siblings[0].id) === String(row.id)
}

const isLastSibling = (row: any) => {
  const siblings = getSiblingGroup(row)
  return siblings.length > 0 && String(siblings[siblings.length - 1].id) === String(row.id)
}

const quickMove = async (row: any, direction: 'up' | 'down') => {
  const siblings = getSiblingGroup(row)
  const currentIndex = siblings.findIndex(s => String(s.id) === String(row.id))
  if (currentIndex === -1) return

  const targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1
  if (targetIndex < 0 || targetIndex >= siblings.length) return

  const targetMenu = siblings[targetIndex]
  const currentOrder = Number(row.order)
  const targetOrder = Number(targetMenu.order)

  // Rule 2 consent confirmation
  try {
    await ElMessageBox.confirm(
      `Apakah Anda setuju untuk menukar urutan menu <strong>"${row.name}"</strong> (urutan ${currentOrder}) dengan menu <strong>"${targetMenu.name}"</strong> (urutan ${targetOrder})?`,
      'Konfirmasi Penukaran Urutan Menu',
      {
        confirmButtonText: 'Ya, Setuju & Tukar',
        cancelButtonText: 'Batal',
        dangerouslyUseHTMLString: true,
        type: 'warning',
        confirmButtonClass: 'el-button--danger'
      }
    )
  } catch {
    return
  }

  isLoading.value = true
  try {
    const res = await menuStore.reorder(row.id, targetOrder, true)
    showSuccessToast(res?.message || `Urutan menu "${row.name}" dan "${targetMenu.name}" berhasil ditukar.`)
    await fetchData()
  } catch (error: any) {
    showErrorToast(error?.response?.data?.message || 'Gagal menukar urutan menu')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.status-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
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
.subtitle {
  color: #6B7280;
  margin: 4px 0 0 0;
  font-size: 0.9rem;
}
.filter-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}
.search-input {
  width: 300px;
}
.status-badge {
  text-transform: capitalize;
}
.icon-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 16px;
  max-height: 400px;
  overflow-y: auto;
  padding: 8px;
}
.icon-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  border-radius: 8px;
  border: 1px solid #E5E7EB;
  cursor: pointer;
  transition: all 0.2s;
}
.icon-item:hover {
  background-color: #F3F4F6;
}
.icon-item.active {
  border-color: #ef4444;
  background-color: #fff1f2;
  color: #ef4444;
}
.icon-name {
  font-size: 11px;
  text-align: center;
  word-break: break-all;
}

.order-cell {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.order-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 24px;
  padding: 0 6px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  background-color: #f3f4f6;
  border: 1px solid #e5e7eb;
}

.order-actions {
  display: inline-flex;
  gap: 2px;
}

.order-btn {
  padding: 4px;
  color: #6b7280;
  transition: all 0.2s;
}

.order-btn:hover:not(:disabled) {
  color: #ef4444;
  background-color: #fef2f2;
}

</style>
