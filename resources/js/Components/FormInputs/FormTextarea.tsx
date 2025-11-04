// @ts-nocheck
import React from "react";
import InputWrapper from "./InputWrapper";

const FormTextarea = (props: any, ref: any) => {
    const { className = '', isDisabled, joinleft, joinright, title, error, helpertext, isoptional, nomargin, WrapperclassName } = props
    const wrapperProps = { title, error, helpertext, isoptional, nomargin, WrapperclassName }
    
    const baseTextareaClasses = `appearance-none relative block w-full px-3 sm:px-4 py-3 sm:py-3.5 border rounded-md text-sm sm:text-base transition-colors ${
        error 
            ? 'border-error-300 focus:border-error-500 focus:ring-error-500' 
            : 'border-border-dark focus:border-primary-500 focus:ring-primary-500'
    } ${isDisabled ? 'bg-secondary-50 cursor-not-allowed' : 'bg-background text-text-primary placeholder-text-muted'} focus:outline-none focus:ring-2 resize-vertical ${className}`
    
    return <InputWrapper {...wrapperProps}>

        {joinleft || joinright ? <div className="join relative">
            {joinleft && joinleft}

            <textarea
                {...props}
                ref={ref}
                className={baseTextareaClasses}
                disabled={isDisabled} />

            {joinright ? joinright : false}

        </div> : <textarea
            {...props}
            ref={ref}
            className={baseTextareaClasses}
            disabled={isDisabled} />}

    </InputWrapper>
}

export default React.memo(React.forwardRef(FormTextarea));
