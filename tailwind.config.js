/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class', // <--- ADICIONE ESSA LINHA! (É a trava de segurança)
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}