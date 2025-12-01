import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',

        // ⭐ PENTING UNTUK FILAMENT
        './vendor/filament/**/*.blade.php',
        './vendor/filament/**/*.php',
        './vendor/filament/**/*.js',
    ],

    safelist: [
        'bg-blue-600',
        'hover:bg-blue-700',
        'bg-green-600',
        'hover:bg-green-700',
        'bg-yellow-500',
        'hover:bg-yellow-600',
        'text-white',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
