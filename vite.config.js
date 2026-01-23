import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa'; // <--- 1. IMPORTAR ISSO

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // <--- 2. ADICIONAR ESSA CONFIGURAÇÃO DO PWA
        VitePWA({
            registerType: 'autoUpdate',
            outDir: 'public/build', // Garante que vá para a pasta certa do Laravel
            manifest: {
                name: 'SkyCast PRO',
                short_name: 'SkyCast',
                description: 'Previsão do tempo profissional com mapas em tempo real.',
                theme_color: '#0f172a', // Cor da barra de status (aquele azul escuro do app)
                background_color: '#0f172a',
                display: 'standalone', // Faz abrir como App (sem barra do navegador)
                orientation: 'portrait',
                icons: [
                    {
                        src: '/icons/pwa-192x192.png', // Vamos criar esses ícones já já
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/icons/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    },
                    {
                        src: '/icons/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable' // Importante para Android novos
                    }
                ]
            }
        })
    ],
});