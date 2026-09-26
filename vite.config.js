import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    build: {
        // Keep prior hashed files so cached HTML can still load its stylesheet
        // while a new deployment replaces the manifest and assets.
        emptyOutDir: false,
    },
    server: {
        host: '127.0.0.1',
        port: process.env.PORT ? Number(process.env.PORT) : 5173,
        strictPort: true,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
