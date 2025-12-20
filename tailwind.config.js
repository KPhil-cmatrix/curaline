/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./*.html"], // or wherever your files are
  theme: {
    extend: {
      colors: {
        primary: '#2F5395',
        accent: '#3EDCDE',
        softblue: '#8FBFE0',
        neutral: '#9FA2B2',
      },
    },
  },
  plugins: [],
};
