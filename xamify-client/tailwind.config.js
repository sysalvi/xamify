/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        brandOrange: '#fd7818',
        brandMagenta: '#9e236f',
      },
      fontFamily: {
        sans: ['Space Grotesk', 'ui-sans-serif', 'system-ui'],
      },
      boxShadow: {
        glow: '0 20px 60px rgba(253, 120, 24, 0.25)',
      },
    },
  },
  plugins: [],
}
