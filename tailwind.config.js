import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gray: {
                    25: '#FCFCFD',
                    50: '#F9FAFB',
                    100: '#F2F4F7',
                    200: '#EAECF0',
                    300: '#D0D5DD',
                    400: '#98A2B3',
                    500: '#667085',
                    600: '#475467',
                    700: '#344054',
                    800: '#182230',
                    900: '#101828',
                    950: '#0C111D',
                },
                success: {
                    25: '#F6FEF9',
                    50: '#ECFDF3',
                    100: '#DCFAE6',
                    200: '#ABEFC6',
                    300: '#75E0A7',
                    400: '#47CD89',
                    500: '#17B26A',
                    600: '#079455',
                    700: '#067647',
                    800: '#085D3A',
                    900: '#074D31',
                    950: '#053321',
                },
                info: {
                    25: '#F5FBFF',
                    50: '#F0F9FF',
                    100: '#E0F2FE',
                    200: '#B9E6FE',
                    300: '#7CD4FD',
                    400: '#36BFFA',
                    500: '#0BA5EC',
                    600: '#0086C9',
                    700: '#026AA2',
                    800: '#065986',
                    900: '#0B4A6F',
                    950: '#062C41',
                },
                warning: {
                    25: '#FFFCF5',
                    50: '#FFFAEB',
                    100: '#FEF0C7',
                    200: '#FEDF89',
                    300: '#FEC84B',
                    400: '#FDB022',
                    500: '#F79009',
                    600: '#DC6803',
                    700: '#B54708',
                    800: '#93370D',
                    900: '#7A2E0E',
                    950: '#4E1D09',
                },
                error: {
                    25: '#FFFBFA',
                    50: '#FEF3F2',
                    100: '#FEE4E2',
                    200: '#FECDCA',
                    300: '#FDA29B',
                    400: '#F97066',
                    500: '#F04438',
                    600: '#D92D20',
                    700: '#B42318',
                    800: '#912018',
                    900: '#7A271A',
                    950: '#55160C',
                },
            },
            typography: ({ theme }) => ({
                DEFAULT: {
                    css: {
                        maxWidth: '100ch',
                        '--tw-prose-paragraphs': theme('colors.gray[950]'),
                        '--tw-prose-headings': theme('colors.gray[950]'),
                        '--tw-prose-counters': theme('colors.gray[950]'),
                        '--tw-prose-bold': theme('colors.gray[950]'),
                        '--tw-prose-bullets': theme('colors.gray[950]'),
                        h1: {
                            fontSize: '28px',
                            lineHeight: '36px'
                        },
                        h2: {
                            fontSize: '24px',
                            lineHeight: '32px'
                        },
                        h3: {
                            fontSize: '20px',
                            lineHeight: '28px'
                        },
                        p: {
                            fontSize: '14px',
                            lineHeight: '20px',
                        }
                    },
                },
            }),
            boxShadow: {
                'input': '0 1px 2px 0px rgba(12, 17, 29, 0.05)',
                'dialog': '0 12px 24px -4px rgba(12, 17, 29, 0.10)',
                'toast': '0 4px 20px 0 rgba(12, 17, 29, 0.08)',
                'box': '0 8px 16px -4px rgba(12, 17, 29, 0.08)',
                'table': '0 2px 8px 0 rgba(12, 17, 29, 0.05)',
                'dropdown': '0px 8px 16px -4px rgba(12, 17, 29, 0.08)',
            },
            fontSize: {
                xxs: ['10px', '16px'],
                xs: ['12px', '18px'],
                sm: ['14px', '20px'],
                base: ['16px', '24px'],
                lg: ['20px', '28px'],
                xl: ['24px', '32px'],
                xxl: ['30px', '42px'],
            },
            screens: {
                'xxs': '340px',
                'xs': '420px',
                'sm': '640px',
                'md': '768px',
                'lg': '1024px',
                'xl': '1280px',
                '2xl': '1536px',
                '3xl': '1792px',
            },
            chip: 'inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-gray-300 bg-white shadow-input hover:bg-gray-50 focus:border-primary-300 focus:bg-primary-50 opacity-50 cursor-not-allowed',
        },
    },

    plugins: [forms, require('tailwindcss-primeui')],
};
