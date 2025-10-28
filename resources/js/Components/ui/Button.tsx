// @ts-nocheck
import React from 'react';
import { colors, radii } from '../../theme/tokens';

type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: 'primary' | 'secondary' | 'outline';
  size?: 'sm' | 'md' | 'lg';
  block?: boolean;
};

const sizeClasses = {
  sm: 'px-3 py-2 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-5 py-3 text-base',
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
      className={`rounded-md font-medium transition-colors ${variants[variant]} ${sizeClasses[size]} ${blockClass} ${className}`}
      style={{ borderRadius: radii.md }}
      {...props}
    />
  );
}


