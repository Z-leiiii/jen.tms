/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        surface: '#E9EDF5',
        shadowlight: '#FFFFFF',
        shadowdark: '#B7C1D6',
        ink: {
          DEFAULT: '#303757',
          soft: '#6B7395',
          faint: '#9AA2C0',
        },
        indigo: {
          DEFAULT: '#5B6EF5',
          soft: '#E4E8FD',
          dark: '#4658D4',
        },
        emerald: {
          DEFAULT: '#2FB380',
          soft: '#DDF3EA',
        },
        amber: {
          DEFAULT: '#F2994A',
          soft: '#FDEBDA',
        },
        coral: {
          DEFAULT: '#EF6461',
          soft: '#FBE4E3',
        },
      },
      fontFamily: {
        display: ['"Space Grotesk"', 'sans-serif'],
        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace'],
      },
      boxShadow: {
        // Full-size neomorphic elevation — used on desktop/tablet raised surfaces.
        neo: '8px 8px 16px #B7C1D6, -8px -8px 16px #FFFFFF',
        // Compact elevation — used on mobile and small components so the
        // shadow spread never overpowers a small touch target.
        'neo-sm': '4px 4px 8px #B7C1D6, -4px -4px 8px #FFFFFF',
        'neo-xs': '2px 2px 5px #B7C1D6, -2px -2px 5px #FFFFFF',
        'neo-inset': 'inset 5px 5px 10px #B7C1D6, inset -5px -5px 10px #FFFFFF',
        'neo-inset-sm': 'inset 3px 3px 6px #B7C1D6, inset -3px -3px 6px #FFFFFF',
      },
      borderRadius: {
        xl2: '20px',
      },
      minHeight: {
        touch: '44px',
      },
      minWidth: {
        touch: '44px',
      },
    },
  },
  plugins: [],
}