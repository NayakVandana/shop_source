// Design Tokens for web and React Native parity
// Keep naming generic so RN can map to StyleSheet equivalents

export const colors = {
  brand: {
    primary: '#4F46E5', // indigo-600
    primaryDark: '#4338CA', // indigo-700
  },
  text: {
    primary: '#111827', // gray-900
    secondary: '#4B5563', // gray-600
    muted: '#6B7280', // gray-500
    inverse: '#FFFFFF',
  },
  surface: {
    background: '#F9FAFB', // gray-50
    card: '#FFFFFF',
    border: '#E5E7EB',
  },
  status: {
    danger: '#DC2626',
    success: '#16A34A',
    info: '#2563EB',
    warning: '#D97706',
  },
};

export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
  '2xl': 32,
};

export const radii = {
  sm: 6,
  md: 8,
  lg: 12,
  xl: 16,
};

export const typography = {
  heading: {
    xl: { fontSize: 36, lineHeight: 44, fontWeight: 700 },
    lg: { fontSize: 30, lineHeight: 36, fontWeight: 700 },
    md: { fontSize: 24, lineHeight: 30, fontWeight: 700 },
  },
  text: {
    md: { fontSize: 16, lineHeight: 24, fontWeight: 400 },
    sm: { fontSize: 14, lineHeight: 20, fontWeight: 400 },
  },
};

export const shadows = {
  card: '0 1px 2px 0 rgb(0 0 0 / 0.05), 0 1px 3px 0 rgb(0 0 0 / 0.1)',
};

export const tokens = { colors, spacing, radii, typography, shadows };

export type Tokens = typeof tokens;


