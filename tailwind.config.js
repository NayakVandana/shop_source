/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx",
    "./resources/**/*.ts",
    "./resources/**/*.tsx",
  ],
  theme: {
    extend: {
      touchAction: {
        manipulation: 'manipulation',
      },
    },
  },
  plugins: [
    function({ addUtilities }) {
      addUtilities({
        '.touch-manipulation': {
          'touch-action': 'manipulation',
          '-webkit-tap-highlight-color': 'transparent',
        },
      })
    },
  ],
}
