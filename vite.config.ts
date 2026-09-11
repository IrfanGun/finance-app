import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import { wayfinder } from '@laravel/vite-plugin-wayfinder'

export default defineConfig({
    server: {
        host: '127.0.0.1',
    },

    optimizeDeps: {
        // ONNX Runtime memuat backend WASM secara dinamis.
        // Jangan prebundle agar import backend tidak diarahkan ke .vite/deps.
        exclude: ['onnxruntime-web'],
    },

    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
        }),

        vue(),

        wayfinder(),
    ],
})
