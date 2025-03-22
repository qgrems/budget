import React from 'react';

type Option = {
    value: string;
    label: string;
    icon?: React.ReactNode;
};

type CustomSelectProps = {
    options: Option[];
    onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
    value?: string;
    className?: string;
    t: (key: string) => string; // Fonction de traduction
};

export default function CustomSelect({
    options,
    onChange,
    value,
    className,
    t,
}: CustomSelectProps) {
    return (
        <div>
            <select
                className={`w-full p-2 md:p-3 mb-4 neomorphic-input custom-class ${className}`}
                onChange={onChange}
                value={value}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {/* Utilisation de `t()` pour la traduction */}
                        {t(option.label) || option.label}
                    </option>
                ))}
            </select>
        </div>
    );
}
