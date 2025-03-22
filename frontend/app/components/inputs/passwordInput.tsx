import React from "react";

type PasswordInputProps = {
    id: string;
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
    label: string;
    required?: boolean;
};

export default function PasswordInput({
    id,
    value,
    onChange,
    placeholder,
    label,
    required = true,
}: PasswordInputProps) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700">
                {label}
            </label>
            <input
                type="password"
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className="w-full p-2 md:p-3 mb-4 neomorphic-input"
                required={required}
            />
        </div>
    );
}
