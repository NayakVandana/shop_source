// @ts-nocheck
import React from 'react';

const paddingClasses = {
  none: 'p-0',
  sm: 'p-3 sm:p-4',
  md: 'p-4 sm:p-5 md:p-6',
  lg: 'p-6 sm:p-7 md:p-8',
};

export default function Card({ padding = 'md', className = '', children }) {
  const paddingClass = paddingClasses[padding] || paddingClasses['md'];

  return (
    <div className={`bg-white rounded-lg shadow ${paddingClass} ${className}`}>
      {children}
    </div>
  );
}


