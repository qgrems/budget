type ActionButtonProps = {
    type: "submit";
    label: string;
    disabled?: boolean;
    className?: string;
};

export default function ActionButton({
    type,
    label,
    disabled = false,
    className = "",
}: ActionButtonProps) {
    const baseClass = "group relative w-full flex justify-center py-2 px-4 neomorphic-button text-primary hover:text-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary";

    return (
        <button
            type={type}
            className={`${baseClass} ${className}`}
            disabled={disabled}
        >
            {label}
        </button>
    );
}
