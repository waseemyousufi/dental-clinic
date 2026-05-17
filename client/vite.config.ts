import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
    optimizeDeps: {
    include: [
      '@cornerstonejs/dicom-image-loader',
      'dicom-parser'
    ],
  },
  plugins: [
    vue(),
    vueJsx(),
    vueDevTools(),
    VitePWA({
      registerType: 'autoUpdate',
      workbox: {
        // Enforce runtime asset caching rules
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/api\.iconify\.design\/.*/i,
            handler: 'CacheFirst', // Query the cache first, bypass network if found
            options: {
              cacheName: 'iconify-remote-icons',
              expiration: {
                maxEntries: 500,           // Caps total storage usage
                maxAgeSeconds: 60 * 60 * 24 * 365, // Cache icons for 1 full year
              },
              cacheableResponse: {
                statuses: [0, 200], // Ensure successful API responses are stored
              },
            },
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      '@api': fileURLToPath(new URL('./src/api', import.meta.url)),
      '@views': fileURLToPath(new URL('./src/views', import.meta.url)),
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  server: {
    proxy: {
      '/api': {
        // target: 'http://127.0.0.1:8000',
        target: 'https://2e2e-203-171-101-118.ngrok-free.app',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '')
      }
    },
    allowedHosts: [
      "4d4d-203-171-101-118.ngrok-free.app"
    ],
    port: 1234,
    host: true,
  }
})
