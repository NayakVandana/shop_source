// @ts-nocheck
import React from 'react';

const headingClasses = {
  '1': 'text-4xl font-bold text-text-primary',
  '2': 'text-3xl font-bold text-text-primary',
  '3': 'text-2xl font-bold text-text-primary',
};

const textClasses = {
  sm: 'text-sm',
  md: 'text-base',
};

export function Heading({ level = 1, children, className = '' }) {
  const Tag = `h${Math.min(Math.max(level, 1), 3)}`;
  const headingClass = headingClasses[level] || headingClasses['1'];

  if (Tag === 'h1') {
    return <h1 className={`${headingClass} ${className}`}>{children}</h1>;
  }
  if (Tag === 'h2') {
    return <h2 className={`${headingClass} ${className}`}>{children}</h2>;
  }
  return <h3 className={`${headingClass} ${className}`}>{children}</h3>;
}

export function Text({ size = 'md', muted = false, className = '', children }) {
  const textClass = textClasses[size] || textClasses['md'];
  const colorClass = muted ? 'text-text-muted' : 'text-text-primary';

  return (
    <p className={`${textClass} ${colorClass} ${className}`}>
      {children}
    </p>
  );
}


