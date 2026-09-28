import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    plugins: [
        forms,
        function({ addUtilities }) {
            const newUtilities = {
                '.flip-horizontal': {
                    transform: 'scaleX(-1)',
                },
            }
            addUtilities(newUtilities, ['responsive', 'hover'])
        }
    ],
};