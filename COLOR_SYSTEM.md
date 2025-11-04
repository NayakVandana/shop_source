# Color System Documentation

## Overview
All colors are now managed centrally in `tailwind.config.js`. This allows for easy theme changes across the entire project.

## Color Tokens

### Primary Colors (Brand)
- `primary-600` - Main brand color (indigo-600)
- `primary-700` - Hover state
- `primary-500` - Focus rings
- Usage: Buttons, links, brand elements

### Secondary/Neutral Colors
- `secondary-50` - Light backgrounds
- `secondary-200` - Borders
- `secondary-300` - Dark borders
- `secondary-500` - Muted text
- `secondary-600` - Secondary text
- `secondary-700` - Primary text (alternative)
- `secondary-800` - Dark hover
- `secondary-900` - Dark backgrounds

### Semantic Colors

#### Background
- `background` - Default white
- `background-primary` - Light gray (gray-50)
- `background-secondary` - White
- `background-dark` - Dark background (gray-900)
- `background-sidebar` - Sidebar background

#### Text
- `text-primary` - Primary text color (gray-900)
- `text-secondary` - Secondary text (gray-600)
- `text-muted` - Muted text (gray-500)
- `text-light` - Light text (gray-400)
- `text-inverse` - White text

#### Border
- `border-default` - Default border (gray-200)
- `border-light` - Light border (gray-100)
- `border-dark` - Dark border (gray-300)
- `border-darker` - Darker border (gray-400)

#### Status Colors
- `success-*` - Success states (green scale)
- `error-*` - Error/danger states (red scale)
- `warning-*` - Warning states (yellow scale)
- `info-*` - Info states (blue scale)

## Migration Guide

### Before (Hardcoded)
```tsx
className="bg-indigo-600 text-gray-900 border-gray-300"
```

### After (Semantic)
```tsx
className="bg-primary-600 text-text-primary border-border-dark"
```

## Common Patterns

### Buttons
- Primary: `bg-primary-600 hover:bg-primary-700`
- Danger: `bg-error-600 hover:bg-error-700`
- Outline: `border-border-default text-text-primary hover:bg-secondary-50`

### Inputs
- Normal: `border-border-dark focus:border-primary-500 focus:ring-primary-500`
- Error: `border-error-300 focus:border-error-500 focus:ring-error-500`

### Backgrounds
- Page: `bg-background-primary`
- Card: `bg-background`
- Dark: `bg-background-dark`

### Text
- Headings: `text-text-primary`
- Body: `text-text-primary`
- Muted: `text-text-muted`
- Secondary: `text-text-secondary`

## Benefits
1. **Easy Theme Changes** - Update colors in one place (tailwind.config.js)
2. **Consistency** - All components use the same color system
3. **Maintainability** - Clear semantic naming
4. **Scalability** - Easy to add new colors or variants
