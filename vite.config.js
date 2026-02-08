import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL || 'http://127.0.0.1:8000';

    return {
        server: {
            proxy: {
                '/api': appUrl,
                '/sanctum': appUrl,
            },
        },
        plugins: [
            vue(),
            laravel({
                input: [
                    'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/admin/main.js',
                'resources/js/seller/main.js',
                'resources/js/home/main.js',
            ],
                refresh: true,
            }),
        ],
    };
});
