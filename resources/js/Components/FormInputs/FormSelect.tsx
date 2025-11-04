// @ts-nocheck
import React from "react";
import InputWrapper from "./InputWrapper";

const FormSelect = (props: any, ref: any) => {
    const { className = '', isDisabled, joinleft, joinright, children, title, error, helpertext, isoptional, nomargin, WrapperclassName } = props
    const wrapperProps = { title, error, helpertext, isoptional, nomargin, WrapperclassName }
    
    const baseSelectClasses = `appearance-none relative block w-full px-3 sm:px-4 py-3 sm:py-3.5 border rounded-md text-sm sm:text-base min-h-[44px] transition-colors ${
        error 
            ? 'border-error-300 focus:border-error-500 focus:ring-error-500' 
            : 'border-border-dark focus:border-primary-500 focus:ring-primary-500'
    } ${isDisabled ? 'bg-secondary-50 cursor-not-allowed' : 'bg-background text-text-primary'} focus:outline-none focus:ring-2 pr-10 ${className}`
    
    return <InputWrapper {...wrapperProps}>

        {joinleft || joinright ? <div className="join relative">
            {joinleft && joinleft}

            <select
                {...props}
                ref={ref}
                className={baseSelectClasses}
                disabled={isDisabled}>
                {children}
            </select>

            {joinright ? joinright : false}

        </div> : <select
            {...props}
            ref={ref}
            className={baseSelectClasses}
            disabled={isDisabled}>
            {children}
        </select>}

    </InputWrapper>
}

export default React.memo(React.forwardRef(FormSelect));
