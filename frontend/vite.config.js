import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: { '/api': 'http://localhost:8080' },
  },
  build: {
    outDir: '../backend/public',
    emptyOutDir: false, // ne pas supprimer index.php et .htaccess
  },
})