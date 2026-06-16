import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Newspaper-style serif for headlines / display type.
                serif: ['"Source Serif 4"', 'Georgia', ...defaultTheme.fontFamily.serif],
                // Brand display font for the "by moon whale media, llc" tagline.
                brand: ['Spantaran', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // NewsFlow brand — deep ink + a confident blue accent.
                ink: {
                    DEFAULT: '#0f172a',
                    soft: '#1e293b',
                },
                brand: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
            },
        },
    },

    plugins: [forms],
};
