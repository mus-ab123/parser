import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    // Add HMR clientPort configuration to ensure HMR works seamlessly behind Nginx reverse proxy
    hmr: {
      clientPort: 80
    },
    allowedHosts: true
  },
  preview: {
    allowedHosts: true
  }
});
