/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.html.twig",
    "../../../modules/custom/tmdb_api/templates/**/*.html.twig"
  ],
  theme: {
    extend: {
      fontFamily: {
        'marquee': ['"Limelight"', 'cursive'],
        'script': ['"Pacifico"', 'cursive'],
        'typewriter': ['"Courier Prime"', 'monospace'],
        'sans': ['"Courier Prime"', 'monospace'],
      },
      colors: {
        pop: {
          rouge: '#FF2A54',
          or: '#FFD700',
          nuit: '#120E0F',
          toile: '#2A1F1D',
          blanc: '#F5E5C0',
          mute: '#9CA3AF',

          velours: '#8D021F',
          cinema: '#0B0808',
        }
      }
    },
  },
  plugins: [],
}