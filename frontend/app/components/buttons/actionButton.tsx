type ActionButtonProps = {
    onClick: () => void;
    label: string;
    disabled?: boolean;
    className?: string;
};

export default function ActionButton({
    onClick,
    label,
    disabled = false,
    className = "",
}: ActionButtonProps) {
    const baseClass = "w-1/4 p-1 md:p-2 neomorphic-button text-xs md:text-sm font-semibold";

    return (
        <button
            onClick={onClick}
            className={`${baseClass} ${className}`}
            disabled={disabled}
        >
            {label}
        </button>
    );
}
