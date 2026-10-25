import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                ink: {
                    DEFAULT: '#08080D',
                    deep: '#050508',
                    surface: '#111119',
                    raised: '#1A1A26',
                    hover: '#22222F',
                    line: 'rgba(255,255,255,0.08)',
                    'line-strong': 'rgba(255,255,255,0.14)',
                    text: '#F2F2F7',
                    muted: '#B8B8CC',
                    faint: '#A0A0B6',
                },
                brand: {
                    violet: '#B14CFF',
                    pink: '#FF4D8D',
                    gold: '#FFD36A',
                    amber: '#F5A524',
                },
                ok: '#3DDC97',
                danger: '#FF5C7A',
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Sora', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 0 0 rgba(255,255,255,0.04) inset, 0 20px 40px -24px rgba(0,0,0,0.8)',
                glow: '0 0 0 1px rgba(177,76,255,0.35), 0 18px 40px -18px rgba(255,77,141,0.65)',
                'glow-gold': '0 0 0 1px rgba(255,211,106,0.35), 0 18px 40px -18px rgba(245,165,36,0.6)',
            },
            transitionDuration: {
                base: '200ms',
            },
            transitionTimingFunction: {
                out: 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'ken-burns': {
                    '0%': { transform: 'scale(1.08)' },
                    '100%': { transform: 'scale(1)' },
                },
                progress: {
                    '0%': { transform: 'scaleX(0)' },
                    '100%': { transform: 'scaleX(1)' },
                },
                float: {
                    '0%, 100%': { transform: 'translate3d(0,0,0)' },
                    '50%': { transform: 'translate3d(0,-14px,0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) both',
                'fade-in': 'fade-in 0.4s ease-out both',
                'ken-burns': 'ken-burns 7s ease-out both',
                progress: 'progress 6s linear both',
                float: 'float 12s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
