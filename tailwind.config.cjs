/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      keyframes: {
        pulse: {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.3' }, // custom low opacity
        },
      },
      animation: {
        'pulse-soft': 'pulse 2s ease-in-out infinite', // custom name
      },
    },
  },
  plugins: [],
}
