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
                ink: {
                    DEFAULT: '#0A0A0F',
                    surface: '#12121A',
                    raised: '#1B1B27',
                    line: 'rgba(255,255,255,0.08)',
                    text: '#F2F2F7',
                    muted: '#9A9AAE',
                },
                ok: '#3DDC97',
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Sora', ...defaultTheme.fontFamily.sans],
            },
            transitionDuration: {
                base: '200ms',
            },
            transitionTimingFunction: {
                out: 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
        },
    },

    plugins: [forms],
};
