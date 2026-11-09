<template>
  <div class="notification-bell-container">
    <el-popover
      v-model:visible="isPopoverVisible"
      placement="bottom-end"
      :width="380"
      trigger="click"
      popper-class="notification-popover"
      :show-arrow="false"
      @show="onPopoverShow"
    >
      <template #reference>
        <div
          class="bell-btn"
          role="button"
          tabindex="0"
          aria-label="Pusat Notifikasi"
          :class="{ 'has-unread': unreadCount > 0 }"
        >
          <el-badge
            :value="unreadCount"
            :max="99"
            :hidden="unreadCount === 0"
            class="bell-badge"
          >
            <el-icon class="bell-icon"><Bell /></el-icon>
          </el-badge>
        </div>
      </template>

      <!-- Popover Content -->
      <div class="notif-dropdown">
        <!-- Header -->
        <div class="notif-header">
          <div class="header-left">
            <h4 class="notif-title">Notifikasi</h4>
            <div class="realtime-status" :title="isConnected ? 'Koneksi Realtime Aktif' : 'Menghubungkan...'">
              <span class="pulse-dot" :class="{ 'connected': isConnected }"></span>
              <span class="status-label">{{ isConnected ? 'Realtime' : 'Sinkron' }}</span>
            </div>
          </div>

          <div class="header-right">
            <el-button
              v-if="unreadCount > 0"
              text
              size="small"
              type="primary"
              @click="markAllAsRead"
              class="btn-mark-all"
            >
              Tandai Semua Dibaca
            </el-button>
          </div>
        </div>

        <!-- Filter Tabs -->
        <div class="notif-tabs">
          <button
            type="button"
            class="tab-btn"
            :class="{ active: activeFilter === 'all' }"
            @click="activeFilter = 'all'"
          >
            Semua ({{ notifications.length }})
          </button>
          <button
            type="button"
            class="tab-btn"
            :class="{ active: activeFilter === 'unread' }"
            @click="activeFilter = 'unread'"
          >
            Belum Dibaca ({{ unreadCount }})
          </button>
        </div>

        <!-- Notification List -->
        <div class="notif-body" v-loading="loading">
          <div v-if="filteredNotifications.length > 0" class="notif-list">
            <div
              v-for="item in filteredNotifications"
              :key="item.id"
              class="notif-item"
              :class="{ 'is-unread': !item.is_read }"
              @click="handleNotificationClick(item)"
            >
              <div class="notif-icon-box" :class="getIconBoxClass(item)">
                <el-icon><component :is="getIconComponent(item)" /></el-icon>
              </div>

              <div class="notif-content">
                <div class="notif-top-row">
                  <span class="notif-item-title">{{ item.title }}</span>
                  <span class="notif-time">{{ formatTimeAgo(item.created_at) }}</span>
                </div>

                <p class="notif-message">{{ item.message }}</p>

                <div class="notif-meta" v-if="item.data?.project_name">
                  <span class="meta-tag">
                    <el-icon class="me-1"><OfficeBuilding /></el-icon>
                    {{ item.data.project_name }}
                  </span>
                  <span v-if="item.data?.prioritas" class="meta-priority" :class="item.data.prioritas.toLowerCase()">
                    {{ item.data.prioritas }}
                  </span>
                </div>
              </div>

              <div class="notif-item-actions">
                <span v-if="!item.is_read" class="unread-dot" title="Belum dibaca"></span>
                <el-button
                  text
                  circle
                  size="small"
                  class="btn-delete-notif"
                  @click.stop="deleteNotification(item.id)"
                  title="Hapus"
                >
                  <el-icon><Close /></el-icon>
                </el-button>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="notif-empty">
            <el-icon class="empty-icon"><BellFilled /></el-icon>
            <p class="empty-text">
              {{ activeFilter === 'unread' ? 'Tidak ada notifikasi baru yang belum dibaca.' : 'Belum ada notifikasi task yang tersedia.' }}
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="notif-footer">
          <router-link to="/dashboard/task-management" class="footer-link" @click="isPopoverVisible = false">
            <span>Buka Halaman Task Management</span>
            <el-icon><ArrowRight /></el-icon>
          </router-link>
        </div>
      </div>
    </el-popover>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import {
  Bell,
  BellFilled,
  OfficeBuilding,
  ArrowRight,
  Close,
  Tickets,
  Check,
  CloseBold,
  StarFilled,
  Clock,
  VideoPlay
} from '@element-plus/icons-vue'
import { useNotificationStore, type TaskNotification } from '@/store/notifications'

const router = useRouter()
const notifStore = useNotificationStore()
const { notifications, unreadCount, loading, isConnected } = storeToRefs(notifStore)

const isPopoverVisible = ref(false)
const activeFilter = ref<'all' | 'unread'>('all')

onMounted(() => {
  notifStore.startRealtimeStream()
})

onUnmounted(() => {
  // Biarkan background stream tetap hidup selama navigasi dashboard
})

const onPopoverShow = () => {
  notifStore.fetchNotifications()
  notifStore.fetchUnreadCount()
}

const filteredNotifications = computed(() => {
  if (activeFilter.value === 'unread') {
    return notifications.value.filter(n => !n.is_read)
  }
  return notifications.value
})

const markAllAsRead = async () => {
  await notifStore.markAllAsRead()
}

const deleteNotification = async (id: number) => {
  await notifStore.deleteNotification(id)
}

const handleNotificationClick = async (item: TaskNotification) => {
  if (!item.is_read) {
    await notifStore.markAsRead(item.id)
  }
  isPopoverVisible.value = false

  // Arahkan ke task management
  router.push({
    path: '/dashboard/task-management',
    query: item.task_id ? { highlight_task: item.task_id } : {}
  })
}

const getIconComponent = (item: TaskNotification) => {
  if (item.type === 'task_rated') {
    return StarFilled
  }
  const status = item.data?.new_status?.toLowerCase()
  if (status === 'done') return Check
  if (status === 'in progress') return VideoPlay
  if (status === 'in review') return Clock
  if (status === 'rejected' || status === 'canceled') return CloseBold
  return Tickets
}

const getIconBoxClass = (item: TaskNotification) => {
  if (item.type === 'task_rated') {
    return 'icon-gold'
  }
  const status = item.data?.new_status?.toLowerCase()
  if (status === 'done') return 'icon-green'
  if (status === 'in progress') return 'icon-blue'
  if (status === 'in review') return 'icon-purple'
  if (status === 'rejected' || status === 'canceled') return 'icon-red'
  return 'icon-blue'
}

const formatTimeAgo = (dateStr: string) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diffSec = Math.floor((now.getTime() - date.getTime()) / 1000)

  if (diffSec < 60) return 'Baru saja'
  const diffMin = Math.floor(diffSec / 60)
  if (diffMin < 60) return `${diffMin} mnt lalu`
  const diffHour = Math.floor(diffMin / 60)
  if (diffHour < 24) return `${diffHour} jam lalu`
  const diffDay = Math.floor(diffHour / 24)
  if (diffDay < 7) return `${diffDay} hr lalu`

  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short'
  })
}
</script>

<style lang="scss">
.notification-popover {
  padding: 0 !important;
  border-radius: 14px !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08) !important;
  border: 1px solid #e2e8f0 !important;
  overflow: hidden;
}
</style>

<style scoped lang="scss">
.notification-bell-container {
  display: inline-flex;
  align-items: center;
}

.bell-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-1px);
  }

  .bell-icon {
    font-size: 19px;
    color: #475569;
  }
}

.bell-badge {
  :deep(.el-badge__content) {
    font-size: 11px;
    height: 18px;
    line-height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    background-color: #ef4444;
    border: 2px solid #ffffff;
    font-weight: 700;
  }
}

/* Popover Content */
.notif-dropdown {
  display: flex;
  flex-direction: column;
}

.notif-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  background-color: #ffffff;

  .header-left {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .notif-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
  }

  .realtime-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 7px;
    background-color: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
  }

  .pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #94a3b8;

    &.connected {
      background-color: #10b981;
      box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }
  }

  .status-label {
    font-size: 10.5px;
    font-weight: 600;
    color: #166534;
  }

  .btn-mark-all {
    font-size: 11.5px;
    padding: 0;
  }
}

/* Tabs */
.notif-tabs {
  display: flex;
  background-color: #f8fafc;
  padding: 6px 14px;
  border-bottom: 1px solid #f1f5f9;
  gap: 8px;

  .tab-btn {
    background: transparent;
    border: none;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;

    &:hover {
      color: #1e293b;
    }

    &.active {
      background-color: #ffffff;
      color: #0284c7;
      font-weight: 600;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
  }
}

/* Body */
.notif-body {
  max-height: 380px;
  overflow-y: auto;
  min-height: 120px;
}

.notif-list {
  display: flex;
  flex-direction: column;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid #f8fafc;
  cursor: pointer;
  transition: background-color 0.15s ease;
  position: relative;

  &:hover {
    background-color: #f8fafc;

    .btn-delete-notif {
      opacity: 1;
    }
  }

  &.is-unread {
    background-color: #f0f9ff;

    &:hover {
      background-color: #e0f2fe;
    }

    .notif-item-title {
      font-weight: 700;
      color: #0f172a;
    }
  }
}

.notif-icon-box {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  margin-top: 2px;

  &.icon-blue { background-color: #eff6ff; color: #2563eb; }
  &.icon-green { background-color: #f0fdf4; color: #16a34a; }
  &.icon-purple { background-color: #faf5ff; color: #9333ea; }
  &.icon-gold { background-color: #fefce8; color: #ca8a04; }
  &.icon-red { background-color: #fef2f2; color: #dc2626; }
}

.notif-content {
  flex: 1;
  min-width: 0;
}

.notif-top-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 8px;
}

.notif-item-title {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.3;
}

.notif-time {
  font-size: 10.5px;
  color: #94a3b8;
  white-space: nowrap;
}

.notif-message {
  margin: 3px 0 6px 0;
  font-size: 12px;
  color: #475569;
  line-height: 1.4;
  word-break: break-word;
}

.notif-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;

  .meta-tag {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    color: #64748b;
    background-color: #ffffff;
    padding: 2px 6px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
  }

  .meta-priority {
    font-size: 10px;
    font-weight: 600;
    padding: 1px 5px;
    border-radius: 4px;

    &.high { background-color: #fef2f2; color: #dc2626; }
    &.medium { background-color: #fefce8; color: #ca8a04; }
    &.low { background-color: #f0fdf4; color: #16a34a; }
  }
}

.notif-item-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  margin-top: 4px;

  .unread-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background-color: #0284c7;
  }

  .btn-delete-notif {
    opacity: 0;
    padding: 2px;
    color: #94a3b8;
    transition: opacity 0.15s ease, color 0.15s ease;

    &:hover {
      color: #ef4444;
    }
  }
}

.notif-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 36px 20px;
  text-align: center;

  .empty-icon {
    font-size: 32px;
    color: #cbd5e1;
    margin-bottom: 8px;
  }

  .empty-text {
    margin: 0;
    font-size: 12.5px;
    color: #64748b;
  }
}

/* Footer */
.notif-footer {
  padding: 10px 16px;
  background-color: #f8fafc;
  border-top: 1px solid #f1f5f9;
  text-align: center;

  .footer-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #0284c7;
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }
}
</style>
