import React from "react";

interface ValidInputButtonProps {
    onClick: () => void;
    icon: React.ReactNode;
    className: string;
    disabled: boolean;
    text: string;
};

const ValidInputButton: React.FC<ValidInputButtonProps> = ({ onClick, icon, className, disabled, text }) => {
    return (
        <button
            onClick={onClick}
            className={`p-1 neomorphic-button ${className} `}
            disabled={disabled}
        >
            {icon}
            {text && <span>{text}</span>}
        </button>
    );
};

export default ValidInputButton;
