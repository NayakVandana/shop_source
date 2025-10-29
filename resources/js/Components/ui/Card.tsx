// @ts-nocheck
import React from 'react';
import { radii, shadows } from '../../theme/tokens';

type CardProps = React.HTMLAttributes<HTMLDivElement> & {
  padding?: 'none' | 'sm' | 'md' | 'lg';
};

const paddingClasses = {
  none: 'p-0',
  sm: 'p-3 sm:p-4',
  md: 'p-4 sm:p-5 md:p-6',
  lg: 'p-6 sm:p-7 md:p-8',
};

export default function Card({ padding = 'md', className = '', ...props }: CardProps) {
  return (
    <div
      className={`bg-white rounded-lg shadow ${paddingClasses[padding]} ${className}`}
      style={{ borderRadius: radii.lg, boxShadow: shadows.card }}
      {...props}
    />
  );
}


