import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  // Hanya dipakai saat `npm run dev`: target proxy untuk path /api.
  // Di Docker Compose, penerusan /api ke backend dilakukan oleh Nginx.
  const apiTarget = env.VITE_API_DEV_PROXY || 'http://127.0.0.1:8000'

  return {
    plugins: [vue()],
    resolve: {
      alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
    },
    server: {
      host: true,
      port: 5173,
      // Browser memanggil /api dengan path relatif, sehingga tidak perlu
      // menulis URL API secara manual di dalam komponen.
      proxy: {
        '/api': { target: apiTarget, changeOrigin: true },
      },
    },
  }
})