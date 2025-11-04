// @ts-nocheck
import React from 'react';

const FormHelperText = ({ helpertext }) => {
    if (!helpertext) return null;
    
    return (
        <div className="mt-1.5">
            <span className="text-xs sm:text-sm text-text-muted">{helpertext}</span>
        </div>
    );
};

export default React.memo(FormHelperText);

