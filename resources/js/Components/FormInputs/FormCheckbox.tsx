// @ts-nocheck
import React from "react";

const FormCheckbox = (props: any) => {
    const { className = '', isDisabled, label, children, title, error, helpertext, nomargin, WrapperclassName } = props
    const checkboxProps = { ...props }
    delete checkboxProps.label
    delete checkboxProps.children
    delete checkboxProps.title
    delete checkboxProps.error
    delete checkboxProps.helpertext
    delete checkboxProps.isoptional
    delete checkboxProps.nomargin
    delete checkboxProps.WrapperclassName
    
    return (
        <div className={`w-full form-control ${nomargin ? "" : "mb-2"} ${WrapperclassName || ""}`}>
            {title && (
                <div className="mb-2">
                    <span className="label-text text-sm">{title}</span>
                </div>
            )}
            <label className="flex items-center cursor-pointer">
                <input
                    {...checkboxProps}
                    type="checkbox"
                    disabled={isDisabled}
                    className={`checkbox ${className}`}
                />
                {(label || children) && (
                    <span className="ml-2 label-text">{label || children}</span>
                )}
            </label>
            {error && (
                <div className="label">
                    <span className="label-text-alt text-red-500">
                        {Array.isArray(error) ? error[0] : error}
                    </span>
                </div>
            )}
            {helpertext && (
                <div className="label">
                    <span className="label-text-alt text-gray-500">{helpertext}</span>
                </div>
            )}
        </div>
    );
}

export default React.memo(FormCheckbox);
