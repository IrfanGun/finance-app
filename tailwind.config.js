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
            colors: {
                app: {
                    DEFAULT: '#FFFFFF',
                    navy: '#11297A',
                    royal: '#1F4AC2',
                    bright: '#2969E6',
                    primary: '#2457DA',
                    page: '#FFFFFF',
                    card: '#F5F7FA',
                    heading: '#152338',
                    muted: '#637083',
                    divider: '#E9EDF3',
                },
                income: '#16815B',
                expense: '#B34735',
            },
            backgroundImage: {
                'app-header': 'linear-gradient(135deg, #11297A 0%, #1F4AC2 52%, #2969E6 100%)',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
