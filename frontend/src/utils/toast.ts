import { h } from 'vue'
import { ElNotification } from 'element-plus'

export const showSuccessToast = (message: string) => {
  ElNotification({
    customClass: 'mkp-success-toast',
    position: 'top-right',
    duration: 3000,
    showClose: false,
    message: h('div', { class: 'mkp-toast-wrapper' }, [
      h('div', { class: 'mkp-toast-sidebar' }, [
        h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '24', height: '24', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
          h('polyline', { points: '20 6 9 17 4 12' })
        ])
      ]),
      h('div', { class: 'mkp-toast-content' }, [
        h('div', { class: 'mkp-toast-header' }, [
          h('h3', 'Berhasil'),
          h('button', { class: 'mkp-toast-close', 'aria-label': 'Tutup notifikasi', onClick: () => ElNotification.closeAll() }, [
            h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '16', height: '16', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
              h('line', { x1: '18', y1: '6', x2: '6', y2: '18' }),
              h('line', { x1: '6', y1: '6', x2: '18', y2: '18' })
            ])
          ])
        ]),
        h('p', message)
      ])
    ])
  })
}

export const showErrorToast = (message: string) => {
  ElNotification({
    customClass: 'mkp-error-toast',
    position: 'top-right',
    duration: 3000,
    showClose: false,
    message: h('div', { class: 'mkp-toast-wrapper' }, [
      h('div', { class: 'mkp-toast-sidebar mkp-toast-sidebar-error' }, [
        h('svg', {
          xmlns: 'http://www.w3.org/2000/svg',
          width: '24',
          height: '24',
          viewBox: '0 0 24 24',
          fill: 'none',
          stroke: 'currentColor',
          'stroke-width': '1.5',
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round'
        }, [
          h('circle', {
            cx: '12',
            cy: '12',
            r: '10'
          }),
          h('line', {
            x1: '15',
            y1: '9',
            x2: '9',
            y2: '15'
          }),
          h('line', {
            x1: '9',
            y1: '9',
            x2: '15',
            y2: '15'
          })
        ])
      ]),
      h('div', { class: 'mkp-toast-content' }, [
        h('div', { class: 'mkp-toast-header' }, [
          h('h3', 'Gagal'),
          h('button', {
            class: 'mkp-toast-close',
            onClick: () => ElNotification.closeAll()
          }, [
            h('svg', {
              xmlns: 'http://www.w3.org/2000/svg',
              width: '16',
              height: '16',
              viewBox: '0 0 24 24',
              fill: 'none',
              stroke: 'currentColor',
              'stroke-width': '2',
              'stroke-linecap': 'round',
              'stroke-linejoin': 'round'
            }, [
              h('line', { x1: '18', y1: '6', x2: '6', y2: '18' }),
              h('line', { x1: '6', y1: '6', x2: '18', y2: '18' })
            ])
          ])
        ]),
        h('p', message)
      ])
    ])
  })
}
