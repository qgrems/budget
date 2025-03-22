import React from "react";

const TextInput = ({ id, name, type = "text", value, onChange, placeholder, label ,className }) => {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700">
                {label}
            </label>
            <input
                id={id}
                name={name}
                type={type}
                required
                value={value}
                onChange={onChange}
                className={`neomorphic-input w-full px-3 py-2 text-foreground ${className}`}
                placeholder={placeholder}
            />
        </div>
    );
};

export default TextInput;
