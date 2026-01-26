/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.jsx',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#3f5850',
                    50:  '#f3f6f5',
                    100: '#e2ebe8',
                    200: '#c3d6d0',
                    300: '#9ebbb2',
                    400: '#6f9588',
                    500: '#3f5850',
                    600: '#365047',
                    700: '#2f443d',
                    800: '#273731',
                    900: '#1f2b26',
                },
                ink: {
                    DEFAULT: '#0f1a17',
                    2: '#162421',
                }
            },
            boxShadow: {
                soft: '0 10px 30px rgba(15,26,23,0.08)',
            },
            borderRadius: {
                xl2: '18px',
            },
        },
    },
    plugins: [],
}
