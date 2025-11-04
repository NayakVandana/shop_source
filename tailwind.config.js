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
      colors: {
        // Primary Brand Colors
        primary: {
          50: '#EEF2FF',
          100: '#E0E7FF',
          200: '#C7D2FE',
          300: '#A5B4FC',
          400: '#818CF8',
          500: '#6366F1',
          600: '#4F46E5', // Main primary color
          700: '#4338CA', // Hover state
          800: '#3730A3',
          900: '#312E81',
        },
        // Secondary/Neutral Colors
        secondary: {
          50: '#F9FAFB',
          100: '#F3F4F6',
          200: '#E5E7EB',
          300: '#D1D5DB',
          400: '#9CA3AF',
          500: '#6B7280',
          600: '#4B5563',
          700: '#374151',
          800: '#1F2937',
          900: '#111827',
        },
        // Success Colors
        success: {
          50: '#F0FDF4',
          100: '#DCFCE7',
          200: '#BBF7D0',
          300: '#86EFAC',
          400: '#4ADE80',
          500: '#22C55E',
          600: '#16A34A',
          700: '#15803D',
          800: '#166534',
          900: '#14532D',
        },
        // Error/Danger Colors
        error: {
          50: '#FEF2F2',
          100: '#FEE2E2',
          200: '#FECACA',
          300: '#FCA5A5',
          400: '#F87171',
          500: '#EF4444',
          600: '#DC2626',
          700: '#B91C1C',
          800: '#991B1B',
          900: '#7F1D1D',
        },
        // Warning Colors
        warning: {
          50: '#FFFBEB',
          100: '#FEF3C7',
          200: '#FDE68A',
          300: '#FCD34D',
          400: '#FBBF24',
          500: '#F59E0B',
          600: '#D97706',
          700: '#B45309',
          800: '#92400E',
          900: '#78350F',
        },
        // Info Colors
        info: {
          50: '#EFF6FF',
          100: '#DBEAFE',
          200: '#BFDBFE',
          300: '#93C5FD',
          400: '#60A5FA',
          500: '#3B82F6',
          600: '#2563EB',
          700: '#1D4ED8',
          800: '#1E40AF',
          900: '#1E3A8A',
        },
        // Background Colors
        background: {
          DEFAULT: '#FFFFFF',
          primary: '#F9FAFB', // gray-50
          secondary: '#FFFFFF',
          dark: '#111827', // gray-900
          sidebar: '#111827', // gray-900
        },
        // Text Colors
        text: {
          DEFAULT: '#111827', // gray-900
          primary: '#111827', // gray-900
          secondary: '#4B5563', // gray-600
          muted: '#6B7280', // gray-500
          light: '#9CA3AF', // gray-400
          inverse: '#FFFFFF',
        },
        // Border Colors
        border: {
          DEFAULT: '#E5E7EB', // gray-200
          light: '#F3F4F6', // gray-100
          dark: '#D1D5DB', // gray-300
          darker: '#9CA3AF', // gray-400
        },
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
