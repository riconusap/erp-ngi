import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

const normalizeBase = (prefix?: string) => {
  if (!prefix || prefix === '/') {
    return '/'
  }

  return `/${prefix.replace(/^\/+|\/+$/g, '')}/`
}

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd())
  const base = normalizeBase(env.VITE_APP_PREFIX)

  return {
    plugins: [
      vue(),
    ],
    base,
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    build: {
      chunkSizeWarningLimit: 2000,
      rollupOptions: {
        output: {
          manualChunks(id) {
            if (id.includes('node_modules')) {
              if (id.includes('element-plus')) {
                return 'element-plus'
              }
              if (id.includes('vue') || id.includes('pinia') || id.includes('vue-router')) {
                return 'vue-vendor'
              }
              if (id.includes('@fortawesome')) {
                return 'fontawesome'
              }
              return 'vendor'
            }
          }
        }
      }
    },
    define: {
      'process.env': {},
      global: 'window',
    },
  }
})