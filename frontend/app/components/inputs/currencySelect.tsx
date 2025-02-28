import React from 'react';
type Option = {
    value: string;
    label: string;
    icon?: React.ReactNode;
};
type CurrencySelectProps = {
    options: Option[];
    onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void
    value?: string;
    className?: string;
}
export default function CurrencySelect({ options, onChange, value, className }): CurrencySelectProps {

    return (
        <div>
            <select
                className='w-full p-2 md:p-3 mb-4 neomorphic-input custom-class'
                onChange={onChange}
                value={value}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    )
}