import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  build: {
    rollupOptions: {
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: (assetInfo) => {
          // Check if it's a CSS file
          if (assetInfo.name && assetInfo.name.indexOf('.css') !== -1) {
            return '[name].[ext]';
          }
          
          // For all other assets (images, etc.), put them in root
          return '[name].[ext]';
        }
      }
    }
  }
})
