// tailwind.config.js

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#f0f9f0',
                    100: '#dcf2dc',
                    200: '#bbe5bb',
                    300: '#8dd18d',
                    400: '#5bb65b',
                    500: '#4caf50',
                    600: '#388e3c',
                    700: '#2e7d32',
                    800: '#1b5e20',
                    900: '#0d47a1',
                },
                green: {
                    50: '#f0f9f0',
                    100: '#dcf2dc',
                    200: '#bbe5bb',
                    300: '#8dd18d',
                    400: '#5bb65b',
                    500: '#4caf50',
                    600: '#388e3c',
                    700: '#2e7d32',
                    800: '#1b5e20',
                    900: '#0d47a1',
                }
            }
        },
    },

    plugins: [forms],
};
