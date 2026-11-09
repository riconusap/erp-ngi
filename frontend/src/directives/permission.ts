import type { Directive, DirectiveBinding } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

/**
 * Directive v-permission="['target', 'action']"
 * Contoh penggunaan:
 * <button v-permission="['Pengguna', 'create']">Tambah User</button>
 * <button v-permission="['/dashboard/admin', 'delete']">Hapus</button>
 */
export const permissionDirective: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    const { value } = binding
    if (!value || !Array.isArray(value)) {
      return
    }

    const [target, action] = value
    const { can } = usePermissions()

    const hasAccess = can(target, action || 'read')
    if (!hasAccess) {
      el.parentNode?.removeChild(el)
    }
  },
  updated(el: HTMLElement, binding: DirectiveBinding) {
    const { value } = binding
    if (!value || !Array.isArray(value)) {
      return
    }

    const [target, action] = value
    const { can } = usePermissions()

    const hasAccess = can(target, action || 'read')
    if (!hasAccess) {
      el.parentNode?.removeChild(el)
    }
  }
}

export default permissionDirective
