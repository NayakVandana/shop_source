// @ts-nocheck
import React from 'react';
import { typography, colors } from '../../theme/tokens';

export function Heading({ level = 1, children, className = '' }) {
  const Tag = (`h${Math.min(Math.max(level, 1), 3)}` as unknown) as keyof JSX.IntrinsicElements;
  const styles = level === 1 ? typography.heading.xl : level === 2 ? typography.heading.lg : typography.heading.md;

  return (
    <Tag
      className={`font-bold text-gray-900 ${className}`}
      style={{ fontSize: styles.fontSize, lineHeight: `${styles.lineHeight}px`, fontWeight: styles.fontWeight, color: colors.text.primary }}
    >
      {children}
    </Tag>
  );
}

export function Text({ size = 'md', muted = false, className = '', children }) {
  const styles = size === 'sm' ? typography.text.sm : typography.text.md;
  return (
    <p
      className={`${muted ? 'text-gray-500' : 'text-gray-700'} ${className}`}
      style={{ fontSize: styles.fontSize, lineHeight: `${styles.lineHeight}px`, fontWeight: styles.fontWeight }}
    >
      {children}
    </p>
  );
}


