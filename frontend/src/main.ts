import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'

// @ts-ignore
import '@fontsource/plus-jakarta-sans' // Defaults to weight 400
// @ts-ignore
import '@fontsource/plus-jakarta-sans/500.css'
// @ts-ignore
import '@fontsource/plus-jakarta-sans/600.css'
// @ts-ignore
import '@fontsource/plus-jakarta-sans/700.css'
import '@fortawesome/fontawesome-free/css/all.css'
import './styles/index.scss'

import ApiService from '@/core/services/ApiService'
import { permissionDirective } from '@/directives/permission'

const app = createApp(App)

ApiService.init(app)
app.use(createPinia())
app.use(router)
app.use(ElementPlus)
app.directive('permission', permissionDirective)

for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

// Fix for Element Plus broken ARIA references (aria-describedby pointing to non-existent body IDs when lazy rendered)
const observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.type === 'attributes' && mutation.attributeName === 'aria-describedby') {
      const el = mutation.target as HTMLElement;
      const refId = el.getAttribute('aria-describedby');
      if (refId && !document.getElementById(refId)) {
        el.removeAttribute('aria-describedby');
      }
    }
    mutation.addedNodes.forEach((node) => {
      if (node.nodeType === 1) { // Element node
        const el = node as HTMLElement;
        if (el.hasAttribute('aria-describedby')) {
          const refId = el.getAttribute('aria-describedby');
          if (refId && !document.getElementById(refId)) {
            el.removeAttribute('aria-describedby');
          }
        }
        const descendants = el.querySelectorAll('[aria-describedby]');
        descendants.forEach(desc => {
          const refId = desc.getAttribute('aria-describedby');
          if (refId && !document.getElementById(refId)) {
            desc.removeAttribute('aria-describedby');
          }
        });
      }
    });
  });
});

observer.observe(document.body, {
  attributes: true,
  childList: true,
  subtree: true,
  attributeFilter: ['aria-describedby']
});
app.mount('#app')