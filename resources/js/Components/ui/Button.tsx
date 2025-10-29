// @ts-nocheck
import React from 'react';
import { colors, radii } from '../../theme/tokens';

type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: 'primary' | 'secondary' | 'outline';
  size?: 'sm' | 'md' | 'lg';
  block?: boolean;
};

const sizeClasses = {
  sm: 'px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm min-h-[36px] sm:min-h-[40px]',
  md: 'px-4 sm:px-5 py-2.5 sm:py-3 text-sm sm:text-base min-h-[44px]',
  lg: 'px-5 sm:px-6 py-3 sm:py-4 text-base sm:text-lg min-h-[48px] sm:min-h-[52px]',
};

export default function Button({
  variant = 'primary',
  size = 'md',
  block = false,
  className = '',
  ...props
}: ButtonProps) {
  const variants: Record<string, string> = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-700',
    secondary: 'bg-gray-900 text-white hover:bg-gray-800',
    outline: 'border border-gray-300 text-gray-700 hover:bg-gray-50',
  };

  const blockClass = block ? 'w-full' : '';

  return (
    <button
      className={`rounded-md font-medium transition-colors touch-manipulation ${variants[variant]} ${sizeClasses[size]} ${blockClass} ${className}`}
      style={{ borderRadius: radii.md }}
      {...props}
    />
  );
}


