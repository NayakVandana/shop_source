// @ts-nocheck
import React from 'react';

const FormError = ({ error }) => {
    if (!error) return null;
    
    const errorMessage = Array.isArray(error) ? error[0] : error;
    
    return (
        <div className="mt-1.5">
            <span className="text-xs sm:text-sm text-error-500">{errorMessage}</span>
        </div>
    );
};

export default React.memo(FormError);

