import React from "react";

type InputNumberProps = {
    value: string | number;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
    disabled?: boolean;
};

export default function InputNumber({
    value,
    onChange,
    placeholder = "",
    className = "",
    disabled = false,
}: InputNumberProps) {
    return (
        <input
            type="text"
            inputMode="decimal"
            value={value}
            onChange={(e) => onChange(e.target.value)}
            placeholder={placeholder}
            className={`w-1/2 p-1 md:p-2 neomorphic-input text-sm md:text-base ${className}`}
            disabled={disabled}
        />
    );
}
