/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#e6f5f3',
          100: '#b3e0d9',
          200: '#80cbbf',
          300: '#4db6a5',
          400: '#26a892',
          500: '#0a9a7f',
          600: '#098c73',
          700: '#087a63',
          800: '#076854',
          900: '#0a5449',
        },
        accent: {
          50: '#e0f9f5',
          100: '#b3efe6',
          200: '#80e5d6',
          300: '#4ddbc6',
          400: '#26d4ba',
          500: '#00BFA5',
          600: '#00b299',
          700: '#009f88',
          800: '#008d77',
          900: '#006d5a',
        },
        sidebar: {
          DEFAULT: '#0a5449',
          hover: '#0d6b5e',
          active: '#0e7566',
        },
      },
    },
  },
  plugins: [],
}
