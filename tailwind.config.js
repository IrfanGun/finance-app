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
                budget: {
                    primary: '#2462F3',
                    'primary-hover': '#1E56DC',
                    link: '#1558E8',
                    header: '#2868F4',
                    'header-middle': '#2869EE',
                    'header-end': '#174BD3',
                    page: '#F8FAFF',
                    surface: '#FFFFFF',
                    heading: '#111D37',
                    ink: '#14213D',
                    muted: '#66748D',
                    border: '#E8EEF8',
                    'progress-track': '#E2E9F5',
                    'icon-surface': '#EAF0FF',
                    'control-surface': '#F0F5FF',
                    success: '#16815B',
                    'success-surface': '#DDF6E8',
                    goal: '#7046B8',
                    'goal-surface': '#F0EAFE',
                },
                income: '#16815B',
                expense: '#B34735',
            },
            backgroundImage: {
                'app-header': 'linear-gradient(135deg, #11297A 0%, #1F4AC2 52%, #2969E6 100%)',
                'budget-header': 'linear-gradient(135deg, theme("colors.budget.header") 0%, theme("colors.budget.header-middle") 52%, theme("colors.budget.header-end") 100%)',
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
