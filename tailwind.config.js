import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Dark Purple Modern Theme Palette
                'dp': {
                    '950': '#09071a', // near-black purple
                    '900': '#0f0b28', // base background
                    '850': '#140e34', // subtle elevation
                    '800': '#1a1240', // card/surface
                    '750': '#211650', // elevated card
                    '700': '#2a1d63', // border / hover
                    '600': '#362878', // strong border
                    '500': '#4a38a8', // accent
                },
            },
        },
    },

    plugins: [forms],
};
