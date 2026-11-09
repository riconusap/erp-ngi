<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">{{ isEdit ? 'Edit Peran & Hak Akses' : 'Tambah Peran & Hak Akses' }}</AppHeading>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/konfigurasi/peran" class="back-link">List Peran</router-link> 
          <span class="separator">/</span> 
          <span class="active">{{ isEdit ? 'Edit' : 'Tambah' }}</span>
        </div>
      </div>
    </div>

    <el-card class="box-card mt-4">
      <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
        <div style="display: flex; gap: 24px; flex-wrap: wrap; align-items: flex-start;">
          <el-form-item label="Nama Peran" prop="name" style="flex: 1; min-width: 250px;">
            <el-input v-model="form.name" placeholder="Masukan nama peran (contoh: Staff)" size="large" />
          </el-form-item>

          <el-form-item label="Akses Admin">
            <div style="display: flex; align-items: center; gap: 10px; height: 40px;">
              <el-switch v-model="form.can_access_admin" />
              <span style="font-size: 13.5px; font-weight: 500; color: #1e293b;">
                {{ form.can_access_admin ? 'Dapat Akses Admin' : 'Khusus Absensi (Non-Admin)' }}
              </span>
            </div>
          </el-form-item>

          <el-form-item label="Status">
            <div style="display: flex; align-items: center; gap: 12px; height: 40px;">
              <span style="font-size: 14px;">Aktif</span>
              <el-switch v-model="form.isActive" />
            </div>
          </el-form-item>
        </div>

        <el-divider content-position="left" class="mt-5 mb-4">Pengaturan Hak Akses (Permissions)</el-divider>
        <div class="permissions-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <div>
            <p class="subtitle" style="margin: 0; font-size: 0.85rem;">
              Centang aksi yang diperbolehkan untuk peran ini pada setiap menu.
            </p>
          </div>
          <el-checkbox
            :model-value="isAllSelected"
            :indeterminate="isIndeterminate"
            @change="handleSelectAll"
            border
            class="global-select"
          >
            Pilih Semua Modul
          </el-checkbox>
        </div>

        <el-collapse v-model="activeNames" class="custom-collapse">
          <el-collapse-item v-for="parent in menuData" :key="parent.id" :name="parent.id">
            <template #title>
              <div class="collapse-title">
                <div class="title-left">
                  <el-icon v-if="parent.icon" class="module-icon"><component :is="parent.icon" /></el-icon>
                  <span class="module-name">{{ parent.name }}</span>
                  <el-tag v-if="parent.status === 'Nonaktif'" size="small" type="danger" effect="plain" class="ml-2">Nonaktif</el-tag>
                </div>
                <div class="title-right" @click.stop>
                  <el-tag size="small" :type="getSelectedCount(parent) === getTotalCount(parent) && getTotalCount(parent) > 0 ? 'success' : 'info'" round class="count-badge">
                    {{ getSelectedCount(parent) }} / {{ getTotalCount(parent) }} Akses
                  </el-tag>
                </div>
              </div>
            </template>
            
            <div class="module-content">
              <!-- Jika parent memiliki aksi sendiri -->
              <div class="permission-row">
                <div class="menu-info">
                  <span class="menu-name">
                    {{ parent.name }}
                    <el-tag size="small" type="info" class="ml-2">Utama</el-tag>
                    <el-tag v-if="parent.status === 'Nonaktif'" size="small" type="danger" effect="plain" class="ml-2">Nonaktif</el-tag>
                  </span>
                </div>
                <div class="menu-actions">
                  <el-checkbox
                    :model-value="isRowAllSelected(parent)"
                    :indeterminate="isRowIndeterminate(parent)"
                    @change="(val: boolean) => handleRowSelectAll(val, parent)"
                    class="select-all-row"
                  >
                    Semua
                  </el-checkbox>
                  <el-checkbox-group v-model="form.permissions[parent.id]" class="action-group">
                    <el-checkbox label="read" border class="default-read-cb">Lihat / Akses</el-checkbox>
                    <el-checkbox v-for="action in parent.actions?.filter(a => a.code !== 'read')" :key="action.code" :label="action.code" border>
                      {{ action.name }}
                    </el-checkbox>
                  </el-checkbox-group>
                </div>
              </div>

              <!-- Sub-menu -->
              <template v-if="parent.children && parent.children.length > 0">
                <div v-for="(child, index) in parent.children" :key="child.id" class="permission-row" :class="{'is-last': index === parent.children.length - 1}">
                  <div class="menu-info">
                    <span class="menu-name">
                      {{ child.name }}
                      <el-tag v-if="child.status === 'Nonaktif'" size="small" type="danger" effect="plain" class="ml-2">Nonaktif</el-tag>
                    </span>
                    <span class="menu-path">{{ child.path }}</span>
                  </div>
                  <div class="menu-actions">
                    <el-checkbox
                      :model-value="isRowAllSelected(child)"
                      :indeterminate="isRowIndeterminate(child)"
                      @change="(val: boolean) => handleRowSelectAll(val, child)"
                      class="select-all-row"
                    >
                      Semua
                    </el-checkbox>
                    <el-checkbox-group v-model="form.permissions[child.id]" class="action-group">
                      <el-checkbox label="read" border class="default-read-cb">Lihat / Akses</el-checkbox>
                      <el-checkbox v-for="action in child.actions?.filter(a => a.code !== 'read')" :key="action.code" :label="action.code" border>
                        {{ action.name }}
                      </el-checkbox>
                    </el-checkbox-group>
                  </div>
                </div>
              </template>
              
            </div>
          </el-collapse-item>
        </el-collapse>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px;">
          <el-button @click="router.push('/dashboard/konfigurasi/peran')" size="large">Batal</el-button>
          <el-button type="danger" color="#ef4444" @click="saveForm" :loading="saving" size="large">Simpan Peran</el-button>
        </div>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import type { FormInstance, FormRules } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import { useRoleStore } from '@/store/role'
import { useMenuStore } from '@/store/menu'

const route = useRoute()
const router = useRouter()
const roleStore = useRoleStore()
const menuStore = useMenuStore()

const isEdit = computed(() => route.name === 'KonfigurasiPeranEdit')
const saving = ref(false)
const activeNames = ref<string[]>([])

const formRef = ref<FormInstance>()
const form = reactive({
  id: '',
  name: '',
  can_access_admin: false,
  isActive: true,
  permissions: {} as Record<string, string[]>
})

const rules = reactive<FormRules>({
  name: [{ required: true, message: 'Nama peran wajib diisi', trigger: 'change' }]
})

const menuData = computed(() => menuStore.data)

// Fungsi untuk inisialisasi semua key menu_id di objek permissions
const initializePermissions = (menus: any[]) => {
  menus.forEach(menu => {
    if (!form.permissions[menu.id]) {
      form.permissions[menu.id] = []
    }
    if (menu.children) {
      initializePermissions(menu.children)
    }
  })
}

onMounted(async () => {
  // Pastikan data menu termuat untuk me-render tabel matrix permission
  if (menuStore.data.length === 0) {
    await menuStore.getData({})
  }
  
  // Inisialisasi struktur permission agar reactive
  initializePermissions(menuStore.data)

  // Pastikan data role termuat
  if (roleStore.data.length === 0) {
    await roleStore.getData({})
  }

  if (isEdit.value) {
    const roleId = route.params.id as string
    const role = roleStore.data.find(r => r.id === roleId)
    if (role) {
      form.id = role.id
      form.name = role.name
      form.can_access_admin = !!role.can_access_admin
      form.isActive = role.status === 'Aktif'
      
      // Merge permissions dari database ke form (untuk retain reaktifitas)
      if (role.permissions) {
        Object.keys(role.permissions).forEach(menuId => {
          form.permissions[menuId] = [...role.permissions[menuId]]
        })
      }
    }
  }

  // Buka semua panel accordion secara default
  activeNames.value = menuData.value.map(m => m.id)
})

// === Helper Counter untuk Badge Accordion ===
const getMenuActions = (menu: any) => {
  // Selalu anggap ada aksi 'read'
  const actions = menu.actions ? [...menu.actions] : []
  if (!actions.find((a: any) => a.code === 'read')) {
    actions.unshift({ code: 'read', name: 'Lihat / Akses' })
  }
  return actions
}

const getTotalCount = (parent: any) => {
  let total = getMenuActions(parent).length
  if (parent.children) {
    total += parent.children.reduce((acc: number, child: any) => acc + getMenuActions(child).length, 0)
  }
  return total
}

const getSelectedCount = (parent: any) => {
  let selected = form.permissions[parent.id]?.length || 0
  if (parent.children) {
    selected += parent.children.reduce((acc: number, child: any) => acc + (form.permissions[child.id]?.length || 0), 0)
  }
  return selected
}

// === Logika Select All (Global) ===
const allMenuWithActions = computed(() => {
  const result: any[] = []
  const traverse = (menus: any[]) => {
    menus.forEach(m => {
      // Sekarang SEMUA menu memiliki setidaknya aksi 'read'
      result.push(m)
      if (m.children) traverse(m.children)
    })
  }
  traverse(menuData.value)
  return result
})

const isAllSelected = computed(() => {
  if (allMenuWithActions.value.length === 0) return false
  return allMenuWithActions.value.every(m => form.permissions[m.id]?.length === getMenuActions(m).length)
})

const isIndeterminate = computed(() => {
  if (allMenuWithActions.value.length === 0) return false
  const totalActions = allMenuWithActions.value.reduce((acc, m) => acc + getMenuActions(m).length, 0)
  const selectedActions = allMenuWithActions.value.reduce((acc, m) => acc + (form.permissions[m.id]?.length || 0), 0)
  return selectedActions > 0 && selectedActions < totalActions
})

const handleSelectAll = (val: boolean) => {
  allMenuWithActions.value.forEach(m => {
    form.permissions[m.id] = val ? getMenuActions(m).map((a: any) => a.code) : []
  })
}

// === Logika Select All (Per Baris) ===
const isRowAllSelected = (row: any) => {
  const actions = getMenuActions(row)
  return form.permissions[row.id]?.length === actions.length
}

const isRowIndeterminate = (row: any) => {
  const actions = getMenuActions(row)
  const len = form.permissions[row.id]?.length || 0
  return len > 0 && len < actions.length
}

const handleRowSelectAll = (val: boolean, row: any) => {
  const actions = getMenuActions(row)
  if (val) {
    form.permissions[row.id] = actions.map((a: any) => a.code)
  } else {
    form.permissions[row.id] = []
  }
}

const saveForm = async () => {
  if (!formRef.value || saving.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    // Clean up empty arrays to save space
    const cleanPermissions: Record<string, string[]> = {}
    Object.keys(form.permissions).forEach(menuId => {
      if (form.permissions[menuId].length > 0) {
        cleanPermissions[menuId] = [...form.permissions[menuId]]
      }
    })

    const payload = {
      name: form.name,
      status: (form.isActive ? 'Aktif' : 'Nonaktif') as 'Aktif' | 'Nonaktif',
      can_access_admin: form.can_access_admin,
      permissions: cleanPermissions
    }

    if (isEdit.value) {
      await roleStore.update(form.id, payload)
      showSuccessToast('Peran berhasil diperbarui')
    } else {
      await roleStore.create(payload)
      showSuccessToast('Peran berhasil ditambahkan')
    }
    router.push('/dashboard/konfigurasi/peran')
  } catch (error) {
    showErrorToast('Gagal menyimpan peran')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.breadcrumb {
  font-size: 14px;
  color: #6B7280;
}
.breadcrumb .back-link {
  color: #111827;
  text-decoration: none;
  font-weight: 500;
  cursor: pointer;
}
.breadcrumb .back-link:hover {
  color: #ef4444;
}
.breadcrumb .separator {
  margin: 0 8px;
  color: #D1D5DB;
}
.breadcrumb .active {
  color: #6B7280;
}
.box-card {
  border-radius: 8px;
  border: 1px solid #E5E7EB;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.custom-collapse {
  border-top: none;
  border-bottom: none;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.custom-collapse :deep(.el-collapse-item) {
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  overflow: hidden;
}
.custom-collapse :deep(.el-collapse-item__header) {
  background-color: #F9FAFB;
  border-bottom: 1px solid #E5E7EB;
  padding: 0 16px;
  height: 56px;
  line-height: 56px;
}
.custom-collapse :deep(.el-collapse-item__header.is-active) {
  border-bottom-color: #E5E7EB;
}
.custom-collapse :deep(.el-collapse-item__wrap) {
  border-bottom: none;
}
.custom-collapse :deep(.el-collapse-item__content) {
  padding: 0;
}

.collapse-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding-right: 16px;
}
.title-left {
  display: flex;
  align-items: center;
  gap: 8px;
}
.module-icon {
  font-size: 18px;
  color: #6B7280;
}
.module-name {
  font-weight: 600;
  font-size: 15px;
  color: #111827;
}

.permission-row {
  display: flex;
  padding: 16px;
  border-bottom: 1px solid #F3F4F6;
}
.permission-row.is-last {
  border-bottom: none;
}
.permission-row:hover {
  background-color: #F9FAFB;
}

.menu-info {
  width: 250px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.menu-name {
  font-weight: 500;
  color: #374151;
  font-size: 14px;
  display: flex;
  align-items: center;
}
.menu-path {
  font-size: 12px;
  color: #9CA3AF;
}

.menu-actions {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 24px;
}
.select-all-row {
  font-weight: 600;
  margin-right: 0;
}
.action-group {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.action-group :deep(.el-checkbox) {
  margin-right: 0;
}
.empty-actions {
  color: #9CA3AF;
  font-size: 13px;
  font-style: italic;
}
.empty-module {
  padding: 24px;
  text-align: center;
  color: #6B7280;
  font-size: 14px;
  background-color: #F9FAFB;
}
</style>
