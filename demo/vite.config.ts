import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    base: '/My_Office/',
    plugins: [vue()],
    resolve: {
        alias: {
            '@': resolve(__dirname, '../resources/js'),
            '@inertiajs/vue3': resolve(__dirname, 'src/mocks/inertia.ts'),
            '../../vendor/tightenco/ziggy': resolve(__dirname, 'src/mocks/ziggy.ts'),
        },
    },
    build: {
        outDir: '../docs',
        emptyOutDir: true,
    },
});
