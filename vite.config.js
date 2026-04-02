import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', // Adicionamos o CSS aqui!
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        vue(),
    ],
});