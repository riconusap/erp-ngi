import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/store/auth'

import DefaultLayout from '../layouts/DefaultLayout.vue'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login',
    meta: {
      pageTitle: 'Login'
    },
  },
  {
    path: '/login',
    name: 'Login',
    meta: {
      pageTitle: 'Login'
    },
    component: () => import('../views/LoginView.vue')
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    meta: {
      pageTitle: 'Lupa Password'
    },
    component: () => import('../views/ForgotPasswordView.vue')
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    meta: {
      pageTitle: 'Reset Password'
    },
    component: () => import('../views/ResetPasswordView.vue')
  },
  {
    path: '/success',
    name: 'Success',
    meta: {
      pageTitle: ''
    },
    component: () => import('../views/SuccessView.vue')
  },
  {
    path: '/admin',
    redirect: '/dashboard/overview'
  },
  {
    path: '/absensi',
    component: DefaultLayout,
    meta: {
      middleware: 'auth'
    },
    children: [
      {
        path: '',
        name: 'AbsensiDashboard',
        meta: {
          pageTitle: 'Presensi Pegawai'
        },
        component: () => import('../views/absensi/AbsensiDashboardView.vue')
      }
    ]
  },
  {
    path: '/dashboard',
    component: DefaultLayout,
    redirect: '/dashboard/overview',
    meta: {
      middleware: 'auth',
    },
    children: [
      {
        path: 'overview',
        name: 'DashboardOverview',
        component: () => import('../views/DashboardView.vue'),
        meta: {
          pageTitle: 'Dashboard'
        },
      },
      {
        path: 'profile',
        name: 'UserProfile',
        component: () => import('../views/profile/ProfileView.vue'),
        meta: {
          pageTitle: 'Profil Pengguna'
        },
      },

      {
        path: 'master-data',
        name: 'ListMasterData',
        component: () => import('../views/master-data/ListMasterDataView.vue'),
        meta: {
          pageTitle: 'Master Data'
        },
      },
      {
        path: 'master-data/pegawai',
        name: 'MasterDataPegawai',
        component: () => import('../views/master-data/PegawaiView.vue'),
        meta: {
          pageTitle: 'Pegawai'
        },
      },
      {
        path: 'master-data/jabatan',
        name: 'MasterDataJabatan',
        component: () => import('../views/master-data/JabatanView.vue'),
        meta: {
          pageTitle: 'Jabatan'
        },
      },
      {
        path: 'master-data/client-area',
        name: 'MasterDataClientArea',
        component: () => import('../views/master-data/ClientAreaView.vue'),
        meta: {
          pageTitle: 'Client & Area'
        },
      },
      {
        path: 'master-data/project',
        name: 'MasterDataProject',
        component: () => import('../views/master-data/ProjectView.vue'),
        meta: {
          pageTitle: 'Project'
        },
      },
      {
        path: 'master-data/kontrak-pegawai',
        name: 'MasterDataKontrakPegawai',
        component: () => import('../views/master-data/KontrakPegawaiView.vue'),
        meta: {
          pageTitle: 'Kontrak Pegawai'
        },
      },
      {
        path: 'master-data/jadwal-kerja',
        name: 'MasterDataJadwalKerja',
        component: () => import('../views/master-data/JadwalKerjaView.vue'),
        meta: {
          pageTitle: 'Jadwal Kerja Pegawai'
        },
      },
      {
        path: 'task-management',
        name: 'TaskManagement',
        component: () => import('../views/task-management/TaskManagementView.vue'),
        meta: {
          pageTitle: 'Task Management'
        },
      },
      {
        path: 'cuti',
        name: 'PengajuanCutiList',
        component: () => import('../views/cuti/CutiListView.vue'),
        meta: {
          pageTitle: 'Pengajuan Cuti'
        },
      },
      {
        path: 'cuti/pengajuan',
        name: 'PengajuanCutiCreate',
        component: () => import('../views/cuti/CutiFormView.vue'),
        meta: {
          pageTitle: 'Form Pengajuan Cuti'
        },
      },
      {
        path: 'konfigurasi/pengguna',
        name: 'KonfigurasiPengguna',
        component: () => import('../views/konfigurasi/PenggunaView.vue'),
        meta: {
          pageTitle: 'Pengguna'
        }
      },
      {
        path: 'konfigurasi/peran',
        name: 'KonfigurasiPeran',
        component: () => import('../views/konfigurasi/PeranView.vue'),
        meta: {
          pageTitle: 'Peran'
        }
      },
      {
        path: 'konfigurasi/peran/create',
        name: 'KonfigurasiPeranCreate',
        component: () => import('../views/konfigurasi/PeranFormView.vue'),
        meta: {
          pageTitle: 'Tambah Peran'
        }
      },
      {
        path: 'konfigurasi/peran/:id/edit',
        name: 'KonfigurasiPeranEdit',
        component: () => import('../views/konfigurasi/PeranFormView.vue'),
        meta: {
          pageTitle: 'Edit Peran'
        }
      },
      {
        path: 'konfigurasi/menu',
        name: 'KonfigurasiMenu',
        component: () => import('../views/konfigurasi/MenuView.vue'),
        meta: {
          pageTitle: 'Menu'
        }
      },
      {
        path: 'konfigurasi/menu/create',
        name: 'KonfigurasiMenuCreate',
        component: () => import('../views/konfigurasi/MenuFormView.vue'),
        meta: {
          pageTitle: 'Tambah Menu'
        }
      },
      {
        path: 'konfigurasi/menu/:id/edit',
        name: 'KonfigurasiMenuEdit',
        component: () => import('../views/konfigurasi/MenuFormView.vue'),
        meta: {
          pageTitle: 'Edit Menu'
        }
      },
      {
        path: 'konfigurasi/2fa',
        name: 'Konfigurasi2FA',
        component: () => import('../views/konfigurasi/DuaFAView.vue'),
        meta: {
          pageTitle: 'Pengaturan 2FA'
        }
      },
      {
        path: 'konfigurasi/kamus-data',
        name: 'KonfigurasiKamusData',
        component: () => import('../views/konfigurasi/KamusDataView.vue'),
        meta: {
          pageTitle: 'Kamus Data'
        }
      },
      {
        path: 'konfigurasi/kamus-data/create',
        name: 'KonfigurasiKamusDataCreate',
        component: () => import('../views/konfigurasi/KamusDataFormView.vue'),
        meta: {
          pageTitle: 'Tambah Kamus Data'
        }
      },
      {
        path: 'konfigurasi/kamus-data/edit/:id',
        name: 'KonfigurasiKamusDataEdit',
        component: () => import('../views/konfigurasi/KamusDataFormView.vue'),
        meta: {
          pageTitle: 'Edit Kamus Data'
        }
      },
      {
        path: 'data-dinamis',
        name: 'DynamicDataIndex',
        component: () => import('../views/data-dinamis/DynamicDataIndexView.vue'),
        meta: { pageTitle: 'Data Dinamis' }
      },
      {
        path: 'data-dinamis/:slug',
        name: 'DynamicDataList',
        component: () => import('../views/data-dinamis/DynamicDataListView.vue'),
        meta: { pageTitle: 'Daftar Data Dinamis' }
      },
      {
        path: 'data-dinamis/:slug/create',
        name: 'DynamicDataCreate',
        component: () => import('../views/data-dinamis/DynamicDataFormView.vue'),
        meta: { pageTitle: 'Tambah Data Dinamis' }
      },
      {
        path: 'data-dinamis/:slug/:id/edit',
        name: 'DynamicDataEdit',
        component: () => import('../views/data-dinamis/DynamicDataFormView.vue'),
        meta: { pageTitle: 'Edit Data Dinamis' }
      },
      {
        path: 'app-builder',
        name: 'AppBuilderIndex',
        component: () => import('../views/app-builder/AppBuilderIndexView.vue'),
        meta: { pageTitle: 'App Builder' }
      },
      {
        path: 'app-builder/create',
        name: 'AppBuilderCreate',
        component: () => import('../views/app-builder/AppBuilderCreateView.vue'),
        meta: { pageTitle: 'Buat Halaman' }
      },
      {
        path: 'app-builder/:id/edit',
        name: 'AppBuilderEdit',
        component: () => import('../views/app-builder/AppBuilderCreateView.vue'),
        meta: { pageTitle: 'Edit Halaman' }
      },
      {
        path: 'pages/view/:slug',
        name: 'AppRendererView',
        component: () => import('../views/app-builder/AppRendererView.vue'),
        meta: { pageTitle: 'View Page' }
      },
      {
        path: 'pages/new/:slug',
        name: 'AppRendererNew',
        component: () => import('../views/app-builder/AppRendererView.vue'),
        meta: { pageTitle: 'Buat Baru' }
      },
      {
        path: 'pages/edit/:slug/:id_data',
        name: 'AppRendererEdit',
        component: () => import('../views/app-builder/AppRendererView.vue'),
        meta: { pageTitle: 'Edit Data' }
      }
    ]
  },
  {
    path: '/form/:slug',
    name: 'PublicForm',
    component: () => import('../views/form-builder/PublicFormView.vue'),
    meta: { pageTitle: 'Form' }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../views/NotFoundView.vue')
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.VITE_APP_PREFIX),
  routes
})

import { usePermissions } from '@/composables/usePermissions'
import { useMenuStore } from '@/store/menu'
import { ElMessage } from 'element-plus'

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  document.title =
    `${import.meta.env.VITE_APP_NAME} - ${to.meta.pageTitle ?? ''}`

  if (
    to.meta.middleware === 'auth' &&
    !authStore.isAuthenticated
  ) {
    return {
      name: 'Login'
    }
  }

  if (authStore.isAuthenticated && !authStore.user) {
    try {
      await authStore.getProfile()
    } catch {
      await authStore.logout()
      return { name: 'Login' }
    }
  }

  if (
    to.name === 'Login' &&
    authStore.isAuthenticated
  ) {
    if (authStore.canAccessAdmin) {
      return '/dashboard/overview'
    }
    return authStore.hasPegawai ? '/absensi' : '/success'
  }

  // Guard Akses Presensi Pegawai: pastikan akun terhubung dengan data pegawai
  if (to.meta.middleware === 'auth' && to.path.startsWith('/absensi')) {
    const hasPegawai = !!(authStore.hasPegawai || authStore.user?.pegawai_id || authStore.user?.pegawai)
    if (!hasPegawai) {
      ElMessage.warning('Akses ditolak: Akun Anda tidak terhubung dengan data pegawai.')
      return authStore.canAccessAdmin ? '/dashboard/overview' : '/success'
    }
  }

  // Guard Akses Admin: pastikan user memiliki flagging can_access_admin untuk /admin atau /dashboard
  if (to.meta.middleware === 'auth') {
    if (to.path === '/admin' || to.path.startsWith('/dashboard')) {
      const canAccess = !!(authStore.canAccessAdmin || authStore.user?.role?.can_access_admin)
      if (!canAccess) {
        ElMessage.warning('Akses ditolak: Akun Anda tidak memiliki izin untuk mengakses Dashboard Admin.')
        return authStore.hasPegawai ? '/absensi' : '/success'
      }
    }
  }

  // Cek jika route yang dituju termasuk menu yang berstatus Nonaktif
  if (to.meta.middleware === 'auth' && to.path !== '/absensi' && to.path !== '/dashboard/profile') {
    const isMenuConfigRoute = to.path === '/dashboard/konfigurasi/menu' || to.path.startsWith('/dashboard/konfigurasi/menu/')
    if (!isMenuConfigRoute) {
      const menuStore = useMenuStore()
      if (menuStore.data.length === 0 && authStore.isAuthenticated) {
        const permMenus = authStore.user?.role?.permissions?.menus || authStore.user?.permissions?.menus
        if (permMenus && Array.isArray(permMenus)) {
          menuStore.setMenusFromPermissions(permMenus)
        }
      }

      const isTargetInactive = (items: any[]): boolean => {
        for (const item of items) {
          const itemPath = (item.path || '').toLowerCase()
          const currentPath = to.path.toLowerCase()
          if (itemPath && (currentPath === itemPath || currentPath.startsWith(itemPath + '/'))) {
            if (item.status === 'Nonaktif') return true
          }
          if (item.children && item.children.length > 0) {
            if (item.status === 'Nonaktif' && item.children.some((c: any) => {
              const cPath = (c.path || '').toLowerCase()
              return cPath && (currentPath === cPath || currentPath.startsWith(cPath + '/'))
            })) {
              return true
            }
            if (isTargetInactive(item.children)) return true
          }
        }
        return false
      }

      if (isTargetInactive(menuStore.data)) {
        ElMessage.warning('Menu atau modul ini sedang dinonaktifkan.')
        return authStore.canAccessAdmin ? '/dashboard/overview' : (authStore.hasPegawai ? '/absensi' : '/success')
      }
    }
  }

  // RBAC Route Guard
  if (to.meta.middleware === 'auth' && to.path !== '/absensi') {
    const { hasRouteAccess } = usePermissions()
    if (!hasRouteAccess(to.path)) {
      ElMessage.warning('Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman ini.')
      return authStore.canAccessAdmin ? '/dashboard/overview' : (authStore.hasPegawai ? '/absensi' : '/success')
    }
  }

  return true
})

export default router
