import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Barlow Condensed', {
                    weights: [600, 700, 800],
                }),
                bunny('Barlow', {
                    weights: [500, 600],
                }),
                bunny('Source Serif 4', {
                    weights: [400, 600],
                    styles: ['normal', 'italic'],
                }),
            ],
        }),
        tailwindcss(),
        svelte(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
