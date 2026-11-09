<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-left">
        <AppHeading level="1">{{ isEdit ? 'Edit Menu' : 'Tambah Menu' }}</AppHeading>
        <div class="breadcrumb mt-2">
          <router-link to="/dashboard/konfigurasi/menu" class="back-link">List Menu</router-link> 
          <span class="separator">/</span> 
          <span class="active">{{ isEdit ? 'Edit' : 'Tambah' }}</span>
        </div>
      </div>
    </div>

    <el-card class="box-card mt-4">
      <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
        <el-row :gutter="24">
          <el-col :span="12">
            <el-form-item label="Nama Menu" prop="name">
              <el-input v-model="form.name" placeholder="Masukan nama menu" size="large" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Path URL" prop="path">
              <el-input v-model="form.path" placeholder="Contoh: /dashboard/konfigurasi/pengguna" size="large" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="24">
          <el-col :span="12">
            <el-form-item label="Parent Menu" prop="parent_id">
              <el-select v-model="form.parent_id" placeholder="Pilih parent menu (Opsional)" size="large" clearable style="width: 100%;" @change="handleParentChange">
                <el-option v-for="item in parentOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item v-if="!form.parent_id" label="Icon" prop="icon">
              <div style="display: flex; gap: 12px; align-items: center;">
                <el-button @click="isIconModalVisible = true" size="large">
                  <template v-if="form.icon">
                    <i v-if="form.icon.startsWith('fa-')" :class="[form.icon, 'me-2']"></i>
                    <el-icon v-else class="me-2"><component :is="form.icon" /></el-icon>
                  </template>
                  {{ form.icon || 'Pilih Icon' }}
                </el-button>
                <el-button v-if="form.icon" text type="danger" @click="form.icon = ''">Hapus</el-button>
              </div>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="24">
          <el-col :span="12">
            <el-form-item label="Urutan" prop="order">
              <el-input-number v-model="form.order" :min="1" size="large" style="width: 100%" />
              <div v-if="orderConflictInfo" class="smart-order-hint">
                <el-icon class="hint-icon"><InfoFilled /></el-icon>
                <span>
                  Urutan <strong>{{ form.order }}</strong> saat ini digunakan oleh menu <strong>"{{ orderConflictInfo.name }}"</strong>.
                  <template v-if="isEdit">
                    Menyimpan form akan menukar posisi menu tersebut menjadi urutan <strong>{{ originalOrder }}</strong>.
                  </template>
                  <template v-else>
                    Menyimpan form akan menyisipkan menu ini di urutan <strong>{{ form.order }}</strong> dan menggeser menu tersebut (+1).
                  </template>
                </span>
              </div>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Status">
              <div style="display: flex; align-items: center; gap: 12px; height: 40px;">
                <el-switch
                  v-model="form.isActive"
                  inline-prompt
                  active-text="Aktif"
                  inactive-text="Nonaktif"
                  style="--el-switch-on-color: #10b981; --el-switch-off-color: #ef4444;"
                />
                <span style="font-size: 14px; font-weight: 500;" :style="{ color: form.isActive ? '#10b981' : '#ef4444' }">
                  {{ form.isActive ? 'Aktif' : 'Nonaktif' }}
                </span>
              </div>
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left" class="mt-5 mb-4">Hak Akses (Aksi)</el-divider>
        <div class="action-section">
          <p class="subtitle" style="margin-bottom: 16px; font-size: 0.85rem;">Atur aksi/permission (Create, Read, Update, Delete) untuk menu ini.</p>
          
          <div style="display: flex; gap: 12px; align-items: flex-end; margin-bottom: 16px;">
            <el-form-item label="Kode Aksi" style="flex: 1; margin-bottom: 0;">
              <el-input v-model="actionForm.code" placeholder="contoh: create" />
            </el-form-item>
            <el-form-item label="Nama Aksi" style="flex: 1; margin-bottom: 0;">
              <el-input v-model="actionForm.name" placeholder="contoh: Tambah Data" />
            </el-form-item>
            <el-button type="danger" plain @click="addAction" :disabled="!actionForm.code || !actionForm.name">Tambah</el-button>
          </div>

          <div class="action-list">
            <el-empty v-if="!form.actions || form.actions.length === 0" description="Belum ada aksi" :image-size="60" />
            <div v-else class="action-item" v-for="(action, index) in form.actions" :key="index">
              <div class="action-info">
                <span class="action-name">{{ action.name }}</span>
                <el-tag size="small" type="info">{{ action.code }}</el-tag>
              </div>
              <el-button text type="danger" @click="removeAction(index)" class="action-delete-btn">
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px;">
          <el-button @click="router.push('/dashboard/konfigurasi/menu')" size="large">Batal</el-button>
          <el-button type="danger" color="#ef4444" @click="saveForm" :loading="saving" size="large">Simpan</el-button>
        </div>
      </el-form>
    </el-card>

    <!-- Modal Icon Picker -->
    <el-dialog v-model="isIconModalVisible" title="Pilih Icon" width="700px" align-center>
      <div style="margin-bottom: 16px;">
        <el-input v-model="searchIconQuery" placeholder="Cari icon (contoh: user, document)" :prefix-icon="Search" clearable />
      </div>
      
      <el-tabs v-model="activeIconTab">
        <el-tab-pane label="Element Plus" name="element">
          <div class="icon-grid">
            <div v-for="icon in filteredElementIcons" :key="icon" class="icon-item" :class="{ 'active': form.icon === icon }" @click="selectIcon(icon)">
              <el-icon :size="24"><component :is="icon" /></el-icon>
              <span class="icon-name">{{ icon }}</span>
            </div>
          </div>
          <div v-if="filteredElementIcons.length === 0" style="text-align: center; padding: 32px; color: #6b7280;">
            Icon tidak ditemukan
          </div>
        </el-tab-pane>
        
        <el-tab-pane label="Font Awesome" name="fontawesome">
          <div class="icon-grid">
            <div v-for="icon in filteredFaIcons" :key="icon" class="icon-item" :class="{ 'active': form.icon === icon }" @click="selectIcon(icon)">
              <i :class="icon" style="font-size: 24px; margin-bottom: 8px;"></i>
              <span class="icon-name" style="font-size: 11px;">{{ icon.replace('fa-solid fa-', '').replace('fa-brands fa-', '') }}</span>
            </div>
          </div>
          <div v-if="filteredFaIcons.length === 0" style="text-align: center; padding: 32px; color: #6b7280;">
            Icon tidak ditemukan
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search, Delete, InfoFilled } from '@element-plus/icons-vue'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import { showSuccessToast, showErrorToast } from '@/utils/toast'
import { ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import AppHeading from '@/components/common/AppHeading.vue'
import { useMenuStore } from '@/store/menu'
import faIcons from '@/utils/fa-icons.json'

const route = useRoute()
const router = useRouter()
const menuStore = useMenuStore()

const isEdit = computed(() => route.name === 'KonfigurasiMenuEdit')
const saving = ref(false)
const parentOptions = ref<{label: string, value: string}[]>([])

const originalOrder = ref<number>(1)
const originalParentId = ref<string | null>(null)

const formRef = ref<FormInstance>()
const form = reactive({
  id: '',
  name: '',
  path: '',
  icon: '',
  parent_id: '',
  order: 1,
  isActive: true,
  actions: [] as any[]
})

const orderConflictInfo = computed(() => {
  const targetParent = form.parent_id || null
  const targetOrder = Number(form.order)
  if (!targetOrder) return null

  if (isEdit.value && targetOrder === originalOrder.value && targetParent === (originalParentId.value || null)) {
    return null
  }

  return menuStore.findSiblingByOrder(targetOrder, targetParent, isEdit.value ? form.id : undefined)
})

const actionForm = reactive({ code: '', name: '' })

const rules = reactive<FormRules>({
  name: [{ required: true, message: 'Nama menu wajib diisi', trigger: 'change' }],
  path: [{ required: true, message: 'Path URL wajib diisi', trigger: 'change' }]
})

const isIconModalVisible = ref(false)
const searchIconQuery = ref('')
const activeIconTab = ref('element')

const allElementIcons = Object.keys(ElementPlusIconsVue)
const allFaIcons = faIcons

const filteredElementIcons = computed(() => {
  if (!searchIconQuery.value) return allElementIcons
  return allElementIcons.filter(icon => icon.toLowerCase().includes(searchIconQuery.value.toLowerCase()))
})

const filteredFaIcons = computed(() => {
  if (!searchIconQuery.value) return allFaIcons
  return allFaIcons.filter(icon => icon.toLowerCase().includes(searchIconQuery.value.toLowerCase()))
})

onMounted(async () => {
  if (menuStore.data.length === 0) {
    await menuStore.getData({})
  }
  parentOptions.value = menuStore.getParentMenus()

  if (isEdit.value) {
    const menuId = route.params.id as string
    const findMenuRec = (items: any[], id: string): any => {
      for (const item of items) {
        if (item.id === id) return item
        if (item.children) {
          const found = findMenuRec(item.children, id)
          if (found) return found
        }
      }
      return null
    }

    const menu = findMenuRec(menuStore.data, menuId)
    if (menu) {
      form.id = menu.id
      form.name = menu.name
      form.path = menu.path
      form.icon = menu.icon || ''
      form.parent_id = menu.parent_id || ''
      form.order = Number(menu.order)
      originalOrder.value = Number(menu.order)
      originalParentId.value = menu.parent_id || null
      form.isActive = menu.status === 'Aktif'
      form.actions = menu.actions ? [...menu.actions] : []
    }
  } else {
    form.order = menuStore.getNextAvailableOrder(null)
  }
})

const selectIcon = (iconName: string) => {
  form.icon = iconName
  isIconModalVisible.value = false
}

const handleParentChange = (val: string) => {
  if (val) {
    form.icon = ''
  }
  if (!isEdit.value) {
    form.order = menuStore.getNextAvailableOrder(val || null)
  }
}

const addAction = () => {
  if (actionForm.code && actionForm.name) {
    if (form.actions.some(a => a.code === actionForm.code)) {
      showErrorToast('Kode aksi sudah ada!')
      return
    }
    form.actions.push({ code: actionForm.code, name: actionForm.name })
    actionForm.code = ''
    actionForm.name = ''
  }
}

const removeAction = (index: number) => {
  form.actions.splice(index, 1)
}

const saveForm = async () => {
  if (!formRef.value || saving.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  // Smart Sorting Consent Check (Rule 2)
  if (orderConflictInfo.value) {
    const conflictName = orderConflictInfo.value.name
    const targetOrder = form.order
    const isSwap = isEdit.value

    const confirmTitle = isSwap ? 'Konfirmasi Penukaran Urutan Menu' : 'Konfirmasi Penggeseran Urutan Menu'
    const confirmHtml = isSwap
      ? `Urutan <strong>${targetOrder}</strong> saat ini digunakan oleh menu <strong>"${conflictName}"</strong>.<br><br>Apakah Anda setuju untuk menukar urutan menu <strong>"${conflictName}"</strong> menjadi urutan <strong>${originalOrder.value}</strong>?`
      : `Urutan <strong>${targetOrder}</strong> sudah digunakan oleh menu <strong>"${conflictName}"</strong>.<br><br>Apakah Anda setuju untuk menyisipkan menu baru ini di urutan <strong>${targetOrder}</strong> dan menggeser posisi menu <strong>"${conflictName}"</strong> beserta menu setelahnya (+1)?`

    try {
      await ElMessageBox.confirm(confirmHtml, confirmTitle, {
        confirmButtonText: 'Ya, Setuju & Simpan',
        cancelButtonText: 'Batal',
        dangerouslyUseHTMLString: true,
        type: 'warning',
        confirmButtonClass: 'el-button--danger'
      })
    } catch {
      return
    }
  }

  saving.value = true
  try {
    const payload = {
      name: form.name,
      path: form.path,
      icon: form.parent_id ? '' : form.icon,
      parent_id: form.parent_id || null,
      order: form.order,
      status: form.isActive ? 'Aktif' : 'Nonaktif',
      actions: form.actions,
      confirm_swap: true,
      swap: true,
      confirm_shift: true
    }

    if (isEdit.value) {
      await menuStore.update(form.id, payload)
      showSuccessToast('Menu berhasil diperbarui')
    } else {
      await menuStore.create(payload)
      showSuccessToast('Menu berhasil ditambahkan')
    }
    router.push('/dashboard/konfigurasi/menu')
  } catch (error: any) {
    const message = error.response?.data?.message || 'Gagal menyimpan menu'
    showErrorToast(message)
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

.action-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.action-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  background-color: #F9FAFB;
}
.action-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.action-name {
  font-weight: 500;
  color: #111827;
}
.action-delete-btn {
  padding: 8px;
}

.smart-order-hint {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 8px;
  padding: 10px 14px;
  background-color: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 8px;
  color: #92400e;
  font-size: 13px;
  line-height: 1.45;
}

.smart-order-hint .hint-icon {
  margin-top: 2px;
  color: #d97706;
  font-size: 16px;
  flex-shrink: 0;
}
</style>
