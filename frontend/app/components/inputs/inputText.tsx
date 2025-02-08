import React from "react";

type InputTextProps = {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
    type?: string;
    disabled?: boolean;
};

export default function InputText({
    value,
    onChange,
    placeholder = "",
    className = "",
    type = "text",
    disabled = false,
}: InputTextProps) {
    return (
        <input
            type={type}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            placeholder={placeholder}
            className={`w-full p-2 md:p-3 mb-4 neomorphic-input ${className}`}
            disabled={disabled}
        />
    );
}
