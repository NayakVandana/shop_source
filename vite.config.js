import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react({
            include: "**/*.{jsx,tsx}",
            fastRefresh: false,
            jsxRuntime: 'automatic',
            jsxImportSource: 'react',
            babel: {
                plugins: []
            }
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: false,
        cors: true,
        strictPort: true,
    },
    define: {
        __VUE_PROD_DEVTOOLS__: false,
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
