/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [

    "./**/*.html.twig",
    "../../../modules/custom/tmdb_api/templates/**/*.html.twig"
  ],
  theme: {
    extend: {
      colors: {
        pop: {
          rouge: '#FF2A54',
          or: '#FFC107',
          nuit: '#0B0F19',
          toile: '#1A2235',
          blanc: '#F8FAFC',
          mute: '#9CA3AF', // Ajouté pour remplacer les text-gray-400 et text-gray-500 (plus cohérent)
        }
      }
    },
  },
  plugins: [],
}