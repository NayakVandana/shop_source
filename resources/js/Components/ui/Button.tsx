// @ts-nocheck
import React from 'react';

const sizeClasses = {
  sm: 'px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm min-h-[36px] sm:min-h-[40px]',
  md: 'px-4 sm:px-5 py-2.5 sm:py-3 text-sm sm:text-base min-h-[44px]',
  lg: 'px-5 sm:px-6 py-3 sm:py-4 text-base sm:text-lg min-h-[48px] sm:min-h-[52px]',
};

const variants = {
  primary: 'bg-primary-600 text-white hover:bg-primary-700',
  secondary: 'bg-secondary-900 text-white hover:bg-secondary-800',
  outline: 'border border-border-default text-text-primary hover:bg-secondary-50',
  danger: 'bg-error-600 text-white hover:bg-error-700',
};

export default function Button({ 
  variant = 'primary', 
  size = 'md', 
  block = false, 
  className = '', 
  children,
  disabled = false,
  type = 'button',
  onClick,
  ...props
}) {
  const variantClass = variants[variant] || variants['primary'];
  const sizeClass = sizeClasses[size] || sizeClasses['md'];
  const blockClass = block ? 'w-full' : '';
  const disabledClass = disabled ? 'opacity-50 cursor-not-allowed' : '';

  return (
    <button 
      type={type}
      disabled={disabled}
      onClick={onClick}
      className={`rounded-md font-medium transition-colors touch-manipulation ${variantClass} ${sizeClass} ${blockClass} ${disabledClass} ${className}`}
      {...props}
    >
      {children}
    </button>
  );
}


