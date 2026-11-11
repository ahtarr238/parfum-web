import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Instrument Sans"', ...defaultTheme.fontFamily.sans],
                serif: ['"Instrument Serif"', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // Neutral ramp hangat (kertas blotter)
                paper: {
                    DEFAULT: '#f7f4ee',
                    card: '#fdfbf7',
                    raised: '#efe9df',
                },
                ink: {
                    DEFAULT: '#1f1b16',
                    2: '#5e554a',
                    3: '#7b7266',
                },
                // Aksen tunggal: ember
                ember: {
                    DEFAULT: '#8a4b1f',
                    dark: '#733e19',
                    soft: '#f3e6da',
                },
                // Titik keluarga aroma
                scent: {
                    woody: '#8b6b4e',
                    floral: '#c0788a',
                    fresh: '#5e9488',
                    oriental: '#b0603a',
                    gourmand: '#a07c4c',
                },
            },
            borderColor: {
                line: 'rgb(31 27 22 / 0.09)',
            },
            transitionTimingFunction: {
                out: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            boxShadow: {
                float: '0 8px 24px rgba(31,27,22,0.08)',
            },
        },
    },

    plugins: [forms],
};
