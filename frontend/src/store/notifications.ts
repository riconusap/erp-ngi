import { defineStore } from 'pinia'
import ApiService from '@/core/services/ApiService'
import { ElNotification } from 'element-plus'
import { getToken } from '@/core/services/JwtService'

export interface TaskNotification {
  id: number
  user_id: number
  pegawai_id?: string | null
  sender_id?: number | null
  sender_pegawai_id?: string | null
  task_id?: number | null
  type: string
  title: string
  message: string
  data?: {
    task_id?: number
    judul?: string
    project_name?: string
    old_status?: string
    new_status?: string
    actor_name?: string
    catatan_review?: string
    rating?: number
    prioritas?: string
    deadline?: string
  } | null
  is_read: boolean
  read_at?: string | null
  created_at: string
  sender?: {
    id: number
    username: string
    avatar?: string | null
  } | null
  sender_pegawai?: {
    id: string
    nama: string
    avatar?: string | null
  } | null
  task?: {
    id: number
    judul: string
    status: string
    prioritas: string
    deadline?: string | null
    project?: {
      id: number
      kode_project: string
      nama_project: string
    } | null
  } | null
}

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [] as TaskNotification[],
    unreadCount: 0,
    loading: false,
    isConnected: false,
    eventSource: null as EventSource | null,
    pollingTimer: null as any | null,
  }),

  actions: {
    async fetchNotifications(unreadOnly = false) {
      this.loading = true
      try {
        const response = await ApiService.get('notifications', {
          params: {
            unread_only: unreadOnly ? 1 : 0,
            limit: 20
          }
        })
        const items = response.data?.data?.data || []
        this.notifications = items
        return items
      } catch (error) {
        console.error('Failed to fetch notifications:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchUnreadCount() {
      try {
        const response = await ApiService.get('notifications/unread-count')
        this.unreadCount = response.data?.data?.unread_count ?? 0
      } catch (error) {
        // Silently catch
      }
    },

    async markAsRead(id: number) {
      try {
        await ApiService.patch(`notifications/${id}/read`, {})
        const target = this.notifications.find(n => n.id === id)
        if (target && !target.is_read) {
          target.is_read = true
          if (this.unreadCount > 0) {
            this.unreadCount--
          }
        }
      } catch (error) {
        console.error('Failed to mark notification as read:', error)
      }
    },

    async markAllAsRead() {
      try {
        await ApiService.post('notifications/read-all', {})
        this.notifications.forEach(n => {
          n.is_read = true
        })
        this.unreadCount = 0
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error)
      }
    },

    async deleteNotification(id: number) {
      try {
        await ApiService.delete(`notifications/${id}`)
        const index = this.notifications.findIndex(n => n.id === id)
        if (index !== -1) {
          const removed = this.notifications.splice(index, 1)[0]
          if (removed && !removed.is_read && this.unreadCount > 0) {
            this.unreadCount--
          }
        }
      } catch (error) {
        console.error('Failed to delete notification:', error)
      }
    },

    /**
     * Memulai koneksi Realtime SSE (Server-Sent Events)
     */
    startRealtimeStream() {
      if (this.eventSource) {
        return
      }

      const token = getToken()
      if (!token) {
        return
      }

      this.fetchUnreadCount()
      this.fetchNotifications()

      const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
      const cleanBase = baseUrl.replace(/\/api\/?$/, '')
      const streamUrl = `${cleanBase}/api/notifications/stream?token=${encodeURIComponent(token)}`

      try {
        const es = new EventSource(streamUrl)
        this.eventSource = es

        es.addEventListener('connected', () => {
          this.isConnected = true
        })

        es.addEventListener('task_notification', (event: MessageEvent) => {
          try {
            const data: TaskNotification = JSON.parse(event.data)
            this.handleIncomingNotification(data)
          } catch (e) {
            console.error('Failed to parse SSE notification payload:', e)
          }
        })

        es.onerror = () => {
          this.isConnected = false
          // Browser EventSource automatically reconnects, but if closed, fallback
        }
      } catch (err) {
        console.error('Failed to initialize EventSource:', err)
      }

      // Fallback heartbeat polling interval setiap 30 detik
      if (!this.pollingTimer) {
        this.pollingTimer = setInterval(() => {
          this.fetchUnreadCount()
        }, 30000)
      }
    },

    stopRealtimeStream() {
      if (this.eventSource) {
        this.eventSource.close()
        this.eventSource = null
      }
      if (this.pollingTimer) {
        clearInterval(this.pollingTimer)
        this.pollingTimer = null
      }
      this.isConnected = false
    },

    /**
     * Memproses notifikasi baru yang masuk secara realtime
     */
    handleIncomingNotification(notif: TaskNotification) {
      // Cek apakah notifikasi sudah ada di daftar lokal
      if (this.notifications.some(n => n.id === notif.id)) {
        return
      }

      this.notifications.unshift(notif)
      this.unreadCount++

      // Play soft chime sound via Web Audio API
      this.playNotificationSound()

      // Tampilkan toast popup Element Plus
      let toastType: 'success' | 'warning' | 'info' = 'info'
      if (notif.type === 'task_rated' || notif.data?.new_status === 'Done') {
        toastType = 'success'
      } else if (notif.data?.new_status === 'Rejected' || notif.type === 'task_overdue') {
        toastType = 'warning'
      }

      ElNotification({
        title: notif.title,
        message: notif.message,
        type: toastType,
        position: 'top-right',
        duration: 6000,
        onClick: () => {
          if (window.location.pathname !== '/dashboard/task-management') {
            window.location.href = '/dashboard/task-management'
          }
        }
      })
    },

    /**
     * Sintesis audio suara notifikasi lembut tanpa memerlukan aset file eksternal
     */
    playNotificationSound() {
      try {
        const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext
        if (!AudioContextClass) return
        const ctx = new AudioContextClass()

        const osc = ctx.createOscillator()
        const gain = ctx.createGain()

        osc.type = 'sine'
        // Nada ting-ting lembut (880Hz -> 1320Hz)
        osc.frequency.setValueAtTime(880, ctx.currentTime)
        osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.15)

        gain.gain.setValueAtTime(0.08, ctx.currentTime)
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35)

        osc.connect(gain)
        gain.connect(ctx.destination)

        osc.start()
        osc.stop(ctx.currentTime + 0.35)
      } catch {
        // Abaikan jika browser memblokir audio autoplay
      }
    }
  }
})
