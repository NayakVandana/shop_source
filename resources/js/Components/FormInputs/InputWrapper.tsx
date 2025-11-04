// @ts-nocheck
import React from "react";
import FormError from "./FormError";
import FormHelperText from "./FormHelpText";

const InputWrapper = (props) => {
    const { isoptional, helpertext, error, title, children, joinleft, joinright, nomargin, needRightIcon, WrapperclassName } = props;
    
    return (
        <React.Fragment>
            <div className={`w-full ${nomargin ? "" : "mb-4 sm:mb-5"} ${WrapperclassName || ""}`}>
                {title ? (
                    <label className="block text-sm sm:text-base font-medium text-text-primary mb-1.5 sm:mb-2">
                        {title}
                        {isoptional && <span className="text-text-muted font-normal ml-1">(Optional)</span>}
                    </label>
                ) : false}

                {children}

                {error && <FormError error={error} />}

                {helpertext && <FormHelperText helpertext={helpertext} />}
            </div>
        </React.Fragment>
    );
};

export default React.memo(InputWrapper);
