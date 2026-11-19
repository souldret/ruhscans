import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                ink: '#0B0709',
                surface: '#150D12',
                wine: '#5A0F2E',
                magenta: '#FF2E88',
                gold: '#E8C26A',
                mist: '#F3E9EE',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                glow: '0 0 28px rgba(255, 46, 136, 0.35)',
            },
        },
    },

    plugins: [forms],
};
