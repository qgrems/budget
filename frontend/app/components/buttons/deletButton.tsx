import React from "react";
type InterfaceDeletButton = {
    onClick: () => void;
    icon: React.ReactNode;
    className: string;
    disabled: boolean;
};

const DeletButton: React.FC<InterfaceDeletButton> = ({ onClick, icon, className, disabled }) => {
    return (
        <button
            onClick={onClick}
            className={`p-2 neomorphic-button text-red-500 hover:text-red-600 ${className}`}
            disabled={disabled}
        >
            {icon}
        </button>
    )
}
export default DeletButton;
